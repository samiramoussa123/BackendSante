<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Maladie;
use App\Models\DossierMedical;

class MaladieController extends Controller
{
    public function AjouterMaladie(Request $req)
    {
        $validated = $req->validate([
            'dossier_medical_id' => 'required|integer|exists:dossier_medical,id',
            'nom_maladie' => 'required|string',
            'date_diagnostic' => 'required|date',
        ]);

        $dossier = DossierMedical::find($validated['dossier_medical_id']);
        if(!$dossier){
            return response()->json([
                'message' => 'Dossier médical introuvable'
            ], 404);
        }

        $maladie = Maladie::create($validated);

        $maladie->load(['dossier']);

        return response()->json([
            'message' => 'Maladie ajoutée avec succès',
            'maladie' => $maladie
        ], 201);
    }

    public function ModifierMaladie(Request $req, $id)
    {
        $maladie = Maladie::find($id);

        if(!$maladie){
            return response()->json([
                'message' => 'Maladie introuvable'
            ], 404);
        }

        $validated = $req->validate([
            'dossier_medical_id' => 'required|integer|exists:dossier_medical,id',
            'nom_maladie' => 'required|string',
            'date_diagnostic' => 'required|date',
        ]);

        $maladie->update($validated);

        $maladie->load(['dossier']);

        return response()->json([
            'message' => 'Maladie modifiée avec succès',
            'maladie' => $maladie
        ], 200);
    }

    public function SupprimerMaladie($id)
    {
        $maladie = Maladie::find($id);

        if(!$maladie){
            return response()->json([
                'message' => 'Maladie introuvable'
            ], 404);
        }

        $maladie->delete();

        return response()->json([
            'message' => 'Maladie supprimée avec succès'
        ], 200);
    }

    public function MaladiesParDossier($dossier_id)
    {
        $maladies = Maladie::where('dossier_medical_id', $dossier_id)
            ->with('dossier')
            ->get();

        if($maladies->isEmpty()){
            return response()->json([
                'message' => 'Aucune maladie trouvée pour ce dossier'
            ], 404);
        }

        return response()->json([
            'maladies' => $maladies,
            'total' => $maladies->count()
        ], 200);
    }
}