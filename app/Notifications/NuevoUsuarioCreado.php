<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NuevoUsuarioCreado extends Notification implements ShouldQueue
{
    use Queueable;

    public $user;
    public $password;

    public function __construct($user, $password)
    {
        $this->user = $user;
        $this->password = $password;
    }

    public function via($notifiable)
    {
        return ['mail']; // Por ahora solo por correo, es más simple
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('¡Bienvenido! Tus Credenciales de Acceso al Sistema')
            ->markdown('emails.nuevo_usuario', [
                'user' => $this->user,
                'password' => $this->password,
            ]);
    }
}