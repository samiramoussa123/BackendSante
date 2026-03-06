<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DossierMedical;
use App\Models\Medecin;
use App\Models\Patient;

class DossierMedicalController extends Controller
{

public function AjouterDossier(Request $req)
{
    $validated = $req->validate([
        'patient_id' => 'required|integer|exists:patients,id',
        'medecin_id' => 'required|integer|exists:medecins,id',
    ]);

    $exist = DossierMedical::where('patient_id', $req->patient_id)
        ->where('medecin_id', $req->medecin_id)
        ->exists();

    if ($exist) {
        return response()->json([
            'message' => 'Ce patient a déjà un dossier médical'
        ], 409);
    }

    $dossierMedical = DossierMedical::create($validated);

    $dossierMedical->load(['patient', 'maladies', 'consultations']);

    return response()->json([
        'message' => 'Dossier ajouté avec succès',
        'dossier_medical' => $dossierMedical
    ], 201);
}
public function ModifierDossier(Request $request, $id)
{
    $dossier = DossierMedical::find($id);

    if (!$dossier) {
        return response()->json([
            'message' => 'Dossier non trouvé',
        ], 404);
    }

    $validated = $request->validate([
        'patient_id' => 'required|integer|exists:patients,id',
        'medecin_id' => 'required|integer|exists:medecins,id',
    ]);

    $dossier->update($validated);

    $dossier->load(['patient', 'medecin', 'maladies', 'consultations']);

    return response()->json([
        'message' => 'Dossier modifié avec succès',
        'dossier_medical' => $dossier
    ], 200);
}
public function AfficherDossierPatient($patient_id)
{
    $dossier = DossierMedical::where('patient_id', $patient_id)
        ->with(['medecin', 'maladies', 'consultations'])
        ->get();

    if ($dossier->isEmpty()) {
        return response()->json([
            'message' => 'Aucun dossier trouvé pour ce patient'
        ], 404);
    }

    return response()->json([
        'dossiers' => $dossier,
        'total' => $dossier->count()
    ], 200);
}

public function DossierByMedecin($medecin_id)
{
    $dossier = DossierMedical::where('medecin_id', $medecin_id)
        ->with(['patient', 'maladies', 'consultations'])
        ->get();

    if ($dossier->isEmpty()) {
        return response()->json([
            'message' => 'Aucun dossier trouvé pour ce médecin'
        ], 404);
    }

    return response()->json([
        'message' => 'Liste des dossiers pour ce médecin',
        'dossiers' => $dossier,
        'total' => $dossier->count()
    ], 200);
}
public function SupprimerDossier($id){
    $dossier=DossierMedical::find($id);
    if(!$dossier){
        return response()->json([
            'message'=>'dossier non trouvée'
        ],409);
    }
    $dossier->delete();
    return response()->json([
        'message'=>'dossier supprimé avec succèes',

    ]);
}
public function AfficherTous()
{
    $dossiers = DossierMedical::with(['patient','medecin'])->get();

    return response()->json([
        'total' => $dossiers->count(),
        'dossiers' => $dossiers
    ], 200);
}


public function PatientsByMedecin($medecin_id)
{
    $dossiers = DossierMedical::where('medecin_id', $medecin_id)
        ->with('patient.user')
        ->get();

    if ($dossiers->isEmpty()) {
        return response()->json([
            'message' => 'Aucun patient trouvé pour ce médecin'
        ], 404);
    }

    $patients = $dossiers->map(function ($d) {
        return [
            'patient_id' => $d->patient->id,
            'nom' => $d->patient->user->nom ?? null,
            'prenom' => $d->patient->user->prenom ?? null,
            'email' => $d->patient->user->email ?? null,
            'dateNaissance' => $d->patient->dateNaissance ?? null,
            'sexe' => $d->patient->sexe ?? null,
        ];
    });

    return response()->json([
        'medecin_id' => $medecin_id,
        'patients' => $patients,
        'total' => $patients->count()
    ], 200);
}

public function MedecinsByPatient($patient_id)
{
    $dossiers = DossierMedical::where('patient_id', $patient_id)
        ->with('medecin')
        ->get();

    if ($dossiers->isEmpty()) {
        return response()->json([
            'message' => 'Aucun médecin trouvé pour ce patient'
        ], 404);
    }

    $medecins = $dossiers->map(function ($d) {
        return [
            'medecin_id' => $d->medecin->id,
            'nom' => $d->medecin->nom ?? null,
            'prenom' => $d->medecin->prenom ?? null,
            'email' => $d->medecin->email ?? null,
            'specialite'=>$d->medecin->specialite?? null,
        ];
    });

    return response()->json([
        'patient_id' => $patient_id,
        'medecins' => $medecins,
        'total' => $medecins->count()
    ], 200);
}
}