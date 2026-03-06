<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Medecin extends Model
{ protected $fillable = [
    'user_id',
  
    'specialite_id',
    'experience',
];
public function user(){
    return $this->belongsTo(User::class);
}
 public function specialite()
    {
        return $this->belongsTo(Specialite::class, 'specialite_id');
    }
    //
}
