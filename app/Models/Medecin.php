<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
class Medecin extends Model
{ use Notifiable;
    protected $fillable = [
        'user_id',
        'specialite_id',
        'experience',
        'verifie_json',
        'donnees_json'
    ];

    protected $casts = [
        'verifie_json' => 'boolean',
        'donnees_json' => 'array'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function specialite()
    {
        return $this->belongsTo(Specialite::class, 'specialite_id');
    }
     public function routeNotificationForMail(): string
    {
        return $this->user->email ?? '';
    }

     public function routeNotificationForCanalPush(): string
    {
        return 'medecin.' . $this->id;
    }
}