<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewUserRegistered extends Notification
{
    use Queueable;

    public function __construct(public User $newUser) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'     => 'new_user_registered',
            'user_id'  => $this->newUser->id,
            'name'     => $this->newUser->name,
            'email'    => $this->newUser->email,
            'position' => $this->newUser->position,
            'message'  => "{$this->newUser->name} se ha registrado y necesita ser asignado a un equipo.",
        ];
    }
}
