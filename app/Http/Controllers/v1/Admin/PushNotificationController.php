<?php

namespace App\Http\Controllers\v1\Admin;

use App\Helpers\Utility;
use App\Http\Controllers\Controller;
use App\Http\Requests\BroadcastMessageRequest;
use App\Models\User;
use App\Notifications\PushNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PushNotificationController extends Controller
{
    /**
     * Send broadcast message to users
     */
    public function sendBroadcast(BroadcastMessageRequest $request)
    {
        try {
            // Get validated data
            $validated = $request->validated();

            // Determine recipients
            $recipients = $this->getRecipients($validated['recipient_type'], $validated['specific_users'] ?? []);

            if ($recipients->isEmpty()) {
                return back()->with('error', 'No recipients found to send the announcement.');
            }

            // Prepare notification data
            $notificationData = [
                'title' => $validated['announcement_title'],
                'message' => $validated['announcement_message'],
                'priority' => $validated['priority'],
                'sent_at' => now(),
                'total_recipients' => $recipients->count()
            ];

            // Send push notifications
            $successCount = 0;
            $failureCount = 0;

            foreach ($recipients as $user) {
                try {
                    // Here you would integrate with your push notification service
                    // For example: FCM, Pusher, OneSignal, etc.
                    $this->sendPushNotification($user, $notificationData);
                    $successCount++;


                } catch (\Exception $e) {
                    Log::error('Failed to send push notification to user ' . $user->id . ': ' . $e->getMessage());
                    $failureCount++;
                }
            }

            // Prepare response message
            if ($successCount > 0 && $failureCount === 0) {
                return back()->with('success', "Announcement sent successfully to {$successCount} users!");
            } elseif ($successCount > 0 && $failureCount > 0) {
                return back()->with('warning', "Announcement sent to {$successCount} users. Failed to send to {$failureCount} users.");
            } else {
                return back()->with('error', 'Failed to send announcement to all users. Please try again.');
            }

        } catch (\Exception $e) {
            Log::error('Broadcast message failed: ' . $e->getMessage());
            return back()->with('error', 'An error occurred while sending the announcement. Please try again.');
        }
    }

    /**
     * Get users for API endpoints (for dynamic loading)
     */
    public function getUsers(Request $request)
    {
        try {
            $type = $request->get('type', 'all');
            $search = $request->get('search', '');

            $query = User::query();

            // Add search functionality
            if (!empty($search)) {
                $query->where(function($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            }

            switch ($type) {
                case 'all':
                    $users = $query->select('id', 'first_name', 'email', 'device_token')->get();
                    break;
                case 'active':
                    $users = $query->where('is_account_restricted', true)
                        ->select('id', 'first_name', 'email', 'device_token')
                        ->get();
                    break;
                case 'inactive':
                    $users = $query->where('is_account_restricted', false)
                        ->select('id', 'first_name', 'email')
                        ->get();
                    break;
                default:
                    $users = collect();
            }

            return response()->json([
                'success' => true,
                'users' => $users,
                'total_count' => $users->count()
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get users: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to load users'
            ], 500);
        }
    }

    /**
     * Get recipients based on type
     */
    private function getRecipients(string $type, array $specificUsers = [])
    {
        switch ($type) {
            case 'all':
                return User::get();
            case 'specific':
                return User::whereIn('id', $specificUsers)->get();
            default:
                return collect();
        }
    }

    /**
     * Send push notification to a user
     * Replace this with your actual push notification service
     */

    private function sendPushNotification($user, $data)
    {
        try {
            if (!$user->device_token) {
                return;
            }
            $user->notify(new PushNotification(
                $data['title'],
                $data['message']
            ));
        } catch (\Exception $e) {
            Log::warning("Push notification failed for user {$user->id}: " . $e->getMessage());
        }
    }



    /**
     * Store notification record in database (optional)
     */
    private function storeNotificationRecord($userId, $data)
    {
        // Optional: Store notification history in database
        /*
        DB::table('push_notifications')->insert([
            'user_id' => $userId,
            'title' => $data['title'],
            'message' => $data['message'],
            'priority' => $data['priority'],
            'sent_at' => $data['sent_at'],
            'created_at' => now(),
            'updated_at' => now()
        ]);
        */
    }


    /**
     * Get users for dropdown (AJAX endpoint)
     */
    public function getUsersForDropdown(Request $request)
    {
        try {
            $type = $request->get('type', 'all');
            $limit = $request->get('limit', 100); // Limit for performance

            $query = User::query();

            switch ($type) {
                case 'all':
                    $users = $query
                        ->select('id', 'first_name', 'email', 'device_token')
                        ->limit($limit)
                        ->get();
                    $totalCount = User::count();
                    break;

                case 'specific':
                    // For specific selection, return a manageable list
                    $users = $query
                        ->select('id', 'first_name', 'email')
                        ->orderBy('first_name')
                        ->limit($limit)
                        ->get();
                    $totalCount = $users->count();
                    break;

                default:
                    $users = collect();
                    $totalCount = 0;
            }

            return response()->json([
                'success' => true,
                'users' => $users,
                'total_count' => $totalCount
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load users',
                'data'  => Utility::getExceptionDetails($e)
            ], 500);
        }
    }
}
