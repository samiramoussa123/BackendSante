<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Pusher\Pusher;

/**
 * Canal de notification push via Pusher.
 *
 * Prérequis : composer require pusher/pusher-php-server
 *
 * Variables .env à ajouter :
 *   PUSHER_APP_ID=votre-app-id
 *   PUSHER_APP_KEY=votre-app-key
 *   PUSHER_APP_SECRET=votre-app-secret
 *   PUSHER_APP_CLUSTER=eu
 *
 * Le front s'abonne au canal : "patient.{id}" ou "medecin.{id}"
 * et écoute l'événement : "rappel.rendez-vous"
 */
class CanalPush
{
    public function send(object $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toFcm')) {
            return;
        }

        $donnees = $notification->toFcm($notifiable);

        try {
            $pusher = new Pusher(
                config('broadcasting.connections.pusher.key'),
                config('broadcasting.connections.pusher.secret'),
                config('broadcasting.connections.pusher.app_id'),
                [
                    'cluster' => config('broadcasting.connections.pusher.options.cluster'),
                    'useTLS'  => true,
                ]
            );

            // Canal privé selon le type de destinataire
            $typeNotifiable = class_basename($notifiable);
            $canal = strtolower($typeNotifiable) . '.' . $notifiable->id;
            // Exemples : "patient.12"  |  "medecin.5"

            $pusher->trigger($canal, 'rappel.rendez-vous', [
                'titre'          => $donnees['titre'],
                'corps'          => $donnees['corps'],
                'rendez_vous_id' => $donnees['donnees']['rendez_vous_id'] ?? null,
                'type'           => $donnees['donnees']['type'] ?? null,
            ]);

        } catch (\Throwable $e) {
            Log::error('CanalPush Pusher : échec', ['message' => $e->getMessage()]);
        }
    }
}