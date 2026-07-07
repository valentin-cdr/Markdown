@extends('layouts.app')
@php
    $cancelUrl = old('cancel_url', url()->previous());
    if ($cancelUrl === url()->current()) {
        $cancelUrl = route('home');
    }

    // --- On récupère la couleur du groupe pour l'éditeur Toast UI ---
    $editorColors = [
        'retd'  => ['light' => '#f59e0b', 'dark' => '#fbbf24'], // Amber
        'onAir' => ['light' => '#3b82f6', 'dark' => '#60a5fa'], // Blue
        'RSC'   => ['light' => '#10b981', 'dark' => '#34d399'], // Emerald
    ];
    // On trouve le groupe actif de l'utilisateur
    $activeGroups = array_intersect(session('keycloak_groups', []), array_keys($editorColors));
    $activeGroup = reset($activeGroups);
    
    // On définit les variables qu'on va injecter dans le CSS
    $colorLight = $activeGroup ? $editorColors[$activeGroup]['light'] : '#4f46e5';
    $colorDark = $activeGroup ? $editorColors[$activeGroup]['dark'] : '#818cf8';
@endphp
@section('title', $document ? 'Modifier - ' . $document->title : 'Nouveau document')

@section('styles')
    <link rel="stylesheet" href="https://uicdn.toast.com/editor/latest/toastui-editor.min.css" />
    <link rel="stylesheet" href="https://uicdn.toast.com/editor/latest/theme/toastui-editor-dark.min.css" />
    
    <style>
        .toastui-editor-defaultUI {
            border-radius: 0.75rem !important;
            border: 1px solid #e5e7eb !important;
            font-family: inherit !important;
            overflow: hidden;
            box-shadow: none !important;
        }
        .dark .toastui-editor-defaultUI {
            border-color: #374151 !important; 
        }
        
        .toastui-editor-defaultUI .toastui-editor-status-bar {
            display: none !important;
        }

        .toastui-editor-defaultUI .ProseMirror h1,
        .toastui-editor-defaultUI .ProseMirror h2,
        .toastui-editor-contents h1,
        .toastui-editor-contents h2 {
            border-bottom: none !important;
            padding-bottom: 0 !important;
        }

        .toastui-editor-defaultUI .ProseMirror,
        .toastui-editor-contents {
            min-height: 400px !important;
        }

        .toastui-editor-defaultUI .toastui-editor-tabs {
            height: 34px !important; 
            line-height: 34px !important;
        }
        .toastui-editor-defaultUI .toastui-editor-tabs .tab-item {
            height: 34px !important; 
            line-height: 34px !important;
            padding: 0 16px !important; 
        }

        .toastui-editor-defaultUI,
        .toastui-editor-defaultUI .toastui-editor-main,
        .toastui-editor-defaultUI .toastui-editor-ww-container,
        .toastui-editor-defaultUI .toastui-editor-md-container,
        .toastui-editor-defaultUI .toastui-editor-md-preview {
            background-color: #ffffff !important;
        }
        .toastui-editor-defaultUI .toastui-editor-toolbar,
        .toastui-editor-defaultUI .toastui-editor-tabs {
            background-color: #f9fafb !important;
            border-bottom-color: #e5e7eb !important;
        }
        .toastui-editor-defaultUI .toastui-editor-tabs .tab-item.active {
            background-color: #ffffff !important;
            color: {{ $colorLight }} !important;
        }
        .toastui-editor-defaultUI .ProseMirror,
        .toastui-editor-contents {
            color: #111827 !important;
        }

        .toastui-editor-defaultUI .ProseMirror strong,
        .toastui-editor-contents strong,
        .toastui-editor-defaultUI .toastui-editor-md-strong {
            font-weight: 800 !important;
            color: #000000 !important;
        }

        .dark .toastui-editor-defaultUI,
        .dark .toastui-editor-defaultUI .toastui-editor-main,
        .dark .toastui-editor-defaultUI .toastui-editor-ww-container,
        .dark .toastui-editor-defaultUI .toastui-editor-md-container,
        .dark .toastui-editor-dark,
        .dark .toastui-editor-defaultUI .toastui-editor-md-preview {
            background-color: #24292e !important; 
        }
        
        .dark .toastui-editor-defaultUI .toastui-editor-toolbar,
        .dark .toastui-editor-defaultUI .toastui-editor-tabs {
            background-color: #232428 !important;
            border-bottom-color: #374151 !important;
        }

        .dark .toastui-editor-defaultUI .toastui-editor-tabs .tab-item {
            color: #64748b !important; 
            background-color: transparent !important;
        }
        .dark .toastui-editor-defaultUI .toastui-editor-tabs .tab-item.active {
            background-color: #24292e !important; 
            color: {{ $colorDark }} !important;
        }

        .dark .toastui-editor-defaultUI .toastui-editor-md-splitter {
            background-color: #374151 !important;
        }

        .dark .toastui-editor-defaultUI .ProseMirror,
        .dark .toastui-editor-contents {
            color: #cbd5e1 !important; 
        }
        
        .dark .toastui-editor-defaultUI .ProseMirror h1,
        .dark .toastui-editor-defaultUI .ProseMirror h2,
        .dark .toastui-editor-contents h1,
        .dark .toastui-editor-contents h2 {
            color: #f8fafc !important; 
        }

        .dark .toastui-editor-defaultUI .ProseMirror strong,
        .dark .toastui-editor-contents strong,
        .dark .toastui-editor-defaultUI .toastui-editor-md-strong {
            font-weight: 800 !important;
            color: #ffffff !important;
        }

        .dark .toastui-editor-defaultUI .toastui-editor-md-heading {
            color: #f8fafc !important; 
        }

        .dark .toastui-editor-contents a,
        .dark .toastui-editor-defaultUI .ProseMirror a,
        .dark .toastui-editor-defaultUI .toastui-editor-md-meta { 
            color: {{ $colorDark }} !important; /* <-- Modifié ici */
        }

        .force-tui-split-width {
            max-width: 80rem !important; 
        }

        .toastui-editor-defaultUI * {
            scrollbar-width: thin !important;
            scrollbar-color: #cbd5e1 transparent !important;
        }
        .dark .toastui-editor-defaultUI * {
            scrollbar-color: #4b5563 transparent !important;
        }

        .toastui-editor-defaultUI *::-webkit-scrollbar {
            width: 6px !important;
            height: 6px !important;
        }
        .toastui-editor-defaultUI *::-webkit-scrollbar-track {
            background: transparent !important;
        }
        .toastui-editor-defaultUI *::-webkit-scrollbar-thumb {
            background-color: #cbd5e1 !important;
            border-radius: 20px !important;
        }
        .toastui-editor-defaultUI *::-webkit-scrollbar-thumb:hover {
            background-color: #9ca3af !important;
        }

        .dark .toastui-editor-defaultUI *::-webkit-scrollbar-thumb {
            background-color: #4b5563 !important;
        }
        .dark .toastui-editor-defaultUI *::-webkit-scrollbar-thumb:hover {
            background-color: #6b7280 !important;
        }

        .toastui-editor-defaultUI ul > li::marker,
        .toastui-editor-contents ul > li::marker {
            content: "• " !important;
            color: #4f46e5 !important;
            font-size: 1.2rem !important;
        }
        .toastui-editor-defaultUI ul ul > li::marker,
        .toastui-editor-contents ul ul > li::marker {
            content: "◦ " !important;
            color: #0284c7 !important;
            font-size: 1.2rem !important;
        }
        .toastui-editor-defaultUI ul ul ul > li::marker,
        .toastui-editor-contents ul ul ul > li::marker {
            content: "▪ " !important;
            color: #059669 !important;
            font-size: 1.1rem !important;
        }

        .dark .toastui-editor-defaultUI ul > li::marker,
        .dark .toastui-editor-contents ul > li::marker {
            content: "• " !important;
            color: #818cf8 !important;
            font-size: 1.2rem !important;
        }
        .dark .toastui-editor-defaultUI ul ul > li::marker,
        .dark .toastui-editor-contents ul ul > li::marker {
            content: "◦ " !important;
            color: #38bdf8 !important;
            font-size: 1.2rem !important;
        }
        .dark .toastui-editor-defaultUI ul ul ul > li::marker,
        .dark .toastui-editor-contents ul ul ul > li::marker {
            content: "▪ " !important;
            color: #34d399 !important;
            font-size: 1.1rem !important;
        }

        /* --- Barre de défilement sur mesure pour les tags --- */
        .custom-scrollbar {
            scrollbar-width: thin;
            scrollbar-color: #cbd5e1 transparent;
        }
        .dark .custom-scrollbar {
            scrollbar-color: #475569 transparent;
        }
        .custom-scrollbar::-webkit-scrollbar {
            height: 6px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: transparent;
            border-radius: 10px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background-color: #cbd5e1;
            border-radius: 10px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background-color: #94a3b8;
        }
        .dark .custom-scrollbar::-webkit-scrollbar-thumb {
            background-color: #475569; 
        }
        .dark .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background-color: #64748b; 
        }
        /* ASTUCE VISUELLE : Cache les pilules au-delà de la 10ème */
        #pills-container .tag-btn:nth-child(n+11) {
            display: none !important;
        }
    </style>
