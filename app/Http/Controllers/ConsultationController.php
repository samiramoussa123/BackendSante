<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Consultation;
use App\Models\DossierMedical;
use App\Models\RendezVous;

class ConsultationController extends Controller
{
    public function AjouterConsultation(Request $req)
    {
        $validated = $req->validate([
            'dossier_medical_id' => 'required|integer|exists:dossier_medical,id',
            'date_consultation' => 'required|date',
            'diagnostique' => 'nullable|string',
            'traitement' => 'nullable|string',
        ]);

        $dossier = DossierMedical::find($validated['dossier_medical_id']);
        if(!$dossier){
            return response()->json([
                'message' => 'Dossier médical introuvable'
            ], 404);
        }

        $rdv = RendezVous::where('id_patient', $dossier->patient_id)
    ->where('id_medecin', $dossier->medecin_id)
            ->whereDate('date', $validated['date_consultation'])
            ->where('etat', 'confirmé') // ou 'status' selon ta table
            ->first();

        if(!$rdv){
            return response()->json([
                'message' => 'Aucun rendez-vous confirmé pour ce patient à cette date'
            ], 409);
        }

        $consultation = Consultation::create($validated);

        $consultation->load(['dossier', 'dossier.patient', 'dossier.medecin']);

        return response()->json([
            'message' => 'Consultation ajoutée avec succès',
            'consultation' => $consultation
        ], 201);
    }

    // Modifier une consultation
    public function ModifierConsultation(Request $req, $id)
    {
        $validated = $req->validate([
            'dossier_medical_id' => 'required|integer|exists:dossier_medical,id',
            'date_consultation' => 'required|date',
            'diagnostique' => 'nullable|string',
            'traitement' => 'nullable|string',
        ]);

        $consultation = Consultation::find($id);
        if(!$consultation){
            return response()->json([
                'message' => "Consultation introuvable"
            ], 404);
        }

        $dossier = DossierMedical::find($validated['dossier_medical_id']);
        if(!$dossier){
            return response()->json([
                'message' => 'Dossier médical introuvable'
            ], 404);
        }

        $rdv = RendezVous::where('patient_id', $dossier->patient_id)
            ->where('medecin_id', $dossier->medecin_id)
            ->whereDate('date', $validated['date_consultation'])
            ->where('etat', 'confirmé')
            ->first();

        if(!$rdv){
            return response()->json([
                'message' => 'Aucun rendez-vous confirmé pour ce patient à cette date'
            ], 409);
        }

        $consultation->update($validated);

        $consultation->load(['dossier', 'dossier.patient', 'dossier.medecin']);

        return response()->json([
            'message' => 'Consultation modifiée avec succès',
            'consultation' => $consultation
        ], 200);
    }

    // Supprimer une consultation
    public function SupprimerConsultation($id)
    {
        $consultation = Consultation::find($id);

        if(!$consultation){
            return response()->json([
                'message' => 'Consultation introuvable'
            ], 404);
        }

        $consultation->delete();

        return response()->json([
            'message' => 'Consultation supprimée avec succès'
        ], 200);
    }

    // Afficher toutes les consultations d'un patient
    public function ConsultationsParPatient($patient_id)
    {
        $consultations = Consultation::whereHas('dossier', function($q) use ($patient_id){
                $q->where('patient_id', $patient_id);
            })
            ->with(['dossier', 'dossier.medecin'])
            ->get();

        if($consultations->isEmpty()){
            return response()->json([
                'message' => 'Aucune consultation trouvée pour ce patient'
            ], 404);
        }

        return response()->json([
            'consultations' => $consultations,
            'total' => $consultations->count()
        ], 200);
    }

    // Afficher toutes les consultations pour un médecin
    public function ConsultationsParMedecin($medecin_id)
    {
        $consultations = Consultation::whereHas('dossier', function($q) use ($medecin_id){
                $q->where('medecin_id', $medecin_id);
            })
            ->with(['dossier', 'dossier.patient'])
            ->get();

        if($consultations->isEmpty()){
            return response()->json([
                'message' => 'Aucune consultation trouvée pour ce médecin'
            ], 404);
        }

        return response()->json([
            'consultations' => $consultations,
            'total' => $consultations->count()
        ], 200);
    }
}