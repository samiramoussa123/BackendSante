<?php

namespace App\Notifications;

use App\Models\RendezVous;
use App\Notifications\CanalPush;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Carbon\Carbon;

class RappelRendezVous extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public RendezVous $rendezVous,
        public string $type = 'matin'
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database', CanalPush::class];
    }

    private function getNomMedecin(): string
    {
        $medecin = $this->rendezVous->medecin;

        if (!$medecin || !$medecin->donnees_json) {
            return 'votre médecin';
        }

        $data = json_decode($medecin->donnees_json, true);

        if (!$data) {
            return 'votre médecin';
        }

        return ($data['prenom'] ?? '') . ' ' . ($data['nom'] ?? '');
    }

    public function toMail(object $notifiable): MailMessage
    {
        $heure = Carbon::parse($this->rendezVous->heure)->format('H:i');
        $date  = Carbon::parse($this->rendezVous->date)->translatedFormat('l d F Y');

        $nomMedecin = $this->getNomMedecin();

        // patient → via user
        $prenom = $notifiable->user->prenom ?? $notifiable->prenom ?? '';

        return (new MailMessage)
            ->subject("Rappel : votre rendez-vous aujourd'hui à {$heure}")
            ->greeting("Bonjour {$prenom},")
            ->line("Vous avez un rendez-vous avec **Dr {$nomMedecin}** le {$date} à **{$heure}**.")
            ->action('Voir mon rendez-vous', url('/rendez-vous/' . $this->rendezVous->id))
            ->line('Merci de votre confiance.');
    }

    public function toFcm(object $notifiable): array
    {
        $heure = Carbon::parse($this->rendezVous->heure)->format('H:i');
        $medecin = $this->getNomMedecin();

        $titre = $this->type === 'matin'
            ? "Rappel de votre RDV aujourd'hui"
            : "Votre RDV commence dans 1 heure";

        $corps = $this->type === 'matin'
            ? "Rendez-vous à {$heure} avec Dr {$medecin}"
            : "Préparez-vous, votre rendez-vous est à {$heure}";

        return [
            'titre' => $titre,
            'corps' => $corps,
            'donnees' => [
                'rendez_vous_id' => (string) $this->rendezVous->id,
                'type' => 'rappel_rendez_vous',
            ],
        ];
    }

    public function toDatabase(object $notifiable): array
    {
        $heure = Carbon::parse($this->rendezVous->heure)->format('H:i');

        return [
            'rendez_vous_id' => $this->rendezVous->id,
            'type' => $this->type,
            'message' => "Rappel RDV à {$heure}",
        ];
    }
}