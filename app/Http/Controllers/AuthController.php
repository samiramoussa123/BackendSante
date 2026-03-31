<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Specialite;
use App\Models\User;
use App\Models\Medecin;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Notifications\CompteVerifie;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
class AuthController extends Controller
{
    private $medecinsJson = [];

    public function __construct()
    {
        $this->middleware('auth:api', [
            'except' => ['login', 'register', 'refresh', 'createAdmin', 'checkMedecin', 'medecinsVerifies', 'verifyEmail',  'forgotPassword', 'resetPassword'
        ,'resetPasswordRedirect'],]);
        $this->chargerJsonMedecins();
    }

    //  VÉRIFICATION EMAIL 
    
     // Envoyer l'email de vérification manuellement pour admin 
     
    private function envoyerEmailVerification(User $user)
    {
        $token = Str::random(64);
        $user->email_verification_token = $token;
        $user->save();

$verifyUrl = url("/api/verify-email/{$token}");
        Mail::send('emails.verify', ['url' => $verifyUrl, 'user' => $user], function ($mail) use ($user) {
            $mail->to($user->email)
                 ->subject('Vérifiez votre adresse email - FontSanté');
        });
    }

    
   //Vérifier l'email via le token reçu par email
     
    public function verifyEmail($token)
    {
        $user = User::where('email_verification_token', $token)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Lien de vérification invalide ou expiré'
            ], 400);
        }

        if ($user->email_verified_at) {
            return response()->json([
                'success' => true,
                'message' => 'Email déjà vérifié, vous pouvez vous connecter'
            ]);
        }

        $user->email_verified_at = now();
        $user->email_verification_token = null;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Email vérifié avec succès, vous pouvez maintenant vous connecter'
        ]);
    }

    
