<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Markdown Workspace')</title>
    
    <!-- ON CHARGE TAILWIND EN PREMIER -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- ON LE CONFIGURE ENSUITE POUR FORCER LE MODE MANUEL -->
    <script>
        tailwind.config = {
            darkMode: 'class', // Force Tailwind à écouter la classe 'dark' sur <html>
        }
    </script>

    <!-- GESTION DU CLIGNOTEMENT AU CHARGEMENT -->
    <script>
        if (localStorage.getItem('color-theme') === 'dark' || (!('color-theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>

    <style>
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

    <!-- BARRE DE NAVIGATION -->
    <nav class="bg-white dark:bg-gray-800 shadow-sm border-b border-gray-200 dark:border-gray-700 py-3 px-6 flex justify-between items-center h-16 shrink-0 transition-colors duration-200">
        <div class="flex items-center space-x-4">
            <a href="{{ route('home') }}" class="flex items-center space-x-2 group">
                <svg class="h-6 w-6 text-indigo-600 dark:text-indigo-400 transform transition-transform group-hover:scale-110" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
                <h1 class="text-lg font-bold text-gray-900 dark:text-white">Workspace</h1>
            </a>
            @yield('header-extra')
        </div>
        
        <div class="flex items-center space-x-4">
            <!-- BOUTON THEME SOMBRE / CLAIR AVEC NOUVELLES ICÔNES HEROICONS -->
            <button id="theme-toggle" type="button" class="text-gray-500 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none rounded-xl text-sm p-2 transition-all duration-300">
                
                <!-- Icône Lune (affichée en mode clair) -->
                <svg id="theme-toggle-dark-icon" class="hidden w-5 h-5 transition-transform duration-300 hover:-rotate-12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                </svg>
                
                <!-- Icône Soleil (affichée en mode sombre) -->
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

    <!-- CONTENU INJECTÉ -->
    @yield('content')

    <!-- LOGIQUE JS DU BOUTON SOMBRE/CLAIR -->
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
        // ✅ Active les transitions sur TOUS les éléments
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

        // ✅ Retire la classe après l'animation (300ms > 200ms pour laisser finir)
        setTimeout(() => document.documentElement.classList.remove('theme-transitioning'), 300);
    });
    </script>
    
    @yield('scripts')
    @stack('scripts')
</body>
</html>