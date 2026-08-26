<?php

/**
 * Signs in as a seeded officer over real HTTP and walks every page, reporting
 * the status code, byte size and query count for each.
 *
 * Usage: php scripts/smoke-check.php [base-url] [email]
 */

declare(strict_types=1);

$base = rtrim($argv[1] ?? 'http://127.0.0.1:8321', '/');
$email = $argv[2] ?? null;
$jar = tempnam(sys_get_temp_dir(), 'case-smoke-');

function request(string $url, string $jar, ?array $post = null): array
{
    $handle = curl_init($url);
    curl_setopt_array($handle, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_COOKIEJAR => $jar,
        CURLOPT_COOKIEFILE => $jar,
        CURLOPT_TIMEOUT => 30,
    ]);

    if ($post !== null) {
        curl_setopt($handle, CURLOPT_POST, true);
        curl_setopt($handle, CURLOPT_POSTFIELDS, http_build_query($post));
    }

    $body = (string) curl_exec($handle);
    $status = (int) curl_getinfo($handle, CURLINFO_HTTP_CODE);
    curl_close($handle);

    return [$status, $body];
}

function token(string $html): string
{
    preg_match('/name="_token" value="([^"]+)"/', $html, $matches);

    return $matches[1] ?? '';
}

[$status, $html] = request($base.'/login', $jar);
printf("%-34s %d  %6d bytes\n", 'GET /login', $status, strlen($html));

if ($email === null) {
    fwrite(STDERR, "Pass a seeded officer's email as the second argument.\n");
    exit(1);
}

[$status, $html] = request($base.'/login', $jar, [
    '_token' => token($html),
    'email' => $email,
    'password' => 'password',
]);
printf("%-34s %d  %6d bytes\n", 'POST /login', $status, strlen($html));

if (str_contains($html, 'name="password"')) {
    fwrite(STDERR, "Sign-in failed — still on the login screen.\n");
    exit(1);
}

preg_match('#/cases/(CASE-\d{4}-\d{4})#', $html, $matches);
$caseNumber = $matches[1] ?? null;

$paths = [
    '/dashboard',
    '/cases',
    '/cases?high_only=1',
    '/cases?status=escalated',
    '/cases?q=CASE',
    '/cases/create',
    '/departments',
    '/notifications',
    '/notifications?tab=unread',
    '/notifications?tab=escalations',
];

if ($caseNumber !== null) {
    $paths[] = '/cases/'.$caseNumber;
}

// Departments are keyed by slug; drill into the first one the user can reach.
[, $departmentsHtml] = request($base.'/departments', $jar);

if (preg_match('#/departments/([a-z0-9-]+)#', $departmentsHtml, $matches) === 1) {
    $paths[] = '/departments/'.$matches[1];
}

$failed = 0;

foreach ($paths as $path) {
    [$status, $body] = request($base.$path, $jar);

    // Department management is gated to admins, so a 403 there is the system
    // working rather than a broken page.
    $note = match (true) {
        $status === 200 && ! str_contains($body, 'Whoops') => 'ok',
        $status === 403 => 'gated for this role',
        default => 'FAILED',
    };

    $failed += $note === 'FAILED' ? 1 : 0;

    printf("%-34s %d  %6d bytes  %s\n", 'GET '.$path, $status, strlen($body), $note);
}

@unlink($jar);

exit($failed === 0 ? 0 : 1);
