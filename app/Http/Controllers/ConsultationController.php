<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Consultation;
use App\Models\DossierMedical;
use App\Models\RendezVous;
use App\Events\ConsultationVideoEvent;
use Firebase\JWT\JWT;
class ConsultationController extends Controller
{
    
    

    public function AjouterConsultation(Request $req)
    {
        $validated = $req->validate([
            'dossier_medical_id' => 'required|integer|exists:dossier_medical,id',
            'date_consultation'  => 'required|date',
            'diagnostique'       => 'nullable|string',
            'traitement'         => 'nullable|string',
            'type'               => 'nullable|in:presentiel,video',
        ]);

        $dossier = DossierMedical::find($validated['dossier_medical_id']);
        if (!$dossier) {
            return response()->json(['message' => 'Dossier médical introuvable'], 404);
        }

        $rdv = RendezVous::where('id_patient', $dossier->patient_id)
            ->where('id_medecin', $dossier->medecin_id)
            ->whereDate('date', $validated['date_consultation'])
            ->where('etat', 'confirmé')
            ->first();

        if (!$rdv) {
            return response()->json([
                'message' => 'Aucun rendez-vous confirmé pour ce patient à cette date'
            ], 409);
        }

        // Générer room_id si consultation vidéo
        if (isset($validated['type']) && $validated['type'] === 'video') {
            $validated['room_id']      = 'room-' . uniqid() . '-' . rand(1000, 9999);
            $validated['statut_video'] = 'en_attente';
        }

        $consultation = Consultation::create($validated);
        $consultation->load(['dossier', 'dossier.patient', 'dossier.medecin']);

        return response()->json([
            'message'      => 'Consultation ajoutée avec succès',
            'consultation' => $consultation
        ], 201);
    }

    public function ModifierConsultation(Request $req, $id)
    {
        $validated = $req->validate([
            'dossier_medical_id' => 'required|integer|exists:dossier_medical,id',
            'date_consultation'  => 'required|date',
            'diagnostique'       => 'nullable|string',
            'traitement'         => 'nullable|string',
        ]);

        $consultation = Consultation::find($id);
        if (!$consultation) {
            return response()->json(['message' => 'Consultation introuvable'], 404);
        }

        $dossier = DossierMedical::find($validated['dossier_medical_id']);
        if (!$dossier) {
            return response()->json(['message' => 'Dossier médical introuvable'], 404);
        }

        $rdv = RendezVous::where('patient_id', $dossier->patient_id)
            ->where('medecin_id', $dossier->medecin_id)
            ->whereDate('date', $validated['date_consultation'])
            ->where('etat', 'confirmé')
            ->first();

        if (!$rdv) {
            return response()->json([
                'message' => 'Aucun rendez-vous confirmé pour ce patient à cette date'
            ], 409);
        }

        $consultation->update($validated);
        $consultation->load(['dossier', 'dossier.patient', 'dossier.medecin']);

        return response()->json([
            'message'      => 'Consultation modifiée avec succès',
            'consultation' => $consultation
        ]);
    }

    public function SupprimerConsultation($id)
    {
        $consultation = Consultation::find($id);
        if (!$consultation) {
            return response()->json(['message' => 'Consultation introuvable'], 404);
        }

        $consultation->delete();
        return response()->json(['message' => 'Consultation supprimée avec succès']);
    }

    public function ConsultationsParPatient($patient_id)
    {
        $consultations = Consultation::whereHas('dossier', fn($q) => $q->where('patient_id', $patient_id))
            ->with(['dossier', 'dossier.medecin'])
            ->get();

        if ($consultations->isEmpty()) {
            return response()->json(['message' => 'Aucune consultation trouvée pour ce patient'], 404);
        }

        return response()->json(['consultations' => $consultations, 'total' => $consultations->count()]);
    }

    public function ConsultationsParMedecin($medecin_id)
    {
        $consultations = Consultation::whereHas('dossier', fn($q) => $q->where('medecin_id', $medecin_id))
            ->with(['dossier', 'dossier.patient'])
            ->get();

        if ($consultations->isEmpty()) {
            return response()->json(['message' => 'Aucune consultation trouvée pour ce médecin'], 404);
        }

        return response()->json(['consultations' => $consultations, 'total' => $consultations->count()]);
    }

    public function ConsultationsParDossier($dossier_id)
    {
        $consultations = Consultation::where('dossier_medical_id', $dossier_id)
            ->with(['dossier'])
            ->get();

        return response()->json(['consultations' => $consultations, 'total' => $consultations->count()]);
    }

    //  VIDÉO — Démarrer une consultation vidéo

