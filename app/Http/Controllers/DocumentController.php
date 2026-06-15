<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DocumentController extends Controller
{
    // Fonction utilitaire privée pour vérifier les droits d'édition
    private function canEditDocument(Document $document)
    {
        if ($document->user_id === Auth::id()) return true; // Propriétaire
        
        // Invité avec les droits d'édition
        return $document->sharedWith()
                        ->where('user_id', Auth::id())
                        ->wherePivot('can_edit', true)
                        ->exists();
    }

    public function create() {
        $user = Auth::user();
        $groups = session('keycloak_groups', []);
        $document = null;
        
        // 🚀 CALCUL DES TAGS (Complet, Top 10, et Sélectionnés)
        $allTagsCollection = \App\Models\Document::pluck('tags')->filter()->flatten();
        $allTags = $allTagsCollection->unique()->values()->sort();

        $tagsWithCount = array_count_values($allTagsCollection->toArray());
        arsort($tagsWithCount);
        $top10Tags = array_slice(array_keys($tagsWithCount), 0, 10);

        $selectedTags = old('tags', []);
        $pillsTags = collect(array_merge($selectedTags, $top10Tags))->unique()->toArray();

        return view('documents.editor', compact('user', 'groups', 'document', 'allTags', 'pillsTags', 'selectedTags'));
    }

    public function edit(Document $document) {
        $document = Document::findOrFail($document->id);

        // 🛡️ SÉCURITÉ : Si l'utilisateur n'est pas le propriétaire 
        // ET qu'il n'a pas reçu le droit d'édition (via la table pivot)
        $isOwner = $document->user_id === auth()->id();
        $canEditShared = $document->sharedWith()->where('user_id', auth()->id())->where('can_edit', true)->exists();

        if (!$isOwner && !$canEditShared) {
            // 🚀 Au lieu d'un abort(403), on redirige proprement avec un message flash !
            return redirect()->route('home')->with('error', "Vous n'avez pas l'autorisation de modifier ce document.");
        }
        
        $user = Auth::user();
        $groups = session('keycloak_groups', []);
        
        // 🚀 CALCUL DES TAGS
        $allTagsCollection = \App\Models\Document::pluck('tags')->filter()->flatten();
        $allTags = $allTagsCollection->unique()->values()->sort();

        $tagsWithCount = array_count_values($allTagsCollection->toArray());
        arsort($tagsWithCount);
        $top10Tags = array_slice(array_keys($tagsWithCount), 0, 10);

        // On récupère les tags existants du document
        $selectedTags = old('tags', $document->tags ?? []);
        $pillsTags = collect(array_merge($selectedTags, $top10Tags))->unique()->toArray();

        return view('documents.editor', compact('user', 'groups', 'document', 'allTags', 'pillsTags', 'selectedTags'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'tags' => 'nullable|array' // 🚀 Changé de string à array
        ]);

        // Nettoyer les tags envoyés (retirer les espaces en trop et les tags vides)
        $tagsArray = array_filter(array_map('trim', $request->tags ?? []));

        $document = $request->user()->documents()->create([
            'title' => $request->title,
            'content' => $request->content,
            'tags' => empty($tagsArray) ? null : array_values($tagsArray)
        ]);

        return redirect()->route('home')
                         ->with('success', 'Document créé avec succès !');
    }

    public function update(Request $request, Document $document)
    {
        // 1. VÉRIFICATION DES CONFLITS (Verrouillage Optimiste)
        if ($request->has('original_updated_at')) {
            $dbUpdatedAt = $document->updated_at->format('Y-m-d H:i:s');
            $submittedUpdatedAt = $request->input('original_updated_at');

            if ($dbUpdatedAt !== $submittedUpdatedAt) {
                return back()
                    ->withInput()
                    ->with('error', '⚠️ Attention : Ce document a été modifié par une autre personne pendant que vous l\'éditiez. Veuillez copier votre texte pour ne pas le perdre, puis rafraîchir la page.');
            }
        }

        // 2. ENREGISTREMENT NORMAL
        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'tags' => 'nullable|array' // 🚀 Changé de string à array
        ]);

        $tagsArray = array_filter(array_map('trim', $request->tags ?? []));

        $document->update([
            'title' => $request->title,
            'content' => $request->content,
            'tags' => empty($tagsArray) ? null : array_values($tagsArray),
        ]);
        // ... tout ton code d'enregistrement (sauvegarde du texte, des tags, etc.) ...
    
        $document->save();

        // 🚀 REDIRECTION INTELLIGENTE SELON LE PROPRIÉTAIRE
        $tab = ($document->user_id === auth()->id()) ? 'my_documents' : 'shared';

        return redirect()->route('home', ['tab' => $tab])
                        ->with('success', 'Document mis à jour avec succès.');
    }

    public function destroy(Document $document) {
        if ($document->user_id !== Auth::id()) abort(403, 'Seul le propriétaire peut supprimer ce fichier.');
        $document->delete();
        return redirect()->route('home')->with('success', 'Document supprimé avec succès !');
    }

    public function show(Document $document) {

        $document = Document::findOrFail($document->id);

        // 🛡️ SÉCURITÉ : L'utilisateur doit être le propriétaire 
        // OU le document doit lui avoir été partagé (présent dans la table pivot)
        $isOwner = $document->user_id === auth()->id();
        $isSharedWithMe = $document->sharedWith()->where('user_id', auth()->id())->exists();

        // 🚀 Cas particulier : si tu as un rôle global ou un groupe spécifique (ex: R&D) 
        // qui a le droit de TOUT voir dans l'onglet "All", tu peux ajouter cette condition :
        $isRetdGroup = in_array('retd', session('keycloak_groups', []));

        if (!$isOwner && !$isSharedWithMe && !$isRetdGroup) {
            // Redirection vers la bibliothèque avec le message d'erreur qu'on a configuré sur l'accueil
            return redirect()->route('home')->with('error', "Vous n'avez pas l'autorisation d'accéder à ce document.");
        }
        $user = Auth::user();
        $groups = session('keycloak_groups', []);
        
        $isRetd = in_array('retd', $groups);

        if (!$isRetd && $document->user_id !== $user->id && !$document->sharedWith->contains($user->id)) {
            abort(403, 'Vous n\'avez pas accès à ce document.');
        }
        
        return view('documents.show', compact('user', 'groups', 'document'));
    }

    public function shareForm(Document $document) {
        if ($document->user_id !== Auth::id()) abort(403, 'Seul le propriétaire peut partager.');
        $user = Auth::user();
        $groups = session('keycloak_groups', []);
        return view('documents.share', compact('user', 'groups', 'document'));
    }

    public function share(Request $request, Document $document) {
        if ($document->user_id !== Auth::id()) abort(403);

        $request->validate(['username' => 'required|string']);
        $userToShare = User::where('username', $request->username)->first();

        if(!$userToShare) return back()->withErrors(['username' => "Aucun utilisateur trouvé avec cet identifiant."]);
        if($userToShare->id === Auth::id()) return back()->withErrors(['username' => "Vous ne pouvez pas partager avec vous-même."]);

        $canEdit = $request->has('can_edit');
        $document->sharedWith()->syncWithoutDetaching([
            $userToShare->id => ['can_edit' => $canEdit]
        ]);
        
        return back()->with('success', 'Document partagé avec succès avec ' . $userToShare->name);
    }

    public function updateShare(Request $request, Document $document, User $user) {
        if ($document->user_id !== Auth::id()) abort(403);
        
        $document->sharedWith()->updateExistingPivot($user->id, [
            'can_edit' => $request->can_edit == '1'
        ]);

        return back()->with('success', 'Permissions mises à jour pour ' . $user->name);
    }

    public function unshare(Document $document, User $user) {
        if ($document->user_id !== Auth::id()) abort(403);
        $document->sharedWith()->detach($user->id);
        return back()->with('success', 'Accès révoqué pour ' . $user->name);
    }
}