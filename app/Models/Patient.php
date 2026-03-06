<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Medecin;
class Patient extends Model

{
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
    //
}
