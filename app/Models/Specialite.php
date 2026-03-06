<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Specialite extends Model
{protected $table = 'specialite';
    protected $fillable = [
        'nom_specialite',
    ];
}