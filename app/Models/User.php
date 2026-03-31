<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable; 

    protected $fillable = [
        'nom', 'prenom', 'email', 'adresse',
        'telephone', 'age', 'mdp', 'role', 'photo',
        'email_verified_at', 'email_verification_token',
    ];

    protected $hidden = [
        'mdp',
        'remember_token',
    ];

    // ← AJOUTER CECI
    public function getAuthPassword()
    {
        return $this->mdp;
    }

    public function patient()
    {
        return $this->hasOne(Patient::class);
    }

    public function medecin()
    {
        return $this->hasOne(Medecin::class);
    }

    public function admin()
    {
        return $this->hasOne(Admin::class);
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }
}