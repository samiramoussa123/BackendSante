<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
         Schema::create('specialite',function(Blueprint $table){
            $table->id();
            $table->string('nom_specialite',255);
            $table->timestamps();
        });
        // Table users
        

        // Table patients
        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->date('dateNaissance')->nullable();
            $table->string('sexe')->nullable();
            $table->timestamps();
        });

        // Table medecins
        Schema::create('medecins', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('specialite_id')->nullable()->constrained('specialite')->onDelete('set null'); // <- ici
  
            $table->integer('experience')->nullable();
            $table->timestamps();
        });
    
        Schema::create('Admin',function (Blueprint $table){
        $table->id();
        $table->boolean('isAdmin')->default(true);
        $table->timestamps();
    });
    }
    public function down(): void
    {
         Schema::dropIfExists('specialite');

        Schema::dropIfExists('medecins');
        Schema::dropIfExists('patients');
        Schema::dropIfExists('users');
        Schema::dropIfExists('Admin');
    
    }
};