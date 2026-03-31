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

class EnvoyerRappelsMatin implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function handle(): void
    {
        $aujourdhui = Carbon::today();

        RendezVous::query()
            ->whereDate('date', $aujourdhui)
            ->where('etat', 'confirmé')
            ->whereNull('rappel_matin_envoye_le')
            ->with(['patient', 'medecin'])
            ->chunk(100, function ($rendezVous) {
                foreach ($rendezVous as $rdv) {
                    try {
                        if ($rdv->patient) {
                            // Notification DB & Mail
                            $rdv->patient->notify(new RappelRendezVous($rdv, 'matin'));

                            // Broadcast en temps réel
                            event(new RappelRendezVousEvent($rdv, 'matin'));
                        }

                        if ($rdv->medecin) {
                            $rdv->medecin->notify(new RappelRendezVous($rdv, 'matin'));

                            // Broadcast en temps réel pour le médecin
                            event(new RappelRendezVousEvent($rdv, 'matin'));
                        }

                        $rdv->update(['rappel_matin_envoye_le' => now()]);

                        Log::info("Rappel matin envoyé pour RDV #{$rdv->id}");
                    } catch (\Throwable $e) {
                        Log::error("Erreur sur le RDV {$rdv->id}", ['message' => $e->getMessage()]);
                    }
                }
            });
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('EnvoyerRappelsMatin a échoué', ['erreur' => $exception->getMessage()]);
    }
}