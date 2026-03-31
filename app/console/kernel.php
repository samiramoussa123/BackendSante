<?php

namespace App\Console;

use App\Jobs\EnvoyerRappelsMatin;
use App\Jobs\EnvoyerRappelsUneHeure;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        // Rappel du matin à 8h00 chaque jour
        $schedule->job(new EnvoyerRappelsMatin)
            ->dailyAt('08:00')
            ->withoutOverlapping()
            ->onFailure(function () {
                \Log::error('EnvoyerRappelsMatin a échoué');
            });

        // Rappel 1 heure avant — vérifie toutes les heures
        $schedule->job(new EnvoyerRappelsUneHeure)
            ->hourly()
            ->withoutOverlapping()
            ->onFailure(function () {
                \Log::error('EnvoyerRappelsUneHeure a échoué');
            });
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
        require base_path('routes/console.php');
    }
}