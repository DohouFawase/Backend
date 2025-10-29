<?php

namespace App\Http\Controllers\v1\Property\PropertyContact;

use App\Http\Controllers\Controller;
use App\Http\Requests\Anounce\ContactPropertyFormRequest;
use App\Mail\PropertyContactMail;
use App\Models\AdVersion;
use App\Models\PropertyContact;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Throwable;
class PropertyContactController extends Controller
{
    //
      /**
     * Envoyer un message de contact pour une annonce (PUBLIC).
     * 
     * @param ContactPropertyFormRequest $request
     * @param string $adVersionId
     * @return JsonResponse
     */
    public function store(ContactPropertyFormRequest $request, string $adVersionId): JsonResponse
    {
        try {
            // Récupérer l'annonce avec le propriétaire
            $adVersion = AdVersion::with(['ad.user'])->findOrFail($adVersionId);
            
            // Vérifier que l'annonce est validée (sécurité)
            if ($adVersion->status !== 'validated') {
                return api_response(false, 'Cette annonce n\'est pas disponible.', null, 404);
            }
            
            $owner = $adVersion->ad->user;
            
            // Créer le contact en base
            $contact = PropertyContact::create([
                'ad_version_id' => $adVersionId,
                'owner_id' => $owner->id,
                'visitor_name' => $request->visitor_name,
                'visitor_email' => $request->visitor_email,
                'visitor_phone' => $request->visitor_phone,
                'message' => $request->message,
                'status' => 'new',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
            
            // Envoyer l'email au propriétaire
            Mail::to($owner->email)->send(new PropertyContactMail($contact, $adVersion));
            
            return api_response(true, 'Votre message a été envoyé avec succès ! Le propriétaire vous contactera bientôt.', [
                'contact_id' => $contact->id
            ], 201);
            
        } catch (Throwable $e) {
            return api_response(false, 'Une erreur est survenue lors de l\'envoi du message.', [
                'error_detail' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Liste des messages reçus pour le propriétaire connecté.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $userId = auth()->id();
            
            $request->validate([
                'status' => 'nullable|in:new,read,replied,archived',
                'per_page' => 'nullable|integer|min:1|max:100',
            ]);
            
            $perPage = $request->input('per_page', 15);
            $status = $request->input('status');
            
            $query = PropertyContact::with(['adVersion.propertyType', 'adVersion.images'])
                ->forOwner($userId)
                ->orderBy('created_at', 'desc');
            
            if ($status) {
                $query->where('status', $status);
            }
            
            $contacts = $query->paginate($perPage);
            
            // Compter les messages non lus
            $unreadCount = PropertyContact::forOwner($userId)->unread()->count();
            
            return api_response(true, 'Messages récupérés avec succès.', [
                'contacts' => $contacts,
                'unread_count' => $unreadCount
            ], 200);
            
        } catch (Throwable $e) {
            return api_response(false, 'Erreur lors de la récupération des messages.', [
                'error_detail' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Afficher un message spécifique.
     * 
     * @param string $contactId
     * @return JsonResponse
     */
    public function show(string $contactId): JsonResponse
    {
        try {
            $userId = auth()->id();
            
            $contact = PropertyContact::with(['adVersion.propertyType', 'adVersion.images'])
                ->forOwner($userId)
                ->findOrFail($contactId);
            
            // Marquer comme lu automatiquement
            $contact->markAsRead();
            
            return api_response(true, 'Détails du message récupérés avec succès.', [
                'contact' => $contact
            ], 200);
            
        } catch (Throwable $e) {
            return api_response(false, 'Message non trouvé ou accès refusé.', [
                'error_detail' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Marquer un message comme répondu.
     * 
     * @param string $contactId
     * @return JsonResponse
     */
    public function markAsReplied(string $contactId): JsonResponse
    {
        try {
            $userId = auth()->id();
            
            $contact = PropertyContact::forOwner($userId)->findOrFail($contactId);
            $contact->markAsReplied();
            
            return api_response(true, 'Message marqué comme répondu.', [
                'contact' => $contact
            ], 200);
            
        } catch (Throwable $e) {
            return api_response(false, 'Erreur lors de la mise à jour du message.', [
                'error_detail' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Archiver un message.
     * 
     * @param string $contactId
     * @return JsonResponse
     */
    public function archive(string $contactId): JsonResponse
    {
        try {
            $userId = auth()->id();
            
            $contact = PropertyContact::forOwner($userId)->findOrFail($contactId);
            $contact->archive();
            
            return api_response(true, 'Message archivé avec succès.', null, 200);
            
        } catch (Throwable $e) {
            return api_response(false, 'Erreur lors de l\'archivage du message.', [
                'error_detail' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Supprimer un message.
     * 
     * @param string $contactId
     * @return JsonResponse
     */
    public function destroy(string $contactId): JsonResponse
    {
        try {
            $userId = auth()->id();
            
            $contact = PropertyContact::forOwner($userId)->findOrFail($contactId);
            $contact->delete();
            
            return api_response(true, 'Message supprimé avec succès.', null, 200);
            
        } catch (Throwable $e) {
            return api_response(false, 'Erreur lors de la suppression du message.', [
                'error_detail' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Statistiques des messages pour le dashboard.
     * 
     * @return JsonResponse
     */
    public function stats(): JsonResponse
    {
        try {
            $userId = auth()->id();
            
            $stats = [
                'total' => PropertyContact::forOwner($userId)->count(),
                'new' => PropertyContact::forOwner($userId)->where('status', 'new')->count(),
                'read' => PropertyContact::forOwner($userId)->where('status', 'read')->count(),
                'replied' => PropertyContact::forOwner($userId)->where('status', 'replied')->count(),
                'archived' => PropertyContact::forOwner($userId)->where('status', 'archived')->count(),
                'today' => PropertyContact::forOwner($userId)
                    ->whereDate('created_at', today())
                    ->count(),
                'this_week' => PropertyContact::forOwner($userId)
                    ->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])
                    ->count(),
            ];
            
            return api_response(true, 'Statistiques récupérées avec succès.', $stats, 200);
            
        } catch (Throwable $e) {
            return api_response(false, 'Erreur lors de la récupération des statistiques.', [
                'error_detail' => $e->getMessage()
            ], 500);
        }
    }
}
