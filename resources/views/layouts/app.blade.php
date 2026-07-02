@php
    $userGroups = [];

    // 1. LECTURE STRICTE DE LA SESSION KEYCLOAK
    if (auth()->check() && !empty(session('keycloak_groups'))) {
        $userGroups = (array) session('keycloak_groups');
    }

    // 2. Sécurisation : Est-ce un super admin ?
    $isAdmin = in_array('retd', $userGroups); 

    // 3. INTERCEPTION DU SÉLECTEUR D'ENVIRONNEMENT (Seulement si admin)
    // Si l'admin a sélectionné une franchise dans le menu, on l'enregistre dans la session
    if ($isAdmin && request()->has('group')) {
        $requestedGroup = request()->query('group');
        if (empty($requestedGroup)) {
            session()->forget('admin_forced_group');
        } else {
            session(['admin_forced_group' => $requestedGroup]);
        }
    }

    // 4. Récupération dynamique depuis la base de données
    $groupBrandConfig = \Illuminate\Support\Facades\Cache::remember('groups_config', 3600, function () {
        return \App\Models\Group::all()->keyBy('key')->toArray();
    });

    // 5. RÉSOLUTION DU GROUPE ACTIF POUR L'AFFICHAGE
    $navGroupBrand = null;
    $currentGroupKey = null; // Utilisé pour présélectionner le bon élément dans le <select>
    
    // Si l'admin a forcé un groupe, c'est lui qui gagne
    if ($isAdmin && session()->has('admin_forced_group')) {
        $forcedKey = session('admin_forced_group');
        if (isset($groupBrandConfig[$forcedKey])) {
            $navGroupBrand = $groupBrandConfig[$forcedKey];
            $currentGroupKey = $forcedKey;
        }
    } else {
        // Sinon, on compare les groupes de l'utilisateur avec la configuration de la BDD
        $matchingGroups = array_intersect($userGroups, array_keys($groupBrandConfig));
        if (!empty($matchingGroups)) {
            $firstMatch = reset($matchingGroups);
            $navGroupBrand = $groupBrandConfig[$firstMatch];
            $currentGroupKey = $firstMatch;
        }
    }
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Glossaire')</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet">

    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Ajout d'Alpine.js pour faire fonctionner le menu burger -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <script>
        // Couleurs de la franchise/groupe actif (avec valeurs de repli si aucun groupe ne correspond)
        const scrollLight = '{{ $navGroupBrand['scroll_light'] ?? '#f97316' }}';
        const scrollDark  = '{{ $navGroupBrand['scroll_dark']  ?? '#ea580c' }}';

        function hexToRgb(hex) {
            hex = hex.replace('#', '');
            if (hex.length === 3) hex = hex.split('').map(c => c + c).join('');
            const num = parseInt(hex, 16);
            return { r: (num >> 16) & 255, g: (num >> 8) & 255, b: num & 255 };
        }
        function rgbToHex({ r, g, b }) {
            return '#' + [r, g, b].map(v => Math.max(0, Math.min(255, Math.round(v))).toString(16).padStart(2, '0')).join('');
        }
        function mix(c1, c2, ratio) {
            return {
                r: c1.r + (c2.r - c1.r) * ratio,
                g: c1.g + (c2.g - c1.g) * ratio,
                b: c1.b + (c2.b - c1.b) * ratio
            };
        }

        const baseLight = hexToRgb(scrollLight);
        const baseDark  = hexToRgb(scrollDark);
        const white = { r: 255, g: 255, b: 255 };
        const black = { r: 0, g: 0, b: 0 };

        const colorPalette = {
            50:  rgbToHex(mix(white, baseLight, 0.12)),
            100: rgbToHex(mix(white, baseLight, 0.25)),
            200: rgbToHex(mix(white, baseLight, 0.45)),
            300: rgbToHex(mix(white, baseLight, 0.65)),
            400: rgbToHex(mix(white, baseLight, 0.85)),
            500: scrollLight,
            600: rgbToHex(mix(baseLight, baseDark, 0.5)),
            700: scrollDark,
            800: rgbToHex(mix(baseDark, black, 0.25)),
            900: rgbToHex(mix(baseDark, black, 0.5)),
            950: rgbToHex(mix(baseDark, black, 0.75))
        };

        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        indigo: colorPalette,
                        purple: colorPalette,
                        violet: colorPalette
                    }
                }
            }
        }
    </script>

    <script>
        if (localStorage.getItem('color-theme') === 'dark' || (!('color-theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>

    <style>
        /* Variables CSS fixes pour les scrollbars (indépendantes du groupe) */
        :root {
            --scrollbar-thumb-light: #cbd5e1; /* Gris clair (slate-300) */
            --scrollbar-thumb-dark: #475569;  /* Gris foncé (slate-600) */
        }

        /* Personnalisation de la barre de défilement */
        ::-webkit-scrollbar {
            width: 12px;
            height: 12px;
        }
        ::-webkit-scrollbar-track {
            background: transparent;
        }
        ::-webkit-scrollbar-thumb {
            background-color: var(--scrollbar-thumb-light);
            border-radius: 6px;
            border: 3px solid #f9fafb; /* bg-gray-50 */
        }
        .dark ::-webkit-scrollbar-thumb {
            background-color: var(--scrollbar-thumb-dark);
            border: 3px solid #111827; /* dark:bg-gray-900 */
        }

        /* Transitions douces pour le dark mode */
        .theme-transitioning,
        .theme-transitioning * {
            transition-property: background-color, color, border-color, fill, stroke !important;
            transition-duration: 200ms !important;
            transition-timing-function: ease !important;
        }

        /* Nécessaire pour éviter le clignotement d'Alpine.js au chargement */
        [x-cloak] { display: none !important; }
    </style>

    @yield('styles')
    
</head>
<body class="bg-gray-50 dark:bg-gray-900 text-gray-800 dark:text-gray-100 font-sans antialiased min-h-screen flex flex-col transition-colors duration-200 relative">

    {{-- ── MENU BURGER FLOTTANT ── --}}
    <div x-data="{ menuOpen: false }" class="fixed top-1.5 left-2 z-[100]">
        {{-- Bouton d'ouverture/fermeture --}}
        <button @click="menuOpen = !menuOpen" 
                class="flex items-center justify-center p-2.5 rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow-sm hover:text-indigo-600 dark:hover:text-indigo-400 hover:border-indigo-500 transition-all focus:outline-none">
            <svg x-show="!menuOpen" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
            <svg x-show="menuOpen" x-cloak class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>

        {{-- Contenu du menu déroulant --}}
        <div x-show="menuOpen"
            @click.outside="menuOpen = false" 
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 -translate-y-2"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 -translate-y-2"
            x-cloak 
            class="absolute top-14 left-0 w-64 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl shadow-xl py-3 flex flex-col gap-1">
            
            {{-- LIEN 0 : Retour à l'accueil (Portail) --}}
            <a href="https://bo-preprod.retdnetworks.com/home" class="flex items-center gap-3 px-4 py-2 mx-2 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 hover:text-indigo-600 dark:hover:text-indigo-400 transition">
                <svg class="w-5 h-5 opacity-70 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
                Accueil Portail
            </a>

            <div class="h-px bg-gray-100 dark:bg-gray-700 my-1 mx-4"></div>

            {{-- LIEN 1 : Configuration (Admins uniquement) --}}
            @if($isAdmin)
                <a href="https://bo-preprod.retdnetworks.com/setup" class="flex items-center gap-3 px-4 py-2 mx-2 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 hover:text-indigo-600 dark:hover:text-indigo-400 transition">
                    <svg class="w-5 h-5 opacity-70 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    Configuration Groupes
                </a>
                <div class="h-px bg-gray-100 dark:bg-gray-700 my-1 mx-4"></div>
            @endif

            @if($canSeePilotage || $canSeeSuperset)
                <a href="{{ $supersetUrl ?? '#' }}" class="flex items-center gap-3 px-4 py-2 mx-2 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-[#2a2a28] hover:text-[var(--brand-primary)] transition">
                    <svg class="w-5 h-5 opacity-70" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                    Pilotage Réseau
                </a>
            @endif

            @if($canSeeGestionClub)
                <a href="https://bo-preprod.retdnetworks.com/dashboard" class="flex items-center gap-3 px-4 py-2 mx-2 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-[#2a2a28] hover:text-[var(--brand-primary)] transition">
                    <svg class="w-5 h-5 opacity-70" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                    Back Office
                </a>
            @endif

            @if($canSeeIA)
                <a href="https://bo-preprod.retdnetworks.com/assistant" class="flex items-center gap-3 px-4 py-2 mx-2 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-[#2a2a28] hover:text-[var(--brand-primary)] transition">
                    <svg class="w-5 h-5 opacity-70" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 00-2.456 2.456z" /></svg>
                    Assistant IA
                </a>
            @endif

            @if($canSeeDolibarr)
                <a href="{{ $dolibarrUrl ?? '#' }}" class="flex items-center gap-3 px-4 py-2 mx-2 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-[#2a2a28] hover:text-[var(--brand-primary)] transition">
                    <svg class="w-5 h-5 opacity-70" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                    Dolibarr
                </a>
            @endif
            
            @if ($canSeeGlossaire)
                <a href="{{ route('home') }}" target="_blank" class="flex items-center gap-3 px-4 py-2 mx-2 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-[#2a2a28] hover:text-[var(--brand-primary)] transition">
                    <svg class="w-5 h-5 opacity-70" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16h8M8 12h8m-8-4h8m-6 8h.01M12 20h.01M16 16h.01M16 12h.01M16 8h.01M12 4h.01M8 8h.01M8 12h.01M8 16h.01M12 20h.01" />
                    </svg>
                    Glossaire
                </a>
            @endif

            {{-- LIEN 8 : Déconnexion --}}
            <form method="POST" action="{{ route('logout') }}" class="flex flex-col m-0">
                @csrf
                <button type="submit" class="flex items-center text-left gap-3 px-4 py-2 mx-2 rounded-lg text-sm font-medium text-red-500 hover:bg-red-50 dark:hover:bg-red-500/10 transition cursor-pointer">
                    <svg class="w-5 h-5 opacity-70 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                    Déconnexion
                </button>
            </form>
        </div>
    </div>

    {{-- Modification ici: pl-14 pour faire de la place au menu burger flottant sur la gauche --}}
    <nav class="bg-white dark:bg-gray-800 shadow-sm border-b border-gray-200 dark:border-gray-700 py-3 pr-6 pl-16 flex justify-between items-center h-16 shrink-0 transition-colors duration-200">
        <div class="flex items-center space-x-4">
            
            {{-- 🛠️ LOGO DYNAMIQUE PAR ENVIRONNEMENT & THÈME (Correction RETD) --}}
            <a href="{{ route('home') }}" class="flex items-center space-x-3 group shrink-0">
                
                <div class="h-8 md:h-10 w-auto flex items-center">
                    {{-- On vérifie si on a un groupe actif ET que ce n'est pas le groupe technique 'retd' --}}
                    @if($currentGroupKey && $currentGroupKey !== 'retd')
                        
                        {{-- 🏢 SI ON EST DANS UNE FRANCHISE (ex: ONAIR, Rituel...) --}}
                        <img src="{{ asset('images/' . ($navGroupBrand['name'] ?? '') . '.png') }}" 
                             alt="Logo {{ $navGroupBrand['name'] ?? '' }}" 
                             class="h-full w-auto object-contain transition-all duration-300 group-hover:scale-105"
                             onerror="this.style.display='none'">
                             
                    @else
                        
                        {{-- 🧪 SI ON EST EN MODE GLOBAL / R&D (Clé vide ou égale à 'retd') --}}
                        {{-- 1. Image NOIRE (Affichée en thème CLAIR, cachée en thème sombre) --}}
                        <img src="{{ asset('images/retd_noir.png') }}" 
                             alt="Logo RETD (Clair)" 
                             class="h-full w-auto object-contain transition-all duration-300 group-hover:scale-105 inline dark:hidden">

                        {{-- 2. Image BLANCHE (Cachée en thème clair, affichée en thème sombre) --}}
                        <img src="{{ asset('images/retd_blanc.png') }}" 
                             alt="Logo RETD (Sombre)" 
                             class="h-full w-auto object-contain transition-all duration-300 group-hover:scale-105 hidden dark:inline">
                             
                    @endif
                </div>
                
                <span class="text-gray-300 dark:text-gray-600 font-light text-xl">|</span>
                
                <h1 class="text-lg font-bold text-gray-900 dark:text-white uppercase tracking-wider">Dashboard</h1>
            </a>

            {{-- 🛠️ LE SÉLECTEUR DE WORKSPACE (Composant Alpine.js - Couleurs 100% natives du site) --}}
            @if($isAdmin && request()->routeIs('home'))
                @php 
                    $allGroups = \App\Models\Group::where('key', '!=', 'retd')->orderBy('name')->get(); 
                    $activeColor = $currentGroupKey ? ($navGroupBrand['scroll_light'] ?? '#f97316') : '#f97316';
                @endphp
                
                <div x-data="{ envOpen: false }" class="relative ml-4 z-[90]">
                    
                    {{-- LE BOUTON (Utilise les vrais bg-gray-800 et border-gray-700 de ton site) --}}
                    <button @click="envOpen = !envOpen" 
                            class="flex items-center gap-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-800 dark:text-white text-xs font-bold rounded-full px-3 py-1.5 focus:outline-none transition-colors hover:bg-gray-50 dark:hover:bg-gray-700 shadow-sm dark:shadow-none">
                        
                        {{-- Point de couleur actif --}}
                        <div class="w-2 h-2 rounded-full shrink-0" style="background-color: {{ $activeColor }}; box-shadow: 0 0 6px {{ $activeColor }}80;"></div>
                        
                        {{-- Texte --}}
                        <span class="tracking-wide">
                            {{ $currentGroupKey ? ($navGroupBrand['name'] ?? 'Inconnu') : 'Réseau Global' }}
                        </span>
                        
                        {{-- Chevron --}}
                        <svg class="w-3.5 h-3.5 text-gray-400 transition-transform duration-200" :class="envOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    {{-- LE MENU DÉROULANT --}}
                    <div x-show="envOpen" 
                         @click.outside="envOpen = false"
                         x-transition:enter="transition ease-out duration-150"
                         x-transition:enter-start="transform opacity-0 scale-95 -translate-y-2"
                         x-transition:enter-end="transform opacity-100 scale-100 translate-y-0"
                         x-transition:leave="transition ease-in duration-100"
                         x-transition:leave-start="transform opacity-100 scale-100"
                         x-transition:leave-end="transform opacity-0 scale-95"
                         x-cloak
                         class="absolute left-0 mt-3 w-72 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl shadow-xl dark:shadow-2xl flex flex-col py-2">
                        
                        {{-- En-tête --}}
                        <div class="px-4 py-2">
                            <span class="text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest">
                                Changer d'environnement
                            </span>
                        </div>

                        {{-- Option 1 : Réseau Global (Annule le filtre) --}}
                        <a href="?group=" 
                           class="flex items-center justify-between px-4 py-2.5 mx-2 rounded-xl text-sm transition-colors {{ (!$currentGroupKey || $currentGroupKey === 'retd') ? 'bg-gray-100 dark:bg-gray-700' : 'hover:bg-gray-50 dark:hover:bg-gray-700/50' }}">
                            <div class="flex items-center gap-3">
                                {{-- Point jaune/orange --}}
                                <div class="w-2 h-2 rounded-full shrink-0" style="background-color: #f97316; box-shadow: 0 0 6px #f9731680;"></div>
                                
                                {{-- Nom et Logo adaptatif --}}
                                <span class="font-medium flex items-center gap-2 {{ (!$currentGroupKey || $currentGroupKey === 'retd') ? 'text-[#f97316]' : 'text-gray-700 dark:text-gray-300' }}">
                                    
                                    {{-- Image NOIRE (Thème clair) --}}
                                    <img src="{{ asset('images/retd_noir.png') }}" 
                                         alt="R&D" 
                                         class="w-4 h-4 object-contain shrink-0 inline dark:hidden">
                                         
                                    {{-- Image BLANCHE (Thème sombre) --}}
                                    <img src="{{ asset('images/retd_blanc.png') }}" 
                                         alt="R&D" 
                                         class="w-4 h-4 object-contain shrink-0 hidden dark:inline">
                                         
                                    Réseau Global (R&D)
                                </span>
                            </div>
                            
                            {{-- Checkmark --}}
                            @if(!$currentGroupKey || $currentGroupKey === 'retd')
                                <svg class="w-4 h-4 text-[#f97316] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                            @endif
                        </a>

                        {{-- Séparateur utilisant les bordures natives --}}
                        <div class="h-px bg-gray-200 dark:bg-gray-700 my-1 mx-4"></div>

                        {{-- Options : Les franchises --}}
                        <div class="max-h-60 overflow-y-auto py-1">
                            @foreach($allGroups as $g)
                                <a href="?group={{ $g->key }}" 
                                   class="flex items-center justify-between px-4 py-2.5 mx-2 rounded-xl text-sm transition-colors {{ $currentGroupKey === $g->key ? 'bg-gray-100 dark:bg-gray-700' : 'hover:bg-gray-50 dark:hover:bg-gray-700/50' }}">
                                    <div class="flex items-center gap-3">
                                        {{-- Point coloré --}}
                                        <div class="w-2 h-2 rounded-full shrink-0" style="background-color: {{ $g->scroll_light }}; box-shadow: 0 0 6px {{ $g->scroll_light }}80;"></div>
                                        
                                        {{-- Logo et Nom --}}
                                        <span class="font-medium flex items-center gap-2 {{ $currentGroupKey === $g->key ? '' : 'text-gray-700 dark:text-gray-300' }}"
                                              style="{{ $currentGroupKey === $g->key ? 'color: ' . $g->scroll_light . ';' : '' }}">
                                            
                                            <img src="{{ asset('images/' . $g->name . '.png') }}" 
                                                 alt="" 
                                                 class="w-4 h-4 object-contain shrink-0"
                                                 onerror="this.style.display='none'"> 
                                            
                                            {{ $g->name }}
                                        </span>
                                    </div>

                                    {{-- Checkmark --}}
                                    @if($currentGroupKey === $g->key)
                                        <svg class="w-4 h-4 shrink-0" style="color: {{ $g->scroll_light }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                    @endif
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            @yield('header-extra')
        </div>
        
        <div class="flex items-center space-x-4">
            <button id="theme-toggle" type="button" class="text-gray-500 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none rounded-xl text-sm p-2 transition-all duration-300">
                
                <svg id="theme-toggle-dark-icon" class="hidden w-5 h-5 transition-transform duration-300 hover:-rotate-12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                </svg>
                
                <svg id="theme-toggle-light-icon" class="hidden w-5 h-5 transition-transform duration-300 hover:rotate-90" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
                
            </button>

            @auth
                <p class="text-sm font-semibold text-gray-700 dark:text-gray-300 hidden sm:block">{{ Auth::user()->name }}</p>
            @endauth
        </div>
    </nav>

    @yield('content')

    <script>
        var themeToggleDarkIcon = document.getElementById('theme-toggle-dark-icon');
        var themeToggleLightIcon = document.getElementById('theme-toggle-light-icon');

        if (document.documentElement.classList.contains('dark')) {
            themeToggleLightIcon.classList.remove('hidden');
        } else {
            themeToggleDarkIcon.classList.remove('hidden');
        }

        var themeToggleBtn = document.getElementById('theme-toggle');

        themeToggleBtn.addEventListener('click', function() {
            document.documentElement.classList.add('theme-transitioning');

            themeToggleDarkIcon.classList.toggle('hidden');
            themeToggleLightIcon.classList.toggle('hidden');

            if (document.documentElement.classList.contains('dark')) {
                document.documentElement.classList.remove('dark');
                localStorage.setItem('color-theme', 'light');
                window.dispatchEvent(new CustomEvent('theme-changed', { detail: { theme: 'light' } }));
            } else {
                document.documentElement.classList.add('dark');
                localStorage.setItem('color-theme', 'dark');
                window.dispatchEvent(new CustomEvent('theme-changed', { detail: { theme: 'dark' } }));
            }

            setTimeout(() => document.documentElement.classList.remove('theme-transitioning'), 300);
        });
    </script>
    
    @yield('scripts')
    @stack('scripts')
</body>
</html>