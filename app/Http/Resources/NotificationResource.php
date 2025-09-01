<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class NotificationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request): array|\JsonSerializable|Arrayable
    {
        return [
            'data' => [
                'type' => 'Notification',
                'id' => $this->id,
                'attributes' => [
                    'title' => $this->data['title'] ?? null,  // Accessing 'title' as array
                    'message' => $this->data['message'] ?? null,
                    'created_at' => Carbon::parse($this->created_at)->format('M d, Y'),
                ],
            ],
            'total_count' => Auth::user()->notifications()->count(),
            'unread_count' => Auth::user()->unreadNotifications()->count(),
        ];
    }
}
