<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DocumentController extends Controller
{
    // 🚀 1. SÉCURITÉ MISE À JOUR : Le groupe 'retd' donne un passe-droit total d'édition
    private function canEditDocument(Document $document)
    {
        if ($document->user_id === Auth::id()) return true; // Propriétaire
        
        if (in_array('retd', session('keycloak_groups', []))) return true; // Membre du groupe 'retd' (Admin)
        
        // Invité avec les droits d'édition accordés individuellement
        return $document->sharedWith()
                        ->where('user_id', Auth::id())
                        ->wherePivot('can_edit', true)
                        ->exists();
    }

    public function create() {
        $user = Auth::user();
        $groups = session('keycloak_groups', []);
        $document = null;
        
        // CALCUL DES TAGS (Complet, Top 10, et Sélectionnés)
        $allTagsCollection = \App\Models\Document::pluck('tags')->filter()->flatten();
        $allTags = $allTagsCollection->unique()->values()->sort();

        $tagsWithCount = array_count_values($allTagsCollection->toArray());
        arsort($tagsWithCount);
        $top10Tags = array_slice(array_keys($tagsWithCount), 0, 10);

        $selectedTags = old('tags', []);
        $pillsTags = collect(array_merge($selectedTags, $top10Tags))->unique()->toArray();

        return view('documents.editor', compact('user', 'groups', 'document', 'allTags', 'pillsTags', 'selectedTags'));
    }

    public function edit($id)
    {
        // 1. On vérifie les groupes de l'utilisateur
        $userGroups = (array) session('keycloak_groups', []);
        $isAdmin = in_array('retd', $userGroups);

        // 2. On va chercher le document (SANS FILTRE GLOBAL pour éviter la 404)
        $document = \App\Models\Document::withoutGlobalScopes()->findOrFail($id);

        // 3. Sécurité : On vérifie les droits SANS MODIFIER LA SESSION
        if (!$isAdmin) {
            // 🔒 Les NON-ADMINS ne peuvent éditer que si le doc appartient à un de leurs groupes
            if (!in_array($document->group_key, $userGroups)) {
                abort(404);
            }
        }

        // --- GESTION DES TAGS (Pour éviter l'erreur Undefined variable) ---
        $allTagsCollection = \App\Models\Document::pluck('tags')->filter()->flatten();
        $allTags = $allTagsCollection->unique()->values()->sort();

        $tagsWithCount = array_count_values($allTagsCollection->toArray());
        arsort($tagsWithCount);
        $top10Tags = array_slice(array_keys($tagsWithCount), 0, 10);

        // Récupérer les tags du document actuel (ou vide si aucun)
        $selectedTags = old('tags', $document->tags ?? []); 
        $pillsTags = collect(array_merge($selectedTags, $top10Tags))->unique()->toArray();
        // --- FIN GESTION DES TAGS ---

        // 4. APPEL DE LA VUE : Ton environnement n'a pas bougé !
        return view('documents.editor', compact('document', 'allTags', 'pillsTags', 'selectedTags'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'tags' => 'nullable|array'
        ]);

        $tagsArray = array_filter(array_map('trim', $request->tags ?? []));

        // 🔒 Le document prend STRICTEMENT le group_key de l'environnement en cours d'utilisation
        $document = $request->user()->documents()->create([
            'title' => $request->title,
            'content' => $request->content,
            'tags' => empty($tagsArray) ? null : array_values($tagsArray),
            'group_key' => $this->getActiveGroupKey() 
        ]);

        return redirect()->route('home')
                         ->with('success', 'Document créé avec succès !');
    }

    public function update(Request $request, $id)
    {
        $document = \App\Models\Document::withoutGlobalScopes()->findOrFail($id);

        if (!$this->canEditDocument($document)) {
            return redirect()->route('home')->with('error', "Vous n'avez pas l'autorisation de modifier ce document.");
        }

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
            'tags' => 'nullable|array'
        ]);

        $tagsArray = array_filter(array_map('trim', $request->tags ?? []));

        $document->update([
            'title' => $request->title,
            'content' => $request->content,
            'tags' => empty($tagsArray) ? null : array_values($tagsArray),
        ]);
    
        $document->save();

        $tab = $request->input('tab');
        if (empty($tab)) {
            if (in_array('retd', session('keycloak_groups', [])) && $document->user_id !== auth()->id()) {
                $tab = 'all';
            } else {
                $tab = ($document->user_id === auth()->id()) ? 'my_documents' : 'shared';
            }
        }

        $redirectParams = ['tab' => $tab];

        $folder = $request->input('folder');
        if (!empty($folder)) {
            $redirectParams['folder'] = $folder;
        }

        return redirect()->route('home', $redirectParams)
                        ->with('success', 'Document mis à jour avec succès.');
    }

    public function destroy($id) { 
        
        // 🚀 2. On cherche le document en forçant Laravel à ignorer TOUS les filtres (les Global Scopes)
        $document = \App\Models\Document::withoutGlobalScopes()->findOrFail($id);

        // 3. Le reste de ton code ne change pas !
        $isAdmin = in_array('retd', session('keycloak_groups', []));
        $isOwner = $document->user_id === Auth::id();

        if (!$isOwner && !$isAdmin) {
            abort(403, 'Seul le propriétaire ou un administrateur peut supprimer ce fichier.');
        }

        $document->delete();
        
        return redirect()->route('home')->with('success', 'Document supprimé avec succès !');
    }

    public function show($id) 
    {
        // 1. On récupère le document en ignorant les filtres de groupe (Global Scopes)
        $document = \App\Models\Document::withoutGlobalScopes()->findOrFail($id);

        // 2. Ta logique de sécurité existante
        $isOwner = $document->user_id === auth()->id();
        $isSharedWithMe = $document->sharedWith()->where('user_id', auth()->id())->exists();
        
        $groups = session('keycloak_groups', []);
        $isRetdGroup = in_array('retd', $groups);

        // 🚀 3. NOUVEAU : Autorisation pour les profils Lecteurs du même groupe
        $isLecteurOfSameGroup = false;
        
        // A. On détecte si l'utilisateur connecté est un lecteur
        $isLecteur = false;
        foreach ($groups as $g) {
            if (str_contains(strtolower($g), 'lecteur')) {
                $isLecteur = true;
                break;
            }
        }

        // B. Si c'est un lecteur, on vérifie qu'il fait partie du même groupe que le document
        if ($isLecteur && auth()->user()->franchise_id) {
            $userGroup = \App\Models\Group::find(auth()->user()->franchise_id);
            if ($userGroup && $document->group_key === $userGroup->key) {
                $isLecteurOfSameGroup = true;
            }
        }

        // 4. Le couperet mis à jour avec le passe-partout lecteur
        if (!$isOwner && !$isSharedWithMe && !$isRetdGroup && !$isLecteurOfSameGroup) {
            return redirect()->route('home')->with('error', "Vous n'avez pas l'autorisation d'accéder à ce document.");
        }

        // 5. Préparation de la vue
        $user = \Illuminate\Support\Facades\Auth::user();
        
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

    /**
     * 🚀 Récupère proprement la clé de groupe active de la session
     */
    protected function getActiveGroupKey()
    {
        return session('active_group_key', 'retd');
    }
}