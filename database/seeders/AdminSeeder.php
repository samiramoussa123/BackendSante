<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run()
{
    User::firstOrCreate(
        ['email' => 'admin@gmail.com'],
        [
            'nom'                      => 'Admin',
            'prenom'                   => 'System',
            'password'                 => Hash::make('Admin123'),
            'role'                     => 'admin',
            'email_verified_at'        => now(),      
            'email_verification_token' => null,    
            'telephone'                => '23432123',
        'adresse'                  => 'Tunis, Tunisie',
        'age'                      => 30,   
        ]
    );
}
}