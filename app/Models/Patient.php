<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Medecin;
use Illuminate\Notifications\Notifiable;
class Patient extends Model

{
    use Notifiable;
     protected $fillable = [
        'user_id',
        'dateNaissance',
        'sexe',

     ];
     public function user() {
    return $this->belongsTo(User::class);
}
public function medecin()
{
    return $this->belongsTo(Medecin::class, 'medecin_id');
}
    public function routeNotificationForMail(): string
    {
        return $this->user->email ?? '';
    }

     public function routeNotificationForCanalPush(): string
    {
        return 'patient.' . $this->id;
    }
}
