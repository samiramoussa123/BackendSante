<?php

namespace App\Jobs;
use App\Events\RappelRendezVousEvent; 

use App\Models\RendezVous;
use App\Notifications\RappelRendezVous;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class EnvoyerRappelsUneHeure implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function handle(): void
    {
        $debutFenetre = Carbon::now()->addMinutes(55);
        $finFenetre   = Carbon::now()->addMinutes(65);

   RendezVous::query()
    ->whereBetween(\DB::raw("STR_TO_DATE(CONCAT(date, ' ', heure), '%Y-%m-%d %H:%i:%s')"), [$debutFenetre, $finFenetre])
    ->where('etat', 'confirmé') // ici 'etat' au lieu de 'statut'
    ->whereNull('rappel_une_heure_envoye_le')
    ->with(['patient', 'medecin'])
    ->chunk(100, function ($rendezVous) {
        foreach ($rendezVous as $rdv) {
            try {
                if ($rdv->patient) {
                    $rdv->patient->notify(new RappelRendezVous($rdv, 'une_heure_avant'));
                    event(new RappelRendezVousEvent($rdv, 'une_heure_avant'));
                }
                if ($rdv->medecin) {
                    $rdv->medecin->notify(new RappelRendezVous($rdv, 'une_heure_avant'));
                    event(new RappelRendezVousEvent($rdv, 'une_heure_avant'));
                }
                $rdv->update(['rappel_une_heure_envoye_le' => now()]);
                \Log::info("Rappel 1h envoyé pour RDV #{$rdv->id}");
            } catch (\Throwable $e) {
                \Log::error("Erreur lors de l'envoi du rappel 1h pour RDV #{$rdv->id}", [
                    'erreur' => $e->getMessage(),
                ]);
            }
        }
    });}
    public function failed(\Throwable $exception): void
    {
        Log::error('EnvoyerRappelsUneHeure a échoué', ['erreur' => $exception->getMessage()]);
    }
}