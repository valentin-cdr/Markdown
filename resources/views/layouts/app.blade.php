<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Dashboard')</title>

    {{-- 🚀 FAVICON DYNAMIQUE (Avec Anti-Cache pour forcer l'affichage) --}}
    @if($currentGroupKey && $currentGroupKey !== 'retd' && isset($navGroupBrand['key']))
        <link rel="icon" type="image/png" href="{{ asset('images/' . $navGroupBrand['key'] . '.png') }}?v={{ time() }}">
    @else
        <link rel="icon" type="image/png" href="{{ asset('images/retd_blanc.png') }}?v={{ time() }}">
    @endif

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet">

    <script src="https://cdn.tailwindcss.com"></script>
    
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
                        violet: colorPalette,
                        // 🚀 COULEURS THÈME SOMBRE DU GLOSSAIRE
                        glossary: {
                            base: '#0F0F0F',
                            card: '#1A1A1A',
                            border: '#2A2A2A',
                            muted: '#8B99A8',
                            accent: '#F05522'
                        }
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
        :root {
            --scrollbar-thumb-light: #cbd5e1; 
            --scrollbar-thumb-dark: #2A2A2A; 
            --brand-primary: {{ $navGroupBrand['scroll_light'] ?? '#f97316' }};
            --brand-dark: {{ $navGroupBrand['scroll_dark'] ?? '#ea580c' }};
        }

        ::-webkit-scrollbar { width: 12px; height: 12px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background-color: var(--scrollbar-thumb-light); border-radius: 6px; border: 3px solid #f9fafb; }
        .dark ::-webkit-scrollbar-thumb { background-color: var(--scrollbar-thumb-dark); border: 3px solid #0F0F0F; }

        .theme-transitioning,
        .theme-transitioning * {
            transition-property: background-color, color, border-color, fill, stroke !important;
            transition-duration: 200ms !important;
            transition-timing-function: ease !important;
        }

        [x-cloak] { display: none !important; }
    </style>

    @yield('styles')
    
</head>
<body class="bg-gray-50 dark:bg-glossary-base text-gray-800 dark:text-white font-sans antialiased min-h-screen flex flex-col transition-colors duration-200 relative">

    {{-- ── MENU BURGER FLOTTANT (Haut Gauche) ── --}}
    <div x-data="{ open: false }" class="fixed top-3 left-2 z-50">
        <button @click="open = !open" 
                class="p-2.5 rounded-xl bg-white dark:bg-glossary-card border border-gray-200 dark:border-glossary-border shadow-sm text-gray-700 dark:text-white hover:text-[var(--brand-primary)] dark:hover:text-[var(--brand-primary)] hover:border-[var(--brand-primary)] dark:hover:border-[var(--brand-primary)] transition-all focus:outline-none">
            <svg x-show="!open" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" /></svg>
            <svg x-show="open" x-cloak class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
        </button>

        <div x-show="open" 
            @click.outside="open = false" 
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 -translate-y-2"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 -translate-y-2"
            x-cloak 
            class="absolute top-14 left-0 w-64 bg-white dark:bg-glossary-card border border-gray-200 dark:border-glossary-border rounded-2xl shadow-xl py-3 flex flex-col gap-1 max-h-[85vh] overflow-y-auto">

            {{-- 🚀 BOUTONS EXTERNES (Avec paramètre) ── --}}
            <a href="https://bo-preprod.retdnetworks.com/home?environnement={{ $currentGroupKey }}" rel="noreferrer" class="flex items-center gap-3 px-4 py-2 mx-2 rounded-lg text-sm font-medium text-gray-700 dark:text-glossary-muted hover:bg-gray-50 dark:hover:bg-glossary-border hover:text-[var(--brand-primary)] dark:hover:text-[var(--brand-primary)] transition">
                <svg class="w-5 h-5 opacity-70" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" /></svg> Accueil
            </a>
            <div class="h-px bg-gray-100 dark:bg-glossary-border my-1 mx-4"></div>

            @if($isAdmin)
                <a href="https://bo-preprod.retdnetworks.com/setup?environnement={{ $currentGroupKey }}" class="flex items-center gap-3 px-4 py-2 mx-2 rounded-lg text-sm font-medium text-gray-700 dark:text-glossary-muted hover:bg-gray-50 dark:hover:bg-glossary-border hover:text-[var(--brand-primary)] dark:hover:text-[var(--brand-primary)] transition">
                    <svg class="w-5 h-5 opacity-70" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg> Configuration
                </a>
                <div class="h-px bg-gray-100 dark:bg-glossary-border my-1 mx-4"></div>
            @endif

            @if($canSeePilotage || $canSeeSuperset)
                <a href="{{ $supersetUrl }}" class="flex items-center gap-3 px-4 py-2 mx-2 rounded-lg text-sm font-medium text-gray-700 dark:text-glossary-muted hover:bg-gray-50 dark:hover:bg-glossary-border hover:text-[var(--brand-primary)] dark:hover:text-[var(--brand-primary)] transition">
                    <svg class="w-5 h-5 opacity-70" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg> Pilotage Réseau
                </a>
            @endif

            @if($canSeeGestionClub)
                <a href="https://bo-preprod.retdnetworks.com/dashboard?environnement={{ $currentGroupKey }}" class="flex items-center gap-3 px-4 py-2 mx-2 rounded-lg text-sm font-medium text-gray-700 dark:text-glossary-muted hover:bg-gray-50 dark:hover:bg-glossary-border hover:text-[var(--brand-primary)] dark:hover:text-[var(--brand-primary)] transition">
                    <svg class="w-5 h-5 opacity-70" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg> Back Office
                </a>
            @endif

            @if($canSeeIA)
                <a href="https://bo-preprod.retdnetworks.com/assistant?environnement={{ $currentGroupKey }}" rel="noreferrer" class="flex items-center gap-3 px-4 py-2 mx-2 rounded-lg text-sm font-medium text-gray-700 dark:text-glossary-muted hover:bg-gray-50 dark:hover:bg-glossary-border hover:text-[var(--brand-primary)] dark:hover:text-[var(--brand-primary)] transition">
                    <svg class="w-5 h-5 opacity-70" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 00-2.456 2.456z" /></svg> Assistant IA
                </a>
            @endif

            @if($canSeeDolibarr)
                <a href="{{ $dolibarrUrl }}" class="flex items-center gap-3 px-4 py-2 mx-2 rounded-lg text-sm font-medium text-gray-700 dark:text-glossary-muted hover:bg-gray-50 dark:hover:bg-glossary-border hover:text-[var(--brand-primary)] dark:hover:text-[var(--brand-primary)] transition">
                    <svg class="w-5 h-5 opacity-70" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" /></svg> Dolibarr
                </a>
            @endif

            @if ($canSeeGlossaire)
                <a href="{{ route('home') }}" class="flex items-center gap-3 px-4 py-2 mx-2 rounded-lg text-sm font-medium text-gray-700 dark:text-glossary-muted hover:bg-gray-50 dark:hover:bg-glossary-border hover:text-[var(--brand-primary)] dark:hover:text-[var(--brand-primary)] transition">
                    <svg class="w-5 h-5 opacity-70" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" /></svg> Glossaire
                </a>
            @endif

            @if($canSeeCartographie)
                <a href="https://bo-preprod.retdnetworks.com/cartographie?environnement={{ $currentGroupKey }}" class="flex items-center gap-3 px-4 py-2 mx-2 rounded-lg text-sm font-medium text-gray-700 dark:text-glossary-muted hover:bg-gray-50 dark:hover:bg-glossary-border hover:text-[var(--brand-primary)] dark:hover:text-[var(--brand-primary)] transition">
                    <svg class="w-5 h-5 opacity-70" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" /></svg> Cartographie
                </a>
            @endif

            @if($canSeeCommunication)
                <a href="https://bo-preprod.retdnetworks.com/communication?environnement={{ $currentGroupKey }}" class="flex items-center gap-3 px-4 py-2 mx-2 rounded-lg text-sm font-medium text-gray-700 dark:text-glossary-muted hover:bg-gray-50 dark:hover:bg-glossary-border hover:text-[var(--brand-primary)] dark:hover:text-[var(--brand-primary)] transition">
                    <svg class="w-5 h-5 opacity-70" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" /></svg> Communication
                </a>
            @endif

            @if($canSeeGoodies)
                <a href="https://bo-preprod.retdnetworks.com/club/home?environnement={{ $currentGroupKey }}" class="flex items-center gap-3 px-4 py-2 mx-2 rounded-lg text-sm font-medium text-gray-700 dark:text-glossary-muted hover:bg-gray-50 dark:hover:bg-glossary-border hover:text-[var(--brand-primary)] dark:hover:text-[var(--brand-primary)] transition">
                    <svg class="w-5 h-5 opacity-70" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" /></svg> Goodies
                </a>
            @endif

            @if($canSeeDiagnostic)
                <a href="https://bo-preprod.retdnetworks.com/diagnostic?environnement={{ $currentGroupKey }}" class="flex items-center gap-3 px-4 py-2 mx-2 rounded-lg text-sm font-medium text-gray-700 dark:text-glossary-muted hover:bg-gray-50 dark:hover:bg-glossary-border hover:text-[var(--brand-primary)] dark:hover:text-[var(--brand-primary)] transition">
                    <svg class="w-5 h-5 opacity-70" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" /></svg> Diagnostic
                </a>
            @endif

            <div class="h-px bg-gray-100 dark:bg-glossary-border my-1 mx-4"></div>

            <form method="POST" action="{{ route('logout') }}" class="w-full">
                @csrf
                <button type="submit" class="flex w-full items-center gap-3 px-4 py-2 mx-2 rounded-lg text-sm font-medium text-red-500 hover:bg-red-50 dark:hover:bg-red-500/10 transition cursor-pointer">
                    <svg class="w-5 h-5 opacity-70" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg> Déconnexion
                </button>
            </form>
        </div>
    </div>

    {{-- ── BARRE DE NAVIGATION ── --}}
    <nav class="sticky top-0 z-40 w-full bg-white dark:bg-glossary-card shadow-sm border-b border-gray-200 dark:border-glossary-border py-3 pr-6 pl-16 flex items-center justify-between h-16 shrink-0 transition-colors duration-200 relative">
        
        {{-- SECTION GAUCHE : Sélecteur d'environnement (Aligné à gauche) --}}
        <div class="flex-1 flex items-center justify-start">
            @if($isAdmin && request()->routeIs('home'))
                <div x-data="{ envOpen: false }" class="relative z-50">
                    <button @click="envOpen = !envOpen" class="flex items-center gap-2 max-w-[60vw] sm:max-w-[260px] pl-2.5 pr-3 py-2 rounded-xl bg-white dark:bg-glossary-card border border-gray-200 dark:border-glossary-border shadow-sm hover:border-[var(--brand-primary)] dark:hover:border-[var(--brand-primary)] transition-all focus:outline-none">
                        @if(!$isGlobalView && isset($navGroupBrand))
                            <img src="{{ asset('images/' . $navGroupBrand['key'] . '.png') }}" alt="" class="h-5 w-5 rounded object-contain shrink-0" onerror="this.style.display='none'">
                        @else
                            <span class="h-2.5 w-2.5 rounded-full shrink-0" style="background: #EEA21E"></span>
                        @endif
                        
                        <span class="text-xs font-bold tracking-tight truncate text-gray-700 dark:text-white">
                            {!! $isGlobalView ? 'Réseau Global' : ($navGroupBrand['name'] ?? 'Inconnu') !!}
                        </span>
                        <svg class="w-3.5 h-3.5 text-gray-400 dark:text-glossary-muted shrink-0 transition-transform duration-200" :class="envOpen ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                    </button>

                    <div x-show="envOpen" @click.outside="envOpen = false" x-cloak
                        x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-2"
                        x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-150"
                        x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-2"
                        class="absolute top-full mt-2 left-0 w-72 max-h-[70vh] overflow-y-auto bg-white dark:bg-glossary-card border border-gray-200 dark:border-glossary-border rounded-2xl shadow-xl py-2">

                        <p class="px-4 pt-1 pb-2 text-[10px] font-black uppercase tracking-widest text-gray-400 dark:text-glossary-muted">Changer d'environnement</p>

                        <a href="{{ route('env.switch', ['group' => 'retd']) }}" class="flex items-center gap-3 px-4 py-2 mx-2 rounded-lg text-sm font-medium transition {{ $isGlobalView ? 'bg-gray-50 dark:bg-glossary-border' : 'text-gray-700 dark:text-glossary-muted hover:bg-gray-50 dark:hover:bg-glossary-border' }}" @if($isGlobalView) style="color:#EEA21E" @endif>
                            <span class="h-2.5 w-2.5 rounded-full shrink-0" style="background:#EEA21E"></span>
                            <span class="flex-1 truncate">Réseau Global (R&D)</span>
                            @if($isGlobalView)<span class="text-[10px]">✓</span>@endif
                        </a>

                        <div class="h-px bg-gray-100 dark:bg-glossary-border my-1.5 mx-4"></div>

                        @foreach($allGroups as $env)
                            @php $isActive = (!$isGlobalView && $currentGroupKey === $env['key']); @endphp
                            <a href="{{ route('env.switch', ['group' => $env['key']]) }}" class="flex items-center gap-3 px-4 py-2 mx-2 rounded-lg text-sm font-medium transition {{ $isActive ? 'bg-gray-50 dark:bg-glossary-border text-[var(--brand-primary)]' : 'text-gray-700 dark:text-glossary-muted hover:bg-gray-50 dark:hover:bg-glossary-border' }}">
                                <img src="{{ asset('images/' . $env['key'] . '.png') }}" alt="" class="h-4 w-4 rounded object-contain shrink-0" onerror="this.style.display='none'">
                                <span class="flex-1 truncate" @if($isActive) style="color: {{ $env['scroll_light'] ?? 'var(--brand-primary)' }}" @endif>{!! $env['name'] !!}</span>
                                @if($isActive)<span class="text-[10px]">✓</span>@endif
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
            @yield('header-extra')
        </div>
        
        {{-- SECTION CENTRE : Logo + Glossaire (Absolument Centré avec pointer-events-none) --}}
        <div class="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 flex items-center justify-center pointer-events-none z-10 w-auto">
            <a href="{{ route('home') }}" class="flex items-center space-x-3 group shrink-0 pointer-events-auto">
                <div class="h-8 md:h-10 w-auto flex items-center">
                    @if($currentGroupKey && $currentGroupKey !== 'retd')
                        <img src="{{ asset('images/' . ($navGroupBrand['key'] ?? '') . '.png') }}" alt="Logo {{ $navGroupBrand['name'] ?? '' }}" class="h-full w-auto object-contain transition-all duration-300 group-hover:scale-105" onerror="this.style.display='none'">
                    @else
                        <img src="{{ asset('images/retd_noir.png') }}" alt="Logo RETD (Clair)" class="h-full w-auto object-contain transition-all duration-300 group-hover:scale-105 inline dark:hidden">
                        <img src="{{ asset('images/retd_blanc.png') }}" alt="Logo RETD (Sombre)" class="h-full w-auto object-contain transition-all duration-300 group-hover:scale-105 hidden dark:inline">
                    @endif
                </div>
                <span class="text-gray-300 dark:text-glossary-border font-light text-xl">|</span>
                <div class="flex items-center gap-2.5">
                    <h1 class="text-lg font-bold text-gray-900 dark:text-white uppercase tracking-wider">Glossaire</h1>
                </div>
            </a>
        </div>
        
        {{-- SECTION DROITE : Thème + Profil --}}
        <div class="flex-1 flex items-center justify-end space-x-4">
            <button id="theme-toggle" type="button" class="text-gray-500 dark:text-glossary-muted hover:text-[var(--brand-primary)] dark:hover:text-[var(--brand-primary)] hover:bg-gray-100 dark:hover:bg-glossary-border focus:outline-none rounded-xl text-sm p-2 transition-all duration-300">
                <svg id="theme-toggle-dark-icon" class="hidden w-5 h-5 transition-transform duration-300 hover:-rotate-12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" /></svg>
                <svg id="theme-toggle-light-icon" class="hidden w-5 h-5 transition-transform duration-300 hover:rotate-90" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
            </button>

            @auth
                <p class="text-sm font-semibold text-gray-700 dark:text-white hidden sm:block">{{ Auth::user()->name }}</p>
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