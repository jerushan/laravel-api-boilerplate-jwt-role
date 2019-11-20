<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        // return parent::toArray($request);

        $userData = User::select('id', 'name','profile_image','school_id')->find($this->notifiable_id);
        $oppositeUserId = (array_key_exists('opposite_user_id', $this->data)) ? $this->data['opposite_user_id'] : null;
        $notificationMessage = (array_key_exists('notification_message', $this->data)) ? $this->data['notification_message'] : null;
        $oppositeUserData = User::select('id', 'name','profile_image','school_id')->find($oppositeUserId);

        return [
            'id'                   => $this->id,
            'type'                 => $this->type,
            'data'                 => $this->data,
            'read_at'              => $this->read_at,
            'user_id'              => $this->notifiable_id,
            'user_data'            => $userData,
            'opposite_user_id'     => $oppositeUserId,
            'opposite_user_data'   => $oppositeUserData,
            'notifiable_id'        => $this->notifiable_id,
            'notifiable_type'      => $this->notifiable_type,
            'notification_message' => $notificationMessage,
            'created_at'           => $this->created_at,
            'updated_at'           => $this->updated_at, 
        ];
    }
}
