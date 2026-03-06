<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable; // Supprimé HasApiTokens qui est pour Sanctum

    protected $fillable = [
        'nom',
        'prenom',
        'email',
        'adresse',
        'telephone',
        'age',
        'password',
        'role',
        'photo',
        
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    // Relations par rôle (héritage logique)
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

    // Méthodes JWT
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }
}