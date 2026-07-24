<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use App\Models\Document;
use App\Models\Group;
use Illuminate\Support\Facades\Cache;

class HomeController extends Controller
{
    public function index(Request $request, $group = null)
    {
        // 🚀 NOUVEAU : On écoute le paramètre d'URL (?environnement=) ou l'ancien paramètre de route
        $requestedGroup = $request->query('environnement') ?? $group;

        // 🚀 1. LE SAS DE REDIRECTION INVISIBLE
        if ($requestedGroup) {
            
            // 🔍 TRADUCTION : On cherche le vrai groupe en base (soit par sa clé, soit par son slug)
            $groupeModel = \App\Models\Group::where('key', $requestedGroup)->first() 
                        ?? \App\Models\Group::where('slug', $requestedGroup)->first();
            
            if ($groupeModel) {
                // On utilise TOUJOURS la "key" officielle pour la session
                $trueKey = $groupeModel->key; 
                
                session(['active_group_key' => $trueKey]);
                session(['forced_group_key' => $trueKey]);
                session(['admin_forced_group' => $trueKey]);
                session(['simulated_group_id' => $groupeModel->id]); 
                session(['simulated_franchise_id' => $groupeModel->id]);
            } else {
                // Optionnel : on force le retour au global si l'environnement envoyé n'existe pas
                session(['active_group_key' => 'retd']);
            }
            session()->save();

            // MAGIE : On te redirige immédiatement vers la vraie page Home !
            return redirect()->route('home');
        }
        // 🚀 1. LE SAS DE REDIRECTION INVISIBLE (S'exécute quand tu cliques sur le bouton du Projet A)
        if ($group) {
            // On mémorise la demande en session
            session(['active_group_key' => $group]);
            session(['forced_group_key' => $group]);
            
            // On associe le design visuel au bon Modèle (Group pour le Glossaire)
            $groupeModel = \App\Models\Group::where('key', $group)->first() ?? \App\Models\Group::where('slug', $group)->first();
            if ($groupeModel) {
                session(['simulated_group_id' => $groupeModel->id]); 
            } else {
                session()->forget('simulated_group_id');
            }
            session()->save();

            // MAGIE : On te redirige immédiatement vers la vraie page Home !
            return redirect()->route('home');
        }

        // 🛑 2. SÉCURITÉ : Si on arrive sur /home mais qu'on n'est pas connecté
        if (!auth()->check()) {
            session(['url.intended' => route('home')]);
            session()->save();
            return redirect('/login');
        }

        // 3. RÉCUPÉRATION DU GROUPE ACTIF SUR LA PAGE HOME
        $activeGroup = session('forced_group_key') ?? session('active_group_key', 'retd');

        // 🛡️ SÉCURITÉ DESIGN : On s'assure que le visuel reste actif si tu rafraîchis la page
        $groupeModel = \App\Models\Group::where('key', $activeGroup)->first() ?? \App\Models\Group::where('slug', $activeGroup)->first();
        if ($groupeModel) {
            session(['simulated_group_id' => $groupeModel->id]); 
        }
        session()->save();

        // --------------------------------------------------------
        // 🌐 RÉCUPÉRATION DES DONNÉES DE L'API EXTERNE (Anti-Cache)
        // --------------------------------------------------------
        $apiDocuments = collect(); 

        try {
            $response = \Illuminate\Support\Facades\Http::withToken(env('RETD_API_TOKEN'))
                            ->withHeaders([
                                'Cache-Control' => 'no-cache, no-store, must-revalidate',
                                'Pragma' => 'no-cache'
                            ])
                            ->timeout(5)
                            ->get(env('RETD_API_URL'), [
                                '_t' => now()->timestamp 
                            ]);

            if ($response->successful()) {
                $apiDocuments = collect($response->json());
            } else {
                \Illuminate\Support\Facades\Log::warning("L'API a renvoyé une erreur : " . $response->status());
            }

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Erreur de connexion à l'API : " . $e->getMessage());
        }

        $tab = $request->input('tab', 'my_documents'); 
        $search = $request->input('search');
        $selectedTags = (array) $request->input('tags', []);
        
        $groups = session('keycloak_groups', []);
        $isRetd = in_array('retd', $groups);

        $selectedFolder = $request->input('folder');

        // 🕵️‍♂️ SÉCURISATION : Détection fine des rôles Keycloak
        $isLecteur = false;
        $isCreator = false;
        foreach ($groups as $g) {
            $gLower = strtolower($g);
            if (str_contains($gLower, 'lecteur')) {
                $isLecteur = true;
            } elseif (str_contains($gLower, 'glossaire') && !str_contains($gLower, 'lecteur')) {
                $isCreator = true; 
            }
        }

        // 🎯 ONGLET PAR DÉFAUT
        $defaultTab = ($isLecteur && !$isCreator && !$isRetd) ? 'group_documents' : 'my_documents';
        $tab = $request->input('tab', $defaultTab);

        // Clé du groupe pour la requête SQL
        $userGroupKey = $activeGroup;

        // --------------------------------------------------------
        // ÉTAPE 1 : INITIALISATION DE LA REQUÊTE SELON L'ONGLET
        // --------------------------------------------------------
        if ($tab === 'shared') {
            $query = auth()->user()->sharedDocuments()
                ->withoutGlobalScopes()
                ->with('user')
                ->orderBy('documents.updated_at', 'desc'); 
                
        } elseif ($tab === 'all' && $isRetd) {
            $query = \App\Models\Document::withoutGlobalScopes()
                ->with('user')
                ->withCount('sharedWith')
                ->orderBy('documents.updated_at', 'desc');
                
        } elseif ($tab === 'group_documents') {
            $query = \App\Models\Document::withoutGlobalScopes();
            
            if ($userGroupKey) {
                $variants = [$userGroupKey];
                if ($userGroupKey === 'on-air') $variants[] = 'onAir'; 
                $query->whereIn('group_key', $variants);
            } else {
                $query->where('id', 0); 
            }
            
            $query->with('user')
                ->withCount('sharedWith')
                ->orderBy('documents.updated_at', 'desc');

        } else {
            $tab = 'my_documents'; 
            $query = auth()->user()->documents()
                ->withCount('sharedWith')
                ->orderBy('documents.updated_at', 'desc'); 
        }

        // --------------------------------------------------------
        // ÉTAPE 2 : LOGIQUE DE RECHERCHE TEXTUELLE
        // --------------------------------------------------------
        if (!empty($search)) {
            if ($tab === 'all' && empty($selectedFolder)) {
                $query->where(function($q) use ($search) {
                    $q->where('title', 'like', '%' . $search . '%')
                      ->orWhere('group_key', 'like', '%' . $search . '%');
                });
            } else {
                $query->where(function($q) use ($search) {
                    $q->where('title', 'like', '%' . $search . '%');
                });
            }
        }

        // --------------------------------------------------------
        // ÉTAPE 3 : EXTRACTION DES TAGS
        // --------------------------------------------------------
        $tagsQuery = clone $query;
        $allTagsDocs = $tagsQuery->get();

        if ($tab === 'all' && !empty($selectedFolder) && $selectedFolder !== 'ALL_DOCS') {
            $allTagsDocs = $allTagsDocs->filter(function($doc) use ($selectedFolder) {
                $envName = $doc->group_key ?? 'retd';
                return strtoupper(trim($envName)) === strtoupper(trim($selectedFolder));
            });
        }

        $allTagsCollection = collect();
        foreach ($allTagsDocs as $doc) {
            $tags = is_string($doc->tags) ? json_decode($doc->tags, true) : $doc->tags;
            if (is_array($tags) && !empty($tags)) {
                $allTagsCollection = $allTagsCollection->merge($tags);
            }
        }
        
        $allTags = $allTagsCollection->unique()->values()->sort();
        $tagsWithCount = array_count_values($allTagsCollection->toArray());
        arsort($tagsWithCount); 
        
        $maxTags = 10;
        $popularUnselected = collect(array_keys($tagsWithCount))
            ->diff($selectedTags)
            ->take(max(0, $maxTags - count($selectedTags)))
            ->toArray();

        $pillsTags = array_merge($selectedTags, $popularUnselected);

        if (!empty($selectedTags)) {
            $query->where(function($q) use ($selectedTags) {
                foreach ($selectedTags as $t) {
                    $q->orWhereJsonContains('tags', $t);
                }
            });
        }

        // --------------------------------------------------------
        // ÉTAPE 5 : RÉCUPÉRATION FINALE ET RÉPARTITION
        // --------------------------------------------------------
        if ($tab === 'all' && $isRetd) {
            if (!empty($selectedFolder)) {
                if ($selectedFolder === 'ALL_DOCS') {
                    $documentsToDisplay = $query->paginate(12)->withQueryString();
                } 
                else {
                    $allDocs = $query->get();
                    $folderDocs = $allDocs->filter(function($doc) use ($selectedFolder) {
                        $envName = $doc->group_key ?? 'retd';
                        return strtoupper(trim($envName)) === strtoupper(trim($selectedFolder));
                    })->values();

                    $perPage = 12;
                    $page = \Illuminate\Pagination\Paginator::resolveCurrentPage() ?: 1;
                    $items = $folderDocs->slice(($page - 1) * $perPage, $perPage)->values();

                    $documentsToDisplay = new \Illuminate\Pagination\LengthAwarePaginator(
                        $items,
                        $folderDocs->count(),
                        $perPage,
                        $page,
                        [
                            'path' => \Illuminate\Pagination\Paginator::resolveCurrentPath(),
                            'query' => $request->query() 
                        ]
                    );
                }
            } else {
                $groupedDocs = $query->get()->groupBy(function($doc) {
                    return strtoupper($doc->group_key ?? 'RETD');
                });

                $documentsToDisplay = $groupedDocs;
            }
        } else {
            $documentsToDisplay = $query->paginate(12)->withQueryString();
        }
        
        // 🛡️ SÉCURISATION D'ACQUISITION DU MODÈLE GROUPE (Évite l'erreur null pointer dans la vue)
        $currentGroupModel = \App\Models\Group::where('key', $activeGroup)->first()
                             ?? \App\Models\Group::where('slug', $activeGroup)->first()
                             ?? new \App\Models\Group(['name' => ucfirst($activeGroup), 'key' => $activeGroup]);

        return view('home', [
            'documents' => $documentsToDisplay,
            'apiDocuments' => $apiDocuments,
            'tab' => $tab,
            'search' => $search,
            'selectedTags' => $selectedTags,
            'pillsTags' => $pillsTags,
            'allTags' => $allTags,
            'selectedFolder' => $selectedFolder,
            'currentGroup' => $currentGroupModel,
            'activeGroupKey' => $activeGroup,
        ]);
    }

    protected function getActiveGroupKey()
    {
        return session('forced_group_key') ?? session('active_group_key', 'retd');
    }
}