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
    ];

    public function dossier()
    {
        return $this->belongsTo(DossierMedical::class, 'dossier_medical_id');
    }
}
