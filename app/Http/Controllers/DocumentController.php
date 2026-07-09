<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DocumentController extends Controller
{
    // 🚀 1. SÉCURITÉ MISE À JOUR : Le groupe 'retd' donne un passe-droit total d'édition
    // 🚀 SÉCURITÉ MISE À JOUR : Édition collaborative pour les Créateurs d'un même groupe
    private function canEditDocument(Document $document)
    {
        // 1. Le propriétaire a toujours le droit
        if ($document->user_id === Auth::id()) return true; 
        
        $groups = session('keycloak_groups', []);
        
        // 2. L'Admin global a toujours le droit
        if (in_array('retd', $groups)) return true; 
        
        // 3. 🚀 NOUVEAU : Les Créateurs peuvent modifier tous les documents de LEUR franchise
        $isCreator = false;
        foreach ($groups as $g) {
            $gLower = strtolower($g);
            // S'il a le tag glossaire mais PAS le tag lecteur, c'est un créateur
            if (str_contains($gLower, 'glossaire') && !str_contains($gLower, 'lecteur')) {
                $isCreator = true;
                break;
            }
        }
        
        if ($isCreator && Auth::check() && Auth::user()->franchise_id) {
            $userGroup = \App\Models\Group::find(Auth::user()->franchise_id);
            // Si la franchise du créateur correspond à la franchise du document : BINGO !
            if ($userGroup && $document->group_key === $userGroup->key) {
                return true; 
            }
        }

        // 4. Invité avec les droits d'édition accordés individuellement
        return $document->sharedWith()
                        ->where('user_id', Auth::id())
                        ->wherePivot('can_edit', true)
                        ->exists();
    }

    public function create() {
        $user = Auth::user();
        $groups = session('keycloak_groups', []);
        $isAdmin = in_array('retd', $groups);
        $document = null;
        
        // 🚀 CORRECTION : Si on est Admin, on désactive le filtre pour récupérer TOUS les tags de la base
        $query = $isAdmin ? \App\Models\Document::withoutGlobalScopes() : \App\Models\Document::query();
        $allTagsCollection = $query->pluck('tags')->filter()->flatten();
        
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
        // 1. On va chercher le document (SANS FILTRE GLOBAL)
        $document = \App\Models\Document::withoutGlobalScopes()->findOrFail($id);

        // 2. 🚀 CORRECTION : On utilise ta propre fonction centralisée pour vérifier les droits !
        if (!$this->canEditDocument($document)) {
            abort(403, "Vous n'avez pas l'autorisation de modifier ce document.");
        }

        // --- GESTION DES TAGS ---
        $isAdmin = in_array('retd', session('keycloak_groups', []));
        
        $query = $isAdmin ? \App\Models\Document::withoutGlobalScopes() : \App\Models\Document::query();
        $allTagsCollection = $query->pluck('tags')->filter()->flatten();
        
        $allTags = $allTagsCollection->unique()->values()->sort();

        $tagsWithCount = array_count_values($allTagsCollection->toArray());
        arsort($tagsWithCount);
        $top10Tags = array_slice(array_keys($tagsWithCount), 0, 10);

        // Récupérer les tags du document actuel (ou vide si aucun)
        $selectedTags = old('tags', $document->tags ?? []); 
        $pillsTags = collect(array_merge($selectedTags, $top10Tags))->unique()->toArray();
        // --- FIN GESTION DES TAGS ---

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
        // 1. On récupère le document en ignorant les filtres de groupe
        $document = \App\Models\Document::withoutGlobalScopes()->findOrFail($id);

        // 2. Ta logique de sécurité existante
        $isOwner = $document->user_id === auth()->id();
        $isSharedWithMe = $document->sharedWith()->where('user_id', auth()->id())->exists();
        
        $groups = session('keycloak_groups', []);
        $isRetdGroup = in_array('retd', $groups);

        // 🚀 3. CORRECTION : Autorisation globale pour TOUS les membres de la franchise (Créateurs ET Lecteurs)
        $isMemberOfSameGroup = false;
        
        if (auth()->check() && auth()->user()->franchise_id) {
            $userGroup = \App\Models\Group::find(auth()->user()->franchise_id);
            if ($userGroup && $document->group_key === $userGroup->key) {
                $isMemberOfSameGroup = true;
            }
        }

        // 4. Le couperet mis à jour avec le passe-partout de la franchise
        if (!$isOwner && !$isSharedWithMe && !$isRetdGroup && !$isMemberOfSameGroup) {
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