//Register
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nom'          => 'required|string|max:255',
            'prenom'       => 'required|string|max:255',
            'email'        => 'required|email|unique:users,email',
            'mdp'     => 'required|min:6|confirmed',
            'role'         => 'required|in:patient,medecin,admin',
            'telephone'    => 'nullable|string|size:8',
            'adresse'      => 'nullable|string|max:255',
            'specialite'   => 'required_if:role,medecin|integer|exists:specialite,id',
            'experience'   => 'nullable|integer|min:0',
            'dateNaissance'=> 'required_if:role,patient|date',
            'sexe'         => 'required_if:role,patient|in:homme,femme',
            'age'          => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors()
            ], 422);
        }

        try {
            $user = User::create([
                'nom'      => $request->nom,
                'prenom'   => $request->prenom,
                'email'    => $request->email,
                'mdp' => Hash::make($request->mdp),
                'role'     => $request->role,
                'telephone'=> $request->telephone,
                'adresse'  => $request->adresse,
                'age'      => $request->age,
                'email_verification_token' => Str::random(64),
            ]);

            if ($request->hasFile('photo')) {
                $user->photo = $request->file('photo')->store('photos', 'public');
                $user->save();
            }

            $message = '';
            $medecinTrouve = null;

            if ($request->role === 'medecin') {
                $specialite = Specialite::find($request->specialite);

                if (!$specialite) {
                    $user->delete();
                    return response()->json([
                        'success' => false,
                        'message' => 'Spécialité non trouvée'
                    ], 404);
                }

                $medecinTrouve = $this->verifierMedecinDansJson(
                    $request->nom,
                    $request->prenom,
                    $specialite->nom_specialite
                );

                $medecinData = [
                    'specialite_id' => $specialite->id,
                    'experience'    => $request->experience ?? 0,
                    'verifie_json'  => $medecinTrouve ? true : false,
                ];

                if ($medecinTrouve) {
                    $medecinData['donnees_json'] = json_encode($medecinTrouve);
                }

                $user->medecin()->create($medecinData);

                $message = $medecinTrouve
                    ? 'Inscription réussie - médecin vérifié'
                    : 'Inscription réussie - médecin en attente de validation';

            } elseif ($request->role === 'patient') {
                $user->patient()->create([
                    'dateNaissance' => $request->dateNaissance,
                    'sexe'          => $request->sexe,
                ]);
                $message = 'Inscription patient réussie';
            } else {
                $message = 'Inscription admin réussie';
            }

            //   email de vérification
            $this->envoyerEmailVerification($user);

            return response()->json([
                'success' => true,
                'message' => $message . '. Vérifiez votre email pour activer votre compte.'
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

   public function login(Request $request)
{
    $validator = Validator::make($request->all(), [
        'email' => 'required|email',
        'mdp'   => 'required',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'errors'  => $validator->errors()
        ], 422);
    }

    // ✅ Mapper 'mdp' vers 'password' pour JWT
    $credentials = [
        'email'    => $request->email,
        'password' => $request->mdp,
    ];

    if (!$token = auth('api')->attempt($credentials)) {
        return response()->json([
            'success' => false,
            'message' => 'Identifiants incorrects'
        ], 401);
    }

    $user = auth('api')->user();

    if (!$user->email_verified_at) {
        auth('api')->logout();
        return response()->json([
            'success'        => false,
            'message'        => 'Veuillez vérifier votre email avant de vous connecter.',
            'email_verified' => false,
        ], 403);
    }

    return $this->respondWithToken($token);
}

    public function me()
    {
        $user = auth('api')->user();

        if ($user->role === 'medecin') {
            $user->load('medecin.specialite');
        } elseif ($user->role === 'patient') {
            $user->load('patient');
        }

        if ($user->photo) {
            $user->photo = url('storage/' . $user->photo);
        }

        return response()->json(['success' => true, 'user' => $user]);
    }

    public function logout()
    {
        auth('api')->logout();
        return response()->json(['success' => true, 'message' => 'Déconnecté avec succès']);
    }

    public function refresh()
    {
        return $this->respondWithToken(auth('api')->refresh());
    }

    protected function respondWithToken($token)
    {
        $user = auth('api')->user();

        if ($user->photo) {
            $user->photo = url('storage/' . $user->photo);
        }

        return response()->json([
            'success'      => true,
            'access_token' => $token,
            'token_type'   => 'bearer',
            'expires_in'   => auth('api')->factory()->getTTL() * 60,
            'user'         => $user,
        ]);
    }

    // ADMIN PAR DEFEAULT

   public function createAdmin()
{
    try {
        // Vérifier si admin existe déjà
        $exists = User::where('email', 'admin@gmail.com')->first();
        
        if ($exists) {
            return response()->json([
                'success' => true,
                'message' => 'Admin existe déjà',
                'admin'   => $exists
            ]);
        }

        $admin = User::create([
            'nom'                => 'Admin',
            'prenom'             => 'System',
            'email'              => 'admin@gmail.com',
            'mdp'           => Hash::make('Admin123'),
            'role'               => 'admin',
            //  Email vérifié d'office
            'email_verified_at'  => now(),
            //  Pas de token de vérification
            'email_verification_token' => null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Admin créé avec succès',
            'admin'   => $admin
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Erreur: ' . $e->getMessage()
        ], 500);
    }
}

    public function medecinsEnAttente()
    {
        $medecins = User::where('role', 'medecin')
            ->whereHas('medecin', fn($q) => $q->where('verifie_json', false))
            ->with('medecin.specialite')
            ->get();

        return response()->json([
            'success'  => true,
            'count'    => $medecins->count(),
            'medecins' => $medecins,
        ]);
    }

    public function verifierManuellement($id)
    {
        //  $id = ID de la table medecins
        $medecin = Medecin::findOrFail($id);
        $medecin->update(['verifie_json' => true]);
        $medecin->user->notify(new CompteVerifie());

        return response()->json([
            'success' => true,
            'message' => 'Médecin vérifié manuellement avec succès'
        ]);
    }

    public function rejeterMedecin($id)
    {
        try {
            // $id = ID de la table medecins
            $medecin = Medecin::findOrFail($id);
            $user = $medecin->user;
            $medecin->delete();
            $user->delete();

            return response()->json([
                'success' => true,
                'message' => 'Médecin rejeté et supprimé avec succès'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }

    public function medecinsVerifies()
    {
        $medecins = User::where('role', 'medecin')
            ->whereHas('medecin', fn($q) => $q->where('verifie_json', true))
            ->with('medecin.specialite')
            ->get();

        return response()->json([
            'success'  => true,
            'count'    => $medecins->count(),
            'medecins' => $medecins,
        ]);
    }

    public function medecinsStats()
    {
        $total     = User::where('role', 'medecin')->count();
        $verifies  = User::where('role', 'medecin')
            ->whereHas('medecin', fn($q) => $q->where('verifie_json', true))
            ->count();

        return response()->json([
            'success' => true,
            'stats'   => [
                'total'      => $total,
                'verifies'   => $verifies,
                'en_attente' => $total - $verifies,
            ],
        ]);
    }

    public function checkMedecin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nom'       => 'required|string',
            'prenom'    => 'required|string',
            'specialite'=> 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $trouve = $this->verifierMedecinDansJson($request->nom, $request->prenom, $request->specialite);

        return response()->json([
            'success' => true,
            'exists'  => (bool) $trouve,
            'message' => $trouve ? "Médecin trouvé dans l'annuaire" : "Médecin non trouvé dans l'annuaire",
            'data'    => $trouve,
        ]);
    }

    // PRIVÉ 

    private function chargerJsonMedecins()
    {
        try {
            $jsonPath = storage_path('app/data/medecins_prives.json');
            if (file_exists($jsonPath)) {
                $this->medecinsJson = json_decode(file_get_contents($jsonPath), true);
            }
        } catch (\Exception $e) {
            $this->medecinsJson = [];
        }
    }

    private function verifierMedecinDansJson($nom, $prenom, $specialiteNom)
    {
        if (empty($this->medecinsJson)) return null;

        $n = strtoupper(trim($nom));
        $p = strtoupper(trim($prenom));
        $s = strtoupper(trim($specialiteNom));

        foreach ($this->medecinsJson as $m) {
            $nj = strtoupper(trim($m['nom'] ?? ''));
            $pj = strtoupper(trim($m['prenom'] ?? ''));
            $sj = strtoupper(trim($m['lib_spec'] ?? ''));

            if ($nj === $n && $sj === $s && ($pj === $p || str_contains($pj, $p) || str_contains($p, $pj))) {
                return $m;
            }
        }

        return null;
    }
public function resetPasswordRedirect(Request $request)
{
    $token = $request->query('token');
    $email = $request->query('email');
    Log::info('Token redirect: ' . $token);
    Log::info('Email redirect: ' . $email);
    Log::info('URL générée: ' . "fontsante://reset-password/{$token}?email={$email}");

    // Redirige vers le deep link de l'app
return redirect("fontsante://reset-password/{$token}?email={$email}");
}
    public function forgotPassword(Request $request)
{
    $validator = Validator::make($request->all(), [
        'email' => 'required|email|exists:users,email',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'errors'  => $validator->errors()
        ], 422);
    }

    $user  = User::where('email', $request->email)->first();
    $token = Str::random(64);

    // Supprimer l'ancien token s'il existe
    DB::table('password_reset_tokens')
        ->where('email', $request->email)
        ->delete();

    // Insérer le nouveau token
    DB::table('password_reset_tokens')->insert([
        'email'      => $request->email,
        'token'      => Hash::make($token),
        'created_at' => Carbon::now(),
    ]);

$resetUrl = url("/api/reset-password-redirect?token={$token}&email={$request->email}");
    Mail::send('emails.mot_de_passe_oublier', ['url' => $resetUrl, 'user' => $user], function ($mail) use ($user) {
        $mail->to($user->email)
             ->subject('Réinitialisation de votre mot de passe - FontSanté');
    });

    return response()->json([
        'success' => true,
        'message' => 'Un lien de réinitialisation a été envoyé à votre adresse email.',
         'token'   => $token,   // ← ajoute cette ligne
    'email'   => $request->email,
    ]);
}

// ─────────────────────────────────────────────
// ÉTAPE 2 : Réinitialiser le mot de passe
// ─────────────────────────────────────────────
public function resetPassword(Request $request)
{
    $validator = Validator::make($request->all(), [
        'email'    => 'required|email|exists:users,email',
        'token'    => 'required|string',
        'mdp'      => 'required|min:6|confirmed',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'errors'  => $validator->errors()
        ], 422);
    }

    // Chercher le token en base
    $record = DB::table('password_reset_tokens')
        ->where('email', $request->email)
        ->first();

    if (!$record || !Hash::check($request->token, $record->token)) {
        return response()->json([
            'success' => false,
            'message' => 'Token invalide ou expiré.'
        ], 400);
    }

    // Vérifier expiration (60 minutes)
    if (Carbon::parse($record->created_at)->addMinutes(60)->isPast()) {
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();
        return response()->json([
            'success' => false,
            'message' => 'Le lien de réinitialisation a expiré. Veuillez en demander un nouveau.'
        ], 400);
    }

    // Mettre à jour le mot de passe
    User::where('email', $request->email)->update([
        'mdp' => Hash::make($request->mdp),
    ]);

    // Supprimer le token utilisé
    DB::table('password_reset_tokens')->where('email', $request->email)->delete();

    return response()->json([
        'success' => true,
        'message' => 'Mot de passe réinitialisé avec succès. Vous pouvez maintenant vous connecter.'
    ]);
}}




