<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class CompteVerifie extends Notification
{
    use Queueable;

   
    public function __construct()
    {
        //
    }

   
    public function via($notifiable)
    {
        return ['mail'];
    }

    
    public function toMail($notifiable)
    {
        return (new MailMessage)
                    ->subject('Votre compte médecin a été vérifié')
                    ->line('Félicitations ! Votre compte médecin a été vérifié avec succès.')
                    ->line('Vous pouvez maintenant accéder à toutes les fonctionnalités de la plateforme.')
                    ->action('Se connecter', url('/login'))
                    ->line('Merci de faire partie de notre communauté !');
    }

   
    public function toArray($notifiable)
    {
        return [
            
        ];
    }
}