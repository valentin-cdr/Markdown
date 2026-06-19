@extends('layouts.app')
<style>
    /* --- Barre de défilement sur mesure --- */

    /* Pour Firefox */
    .custom-scrollbar {
        scrollbar-width: thin;
        scrollbar-color: #cbd5e1 transparent; /* Couleur de la barre et du fond */
    }
    .dark .custom-scrollbar {
        scrollbar-color: #475569 transparent; /* Couleurs pour le mode sombre */
    }

    /* Pour Chrome, Edge, Safari */
    .custom-scrollbar::-webkit-scrollbar {
        height: 6px; /* Barre horizontale très fine */
    }
    .custom-scrollbar::-webkit-scrollbar-track {
        background: transparent; /* Fond invisible */
        border-radius: 10px;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb {
        background-color: #cbd5e1; /* Gris clair */
        border-radius: 10px;
    }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover {
        background-color: #94a3b8; /* Gris un peu plus foncé au survol */
    }

    /* Mode sombre pour Chrome, Edge, Safari */
    .dark .custom-scrollbar::-webkit-scrollbar-thumb {
        background-color: #475569; 
    }
    .dark .custom-scrollbar::-webkit-scrollbar-thumb:hover {
        background-color: #64748b; 
    }
</style>
@section('title', 'Tableau de bord')

@section('content')
<main class="max-w-6xl w-full mx-auto p-6 flex-1">
    
    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center mb-6 space-y-4 sm:space-y-0">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white transition-colors duration-200">Bibliothèque</h2>
        
        <div class="flex flex-col sm:flex-row items-center space-y-3 sm:space-y-0 sm:space-x-4 w-full sm:w-auto">

           <form id="search-form" method="GET" action="{{ route('home') }}" class="w-full sm:w-64 m-0">
                <input type="hidden" name="tab" value="{{ $tab }}">
                
                @if(!empty($selectedFolder))
                    <input type="hidden" name="folder" value="{{ $selectedFolder }}">
                @endif
                @foreach($selectedTags as $st)
                    <input type="hidden" name="tags[]" value="{{ $st }}">
                @endforeach

                <div class="relative flex items-center">
                    {{-- Icône Loupe --}}
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-4 w-4 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    
                    {{-- Champ de recherche (on a changé pr-3 en pr-10 pour laisser la place à la croix) --}}
                    <input type="text" id="search-input" name="search" value="{{ $search ?? '' }}" 
                           class="block w-full h-10 pl-9 pr-10 border border-gray-200 dark:border-gray-700 rounded-xl leading-5 bg-white dark:bg-gray-800 text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm transition-colors shadow-sm" 
                           placeholder="{{ ($tab === 'all' && empty($selectedFolder)) ? 'Rechercher un dossier...' : 'Rechercher un document...' }}">

                    {{-- La croix pour effacer (visible seulement si une recherche est en cours) --}}
                    @if(!empty($search))
                        <button type="button" 
                                onclick="document.getElementById('search-input').value=''; document.getElementById('search-form').submit();" 
                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-red-500 dark:hover:text-red-400 transition-colors focus:outline-none"
                                title="Effacer la recherche">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    @endif
                </div>
            </form>

            <a href="{{ route('documents.create') }}" class="inline-flex items-center justify-center w-full sm:w-auto h-10 px-5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-xl transition whitespace-nowrap shadow-sm">
                + Nouveau
            </a>
            
        </div>
    </div>

    <div class="border-b border-gray-200 dark:border-gray-700 mb-8 transition-colors duration-200">
        <nav class="-mb-px flex space-x-8">
            <a href="{{ route('home', ['tab' => 'my_documents']) }}" class="{{ $tab === 'my_documents' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300' }} whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition">
                Mes documents
            </a>
            <a href="{{ route('home', ['tab' => 'shared']) }}" class="{{ $tab === 'shared' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300' }} whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition">
                Partagés avec moi
            </a>
            
            {{-- L'ONGLET SECRET R&D --}}
            @if(in_array('retd', session('keycloak_groups', [])))
                <a href="{{ route('home', ['tab' => 'all']) }}" class="{{ $tab === 'all' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300' }} whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition">
                    Tous les documents (Global)
                </a>
            @endif
        </nav>
    </div>

    @if(session('success'))
        <div class="bg-green-50 dark:bg-green-900/30 border-l-4 border-green-500 p-4 mb-6 rounded-r-xl shadow-sm transition-colors duration-200">
            <p class="text-sm font-medium text-green-800 dark:text-green-300">{{ session('success') }}</p>
        </div>
    @endif

    {{-- BLOC D'AFFICHAGE DES ERREURS DE REDIRECTION --}}
    @if(session('error'))
        <div class="bg-red-50 dark:bg-red-900/30 border-l-4 border-red-500 p-4 mb-6 rounded-r-xl shadow-sm transition-colors duration-200">
            <p class="text-sm font-medium text-red-800 dark:text-red-300">{{ session('error') }}</p>
        </div>
    @endif

    {{-- EN-TÊTE DU DOSSIER (Visible seulement si on est dans un dossier) --}}
    @if($tab === 'all' && !empty($selectedFolder))
        <div class="mb-4 mt-2 flex items-center space-x-4 animate-fade-in-down">
            <a href="{{ route('home', ['tab' => 'all']) }}" 
               class="p-2.5 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-gray-500 hover:text-indigo-600 dark:hover:text-indigo-400 shadow-sm transition-all hover:-translate-x-1" title="Retour aux dossiers">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
            </a>
            <h3 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center">
                <svg class="w-8 h-8 mr-3 text-indigo-500 drop-shadow-sm" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z" />
                </svg>
                {{ $selectedFolder === 'ALL_DOCS' ? 'Tous les fichiers' : $selectedFolder }}
            </h3>
        </div>
    @endif

    {{-- BOÎTE DES TAGS : VISIBLE PARTOUT SAUF SUR LA RACINE DES DOSSIERS --}}
    @if($tab !== 'all' || !empty($selectedFolder))
        @if(!empty($allTags) && count($allTags) > 0)
            <div class="flex items-center gap-2 pb-4 w-full animate-fade-in-down">
                
                {{-- 1. La zone des tags (relative pour que le script calcule bien l'espace, et overflow-x-auto pour le scroll) --}}
                <div class="flex-1 min-w-0">
                    <div id="tags-scroll-container" class="flex flex-nowrap overflow-x-auto gap-2 custom-scrollbar pb-1 relative" style="scroll-behavior: smooth;">
                        @foreach($pillsTags as $t)
                            @php
                                $isActive = in_array($t, $selectedTags);
                                $newTags = $isActive ? array_diff($selectedTags, [$t]) : array_merge($selectedTags, [$t]);
                                
                                $routeParams = ['tab' => $tab, 'search' => $search, 'tags' => $newTags];
                                if (!empty($selectedFolder)) $routeParams['folder'] = $selectedFolder;
                            @endphp
                            
                            {{-- On ajoute une classe spécifique pour les tags cliqués ou non --}}
                            <a href="{{ route('home', $routeParams) }}" 
                               class="shrink-0 whitespace-nowrap inline-flex items-center px-3 py-1.5 rounded-full text-[13px] font-medium border transition-colors duration-200 focus:outline-none {{ $isActive ? 'tag-selected bg-indigo-100 border-indigo-200 text-indigo-700 hover:bg-indigo-200 dark:bg-indigo-900/60 dark:border-indigo-700/50 dark:text-indigo-300 dark:hover:bg-indigo-900/80' : 'tag-suggested bg-white border-gray-200 text-gray-700 hover:bg-gray-50 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-700' }}">
                                
                                <span class="{{ $isActive ? 'text-indigo-400 dark:text-indigo-500' : 'text-gray-400 dark:text-gray-500' }} mr-1.5">#</span>
                                {{ $t }}
                                
                                @if($isActive)
                                    <svg class="w-3 h-3 ml-1.5 text-indigo-500 dark:text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                @endif
                            </a>
                        @endforeach
                    </div>
                </div>

                {{-- 2. Le menu déroulant "Tous les tags" --}}
                @if(count($allTags) > 10)
                    <div class="relative shrink-0 ml-1">
                        <button type="button" onclick="toggleTagDropdown()" class="shrink-0 whitespace-nowrap inline-flex items-center px-3 py-1.5 rounded-full text-[13px] font-medium bg-gray-100 border border-transparent text-gray-600 hover:bg-gray-200 dark:bg-gray-800/50 dark:text-gray-400 dark:hover:bg-gray-800 dark:border-gray-700 transition-colors duration-200 focus:outline-none shadow-sm">
                            <svg class="w-3.5 h-3.5 mr-1.5 opacity-70" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                            Tous les tags
                        </button>
                        
                        <div id="tag-dropdown-menu" class="hidden absolute right-0 z-50 mt-2 w-56 origin-top-right rounded-xl bg-white dark:bg-gray-800 shadow-lg ring-1 ring-black ring-opacity-5 dark:ring-gray-700 focus:outline-none overflow-hidden transition-all">
                            <div class="p-2 border-b border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50">
                                <input type="text" id="tag-search-input" onkeyup="filterDropdownTags()" placeholder="Chercher un tag..." 
                                    class="block w-full rounded-lg border-0 py-1.5 px-3 text-sm text-gray-900 dark:text-white shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-600 focus:ring-2 focus:ring-inset focus:ring-indigo-600 dark:bg-gray-700">
                            </div>
                            
                            <div class="max-h-60 overflow-y-auto custom-scrollbar py-1" id="tag-dropdown-list">
                                @foreach($allTags as $t)
                                    @php
                                        $isActive = in_array($t, $selectedTags);
                                        $newTags = $isActive ? array_diff($selectedTags, [$t]) : array_merge($selectedTags, [$t]);
                                        
                                        $routeParams = ['tab' => $tab, 'search' => $search, 'tags' => $newTags];
                                        if (!empty($selectedFolder)) $routeParams['folder'] = $selectedFolder;
                                    @endphp
                                    
                                    <a href="{{ route('home', $routeParams) }}" 
                                    class="tag-dropdown-item w-full flex items-center justify-between px-4 py-2 text-sm transition-colors focus:outline-none {{ $isActive ? 'bg-indigo-50 dark:bg-indigo-900/20 text-indigo-700 dark:text-indigo-400 font-semibold' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                                        
                                        {{-- L'AJOUT EST ICI : Le design aligné avec le # --}}
                                        <div class="flex items-center">
                                            <span class="{{ $isActive ? 'text-indigo-400 dark:text-indigo-500' : 'text-gray-400 dark:text-gray-500' }} mr-1.5">#</span>
                                            <span class="tag-name">{{ $t }}</span>
                                        </div>
                                        
                                        @if($isActive)
                                            <svg class="h-4 w-4 text-indigo-600 dark:text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                        @endif
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        @endif
    @endif

    {{-- LOGIQUE INTELLIGENTE D'AFFICHAGE DU VIDE VS DOSSIERS --}}
    @php
        $isRootAllTab = ($tab === 'all' && empty($selectedFolder));
        
        // Est-ce qu'on doit afficher le super-dossier selon la recherche ?
        $showAllDocs = $isRootAllTab && (empty($search) || 
                       str_contains(strtolower('tous les fichiers'), strtolower($search)) || 
                       str_contains(strtolower('all_docs'), strtolower($search)));
        
        // La page est considérée comme vide si aucun dossier ET aucun super-dossier ne correspondent
        $isPageEmpty = $isRootAllTab ? ($documents->isEmpty() && !$showAllDocs) : $documents->isEmpty();
    @endphp

    @if($isPageEmpty)
        <div class="text-center bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl p-16 shadow-sm transition-colors duration-200">
            @if(!empty($search) || !empty($selectedTags))
                <p class="text-gray-500 dark:text-gray-400">Aucun résultat trouvé pour cette recherche ou ces filtres.</p>
                <a href="{{ route('home', ['tab' => $tab, 'folder' => $selectedFolder]) }}" class="text-indigo-500 hover:underline mt-2 inline-block text-sm">Effacer les filtres</a>
            @else
                <p class="text-gray-500 dark:text-gray-400">Aucun document dans cet onglet.</p>
            @endif
        </div>
    @else
        {{-- SI ON EST DANS L'ONGLET R&D GLOBAL --}}
        @if($tab === 'all' && in_array('retd', session('keycloak_groups', [])))
            
            @if(empty($selectedFolder))
                {{-- 1. VUE "RACINE" : LA GRILLE DES DOSSIERS --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6 animate-fade-in-down">
                    
                    {{-- AFFICHAGE CONDITIONNEL DU SUPER-DOSSIER --}}
                    @if($showAllDocs)
                        <a href="{{ route('home', ['tab' => 'all', 'folder' => 'ALL_DOCS']) }}" 
                           class="group bg-indigo-50 dark:bg-indigo-900/30 border border-indigo-200 dark:border-indigo-700 rounded-2xl p-5 shadow-sm hover:shadow-md hover:border-indigo-400 transition-all duration-200 flex items-center space-x-4 cursor-pointer">
                            
                            <div class="p-3 bg-indigo-600 text-white rounded-xl group-hover:scale-110 group-hover:bg-indigo-500 transition-all shadow-sm">
                                <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                </svg>
                            </div>
                            
                            <div class="flex-1 min-w-0">
                                <h3 class="text-sm font-bold text-indigo-900 dark:text-indigo-100 truncate">TOUS LES FICHIERS</h3>
                                <p class="text-xs text-indigo-600 dark:text-indigo-400 mt-0.5">{{ $documents->flatten(1)->count() }} document(s)</p>
                            </div>
                        </a>
                    @endif

                    {{-- Boucle des dossiers filtrés par PHP --}}
                    @foreach($documents as $groupName => $groupDocs)
                        <a href="{{ route('home', ['tab' => 'all', 'folder' => $groupName]) }}" 
                           class="group bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl p-5 shadow-sm hover:shadow-md hover:border-indigo-400 dark:hover:border-indigo-500 transition-all duration-200 flex items-center space-x-4 cursor-pointer">
                            
                            <div class="p-3 bg-indigo-50 dark:bg-indigo-900/40 text-indigo-500 dark:text-indigo-400 rounded-xl group-hover:scale-110 group-hover:bg-indigo-100 dark:group-hover:bg-indigo-900/60 transition-all">
                                <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z" />
                                </svg>
                            </div>
                            
                            <div class="flex-1 min-w-0">
                                <h3 class="text-sm font-bold text-gray-900 dark:text-white truncate">{{ $groupName }}</h3>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ $groupDocs->count() }} document(s)</p>
                            </div>
                        </a>
                    @endforeach
                </div>
            @else
                {{-- 2. VUE "INTÉRIEUR" : CONTENU D'UN DOSSIER CLIQUÉ --}}
                <div class="animate-fade-in-down">

                    {{-- La grille des documents de CE dossier uniquement --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mt-4">
                        @foreach($documents as $doc)
                            {{-- On ajoute 'relative' sur la carte pour pouvoir positionner le nom de l'auteur en haut à droite --}}
                            <div class="relative bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl p-5 shadow-sm hover:shadow-md transition flex flex-col justify-between h-48">
                                
                                {{-- NOM DE L'AUTEUR POSITIONNÉ EN HAUT À DROITE --}}
                                <span class="absolute top-4 right-5 text-[11px] font-medium text-gray-400 dark:text-gray-500 bg-gray-50 dark:bg-gray-900/40 px-2 py-0.5 rounded-md border border-gray-100 dark:border-gray-800">
                                    Par : <strong class="text-gray-600 dark:text-gray-300 font-semibold">{{ $doc->user->username }}</strong>
                                </span>

                                <div>
                                    {{-- On ajoute 'pr-20' (padding-right) pour éviter que le titre ne chevauche le nom de l'auteur --}}
                                    <h3 class="text-base font-bold text-gray-900 dark:text-white truncate transition-colors duration-200 pr-20">{{ $doc->title }}</h3>
                                    
                                    @if(!empty($doc->tags))
                                        <div class="flex flex-wrap gap-2 mb-2 mt-1">
                                            @foreach($doc->tags as $t)
                                                <a href="{{ route('home', ['tab' => $tab, 'tags' => [$t], 'folder' => $selectedFolder]) }}" 
                                                class="inline-flex items-center mt-0.5 px-2 py-0.5 rounded-full text-[11px] font-medium bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-900/30 dark:hover:bg-indigo-900/50 text-indigo-600 dark:text-indigo-400 border border-indigo-100 dark:border-indigo-800/50 transition-colors cursor-pointer max-w-[120px]">
                                                    <span class="text-indigo-400 dark:text-indigo-500 mr-0.5 shrink-0">#</span>
                                                    <span class="truncate">{{ $t }}</span>
                                                </a>
                                            @endforeach
                                        </div>
                                    @endif
                                    <p class="text-sm text-gray-500 dark:text-gray-400 line-clamp-3 mt-2 transition-colors duration-200">{{ $doc->clean_preview }}</p>
                                </div>

                                <div class="flex justify-between items-center border-t border-gray-100 dark:border-gray-700 pt-3 mt-auto transition-colors duration-200">
                                    {{-- Date tout à gauche --}}
                                    <span class="text-xs text-gray-400">{{ $doc->updated_at->diffForHumans() }}</span>
                                    
                                    {{-- Uniquement les boutons à droite --}}
                                    <div class="flex items-center space-x-2">
                                        @if(isset($doc->shared_with_count) && $doc->shared_with_count > 0)
                                            <span class="inline-flex items-center gap-x-1.5 rounded-md bg-indigo-50 dark:bg-indigo-900/40 px-2 py-1 text-xs font-medium text-indigo-700 dark:text-indigo-400 border border-indigo-200/50 dark:border-indigo-900/50 shadow-sm transition-colors mr-1">
                                                <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                                                    <path d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" />
                                                </svg>
                                                {{ $doc->shared_with_count }}
                                            </span>
                                        @endif
                                        
                                        @if($tab === 'my_documents')
                                            <a href="{{ route('documents.share.form', $doc->id) }}" class="text-xs font-medium text-gray-600 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 py-1.5 px-3 rounded-lg transition flex items-center">Partager</a>
                                            <a href="{{ route('documents.edit', $doc->id) }}" class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-900/50 hover:bg-indigo-100 dark:hover:bg-indigo-900 py-1.5 px-3 rounded-lg transition flex items-center">Éditer</a>
                                        @else
                                            {{-- Correction faite ici : @else est tout propre --}}
                                            <a href="{{ route('documents.show', $doc->id) }}" class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-900/50 hover:bg-indigo-100 dark:hover:bg-indigo-900/70 py-1.5 px-3 rounded-lg transition flex items-center shadow-sm">Consulter</a>
                                            <a href="{{ route('documents.edit', ['document' => $doc->id, 'folder' => $selectedFolder, 'tab' => $tab]) }}" class="text-xs font-semibold text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-900/40 hover:bg-amber-100 dark:hover:bg-amber-900/60 py-1.5 px-3 rounded-lg transition flex items-center shadow-sm">Éditer</a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="mt-8 transition-colors duration-200">
                        {{ $documents->links() }}
                    </div>
                </div>
            @endif

        {{-- SINON, AFFICHAGE NORMAL POUR LES AUTRES ONGLETS (Mes docs / Partagés) --}}
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mt-4">
                @foreach($documents as $doc)
                    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl p-5 shadow-sm hover:shadow-md transition flex flex-col justify-between h-48">
                        <div>
                            <h3 class="text-base font-bold text-gray-900 dark:text-white truncate transition-colors duration-200">{{ $doc->title }}</h3>
                            @if(!empty($doc->tags))
                                @php
                                    // On prend les 3 premiers tags seulement
                                    $displayTags = array_slice($doc->tags, 0, 3);
                                    // On calcule combien il en reste à cacher
                                    $hiddenCount = count($doc->tags) - 3;
                                @endphp
                                
                                <div class="flex flex-wrap items-center gap-1.5 mb-2">
                                    @foreach($displayTags as $t)
                                        <a href="{{ route('home', ['tab' => $tab, 'tags' => [$t], 'folder' => $selectedFolder ?? null]) }}" 
                                        {{-- SUPPRESSION DE mt-0.5 ET -ml-1 ICI --}}
                                        class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-900/30 dark:hover:bg-indigo-900/50 text-indigo-600 dark:text-indigo-400 border border-indigo-100 dark:border-indigo-800/50 transition-colors cursor-pointer max-w-[100px]">
                                            
                                            {{-- Le petit croisillon --}}
                                            <span class="text-indigo-400 dark:text-indigo-500 mr-0.5 shrink-0">#</span>
                                            
                                            {{-- Le texte du tag --}}
                                            <span class="truncate">{{ $t }}</span>
                                        </a>
                                    @endforeach
                                    
                                    {{-- S'il y a plus de 3 tags, on affiche la pilule "+X" --}}
                                    @if($hiddenCount > 0)
                                        {{-- PASSAGE EN rounded-full ET inline-flex POUR UN ALIGNEMENT PARFAIT --}}
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400 border border-gray-200 dark:border-gray-600 cursor-default">
                                            +{{ $hiddenCount }}
                                        </span>
                                    @endif
                                </div>
                            @endif
                            <p class="text-sm text-gray-500 dark:text-gray-400 line-clamp-3 mt-2 transition-colors duration-200">{{ $doc->clean_preview }}</p>
                        </div>
                        
                        <div class="flex justify-between items-center border-t border-gray-100 dark:border-gray-700 pt-3 mt-auto transition-colors duration-200">
                            {{-- Date tout à gauche --}}
                            <span class="text-xs text-gray-400">{{ $doc->updated_at->diffForHumans() }}</span>
                            
                            {{-- Auteur et Boutons à droite, strictement alignés --}}
                            <div class="flex items-center space-x-3">
                                @if(isset($doc->shared_with_count) && $doc->shared_with_count > 0)
                                    <span class="inline-flex items-center gap-x-1.5 rounded-md bg-indigo-50 dark:bg-indigo-900/40 px-2 py-1 text-xs font-medium text-indigo-700 dark:text-indigo-400 border border-indigo-200/50 dark:border-indigo-900/50 shadow-sm transition-colors">
                                        <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                                            <path d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" />
                                        </svg>
                                        {{ $doc->shared_with_count }}
                                    </span>
                                @endif
                                
                                @if($tab === 'my_documents')
                                    <a href="{{ route('documents.share.form', $doc->id) }}" class="text-xs font-medium text-gray-600 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 py-1.5 px-3 rounded-lg transition flex items-center">Partager</a>
                                    <a href="{{ route('documents.edit', $doc->id) }}" class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-900/50 hover:bg-indigo-100 dark:hover:bg-indigo-900 py-1.5 px-3 rounded-lg transition flex items-center">Éditer</a>
                                @else
                                    {{-- Le texte est centré verticalement par rapport au bouton --}}
                                    <span class="text-xs text-gray-500 dark:text-gray-400 flex items-center whitespace-nowrap">
                                        De : <strong class="ml-1 text-gray-700 dark:text-gray-300 font-medium">{{ $doc->user->username }}</strong>
                                    </span>

                                    @if($doc->pivot?->can_edit)
                                        <a href="{{ route('documents.edit', $doc->id) }}" class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-900/50 hover:bg-indigo-100 dark:hover:bg-indigo-900/70 py-1.5 px-3 rounded-lg transition flex items-center shadow-sm">Éditer</a>
                                    @else
                                        <a href="{{ route('documents.show', $doc->id) }}" class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-900/50 hover:bg-indigo-100 dark:hover:bg-indigo-900/70 py-1.5 px-3 rounded-lg transition flex items-center shadow-sm">Consulter</a>
                                    @endif
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-8 transition-colors duration-200">
                {{ $documents->links() }}
            </div>
        @endif
    @endif
</main>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('search-input');
        const searchForm = document.getElementById('search-form');
        let timeout = null;

        if (searchInput && searchForm) {
            searchInput.addEventListener('input', function() {
                clearTimeout(timeout);
                
                // Si la recherche se termine par un espace, on ne fait rien et on attend la suite !
                if (searchInput.value.endsWith(' ')) {
                    return;
                }
                
                timeout = setTimeout(function() {
                    searchForm.submit(); 
                }, 500); 
            });

            if (searchInput.value) {
                searchInput.focus();
                const val = searchInput.value;
                searchInput.value = '';
                searchInput.value = val;
            }
        }
    });

    // 1. Afficher/Cacher le menu
    function toggleTagDropdown() {
        const menu = document.getElementById('tag-dropdown-menu');
        menu.classList.toggle('hidden');
        
        // Si on ouvre le menu, on met le focus sur le champ de recherche
        if(!menu.classList.contains('hidden')) {
            setTimeout(() => document.getElementById('tag-search-input').focus(), 50);
        }
    }

    // 2. Fermer le menu si on clique ailleurs sur la page
    document.addEventListener('click', function(event) {
        const menu = document.getElementById('tag-dropdown-menu');
        const button = event.target.closest('button[onclick="toggleTagDropdown()"]');
        if (!button && menu && !menu.contains(event.target)) {
            menu.classList.add('hidden');
        }
    });

    // 3. Filtrer les tags dans le menu quand on tape du texte
    function filterDropdownTags() {
        let input = document.getElementById('tag-search-input');
        let filter = input.value.toLowerCase();
        let items = document.querySelectorAll('.tag-dropdown-item');

        items.forEach(function(item) {
            let text = item.querySelector('.tag-name').innerText.toLowerCase();
            if (text.includes(filter)) {
                item.style.display = "flex"; // Garde le design Flexbox
            } else {
                item.style.display = "none";
            }
        });
    }
    
    // 4. Masquer les tags gris qui dépassent de l'écran
    function updateTagsVisibility() {
        const container = document.getElementById('tags-scroll-container');
        if (!container) return;
        
        // On ne cible QUE les tags gris (suggestions). Les bleus restent intouchables !
        const suggestedTags = container.querySelectorAll('.tag-suggested');
        
        // 1. Réafficher tout pour recalculer correctement la place
        suggestedTags.forEach(tag => tag.style.display = 'inline-flex');
        
        // 2. Largeur visible de l'écran pour les tags
        const containerWidth = container.clientWidth;
        
        // 3. Couper proprement les tags gris en trop
        suggestedTags.forEach(tag => {
            // offsetLeft donne la position de départ du tag. 
            // offsetWidth donne sa taille.
            // Si le tag déborde de l'écran, on l'efface totalement (display: none).
            if ((tag.offsetLeft + tag.offsetWidth) > containerWidth) {
                tag.style.display = 'none';
            }
        });
    }

    // Lancer au chargement et au redimensionnement de la fenêtre
    window.addEventListener('DOMContentLoaded', updateTagsVisibility);
    window.addEventListener('resize', updateTagsVisibility);
</script>
@endpush
@endsection