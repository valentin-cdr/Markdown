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
    public function index(Request $request)
    {
        // --------------------------------------------------------
        // 🔄 1. INTERRUPTEUR D'ENVIRONNEMENT (Prise en compte immédiate sans redirection)
        // --------------------------------------------------------
        if ($request->has('group')) {
            $group = $request->input('group');
            
            if (empty($group) || $group === 'retd') {
                session()->forget('active_group_key');
            } else {
                session(['active_group_key' => $group]);
            }
            
            session()->save(); // On force l'enregistrement immédiat en mémoire
        }

        // --------------------------------------------------------
        // 🌐 RÉCUPÉRATION DES DONNÉES DE L'API EXTERNE (Anti-Cache)
        // --------------------------------------------------------
        $apiDocuments = collect(); 

        try {
            // 1. La requête HTTP (avec l'anti-cache)
            $response = \Illuminate\Support\Facades\Http::withToken(env('RETD_API_TOKEN'))
                            ->withHeaders([
                                'Cache-Control' => 'no-cache, no-store, must-revalidate',
                                'Pragma' => 'no-cache'
                            ])
                            ->timeout(5)
                            ->get(env('RETD_API_URL'), [
                                '_t' => now()->timestamp 
                            ]);

            // 2. On vérifie SI la réponse est un succès À L'INTÉRIEUR du try
            if ($response->successful()) {
                
                $apiDocuments = collect($response->json());
                
                // Si tu avais besoin de vider le cache des groupes, ça se passe ici :
                // \Illuminate\Support\Facades\Cache::forget('groups_config');

            } else {
                // Optionnel : Enregistrer si l'API répond, mais avec une erreur (ex: 404, 401)
                \Illuminate\Support\Facades\Log::warning("L'API a renvoyé une erreur : " . $response->status());
            }

        } catch (\Exception $e) {
            // 3. Si le serveur d'en face est totalement planté ou injoignable, on arrive ici
            \Illuminate\Support\Facades\Log::error("Erreur de connexion à l'API : " . $e->getMessage());
        }

        $tab = $request->input('tab', 'my_documents'); 
        $search = $request->input('search');
        $selectedTags = (array) $request->input('tags', []);
        
        $groups = session('keycloak_groups', []);
        $isRetd = in_array('retd', $groups);

        $selectedFolder = $request->input('folder');
        $activeGroup = $this->getActiveGroupKey();

        // 🕵️‍♂️ SÉCURISATION : Détection du rôle Lecteur
        $isLecteur = false;
        foreach ($groups as $g) {
            if (str_contains(strtolower($g), 'lecteur')) {
                $isLecteur = true;
                break;
            }
        }

        // --------------------------------------------------------
        // ÉTAPE 1 : INITIALISATION DE LA REQUÊTE SELON L'ONGLET
        // --------------------------------------------------------
        if ($tab === 'shared') {
            $query = auth()->user()->sharedDocuments()
                ->with('user')
                ->orderBy('documents.updated_at', 'desc'); 
                
        } elseif ($tab === 'all' && $isRetd) {
            // 🌍 REFUGE GLOBAL : Désactivation du Scope Global d'isolation uniquement ici pour l'admin
            $query = \App\Models\Document::withoutGlobalScopes()
                ->with('user')
                ->withCount('sharedWith')
                ->orderBy('documents.updated_at', 'desc');
                
        } else {
            $tab = 'my_documents'; 
            
            if ($isLecteur) {
                // 🚀 FIX : On désactive le Scope Global pour sauter l'isolation de l'utilisateur
                // et on filtre strictement sur le groupe du lecteur
                $query = \App\Models\Document::withoutGlobalScopes();
                
                if (auth()->user()->franchise_id) {
                    $userGroup = \App\Models\Group::find(auth()->user()->franchise_id);
                    if ($userGroup) {
                        // On récupère tous les documents de ce groupe spécifique
                        $query->where('group_key', $userGroup->key);
                    }
                } else {
                    // Sécurité : si le lecteur n'a pas de groupe, il ne voit rien du tout
                    $query->where('id', 0);
                }
                
                $query->with('user')
                    ->withCount('sharedWith')
                    ->orderBy('documents.updated_at', 'desc');
            } else {
                // POUR UN UTILISATEUR NORMAL : Uniquement ses propres documents créés
                $query = auth()->user()->documents()
                    ->withCount('sharedWith')
                    ->orderBy('documents.updated_at', 'desc'); 
            }
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
        // ÉTAPE 3 : EXTRACTION DES TAGS BASÉE SUR L'ENVIRONNEMENT
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

        return view('home', [
            'documents' => $documentsToDisplay,
            'apiDocuments' => $apiDocuments,
            'tab' => $tab,
            'search' => $search,
            'selectedTags' => $selectedTags,
            'pillsTags' => $pillsTags,
            'allTags' => $allTags,
            'selectedFolder' => $selectedFolder,
        ]);
    }

    protected function getActiveGroupKey()
    {
        return session('active_group_key', 'retd');
    }
}