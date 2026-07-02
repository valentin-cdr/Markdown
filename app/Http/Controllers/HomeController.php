<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Document;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->input('tab', 'my_documents'); 
        $search = $request->input('search');
        $selectedTags = (array) $request->input('tags', []);
        
        $groups = session('keycloak_groups', []);
        $isRetd = in_array('retd', $groups);

        $selectedFolder = $request->input('folder');

        // On récupère l'environnement sélectionné pour "Mes documents" et "Partagés"
        $activeGroup = $this->getActiveGroupKey();

        // --------------------------------------------------------
        // ÉTAPE 1 : INITIALISATION DE LA REQUÊTE SELON L'ONGLET
        // --------------------------------------------------------
        if ($tab === 'shared') {
            // 🔒 Cloisonné : uniquement les partagés de l'environnement actif
            $query = auth()->user()->sharedDocuments()
                ->where('documents.group_key', $activeGroup) 
                ->with('user')
                ->orderBy('documents.updated_at', 'desc'); 
                
        } elseif ($tab === 'all' && $isRetd) {
            // 🌍 REFUGE GLOBAL : Aucun filtre group_key ici ! 
            // On charge TOUS les documents de la base de données pour le regroupement par dossier.
            $query = \App\Models\Document::with('user')
                ->withCount('sharedWith')
                ->orderBy('documents.updated_at', 'desc');
                
        } else {
            $tab = 'my_documents'; 
            // 🔒 Cloisonné : uniquement mes documents de l'environnement actif
            $query = auth()->user()->documents()
                ->where('documents.group_key', $activeGroup) 
                ->withCount('sharedWith')
                ->orderBy('documents.updated_at', 'desc'); 
        }

        // --------------------------------------------------------
        // ÉTAPE 2 : LOGIQUE DE RECHERCHE TEXTUELLE
        // --------------------------------------------------------
        if (!empty($search)) {
            // 📄 On n'applique le filtre SQL QUE si on n'est PAS à la racine de l'onglet Global
            // Car à la racine, on veut filtrer le NOM des dossiers après le regroupement !
            if (!($tab === 'all' && empty($selectedFolder))) {
                $query->where(function($q) use ($search) {
                    $q->where('title', 'like', '%' . $search . '%');
                });
            }
        }

        // --------------------------------------------------------
        // ÉTAPE 3 : EXTRACTION DES TAGS (AVANT DE LES FILTRER)
        // --------------------------------------------------------
        $tagsQuery = clone $query;
        $allTagsDocs = $tagsQuery->get();

        // 1. On filtre les documents selon le dossier (si on est dedans)
        if ($tab === 'all' && !empty($selectedFolder) && $selectedFolder !== 'ALL_DOCS') {
            $allTagsDocs = $allTagsDocs->filter(function($doc) use ($selectedFolder) {
                $groupName = $doc->user->group_name ?? 'GÉNÉRAL / SANS GROUPE';
                return strtoupper(trim($groupName)) === strtoupper(trim($selectedFolder));
            });
        }

        // 2. EXTRACTION ROBUSTE DES TAGS (Anti-bug de format)
        $allTagsCollection = collect();
        foreach ($allTagsDocs as $doc) {
            // On vérifie si les tags sont en texte brut (JSON) ou déjà en tableau
            $tags = is_string($doc->tags) ? json_decode($doc->tags, true) : $doc->tags;
            
            // Si c'est bien un tableau et qu'il n'est pas vide, on l'ajoute à notre collection
            if (is_array($tags) && !empty($tags)) {
                $allTagsCollection = $allTagsCollection->merge($tags);
            }
        }
        
        // 3. Tri et comptage
        $allTags = $allTagsCollection->unique()->values()->sort();
        $tagsWithCount = array_count_values($allTagsCollection->toArray());
        arsort($tagsWithCount); 
        
        $maxTags = 10;
        
        $popularUnselected = collect(array_keys($tagsWithCount))
            ->diff($selectedTags)
            ->take(max(0, $maxTags - count($selectedTags)))
            ->toArray();

        $pillsTags = array_merge($selectedTags, $popularUnselected);

        // --------------------------------------------------------
        // ÉTAPE 4 : APPLICATION DU FILTRE DES TAGS SÉLECTIONNÉS
        // --------------------------------------------------------
        if (!empty($selectedTags)) {
            $query->where(function($q) use ($selectedTags) {
                foreach ($selectedTags as $t) {
                    $q->orWhereJsonContains('tags', $t);
                }
            });
        }

        // --------------------------------------------------------
        // ÉTAPE 5 : RÉCUPÉRATION FINALE DES DONNÉES
        // --------------------------------------------------------
        if ($tab === 'all' && $isRetd) {
            
            if (!empty($selectedFolder)) {
                
                // Le dossier spécial qui affiche TOUT
                if ($selectedFolder === 'ALL_DOCS') {
                    $documentsToDisplay = $query->paginate(12)->withQueryString();
                } 
                else {
                    // Vue Intérieure d'un dossier : Filtrage normal
                    $allDocs = $query->get();

                    $folderDocs = $allDocs->filter(function($doc) use ($selectedFolder) {
                        $groupName = $doc->user->group_name ?? 'GÉNÉRAL / SANS GROUPE';
                        return strtoupper(trim($groupName)) === strtoupper(trim($selectedFolder));
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
                // 📁 VUE RACINE : On regroupe tous les documents existants par Dossier (Nom du groupe de l'auteur)
                $groupedDocs = $query->get()->groupBy(function($doc) {
                    return strtoupper($doc->user->group_name ?? 'GÉNÉRAL / SANS GROUPE');
                });

                if (!empty($search)) {
                    $groupedDocs = $groupedDocs->filter(function($groupDocs, $groupName) use ($search) {
                        return str_contains(strtolower($groupName), strtolower($search));
                    });
                }

                $documentsToDisplay = $groupedDocs;
            }
            
        } else {
            $documentsToDisplay = $query->paginate(12)->withQueryString();
        }

        return view('home', [
            'documents' => $documentsToDisplay,
            'tab' => $tab,
            'search' => $search,
            'selectedTags' => $selectedTags,
            'pillsTags' => $pillsTags,
            'allTags' => $allTags,
            'selectedFolder' => $selectedFolder,
        ]);
    }
}