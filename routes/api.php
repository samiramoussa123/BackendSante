<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\RendezVousController;
use App\Http\Controllers\Gestion_Profile;
use App\Http\Controllers\SpecialiteController;
use App\Http\Controllers\DossierMedicalController;
use App\Http\Controllers\ConsultationController;
use App\Http\Controllers\MaladieController;

Route::get('/test', function () {
    return response()->json(['message' => 'API fonctionne']);
});

Route::post('/register',[AuthController::class, 'register']);
Route::post('/login',[AuthController::class, 'login'])->name('login');
Route::get('/create-admin',[AuthController::class, 'createAdmin']);
Route::post('/check-medecin',[AuthController::class, 'checkMedecin']);
Route::get('/medecins',[Gestion_Profile::class, 'afficheMedecin']);
Route::get('/patients',[Gestion_Profile::class, 'affichePatient']);
Route::get('/specialite',[SpecialiteController::class, 'AfficherTous']);
Route::get('/users/{id}',[Gestion_Profile::class, 'AfficherProfile']);
Route::get('/admin/medecins/verifies', [AuthController::class, 'medecinsVerifies']);
Route::get('/verify-email/{token}', [AuthController::class, 'verifyEmail']);

Route::get('/photo/{filename}', function ($filename) {
    $path = storage_path('app/public/photos/' . $filename);
    if (!file_exists($path)) abort(404);
    return response()->file($path);
});

Route::middleware('auth:api')->group(function () {

    
    Route::post('/logout',  [AuthController::class, 'logout']);
    Route::post('/refresh', [AuthController::class, 'refresh']);
    Route::get('/me',       [Gestion_Profile::class, 'MonProfile']);

    Route::get('/users',[Gestion_Profile::class, 'AfficherTousProfiles']);
    Route::put('/users/{id}',[Gestion_Profile::class, 'ModifierProfile']);
    Route::delete('/users/{id}',[Gestion_Profile::class, 'SupprimerProfile']);
    
    Route::post('/modifierPhoto', [Gestion_Profile::class, 'modifierPhoto']);

    Route::get('/admin/medecins/en-attente',[AuthController::class, 'medecinsEnAttente']);
    Route::get('/admin/medecins/stats', [AuthController::class, 'medecinsStats']);
    Route::put('/admin/medecins/{id}/verifier', [AuthController::class, 'verifierManuellement']);
    Route::delete('/admin/medecins/{id}/rejeter',[AuthController::class, 'rejeterMedecin']);

    Route::post('/rendezvous', [RendezVousController::class, 'AjouterRendezVous']);
    Route::get('/rendezvous',[RendezVousController::class, 'AfficherRendezVous']);
    Route::get('/rendezvous/medecin/{id_medecin}',[RendezVousController::class, 'AfficherRendezVousByMedecin']);
    Route::get('/rendezvous/patient/{id_patient}',[RendezVousController::class, 'AfficherRendezVousByPatient']);
    Route::get('/rendezvous/etat/{etat}', [RendezVousController::class, 'AfficherRendezVousParEtat']);
    Route::patch('/rendezvous/{id}/medecin/{id_medecin}/etat',[RendezVousController::class, 'ChangerEtat']);
    Route::put('/rendezvous/{id}',[RendezVousController::class, 'ModifierRendezVous']);
    Route::delete('/rendezvous/{id}',[RendezVousController::class, 'SupprimerRendezVous']);

    Route::post('/specialite',[SpecialiteController::class, 'AjouterSpecialite']);
    Route::get('/specialite/{id}',[SpecialiteController::class, 'SpecialiteById']);
    Route::put('/specialite/{id}',[SpecialiteController::class, 'ModifierSpecialite']);
    Route::delete('/specialite/{id}',[SpecialiteController::class, 'SupprimerSpecialite']);

    Route::post('/dossiers',[DossierMedicalController::class, 'AjouterDossier']);
    Route::get('/dossiers', [DossierMedicalController::class, 'AfficherTous']);
 Route::get('/dossiers/medecin/{id}/patient',[DossierMedicalController::class, 'PatientsByMedecin']);
    Route::get('/dossiers/patient/{id}/medecin',[DossierMedicalController::class, 'MedecinsByPatient']);


    Route::get('/dossiers/patient/{id}', [DossierMedicalController::class, 'AfficherDossierPatient']);
    Route::get('/dossiers/medecin/{id}',[DossierMedicalController::class, 'DossierByMedecin']);
    Route::put('/dossiers/{id}',[DossierMedicalController::class, 'ModifierDossier']);
    Route::delete('/dossiers/{id}',[DossierMedicalController::class, 'SupprimerDossier']);
   
    
    Route::post('/maladies',[MaladieController::class, 'AjouterMaladie']);
    Route::put('/maladies/{id}', [MaladieController::class, 'ModifierMaladie']);
    Route::delete('/maladies/{id}', [MaladieController::class, 'SupprimerMaladie']);
    Route::get('/maladies/dossier/{id}',[MaladieController::class, 'MaladiesParDossier']);

    Route::post('/consultations',[ConsultationController::class, 'AjouterConsultation']);
    Route::put('/consultations/{id}',[ConsultationController::class, 'ModifierConsultation']);
    Route::delete('/consultations/{id}',[ConsultationController::class, 'SupprimerConsultation']);
    Route::get('/consultations/patient/{id}',[ConsultationController::class, 'ConsultationsParPatient']);
    Route::get('/consultations/medecin/{id}',[ConsultationController::class, 'ConsultationsParMedecin']);
    Route::get('/consultations/dossier/{id}',[ConsultationController::class, 'ConsultationsParDossier']);

    Route::post('/consultations/{id}/video/demarrer', [ConsultationController::class, 'DemarrerVideo']);
    Route::post('/consultations/{id}/video/rejoindre',[ConsultationController::class, 'RejoindreVideo']);
    Route::post('/consultations/{id}/video/signal',[ConsultationController::class, 'Signal']);
    Route::post('/consultations/{id}/video/terminer', [ConsultationController::class, 'TerminerVideo']);
     Route::post('/consultations/{id}/video/token', [ConsultationController::class, 'GenererTokenJaaS']);
}); 
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password/{token}', [AuthController::class, 'resetPassword']);
Route::get('/reset-password-redirect', [AuthController::class, 'resetPasswordRedirect']);