<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Patient;
use App\Models\User;
use App\Models\Maladie;
use App\Models\Consultation;
class DossierMedical extends Model
{
    protected $table = 'dossier_medical';

    protected $fillable = [
        'patient_id',
        'medecin_id',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }
    public function medecin()
    {
        return $this->belongsTo(User::class, 'medecin_id');
    }

    public function maladies()
    {
        return $this->hasMany(Maladie::class);
    }

    public function consultations()
    {
        return $this->hasMany(Consultation::class);
    }
}