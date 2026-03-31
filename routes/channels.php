<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Channel pour les consultations vidéo
Broadcast::channel('consultation.{roomId}', function ($user, $roomId) {
    return \App\Models\Consultation::where('room_id', $roomId)
        ->whereHas('dossier', fn($q) => $q
            ->where('patient_id', $user->id)
            ->orWhere('medecin_id', $user->id)
        )->exists();
});

Broadcast::channel('rendez-vous.{patientId}', function ($user, $patientId) {
    return (int) $user->id === (int) $patientId;
});