@php
    $userGroups = [];

    // LECTURE STRICTE DE LA SESSION KEYCLOAK (Production)
    if (auth()->check() && !empty(session('keycloak_groups'))) {
        $userGroups = (array) session('keycloak_groups');
    }

    // Récupération dynamique depuis la base de données (avec Cache d'1 heure pour les performances)
    $groupBrandConfig = \Illuminate\Support\Facades\Cache::remember('groups_config', 3600, function () {
        return \App\Models\Group::all()->keyBy('key')->toArray();
    });

    // Résolution du groupe actif pour l'affichage
    $navGroupBrand = null;
    
    // On compare les groupes de l'utilisateur avec notre configuration de couleurs
    $matchingGroups = array_intersect($userGroups, array_keys($groupBrandConfig));

    if (!empty($matchingGroups)) {
        $firstMatch = reset($matchingGroups);
        $navGroupBrand = $groupBrandConfig[$firstMatch];
    }

    // Sécurisation des droits pour le menu global
    $isAdmin = in_array('retd', $userGroups); // On vérifie si l'utilisateur a le rôle admin
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
                <a href="{{ route('settings.index') }}" class="flex items-center gap-3 px-4 py-2 mx-2 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 hover:text-indigo-600 dark:hover:text-indigo-400 transition">
                    <svg class="w-5 h-5 opacity-70 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    Configuration Groupes
                </a>
                <div class="h-px bg-gray-100 dark:bg-gray-700 my-1 mx-4"></div>
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
            
            <a href="{{ route('home') }}" class="flex items-center space-x-3 group">
                @if($navGroupBrand)
                    {{-- FIX PROD : Rendu du dégradé en CSS inline natif à partir de la BDD pour éviter le blocage du compilateur Tailwind --}}
                    <span class="text-2xl md:text-3xl font-serif text-transparent bg-clip-text tracking-widest italic select-none transition-all duration-300 group-hover:scale-105" 
                          style="font-family: 'Playfair Display', serif; background-image: linear-gradient(to bottom right, {{ $navGroupBrand['scroll_light'] }}, {{ $navGroupBrand['scroll_dark'] }});">
                        {{ $navGroupBrand['name'] }}
                    </span>
                    <span class="text-gray-300 dark:text-gray-600 font-light text-xl">|</span>
                @else
                    <svg class="h-6 w-6 text-indigo-600 dark:text-indigo-400 transform transition-transform group-hover:scale-110" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                @endif
                
                <h1 class="text-lg font-bold text-gray-900 dark:text-white uppercase tracking-wider">Glossaire</h1>
            </a>

            {{-- Le bouton de configuration classique est retiré d'ici puisqu'il est maintenant dans le menu Burger --}}
            
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