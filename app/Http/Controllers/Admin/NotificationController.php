<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class NotificationController extends Controller
{
    public function markAllRead(Request $request): RedirectResponse
    {
        if (Schema::hasTable('notifications')) {
            $request->user('admin')?->unreadNotifications()->update(['read_at' => now()]);
        }

        return back()->with('status', __('admin.flash.notifications_read'));
    }
}
