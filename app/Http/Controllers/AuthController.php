<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Specialite;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api', [
            'except' => ['login', 'register', 'refresh', 'createAdmin']
        ]);
    }

    public function register(Request $request)
    {
        // Validation
        $request->validate([
            'nom' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'adresse' => 'nullable|string|max:255',
            'telephone' => 'nullable|string|size:8',
            'age' => 'nullable|integer|min:1|max:120',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'role' => 'required|in:patient,medecin,admin',
            'dateNaissance' => 'required_if:role,patient|date',
            'sexe' => 'required_if:role,patient|in:homme,femme',
            'specialite' => 'required_if:role,medecin|integer|exists:specialite,id',
            'experience' => 'required_if:role,medecin|integer|min:0',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
    ]);

   $photoPath = null;
if ($request->hasFile('photo')) {
   
    $photoPath = $request->file('photo')->store('uploads/users', 'public'); 
    
}

        // Création de l'utilisateur
        $user = User::create([
            'nom' => $request->nom,
            'prenom' => $request->prenom,
            'adresse' => $request->adresse,
            'telephone' => $request->telephone,
            'age' => $request->age,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'photo' => $photoPath,

        ]);

        // Profil patient
        if ($request->role === 'patient') {
            $user->patient()->create([
                'dateNaissance' => $request->dateNaissance,
                'sexe' => $request->sexe
            ]);
        }

        // Profil médecin
        if ($request->role === 'medecin') {
            $specialite = Specialite::find($request->specialite);
            if (!$specialite) {
                return response()->json([
                    'error' => 'La spécialité sélectionnée est invalide.'
                ], 422);
            }

            $user->medecin()->create([
                'specialite_id' => $specialite->id,
                'experience' => $request->experience
            ]);
        }

        // Token JWT
        $token = auth('api')->login($user);

        return response()->json([
    'user' => [
        'id' => $user->id,
        'nom' => $user->nom,
        'prenom' => $user->prenom,
        'email' => $user->email,
        'role' => $user->role,
        'photo' => $photoPath ? url('storage/' . $photoPath) : null, 
    ],
    'token' => $token
], 201);

       
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if (!$token = auth('api')->attempt($request->only('email', 'password'))) {
            return response()->json(['error' => 'Identifiants incorrects'], 401);
        }

        return $this->respondWithToken($token);
    }

    public function me()
{
    $user = auth('api')->user()->load(['patient', 'medecin']);

    if ($user->photo) {
        $user->photo = url('storage/' . $user->photo);
    }

    return response()->json($user);
}

    public function logout()
    {
        auth('api')->logout();
        return response()->json(['message' => 'Déconnecté avec succès']);
    }

    public function refresh()
    {
        return $this->respondWithToken(auth('api')->refresh());
    }

    protected function respondWithToken($token)
{
    $user = auth('api')->user()->load(['patient', 'medecin']);

    if ($user->photo) {
        $user->photo = url('storage/' . $user->photo);
    }

    return response()->json([
        'access_token' => $token,
        'token_type' => 'bearer',
        'expires_in' => auth('api')->factory()->getTTL() * 60,
        'user' => $user
    ]);
}

    public function createAdmin()
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'nom' => 'Admin',
                'prenom' => 'System',
                'password' => Hash::make('Admin123'),
                'role' => 'admin'
            ]
        );

        return response()->json(['message' => 'Admin prêt', 'admin' => $admin]);
    }
}