@endsection

@section('header-extra')
    <span class="text-gray-300 dark:text-gray-600 mx-2">|</span>
    <span class="text-sm text-gray-500 dark:text-gray-400">{{ $document ? 'Édition' : 'Création' }}</span>
@endsection

@section('content')
<main id="main-wrapper" class="max-w-6xl w-full mx-auto p-6 flex-1 transition-all duration-300">
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 transition-colors duration-200">
        @if(session('error'))
            <div class="bg-red-50 dark:bg-red-900/30 border-l-4 border-red-500 p-4 mb-6 rounded-r-xl shadow-sm transition-colors duration-200">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-red-800 dark:text-red-300">
                            {{ session('error') }}
                        </p>
                    </div>
                </div>
            </div>
        @endif
        <form id="document-form" action="{{ $document ? route('documents.update', $document->id) : route('documents.store') }}" method="POST">
            @if($document && $document->exists)
                <input type="hidden" name="original_updated_at" value="{{ $document->updated_at->format('Y-m-d H:i:s') }}">
            @endif
            @if(isset($folder))
                <input type="hidden" name="folder" value="{{ $folder }}">
            @endif
            @if(isset($tab))
                <input type="hidden" name="tab" value="{{ $tab }}">
            @endif
            @csrf
            <input type="hidden" name="cancel_url" value="{{ $cancelUrl }}">
            @if($document) @method('PUT') @endif
            
            {{-- En-tête de l'éditeur : Titre à gauche, Auteur en haut à droite --}}
            <div class="mb-5">
                
                {{-- Ligne 1 : Les textes (Label à gauche, Auteur à droite) --}}
                <div class="flex justify-between items-baseline mb-2">
                    <label class="block text-xs font-semibold text-gray-400 uppercase tracking-wider">Titre du document</label>
                </div>

                {{-- Ligne 2 : Le champ de saisie (prend 100% de la largeur) --}}
                <input type="text" name="title" value="{{ old('title', $document->title ?? '') }}" 
                    class="w-full bg-white dark:bg-gray-900 border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white rounded-xl focus:ring-2 focus:ring-indigo-500 p-3.5 border text-xl font-bold transition-colors" 
                    placeholder="Nom du fichier...">
                    
            </div>
            {{-- ZONE DES TAGS --}}
            <div class="mb-4">
                <label class="block text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Tags du document</label>
                
                {{-- STOCKAGE SECRET POUR LE FORMULAIRE --}}
                <div id="hidden-tags-container">
                    @foreach($selectedTags as $st)
                        <input type="hidden" name="tags[]" value="{{ $st }}" id="hidden-tag-{{ preg_replace('/[^a-zA-Z0-9]/', '', base64_encode($st)) }}">
                    @endforeach
                </div>

                {{-- Conteneur Flex global (Tags + Bouton Menu) --}}
                <div class="flex items-center gap-2 w-full">
                    
                    {{-- 1. La barre défilante des Tags --}}
                    <div class="flex-1 min-w-0">
                        <div id="pills-container" class="flex flex-nowrap overflow-x-auto gap-2 custom-scrollbar pb-1 relative" style="scroll-behavior: smooth;">
                            @foreach($pillsTags as $t)
                                @php $isActive = in_array($t, $selectedTags); @endphp
                                
                                <button type="button" onclick="toggleEditorTag('{{ addslashes($t) }}')" data-tag="{{ $t }}"
                                   class="tag-btn shrink-0 whitespace-nowrap inline-flex items-center px-3 py-1.5 rounded-full text-[13px] font-medium border transition-colors duration-200 focus:outline-none {{ $isActive ? 'tag-selected bg-indigo-100 border-indigo-200 text-indigo-700 hover:bg-indigo-200 dark:bg-indigo-900/60 dark:border-indigo-700/50 dark:text-indigo-300 dark:hover:bg-indigo-900/80' : 'tag-suggested bg-white border-gray-200 text-gray-700 hover:bg-gray-50 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-700' }}">
                                    
                                    <span class="{{ $isActive ? 'text-indigo-400 dark:text-indigo-500' : 'text-gray-400 dark:text-gray-500' }} mr-1.5">#</span>
                                    {{ $t }}
                                    
                                    @if($isActive)
                                        <svg class="h-3 w-3 ml-1.5 text-indigo-500 dark:text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                    @endif
                                </button>
                            @endforeach
                        </div>
                    </div>

                    {{-- 2. Le menu déroulant "Tous les tags" --}}
                    <div class="relative shrink-0 pb-1">
                        {{-- 🚀 LE BOUTON A ÉTÉ MIS À JOUR POUR ÊTRE IDENTIQUE À LA HOME PAGE --}}
                        <button type="button" onclick="toggleTagDropdown()" class="shrink-0 whitespace-nowrap inline-flex items-center px-3 py-1.5 rounded-full text-[13px] font-medium bg-gray-100 border border-transparent text-gray-600 hover:bg-gray-200 dark:bg-gray-800/50 dark:text-gray-400 dark:hover:bg-gray-800 dark:border-gray-700 transition-colors duration-200 focus:outline-none shadow-sm">
                            <svg class="w-3.5 h-3.5 mr-1.5 opacity-70" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                            Tous les tags
                        </button>

                        <div id="tag-dropdown-menu" class="hidden absolute right-0 z-50 mt-2 w-56 origin-top-right rounded-xl bg-white dark:bg-gray-800 shadow-lg ring-1 ring-black ring-opacity-5 dark:ring-gray-700 focus:outline-none overflow-hidden transition-all">
                            <div class="p-2 border-b border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50">
                                <input type="text" id="tag-search-input" onkeydown="if(event.key==='Enter') event.preventDefault();" onkeyup="filterDropdownTags(event)" placeholder="Chercher ou créer..." 
                                       class="block w-full rounded-lg border-0 py-1.5 px-3 text-sm text-gray-900 dark:text-white shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-600 focus:ring-2 focus:ring-inset focus:ring-indigo-600 dark:bg-gray-700">
                            </div>
                            
                            <div class="max-h-60 overflow-y-auto custom-scrollbar py-1" id="tag-dropdown-list">
                                @foreach($allTags as $t)
                                    @php $isActive = in_array($t, $selectedTags); @endphp
                                    
                                    <button type="button" onclick="toggleEditorTag('{{ addslashes($t) }}')" data-tag="{{ $t }}"
                                       class="tag-btn tag-dropdown-item w-full flex items-center justify-between px-4 py-2 text-sm transition-colors focus:outline-none {{ $isActive ? 'bg-indigo-50 dark:bg-indigo-900/20 text-indigo-700 dark:text-indigo-400 font-semibold' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                                        
                                        <div class="flex items-center">
                                            <span class="{{ $isActive ? 'text-indigo-400 dark:text-indigo-500' : 'text-gray-400 dark:text-gray-500' }} mr-1.5">#</span>
                                            <span class="tag-name">{{ $t }}</span>
                                        </div>
                                        
                                        @if($isActive)
                                            <svg class="h-4 w-4 text-indigo-600 dark:text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                        @endif
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mb-6">
                <div class="flex justify-between items-center mb-2">
                    <label class="block text-xs font-semibold text-gray-400 uppercase tracking-wider">Contenu</label>
                    
                    <div class="flex bg-gray-100 dark:bg-gray-900 p-1 rounded-xl border border-gray-200 dark:border-gray-700 space-x-1 text-xs transition-colors shadow-inner">
                        <button type="button" id="btn-view-tags" class="px-3 py-1.5 rounded-lg font-semibold transition bg-white dark:bg-gray-700 text-indigo-600 dark:text-indigo-400 shadow-sm">
                            Balises visibles
                        </button>
                        <button type="button" id="btn-view-clean" class="px-3 py-1.5 rounded-lg font-semibold transition text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300">
                            Vue épurée
                        </button>
                        <button type="button" id="btn-view-split" class="px-3 py-1.5 rounded-lg font-semibold transition text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300">
                            Écran scindé
                        </button>
                    </div>
                </div>

                <div id="editor-container" class="bg-white dark:bg-gray-900 rounded-xl"></div>
                <input type="hidden" id="hidden-content" name="content" value="{{ old('content', $document->content ?? '') }}">
            </div>

            <div class="flex justify-between items-center border-t border-gray-100 dark:border-gray-700 pt-5">
                <div>
                    @if($document && ($document->user_id === Auth::id() || in_array('retd', session('keycloak_groups', []))))
                        <button type="button" onclick="confirmDelete()" class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300 text-sm font-medium py-2 px-4 transition">Supprimer</button>
                    @endif
                </div>
                <div class="flex space-x-3">
                    <a href="{{ $cancelUrl }}" class="bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 py-2.5 px-5 rounded-xl text-sm transition">
                        Annuler
                    </a>
                    <button type="submit" id="btn-submit-form" class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2.5 px-6 rounded-xl shadow-sm transition text-sm">
                        {{ $document ? 'Mettre à jour' : 'Enregistrer' }}
                    </button>
                </div>
            </div>
        </form>
    </div>
</main>
@endsection

@section('scripts')
<script src="https://uicdn.toast.com/editor/latest/toastui-editor-all.min.js"></script>
<script src="https://uicdn.toast.com/editor/latest/i18n/fr-fr.min.js"></script>

<script>
    // 🚀 FONCTION POUR AJOUTER/RETIRER UN TAG SANS RECHARGER LA PAGE
    async function toggleEditorTag(tagName) {
        if(!tagName) return;

        const container = document.getElementById('hidden-tags-container');
        // Génère un ID propre même avec des accents ou espaces
        const safeId = 'hidden-tag-' + btoa(unescape(encodeURIComponent(tagName))).replace(/[^a-zA-Z0-9]/g, '');
        let existingInput = document.getElementById(safeId);
        
        let isSelected = false;

        // 1. Ajouter/Retirer du formulaire
        if (existingInput) {
            existingInput.remove(); 
        } else {
            // SÉCURITÉ : VÉRIFICATION DES 10 TAGS MAX
            const currentTagsCount = document.querySelectorAll('input[name="tags[]"]').length;
            if (currentTagsCount >= 10) {
                alert("Vous ne pouvez sélectionner que 10 tags au maximum !");
                return; // On stoppe la fonction ici, le tag ne se coche pas
            }

            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'tags[]';
            input.value = tagName;
            input.id = safeId;
            container.appendChild(input);
            isSelected = true;
        }

        let buttons = document.querySelectorAll(`.tag-btn[data-tag="${tagName}"]`);
        
        // ========================================================
        // CORRECTION : VÉRIFICATION SPÉCIFIQUE POUR LA BARRE DU HAUT
        // ========================================================
        const pillsContainer = document.getElementById('pills-container');
        // On cherche si la pilule existe DANS le container du haut
        let pillExists = pillsContainer.querySelector(`.tag-btn[data-tag="${tagName}"]`);

        // Si la pilule n'existe pas dans la barre du haut et qu'on a cliqué dessus, on la crée de force !
        if (!pillExists && isSelected) {
            const newBtn = document.createElement('button');
            newBtn.type = 'button';
            newBtn.className = 'tag-btn'; // On lui donne juste la classe de base, le script d'en dessous fera le design
            newBtn.setAttribute('data-tag', tagName);
            newBtn.onclick = () => toggleEditorTag(tagName);
            pillsContainer.prepend(newBtn); // On le met tout à gauche directement
            
            // 🔄 Très important : on rafraîchit notre liste de boutons pour inclure le petit nouveau !
            buttons = document.querySelectorAll(`.tag-btn[data-tag="${tagName}"]`);
        }

        // ========================================================
        // 2. Mettre à jour visuellement les boutons
        // ========================================================
        buttons.forEach(btn => {
            
            const isDropdownItem = btn.closest('#tag-dropdown-list') !== null;

            if (isSelected) {
                if (isDropdownItem) {
                    // DESIGN MENU DÉROULANT (SÉLECTIONNÉ)
                    btn.className = 'tag-btn tag-dropdown-item w-full flex items-center justify-between px-4 py-2 text-sm bg-indigo-50 dark:bg-indigo-900/20 text-indigo-700 dark:text-indigo-400 font-semibold transition-colors focus:outline-none';
                    btn.innerHTML = `<div class="flex items-center"><span class="text-indigo-400 dark:text-indigo-500 mr-1.5">#</span><span class="tag-name">${tagName}</span></div> <svg class="h-4 w-4 text-indigo-600 dark:text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>`;
                } else {
                    // DESIGN PILULE HAUT (SÉLECTIONNÉ)
                    btn.className = 'tag-btn shrink-0 whitespace-nowrap inline-flex items-center px-3 py-1.5 rounded-full text-[13px] font-medium border transition-colors duration-200 focus:outline-none tag-selected bg-indigo-100 border-indigo-200 text-indigo-700 hover:bg-indigo-200 dark:bg-indigo-900/60 dark:border-indigo-700/50 dark:text-indigo-300 dark:hover:bg-indigo-900/80';
                    btn.innerHTML = `<span class="text-indigo-400 dark:text-indigo-500 mr-1.5">#</span> ${tagName} <svg class="w-3 h-3 ml-1.5 text-indigo-500 dark:text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>`;
                    
                    const container = btn.parentNode;
                    container.prepend(btn);
                    container.scrollTo({ left: 0, behavior: 'smooth' });
                }
            } else {
                if (isDropdownItem) {
                    // DESIGN MENU DÉROULANT (NON SÉLECTIONNÉ)
                    btn.className = 'tag-btn tag-dropdown-item w-full flex items-center justify-between px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors focus:outline-none';
                    btn.innerHTML = `<div class="flex items-center"><span class="text-gray-400 dark:text-gray-500 mr-1.5">#</span><span class="tag-name">${tagName}</span></div>`;
                } else {
                    // DESIGN PILULE HAUT (NON SÉLECTIONNÉ)
                    btn.className = 'tag-btn shrink-0 whitespace-nowrap inline-flex items-center px-3 py-1.5 rounded-full text-[13px] font-medium border transition-colors duration-200 focus:outline-none tag-suggested bg-white border-gray-200 text-gray-700 hover:bg-gray-50 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-700';
                    btn.innerHTML = `<span class="text-gray-400 dark:text-gray-500 mr-1.5">#</span> ${tagName}`;
                    
                    const container = btn.parentNode;
                    container.appendChild(btn);
                }
            }
        });
        setTimeout(updateEditorTagsVisibility, 50);
    }

    // FONCTION POUR LE MENU DÉROULANT ET LA CRÉATION DE NOUVEAUX TAGS
    function filterDropdownTags(e) {
        let input = document.getElementById('tag-search-input');
        let filter = input.value.toLowerCase().trim();
        let items = document.querySelectorAll('#tag-dropdown-list .tag-btn');

        items.forEach(function(item) {
            let text = item.querySelector('.tag-name').innerText.toLowerCase();
            if (text.includes(filter)) {
                item.style.display = "flex"; 
            } else {
                item.style.display = "none";
            }
        });

        if (e && e.key === 'Enter') {
            e.preventDefault(); 
            if (input.value.trim() !== '') {
                toggleEditorTag(input.value.trim());
                input.value = '';
                filterDropdownTags();
            }
        }
    }

    function toggleTagDropdown() {
        const menu = document.getElementById('tag-dropdown-menu');
        menu.classList.toggle('hidden');
        if(!menu.classList.contains('hidden')) {
            setTimeout(() => document.getElementById('tag-search-input').focus(), 50);
        }
    }

    document.addEventListener('click', function(event) {
        const menu = document.getElementById('tag-dropdown-menu');
        const button = event.target.closest('button[onclick="toggleTagDropdown()"]');
        if (!button && !menu.contains(event.target)) {
            menu.classList.add('hidden');
        }
    });

    document.addEventListener('DOMContentLoaded', function() {
        let isDark = document.documentElement.classList.contains('dark');
        const mainWrapper = document.getElementById('main-wrapper');
        const container = document.getElementById('editor-container');
        
        const editor = new toastui.Editor({
            el: container,
            height: 'auto', 
            initialEditType: 'markdown', 
            previewStyle: 'tab',         
            initialValue: document.getElementById('hidden-content').value,
            language: 'fr-FR',
            theme: isDark ? 'dark' : 'light',
            hideModeSwitch: true, 
            toolbarItems: [
                ['heading', 'bold', 'italic', 'strike'],
                ['hr', 'quote'],
                ['ul', 'ol', 'task', 'indent', 'outdent'],
                ['table', 'link'],
                ['code', 'codeblock']
            ]
        });
        

        if (isDark) {
            document.querySelector('.toastui-editor-defaultUI').classList.add('toastui-editor-dark');
        }

        const btnTags = document.getElementById('btn-view-tags');
        const btnClean = document.getElementById('btn-view-clean');
        const btnSplit = document.getElementById('btn-view-split');

        function resetButtons() {
            [btnTags, btnClean, btnSplit].forEach(btn => {
                btn.className = "px-3 py-1.5 rounded-lg font-semibold transition text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300";
                btn.style.color = '';
            });
        }

        editor.on('change', function() {
            if (mainWrapper.classList.contains('force-tui-split-width')) {
                const previewElement = document.querySelector('.toastui-editor-md-preview .toastui-editor-contents');
                if (previewElement) {
                    let rightSideHeight = previewElement.scrollHeight;
                    let finalHeight = Math.max(500, rightSideHeight + 50); 
                    editor.setHeight(finalHeight + 'px');
                }
            }
        });

        btnTags.addEventListener('click', function() {
            resetButtons();
            btnTags.className = "px-3 py-1.5 rounded-lg font-semibold transition bg-white dark:bg-gray-700 shadow-sm";
            btnTags.style.color = '{{ $colorLight }}'; 
            
            mainWrapper.classList.remove('force-tui-split-width');
            editor.setHeight('auto'); 
            editor.changeMode('markdown');
            editor.changePreviewStyle('tab'); 
        });

        btnClean.addEventListener('click', function() {
            resetButtons();
            btnClean.className = "px-3 py-1.5 rounded-lg font-semibold transition bg-white dark:bg-gray-700 shadow-sm";
            btnClean.style.color = '{{ $colorLight }}'; 
            
            mainWrapper.classList.remove('force-tui-split-width');
            editor.setHeight('auto'); 
            editor.changeMode('wysiwyg'); 
        });

        btnSplit.addEventListener('click', function() {
            resetButtons();
            btnSplit.className = "px-3 py-1.5 rounded-lg font-semibold transition bg-white dark:bg-gray-700 shadow-sm";
            btnSplit.style.color = '{{ $colorLight }}'; 
            
            mainWrapper.classList.add('force-tui-split-width');
            editor.changeMode('markdown');
            editor.changePreviewStyle('vertical');
            
            setTimeout(() => { 
                const previewElement = document.querySelector('.toastui-editor-md-preview .toastui-editor-contents');
                if (previewElement) {
                    let rightSideHeight = previewElement.scrollHeight;
                    let finalHeight = Math.max(500, rightSideHeight + 50);
                    editor.setHeight(finalHeight + 'px');
                }
                window.dispatchEvent(new Event('resize')); 
            }, 50);
        });

        window.addEventListener('theme-changed', function(e) {
            let isDarkTheme = e.detail.theme === 'dark';
            const editorUI = document.querySelector('.toastui-editor-defaultUI');
            
            if (editorUI) {
                if (isDarkTheme) {
                    editorUI.classList.add('toastui-editor-dark');
                } else {
                    editorUI.classList.remove('toastui-editor-dark');
                }
            }
        });
        
        document.getElementById('document-form').addEventListener('submit', function(e) {
            document.getElementById('hidden-content').value = editor.getMarkdown();

            const submitBtn = document.getElementById('btn-submit-form');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
                submitBtn.innerHTML = "Enregistrement en cours...";
            }
        });
    });
</script>

@if($document)
    <form id="delete-form" action="{{ route('documents.destroy', $document->id) }}" method="POST" class="hidden">
        @csrf @method('DELETE')
    </form>
    <script>
        function confirmDelete() {
            if (confirm("Supprimer définitivement ce document ?")) {
                document.getElementById('delete-form').submit();
            }
        }
    </script>
@endif
@endsection