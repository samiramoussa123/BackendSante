<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\DossierMedical;

class Consultation extends Model
{
    protected $table = 'consultations';

    protected $fillable = [
    'dossier_medical_id',
    'date_consultation',
    'diagnostique',
    'traitement',
    'room_id',
    'type',
    'statut_video',
    'debut_video',
    'fin_video',
];

protected $casts = [
    'debut_video' => 'datetime',
    'fin_video'   => 'datetime',
];

// Générer un room_id unique automatiquement
public static function genererRoomId(): string
{
    return 'room-' . uniqid() . '-' . rand(1000, 9999);
}

    public function dossier()
    {
        return $this->belongsTo(DossierMedical::class, 'dossier_medical_id');
    }
}
