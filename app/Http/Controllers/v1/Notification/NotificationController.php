<?php

namespace App\Http\Controllers\v1\Notification;

use App\Helpers\Utility;
use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class NotificationController extends Controller
{
    public function getAllNotifications(): \Illuminate\Http\JsonResponse
    {
        try {
            $user = Auth::user();

            $notifications = $user->notifications;
            if ($notifications->isEmpty()) {
                return Utility::outputData(false, "No notifications found", [], 200);
            }
            #  Mark all unread notifications as read
//            $user->unreadNotifications->markAsRead();
            return Utility::outputData(true, "All notifications fetched", NotificationResource::collection($notifications), 200);
        } catch (Throwable $e) {
            Log::error("Error Getting All Notifications: " . $e->getMessage());
            return Utility::outputData(false, "Failed to fetch notifications", [], 500);
        }
    }


    public function getUnreadCount()
    {
        try {
            $unreadCount = Auth::user()->unreadNotifications()->count();
            return Utility::outputData(true, "Unread notifications count fetched", ['unread_count' => $unreadCount], 200);
        } catch (\Exception $e) {
            Log::error("Error Getting Unread Notifications: " . $e->getMessage());
            return Utility::outputData(false, "Failed to fetch unread notifications count", [], 500);
        }
    }

    public function getAllCount()
    {
        try {
            $allCount = Auth::user()->notifications()->count();
            return Utility::outputData(true, "All notifications count fetched", ['all_count' => $allCount], 200);
        } catch (\Exception $e) {
            Log::error("Error Getting Count of all Notifications: " . $e->getMessage());
            return Utility::outputData(false, "Failed to fetch all notifications count",[], 500);
        }
    }


    public function markAllAsRead()
    {
        try {
            $user = Auth::user();
            $user->unreadNotifications->markAsRead();
            return Utility::outputData(true, "All notifications marked as read", [], 200);
        } catch (\Exception $e) {
            return Utility::outputData(false, "Failed to mark notifications as read", $e->getMessage(), 500);
        }
    }

    public function markAsRead($id)
    {
        try {
            $user = Auth::user();
            $notification = $user->notifications()->where('id', $id)->first();
            if ($notification) {
                $notification->markAsRead();
                return Utility::outputData(true, "Notification marked as read", [], 200);
            } else {
                return Utility::outputData(false, "Notification not found", [], 404);
            }
        } catch (\Exception $e) {
            return Utility::outputData(false, "Failed to mark notification as read", $e->getMessage(), 500);
        }
    }

}
