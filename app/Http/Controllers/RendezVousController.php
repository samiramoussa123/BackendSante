<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RendezVous;
use App\Models\Patient;
use App\Models\Medecin;

class RendezVousController extends Controller
{
    
    public function AjouterRendezVous(Request $request)
    {
        $validated = $request->validate([
            'date' => 'required|date|after_or_equal:today',
            'heure' => 'required|date_format:H:i',
            'id_patient' => 'required|integer|exists:patients,id',
            'id_medecin' => 'required|integer|exists:medecins,id',
        ]);

        $validated['etat'] = 'en attend';

        $existe = RendezVous::where('id_medecin', $request->id_medecin)
            ->where('date', $request->date)
            ->where('heure', $request->heure)
            ->exists();

        if ($existe) {
            return response()->json([
                'message' => 'Ce médecin a déjà un rendez-vous à cette heure'
            ], 409);
        }

        $rendezvous = RendezVous::create($validated);
        $rendezvous->load(['patient', 'medecin']);

        return response()->json([
            'message' => 'Rendez-vous ajouté avec succès',
            'rendez_vous' => $rendezvous
        ], 201);
    }

   
    public function AfficherRendezVous()
    {
        $rendezvous = RendezVous::with(['patient', 'medecin'])->get();

        return response()->json([
            'rendez_vous' => $rendezvous,
            'total' => $rendezvous->count()
        ]);
    }

   
    public function AfficherRendezVousByMedecin($id_medecin)
    {
        $medecin = Medecin::find($id_medecin);

        if (!$medecin) {
            return response()->json([
                'message' => 'Médecin non trouvé'
            ], 404);
        }

        $rendezvous = RendezVous::where('id_medecin', $id_medecin)
            ->with('patient')
            ->orderBy('date')
            ->orderBy('heure')
            ->get();

        return response()->json([
            'medecin' => $medecin->nom,
            'rendez_vous' => $rendezvous,
            'total' => $rendezvous->count()
        ]);
    }

    
    public function AfficherRendezVousByPatient($id_patient)
    {
        $patient = Patient::find($id_patient);

        if (!$patient) {
            return response()->json([
                'message' => 'Patient non trouvé'
            ], 404);
        }

        $rendezvous = RendezVous::where('id_patient', $id_patient)
            ->with('medecin')
            ->orderBy('date')
            ->orderBy('heure')
            ->get();

        return response()->json([
            'patient' => $patient->nom,
            'rendez_vous' => $rendezvous,
            'total' => $rendezvous->count()
        ]);
    }

    
    public function AfficherRendezVousParEtat($etat)
    {
        $rendezvous = RendezVous::where('etat', $etat)
            ->with(['patient', 'medecin'])
            ->get();

        return response()->json([
            'etat' => $etat,
            'rendez_vous' => $rendezvous,
            'total' => $rendezvous->count()
        ]);
    }

    
    public function ChangerEtat(Request $request, $id, $id_medecin)
    {
        $rendezvous = RendezVous::where('id', $id)
            ->where('id_medecin', $id_medecin)
            ->first();

        if (!$rendezvous) {
            return response()->json([
                'message' => 'Rendez-vous non trouvé ou vous n\'êtes pas autorisé'
            ], 404);
        }

        $validated = $request->validate([
            'etat' => 'required|in:confirmé,refusé'
        ]);

        $rendezvous->update(['etat' => $validated['etat']]);
        $rendezvous->load(['patient', 'medecin']);

        return response()->json([
            'message' => 'État du rendez-vous mis à jour avec succès',
            'rendez_vous' => $rendezvous
        ]);
    }

    
    public function ModifierRendezVous(Request $request, $id)
    {
        $rendezvous = RendezVous::find($id);

        if (!$rendezvous) {
            return response()->json([
                'message' => 'Rendez-vous non trouvé'
            ], 404);
        }

        if ($rendezvous->etat !== 'en attend') {
            return response()->json([
                'message' => 'Impossible de modifier un rendez-vous déjà ' . $rendezvous->etat
            ], 403);
        }

        $validated = $request->validate([
            'date' => 'sometimes|required|date|after_or_equal:today',
            'heure' => 'sometimes|required|date_format:H:i',
            'id_patient' => 'sometimes|required|integer|exists:patients,id',
            'id_medecin' => 'sometimes|required|integer|exists:medecins,id',
        ]);

        $medecinChange = $request->has('id_medecin') && $request->id_medecin != $rendezvous->id_medecin;
        $dateChange = $request->has('date') && $request->date != $rendezvous->date;
        $heureChange = $request->has('heure') && $request->heure != $rendezvous->heure;

        if ($medecinChange || $dateChange || $heureChange) {
            $existe = RendezVous::where('id_medecin', $request->id_medecin ?? $rendezvous->id_medecin)
                ->where('date', $request->date ?? $rendezvous->date)
                ->where('heure', $request->heure ?? $rendezvous->heure)
                ->where('id', '!=', $id)
                ->exists();

            if ($existe) {
                return response()->json([
                    'message' => 'Ce créneau n\'est pas disponible'
                ], 409);
            }
        }

        $rendezvous->update($validated);
        $rendezvous->load(['patient', 'medecin']);

        return response()->json([
            'message' => 'Rendez-vous modifié avec succès',
            'rendez_vous' => $rendezvous
        ]);
    }

  
    public function SupprimerRendezVous($id)
    {
        $rendezvous = RendezVous::find($id);

        if (!$rendezvous) {
            return response()->json([
                'message' => 'Rendez-vous non trouvé'
            ], 404);
        }

        if ($rendezvous->etat !== 'en attend') {
            return response()->json([
                'message' => 'Impossible de supprimer un rendez-vous déjà ' . $rendezvous->etat
            ], 403);
        }

        $rendezvous->delete();

        return response()->json([
            'message' => 'Rendez-vous supprimé avec succès'
        ]);
    }
}