    public function DemarrerVideo(Request $req, $id)
    {
        $consultation = Consultation::find($id);
        if (!$consultation) {
            return response()->json(['message' => 'Consultation introuvable'], 404);
        }

        // Générer room_id si pas encore fait
        if (!$consultation->room_id) {
            $consultation->room_id = 'room-' . uniqid() . '-' . rand(1000, 9999);
        }

        $consultation->statut_video = 'en_cours';
        $consultation->debut_video  = now();
        $consultation->type         = 'video';
        $consultation->save();

        //  Notifier via Reverb que la consultation démarre
        broadcast(new ConsultationVideoEvent(
            $consultation->room_id,
            'start',
            ['consultationId' => $consultation->id],
            auth('api')->id()
        ));

        return response()->json([
            'message'      => 'Consultation vidéo démarrée',
            'room_id'      => $consultation->room_id,
            'consultation' => $consultation,
        ]);
    }

    
    public function RejoindreVideo(Request $req, $id)
    {
        $consultation = Consultation::find($id);
        if (!$consultation) {
            return response()->json(['message' => 'Consultation introuvable'], 404);
        }

        if (!$consultation->room_id) {
            return response()->json(['message' => 'La consultation vidéo n\'a pas encore démarré'], 400);
        }

        //  Notifier que le patient a rejoint
        broadcast(new ConsultationVideoEvent(
            $consultation->room_id,
            'join',
            ['consultationId' => $consultation->id],
            auth('api')->id()
        ));

        return response()->json([
            'message' => 'Vous avez rejoint la consultation',
            'room_id' => $consultation->room_id,
        ]);
    }

  
    public function Signal(Request $req, $id)
    {
        $req->validate([
            'type' => 'required|in:offer,answer,ice-candidate',
            'data' => 'required',
        ]);

        $consultation = Consultation::find($id);
        if (!$consultation || !$consultation->room_id) {
            return response()->json(['message' => 'Room introuvable'], 404);
        }

        //  Relayer le signal WebRTC via Reverb
        broadcast(new ConsultationVideoEvent(
            $consultation->room_id,
            $req->type,
            $req->data,
            auth('api')->id()
        ));

        return response()->json(['message' => 'Signal envoyé']);
    }

   
    public function TerminerVideo(Request $req, $id)
    {
        $consultation = Consultation::find($id);
        if (!$consultation) {
            return response()->json(['message' => 'Consultation introuvable'], 404);
        }

        $consultation->statut_video = 'terminee';
        $consultation->fin_video    = now();
        $consultation->save();

        //  Notifier la fin
        broadcast(new ConsultationVideoEvent(
            $consultation->room_id,
            'end',
            ['consultationId' => $consultation->id],
            auth('api')->id()
        ));

        return response()->json([
            'message'      => 'Consultation vidéo terminée',
            'consultation' => $consultation,
        ]);
    }



public function GenererTokenJaaS(Request $req, $id)
{
    $consultation = Consultation::find($id);
    if (!$consultation) {
        return response()->json(['message' => 'Consultation introuvable'], 404);
    }

    $user = auth('api')->user();
    if (!$user) {
        return response()->json(['message' => 'Non authentifié'], 401);
    }

    $keyPath = storage_path('app/jaas_private_key.pk8');
    if (!file_exists($keyPath)) {
        return response()->json(['message' => 'Clé privée JaaS introuvable'], 500);
    }

    $appId = env('JAAS_APP_ID');
    $keyId = env('JAAS_KEY_ID');

    if (!$keyId) {
        return response()->json(['message' => 'JAAS_KEY_ID manquant dans .env'], 500);
    }

    $payload = [
        'iss'  => 'chat',
        'aud'  => 'jitsi',
        'iat'  => time(),
        'exp'  => time() + 7200,
        'nbf'  => time() - 10,
        'room' => $consultation->room_id,
        'sub'  => $appId,
        'context' => [
            'user' => [
                'id'        => (string) $user->id,
                'name'      => $user->prenom . ' ' . $user->nom,
                'email'     => $user->email,
                'avatar'    => '',
                'moderator' => $req->role === 'medecin' ? 'true' : 'false',
            ],
            'features' => [
                'livestreaming' => 'false',
                'recording'     => 'false',
                'transcription' => 'false',
                'outbound-call' => 'false',
            ],
        ],
    ];

    $privateKey = file_get_contents($keyPath);
    $token = \Firebase\JWT\JWT::encode($payload, $privateKey, 'RS256', $keyId);

    return response()->json([
        'token'   => $token,
        'room_id' => $consultation->room_id,
        'app_id'  => $appId,
    ]);
}
}