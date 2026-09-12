<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Setting;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class SettingsController extends Controller
{
    public function __construct(private ActivityLogger $logger) {}

    public function index()
    {
        return view('settings.index', [
            'settings' => [
                'club_name' => Setting::get('club_name', 'Sport Avenue Club'),
                'slot_duration' => Setting::slotDuration(),
                'currency' => Setting::get('currency', 'PKR'),
                'default_open' => Setting::get('default_open', '08:00'),
                'default_close' => Setting::get('default_close', '23:00'),
            ],
            'users' => User::query()->orderBy('name')->get(),
            'logs' => ActivityLog::query()->with('user')->latest()->limit(500)->get()->map(fn ($log) => [
                'id' => $log->id,
                'time' => $log->created_at?->format('d M Y H:i'),
                'user' => $log->user?->name ?? 'System',
                'action' => $log->action,
                'description' => $log->description,
            ])->values(),
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'club_name' => ['required', 'string', 'max:120'],
            'slot_duration' => ['required', 'in:30,60,90,120'],
            'currency' => ['required', 'string', 'max:8'],
            'default_open' => ['required', 'date_format:H:i'],
            'default_close' => ['required', 'date_format:H:i'],
        ]);

        foreach ($data as $key => $value) {
            Setting::put($key, $value);
        }

        $this->logger->log('settings.updated', "{$request->user()->name} updated club settings", null, $request->user());

        return back()->with('success', 'Settings saved.');
    }

    public function storeUser(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', 'in:admin,manager,staff'],
        ]);

        $user = User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => $data['role'],
            'status' => 'active',
        ]);

        $this->logger->log('user.created', "{$request->user()->name} created user {$user->name}", $user, $request->user());

        return back()->with('success', 'Staff account created.');
    }

    public function updateUser(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'role' => ['required', 'in:admin,manager,staff'],
            'status' => ['required', 'in:active,inactive'],
            'password' => ['nullable', 'string', 'min:8'],
        ]);

        $user->fill([
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => $data['role'],
            'status' => $data['status'],
        ]);

        if (! empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }

        $user->save();

        return back()->with('success', 'Staff account updated.');
    }
}
