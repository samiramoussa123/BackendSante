<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Specialite;
use App\Models\Patient;
use App\Models\Medecin;
use Illuminate\Support\Facades\Storage;

class Gestion_Profile extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api',['except' => ['afficheMedecin'],['AfficherProfile'],['affichePatient']]);
    }

    public function MonProfile()
    {
        $user = auth('api')->user()->load(['patient', 'medecin']);
        return response()->json(['user' => $user]);
    }

    public function AfficherProfile($id)
    {
        if (auth('api')->user()->role !== 'admin') {
            return response()->json(['message' => 'Non autorisé'], 403);
        }
        $user = User::with(['patient', 'medecin'])->find($id);
        if (!$user) {
            return response()->json(['message' => 'Utilisateur non trouvé'], 404);
        }
        return response()->json(['user' => $user]);
    }

    public function ModifierProfile(Request $request, $id)
    {
        $authUser = auth('api')->user();
        $user = User::with(['patient', 'medecin'])->find($id);
        if (!$user) {
            return response()->json(['message' => 'Utilisateur non trouvé'], 404);
        }
        if ($authUser->id !== $user->id && $authUser->role !== 'admin') {
            return response()->json(['message' => 'Non autorisé'], 403);
        }
        $request->validate([
            'nom'           => 'sometimes|string|max:255',
            'prenom'        => 'sometimes|string|max:255',
            'adresse'       => 'nullable|string|max:255',
            'telephone'     => 'nullable|string|size:8',
            'age'           => 'nullable|integer|min:1|max:120',
            'dateNaissance' => 'sometimes|date',
            'sexe'          => 'sometimes|in:homme,femme',
            'specialite'    => 'sometimes|exists:specialite,nom_specialite',
            'experience'    => 'sometimes|integer|min:0'
        ]);
        $user->update($request->only(['nom', 'prenom', 'adresse', 'telephone', 'age']));
        if ($user->role === 'patient' && $user->patient) {
            $user->patient->update($request->only(['dateNaissance', 'sexe']));
        }
        if ($user->role === 'medecin' && $user->medecin) {
            if ($request->has('specialite')) {
                $specialite = Specialite::where('nom_specialite', $request->specialite)->first();
                if ($specialite) {
                    $user->medecin->specialite_id = $specialite->id;
                }
            }
            if ($request->has('experience')) {
                $user->medecin->experience = $request->experience;
            }
            $user->medecin->save();
        }
        return response()->json([
            'message' => 'Profil mis à jour avec succès',
            'user'    => $user->fresh(['patient', 'medecin'])
        ]);
    }

    public function SupprimerProfile($id)
    {
        if (auth('api')->user()->role !== 'admin') {
            return response()->json(['message' => 'Non autorisé'], 403);
        }
        $user = User::find($id);
        if (!$user) {
            return response()->json(['message' => 'Utilisateur non trouvé'], 404);
        }
        $user->delete();
        return response()->json(['message' => 'Utilisateur supprimé avec succès']);
    }

    public function AfficherTousProfiles()
    {
        if (auth('api')->user()->role !== 'admin') {
            return response()->json(['message' => 'Non autorisé'], 403);
        }
        $users = User::with(['patient', 'medecin'])->get();
        return response()->json(['users' => $users]);
    }

    public function afficheMedecin()
    {
        $medecins = User::where('role', 'medecin')
            ->with('medecin.specialite')
            ->get()
            ->map(function ($m) {
                $photoUrl = null;
                if ($m->photo) {
                    if (filter_var($m->photo, FILTER_VALIDATE_URL)) {
                        $photoUrl = $m->photo;
                    } elseif (strpos($m->photo, 'uploads/') !== false) {
                        $photoUrl = url('storage/photos/' . basename($m->photo));
                    } else {
                        $photoUrl = url('storage/photos/' . $m->photo);
                    }
                }

                return [
                    'id'             => $m->id,
                    'medecin_id'     => $m->medecin->id ?? null,
                    'nom'            => $m->nom,
                    'prenom'         => $m->prenom,
                    'email'          => $m->email,
                    'telephone'      => $m->telephone ?? '—',
                    'adresse'        => $m->adresse ?? '—',
                    'age'            => $m->age,
                    'role'           => $m->role,
                    'specialite_id'  => $m->medecin->specialite_id ?? null,
                    'nom_specialite' => $m->medecin->specialite->nom_specialite ?? '—',
                    'experience'     => $m->medecin->experience ?? 0,
                    'photo'          => $photoUrl,
                ];
            });

        return response()->json($medecins);
    }

    public function affichePatient()
    {
        $patients = User::where('role', 'patient')
            ->with('patient')
            ->get()
            ->map(function ($p) {
                $photoUrl = null;
                if ($p->photo) {
                    if (filter_var($p->photo, FILTER_VALIDATE_URL)) {
                        $photoUrl = $p->photo;
                    } elseif (strpos($p->photo, 'uploads/') !== false) {
                        $photoUrl = url('storage/photos/' . basename($p->photo));
                    } else {
                        $photoUrl = url('storage/photos/' . $p->photo);
                    }
                }
                return [
                    'id'            => $p->id,
                    //  AJOUT : id de la table patients (utilisé pour les rendez-vous)
                    'patient_id'    => $p->patient->id ?? null,
                    'nom'           => $p->nom,
                    'prenom'        => $p->prenom,
                    'email'         => $p->email,
                    'telephone'     => $p->telephone ?? '—',
                    'adresse'       => $p->adresse ?? '—',
                    'age'           => $p->age,
                    'role'          => $p->role,
                    'dateNaissance' => $p->patient->dateNaissance ?? null,
                    'sexe'          => $p->patient->sexe ?? '—',
                    'photo'         => $photoUrl,
                ];
            });

        return response()->json($patients);
    }

    public function modifierPhoto(Request $req)
    {
        $user = auth('api')->user();
        if (!$user) {
            return response()->json(['message' => 'Utilisateur non authentifié'], 401);
        }
        if ($req->hasFile('photo')) {
            if ($user->photo) {
                Storage::disk('public')->delete('photos/' . $user->photo);
            }
            $file         = $req->file('photo');
            $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $extension    = $file->getClientOriginalExtension();
            $safeName     = preg_replace('/[^a-zA-Z0-9_-]/', '_', $originalName);
            $filename     = time() . '_' . $safeName . '.' . $extension;
            $file->storeAs('photos', $filename, 'public');
            $user->photo = $filename;
            $user->save();
            return response()->json([
                'message' => 'Photo mise à jour avec succès',
                'photo'   => $filename
            ], 200);
        }
        return response()->json(['message' => 'Aucun fichier envoyé'], 400);
    }
}