<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Specialite;

class SpecialiteController extends Controller
{
    // Ajouter
    public function AjouterSpecialite(Request $req)
    {
        $req->validate([
            'nom_specialite' => 'required|string|max:255',
        ]);

        $specialite = Specialite::create([
            'nom_specialite' => $req->nom_specialite,
        ]);

        return response()->json($specialite, 201);
    }

    // Modifier
    public function ModifierSpecialite(Request $req, $id)
    {
        $req->validate([
            'nom_specialite' => 'required|string|max:255',
        ]);

        $specialite = Specialite::find($id);

        if (!$specialite) {
            return response()->json([
                'message' => 'Spécialité non trouvée'
            ], 404);
        }

        $specialite->update($req->all());

        return response()->json([
            'message' => 'Spécialité modifiée avec succès',
            'specialite' => $specialite
        ]);
    }

    // Afficher tous
    public function AfficherTous()
    {
        $specialites = Specialite::all();

        return response()->json($specialites);
    }

    // Supprimer
    public function SupprimerSpecialite($id)
    {
        $specialite = Specialite::find($id);

        if (!$specialite) {
            return response()->json([
                'message' => 'Spécialité non trouvée'
            ], 404);
        }

        $specialite->delete();

        return response()->json([
            'message' => 'Spécialité supprimée avec succès'
        ]);
    }

    // Afficher par ID
    public function SpecialiteById($id)
    {
        $specialite = Specialite::find($id);

        if (!$specialite) {
            return response()->json([
                'message' => 'Spécialité non trouvée'
            ], 404);
        }

        return response()->json($specialite);
    }
}