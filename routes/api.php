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

//Authentication
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::get('/create-admin', [AuthController::class, 'createAdmin']); 

//protege des routes 
Route::middleware('auth:api')->group(function () {

    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/refresh', [AuthController::class, 'refresh']);
    Route::get('/me', [Gestion_Profile::class, 'MonProfile']);

   //Profile
    Route::get('/users', [Gestion_Profile::class, 'AfficherTousProfiles']);
    Route::get('/users/{id}', [Gestion_Profile::class, 'AfficherProfile']);
    Route::put('/users/{id}', [Gestion_Profile::class, 'ModifierProfile']);
    Route::delete('/users/{id}', [Gestion_Profile::class, 'SupprimerProfile']);
    Route::get('/medecins',[Gestion_Profile::class,'afficheMedecin']);
    Route::get('/patients',[Gestion_Profile::class,'affichePatient']);
    Route::post('/modifierPhoto', [Gestion_Profile::class, 'modifierPhoto']);


    Route::post('/rendezvous', [RendezVousController::class, 'AjouterRendezVous']);
    Route::get('/rendezvous', [RendezVousController::class, 'AfficherRendezVous']);
    Route::get('/rendezvous/medecin/{id_medecin}', [RendezVousController::class, 'AfficherRendezVousByMedecin']);
    Route::get('/rendezvous/patient/{id_patient}', [RendezVousController::class, 'AfficherRendezVousByPatient']);
    Route::get('/rendezvous/etat/{etat}', [RendezVousController::class, 'AfficherRendezVousParEtat']);
    Route::patch('/rendezvous/{id}/medecin/{id_medecin}/etat', [RendezVousController::class, 'ChangerEtat']);
    Route::put('/rendezvous/{id}', [RendezVousController::class, 'ModifierRendezVous']);
    Route::delete('/rendezvous/{id}', [RendezVousController::class, 'SupprimerRendezVous']);

    // Spécialités
    
    Route::post('/specialite', [SpecialiteController::class, 'AjouterSpecialite']);
    Route::get('/specialite', [SpecialiteController::class, 'AfficherTous']);
    Route::get('/specialite/{id}', [SpecialiteController::class, 'SpecialiteById']);
    Route::put('/specialite/{id}', [SpecialiteController::class, 'ModifierSpecialite']);
    Route::delete('/specialite/{id}', [SpecialiteController::class, 'SupprimerSpecialite']);

    //dossiers medical
   Route::post('/dossiers', [DossierMedicalController::class, 'AjouterDossier']);
    Route::get('/dossiers', [DossierMedicalController::class, 'AfficherTous']);
    Route::get('/dossiers/patient/{id}', [DossierMedicalController::class, 'AfficherDossierPatient']);
    Route::get('/dossiers/medecin/{id}', [DossierMedicalController::class, 'DossierByMedecin']);
    Route::put('/dossiers/{id}', [DossierMedicalController::class, 'ModifierDossier']);
    Route::delete('/dossiers/{id}', [DossierMedicalController::class, 'SupprimerDossier']);
    Route::get('/dossiers/medecin/{id}/patient',[DossierMedicalController::class, 'PatientsByMedecin']);
    Route::get('/dossiers/patient/{id}/medecin',[DossierMedicalController::class, 'MedecinsByPatient']);


   //maladie
   Route::post('/maladies', [MaladieController::class, 'AjouterMaladie']);
    Route::put('/maladies/{id}', [MaladieController::class, 'ModifierMaladie']);
    Route::delete('/maladies/{id}', [MaladieController::class, 'SupprimerMaladie']);
    Route::get('/maladies/dossier/{id}', [MaladieController::class, 'MaladiesParDossier']);

    //consultation
    Route::post('/consultations', [ConsultationController::class, 'AjouterConsultation']);
    Route::put('/consultations/{id}', [ConsultationController::class, 'ModifierConsultation']);
    Route::delete('/consultations/{id}', [ConsultationController::class, 'SupprimerConsultation']);
    Route::get('/consultations/patient/{id}', [ConsultationController::class, 'ConsultationsParPatient']);
    Route::get('/consultations/medecin/{id}', [ConsultationController::class, 'ConsultationsParMedecin']);

    Route::get('/consultations/dossier/{id}', [ConsultationController::class, 'ConsultationsParDossier']);




});
Route::get('/photo/{filename}', function ($filename) {
    $path = storage_path('app/public/photos/' . $filename);
    if (!file_exists($path)) abort(404);
    return response()->file($path);
});