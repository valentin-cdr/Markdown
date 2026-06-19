@php
    $userGroups = [];

    if (auth()->check()) {
        // 1. SYSTÈME DE DEBUG : Forçage temporaire via l'URL (ex: ?group=onAir)
        if (request()->has('group')) {
            $userGroups = [request()->query('group')];
            // On met à jour la session pour que la couleur reste en naviguant
            session(['keycloak_groups' => $userGroups]); 
        } 
        // 2. LECTURE DE LA SESSION : 
        // Fonctionne pour le vrai SSO Keycloak ET pour tes routes locales (/dev/login-test)
        elseif (!empty(session('keycloak_groups'))) {
            $userGroups = (array) session('keycloak_groups');
        }
    }

    // 2. Récupération dynamique depuis la base de données (avec Cache d'1 heure pour les performances)
    $groupBrandConfig = \Illuminate\Support\Facades\Cache::remember('groups_config', 3600, function () {
        // Transforme les résultats de la BDD pour qu'ils aient la même structure que ton ancien tableau
        return \App\Models\Group::all()->keyBy('key')->toArray();
    });

    // 3. Résolution du groupe actif pour l'affichage
    $navGroupBrand = null;
    
    // On compare les groupes de l'utilisateur avec notre configuration de couleurs
    $matchingGroups = array_intersect($userGroups, array_keys($groupBrandConfig));

    if (!empty($matchingGroups)) {
        // S'il a plusieurs groupes valides, on prend le premier qui correspond
        $firstMatch = reset($matchingGroups);
        $navGroupBrand = $groupBrandConfig[$firstMatch];
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

    <script>
        // 1. On récupère les données de la base de données
        const dbTheme = '{{ $navGroupBrand['theme'] ?? 'orange' }}';
        const scrollLight = '{{ $navGroupBrand['scroll_light'] ?? '#f97316' }}'; // Couleur claire par défaut (orange)
        const scrollDark = '{{ $navGroupBrand['scroll_dark'] ?? '#ea580c' }}';   // Couleur sombre par défaut

        let colorPalette;

        // 2. Si le thème est "custom", on génère une palette Tailwind dynamique avec la couleur choisie !
        if (dbTheme === 'custom') {
            colorPalette = {
                50: scrollLight,
                100: scrollLight,
                200: scrollLight,
                300: scrollLight,
                400: scrollLight,
                50: scrollLight,
                500: scrollLight, // Couleur principale utilisée par bg-indigo-600 ou text-indigo-600
                600: scrollLight, // Couleur principale (hover)
                700: scrollDark,
                800: scrollDark,
                900: scrollDark,
                950: scrollDark
            };
        } else {
            // Sinon, on prend la palette classique (blue, amber, emerald, etc.)
            colorPalette = tailwind.colors[dbTheme];
        }

        // 3. Sécurité ultime : si toujours rien (ex: bug BDD), on force l'orange de Tailwind
        if (!colorPalette) {
            colorPalette = tailwind.colors.orange;
        }

        // 4. On injecte la palette finale dans la configuration globale
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
        /* Variables CSS dynamiques pour les scrollbars */
        :root {
            --scrollbar-thumb-light: {{ $navGroupBrand['scroll_light'] ?? '#6366f1' }};
            --scrollbar-thumb-dark: {{ $navGroupBrand['scroll_dark'] ?? '#818cf8' }};
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
    </style>

    @yield('styles')
    
</head>
<body class="bg-gray-50 dark:bg-gray-900 text-gray-800 dark:text-gray-100 font-sans antialiased min-h-screen flex flex-col transition-colors duration-200">

    <nav class="bg-white dark:bg-gray-800 shadow-sm border-b border-gray-200 dark:border-gray-700 py-3 px-6 flex justify-between items-center h-16 shrink-0 transition-colors duration-200">
        <div class="flex items-center space-x-4">
            
            <a href="{{ route('home') }}" class="flex items-center space-x-3 group">
                @if($navGroupBrand)
                    {{-- LE GRADIENT EST MAINTENANT DYNAMIQUE SELON LE GROUPE --}}
                    <span class="text-2xl md:text-3xl font-serif text-transparent bg-clip-text bg-gradient-to-br {{ $navGroupBrand['gradient'] }} tracking-widest italic select-none transition-all duration-300 group-hover:scale-105" 
                          style="font-family: 'Playfair Display', serif;">
                        {{ $navGroupBrand['name'] }}
                    </span>
                    
                    <span class="text-gray-300 dark:text-gray-600 font-light text-xl">|</span>
                @else
                    {{-- Icône par défaut si aucun groupe --}}
                    <svg class="h-6 w-6 text-indigo-600 dark:text-indigo-400 transform transition-transform group-hover:scale-110" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                @endif
                
                <h1 class="text-lg font-bold text-gray-900 dark:text-white uppercase tracking-wider">Glossaire</h1>
            </a>
            @if(in_array('retd', session('keycloak_groups', [])))
                <a href="{{ route('settings.index') }}" 
                    class="p-2 text-gray-500 rounded-lg hover:text-gray-900 hover:bg-gray-100 dark:text-gray-400 dark:hover:text-white dark:hover:bg-gray-700 transition-colors"
                    title="Configuration des groupes">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                    </a>
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
                <form action="{{ route('logout') }}" method="POST" class="mb-0">
                    @csrf
                    <button type="submit" class="text-xs text-gray-500 dark:text-gray-400 hover:text-red-600 dark:hover:text-red-400 transition font-medium">Déconnexion</button>
                </form>
            @endauth
        </div>
    </nav>

    @yield('content')

    <script>
        var themeToggleDarkIcon = document.getElementById('theme-toggle-dark-icon');
        var themeToggleLightIcon = document.getElementById('theme-toggle-light-icon');

        // Affiche la bonne icône au chargement
        if (document.documentElement.classList.contains('dark')) {
            themeToggleLightIcon.classList.remove('hidden');
        } else {
            themeToggleDarkIcon.classList.remove('hidden');
        }

        var themeToggleBtn = document.getElementById('theme-toggle');

        themeToggleBtn.addEventListener('click', function() {
            // Active les transitions sur TOUS les éléments
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

            // Retire la classe après l'animation (300ms > 200ms pour laisser finir)
            setTimeout(() => document.documentElement.classList.remove('theme-transitioning'), 300);
        });

        // 🎨 FONCTION POUR METTRE À JOUR LES COULEURS
        function updateThemeColors(theme) {
            const themeColor = theme === 'dark' ? '#374151' : '#ffffff';
            const defaultColors = ['#ffffff', '#374151']; // Les couleurs par défaut de tes thèmes

            const inputIds = [
                'create-scroll-light', 
                'create-scroll-dark', 
                'edit-scroll-light', 
                'edit-scroll-dark'
            ];

            inputIds.forEach(id => {
                const input = document.getElementById(id);
                if (input) {
                    // On met à jour SEULEMENT si la couleur actuelle est une couleur par défaut
                    // (Si l'utilisateur a cliqué et choisi du rouge, on ne l'écrase pas !)
                    if (defaultColors.includes(input.value.toLowerCase())) {
                        input.value = themeColor;
                    }
                }
            });
        }

        // 1. On applique la bonne couleur au chargement de la page
        const initialTheme = document.documentElement.classList.contains('dark') ? 'dark' : 'light';
        updateThemeColors(initialTheme);

        // 2. On écoute ton événement personnalisé quand tu cliques sur le bouton
        window.addEventListener('theme-changed', function(e) {
            updateThemeColors(e.detail.theme);
        });
    </script>
    
    @yield('scripts')
    @stack('scripts')
</body>
</html>