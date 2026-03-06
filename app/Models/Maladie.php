<?php

namespace App\Models;
use App\Models\DossierMedical;
use Illuminate\Database\Eloquent\Model;
class Maladie extends Model
{
    protected $table = 'maladies';

    protected $fillable = [
        'dossier_medical_id',
        'nom_maladie',
        'date_diagnostic',
    ];

    public function dossier()
    {
        return $this->belongsTo(DossierMedical::class, 'dossier_medical_id');
    }
}
