<?php

use App\Exceptions\BookingConflictException;
use App\Http\Middleware\EnsureRole;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => EnsureRole::class,
        ]);
        $middleware->redirectGuestsTo('/login');
        $middleware->redirectUsersTo('/dashboard');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(function (BookingConflictException $e, Request $request) {
            $payload = [
                'ok' => false,
                'conflict' => true,
                'message' => $e->getMessage() ?: 'This time slot is no longer available. Please select another slot.',
            ];

            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json($payload, 409);
            }

            return back()->withErrors(['slot' => $payload['message']])->withInput();
        });

        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'ok' => false,
                    'message' => collect($e->errors())->flatten()->first() ?: 'Please check the highlighted fields.',
                    'errors' => $e->errors(),
                ], 422);
            }
        });
    })->create();
