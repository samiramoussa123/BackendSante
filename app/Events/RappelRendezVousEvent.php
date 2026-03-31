<?php

namespace App\Events;

use App\Models\RendezVous;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast; 

class RappelRendezVousEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public RendezVous $rendezVous;
    public string $type;

    public function __construct(RendezVous $rendezVous, string $type)
    {
        $this->rendezVous = $rendezVous;
        $this->type = $type;
    }

    // Canal privé pour le patient
    public function broadcastOn(): Channel
    {
        return new PrivateChannel('rendez-vous.' . $this->rendezVous->id_patient);
    }

    // Les données envoyées côté client
    public function broadcastWith(): array
    {
        return [
            'rendez_vous_id' => $this->rendezVous->id,
            'type' => $this->type,
            'date' => $this->rendezVous->date,
            'heure' => $this->rendezVous->heure,
        ];
    }
}