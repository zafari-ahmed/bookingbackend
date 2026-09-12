<?php

namespace App\Http\Controllers;

use App\Models\StaffNotification;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(NotificationService $notifications)
    {
        return response()->json([
            'items' => $notifications->unread(auth()->id()),
            'unread' => $notifications->unreadCount(auth()->id()),
        ]);
    }

    public function read(StaffNotification $notification)
    {
        $notification->update(['read_at' => now()]);

        return response()->json(['ok' => true]);
    }

    public function readAll(Request $request)
    {
        StaffNotification::query()
            ->whereNull('read_at')
            ->where(function ($q) use ($request) {
                $q->whereNull('user_id')->orWhere('user_id', $request->user()->id);
            })
            ->update(['read_at' => now()]);

        return response()->json(['ok' => true]);
    }
}
