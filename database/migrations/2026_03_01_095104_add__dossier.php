<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // TABLE DOSSIERS MEDICAUX
        Schema::create('dossier_medical', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignId('medecin_id')->constrained('medecins')->cascadeOnDelete();
            $table->unique(['patient_id', 'medecin_id']);
            $table->timestamps();
        });

        // TABLE MALADIES
        Schema::create('maladies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dossier_medical_id')
                  ->constrained('dossier_medical')
                  ->cascadeOnDelete();
            $table->string('nom_maladie')->nullable();
            $table->date('date_diagnostic')->nullable();
            $table->timestamps();
        });

        // TABLE CONSULTATIONS
        Schema::create('consultations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dossier_medical_id')
                  ->constrained('dossier_medical')
                  ->cascadeOnDelete();
            $table->date('date_consultation')->nullable();
            $table->string('diagnostique')->nullable();
            $table->string('traitement')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consultations');
        Schema::dropIfExists('maladies');
        Schema::dropIfExists('dossier_medical');
    }
};