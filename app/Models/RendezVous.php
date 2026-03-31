<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Patient;
use App\Models\Medecin;
class RendezVous extends Model

{
    protected $table = 'rendez_vous';


    protected $fillable = [
        'id_patient',
        'id_medecin',
        'date',
        'heure',
        'etat',
        'rappel_matin_envoye_le', 'rappel_une_heure_envoye_le',
    ];
    protected $casts = [
        'etat' => 'string',
    ];

    public function patient()
{
    return $this->belongsTo(Patient::class, 'id_patient');
}
public function medecin(){
    return $this->belongsTo(Medecin::class,'id_medecin');
}
}