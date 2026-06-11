@extends('layouts.app')
@section('title', 'Lecture : ' . $document->title)

@section('styles')
    <link rel="stylesheet" href="https://uicdn.toast.com/editor/latest/toastui-editor.min.css" />
    <link rel="stylesheet" href="https://uicdn.toast.com/editor/latest/theme/toastui-editor-dark.min.css" />
    
    <style>
        /* ========================================================
           📦 STRUCTURE ET MASQUAGE (Pour la lecture seule)
           ======================================================== */
        .toastui-editor-defaultUI {
            border-radius: 0.75rem !important;
            border: 1px solid #e5e7eb !important;
            font-family: inherit !important;
            overflow: hidden;
            box-shadow: none !important;
        }
        .dark .toastui-editor-defaultUI { border-color: #374151 !important; }

        /* On cache les éléments de l'éditeur qui ne servent pas en lecture */
        .toastui-editor-defaultUI .toastui-editor-toolbar,
        .toastui-editor-defaultUI .toastui-editor-tabs,
        .toastui-editor-defaultUI .toastui-editor-status-bar {
            display: none !important;
        }

        .toastui-editor-defaultUI .ProseMirror h1,
        .toastui-editor-defaultUI .ProseMirror h2 {
            border-bottom: none !important;
            padding-bottom: 0 !important;
        }
        .toastui-editor-defaultUI .ProseMirror { min-height: 400px !important; }

        /* ========================================================
           🎨 COULEURS CLAIR / SOMBRE (Pour la transition)
           ======================================================== */
        /* --- MODE CLAIR --- */
        .toastui-editor-defaultUI,
        .toastui-editor-defaultUI .toastui-editor-main,
        .toastui-editor-defaultUI .toastui-editor-ww-container {
            background-color: #ffffff !important;
        }
        .toastui-editor-defaultUI .ProseMirror { color: #111827 !important; }
        .toastui-editor-defaultUI .ProseMirror strong { font-weight: 800 !important; color: #000000 !important; }

        /* --- MODE SOMBRE --- */
        .dark .toastui-editor-defaultUI,
        .dark .toastui-editor-defaultUI .toastui-editor-main,
        .dark .toastui-editor-defaultUI .toastui-editor-ww-container,
        .dark .toastui-editor-dark {
            background-color: #24292e !important; 
        }
        .dark .toastui-editor-defaultUI .ProseMirror { color: #cbd5e1 !important; }
        .dark .toastui-editor-defaultUI .ProseMirror h1,
        .dark .toastui-editor-defaultUI .ProseMirror h2 { color: #f8fafc !important; }
        .dark .toastui-editor-defaultUI .ProseMirror strong { font-weight: 800 !important; color: #ffffff !important; }
        .dark .toastui-editor-defaultUI .ProseMirror a { color: #818cf8 !important; }

        /* ========================================================
           🎯 STYLE DES PUCES IMBRIQUÉES
           ======================================================== */
        .toastui-editor-defaultUI ul > li::marker { content: "• " !important; color: #4f46e5 !important; font-size: 1.2rem !important; }
        .toastui-editor-defaultUI ul ul > li::marker { content: "◦ " !important; color: #0284c7 !important; font-size: 1.2rem !important; }
        .toastui-editor-defaultUI ul ul ul > li::marker { content: "▪ " !important; color: #059669 !important; font-size: 1.1rem !important; }

        .dark .toastui-editor-defaultUI ul > li::marker { color: #818cf8 !important; }
        .dark .toastui-editor-defaultUI ul ul > li::marker { color: #38bdf8 !important; }
        .dark .toastui-editor-defaultUI ul ul ul > li::marker { color: #34d399 !important; }

        /* ========================================================
           🔒 COMPORTEMENT ET SCROLLBARS DES BLOCS DE CODE
           ======================================================== */
        .ProseMirror { caret-color: transparent !important; outline: none !important; }
        
        /* Supprime le bouton d'édition PHP */
        .te-code-block-editor-toggle, .toastui-editor-popup { display: none !important; visibility: hidden !important; }

        /* Défilement des blocs de code */
        .toastui-editor-defaultUI * { scrollbar-width: thin !important; scrollbar-color: #cbd5e1 transparent !important; }
        .dark .toastui-editor-defaultUI * { scrollbar-color: #4b5563 transparent !important; }
        .toastui-editor-defaultUI *::-webkit-scrollbar { width: 6px !important; height: 6px !important; }
        .toastui-editor-defaultUI *::-webkit-scrollbar-thumb { background-color: #cbd5e1 !important; border-radius: 20px !important; }
        .dark .toastui-editor-defaultUI *::-webkit-scrollbar-thumb { background-color: #4b5563 !important; }
    
        /* ========================================================
           🛑 VERROUILLAGE DES CASES À COCHER (MODE VIEWER)
           ======================================================== */
        /* On remet un curseur de texte normal sur toute la ligne */
        .toastui-editor-contents .task-list-item {
            cursor: text !important; 
        }
        /* On désactive totalement la case pour la souris */
        .toastui-editor-contents input[type="checkbox"],
        .toastui-editor-contents .task-list-item-checkbox {
            pointer-events: none !important;
            cursor: default !important;
        }
    </style>
@endsection

@section('header-extra')
    <span class="text-gray-300 dark:text-gray-600 mx-2">|</span>
    <span class="text-sm text-gray-500 dark:text-gray-400">Lecture seule</span>
@endsection

@section('content')
<main id="main-wrapper" class="max-w-6xl w-full mx-auto p-6 flex-1 transition-all duration-300">
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 transition-colors duration-200">
        
        <div class="mb-5">
            <label class="block text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Titre du document</label>
            <h1 class="w-full text-gray-900 dark:text-white text-xl font-bold transition-colors">{{ $document->title }}</h1>
            <p class="text-xs text-indigo-600 dark:text-indigo-400 mt-2">Partagé par {{ $document->user->name }}</p>
        </div>

        <div class="mb-6">
            <label class="block text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Contenu</label>
            <div id="editor-container" class="bg-white dark:bg-gray-900 rounded-xl"></div>
            <input type="hidden" id="hidden-content" value="{{ $document->content }}">
        </div>

        <div class="flex justify-between items-center border-t border-gray-100 dark:border-gray-700 pt-5">
            <div></div>
            <div class="flex space-x-3">
                <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('home') }}" class="bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 py-2.5 px-5 rounded-xl text-sm transition">
                    Retour à la bibliothèque
                </a>
            </div>
        </div>
    </div>
</main>
@endsection

@section('scripts')
<script src="https://uicdn.toast.com/editor/latest/toastui-editor-all.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        let isDark = document.documentElement.classList.contains('dark');
        const container = document.getElementById('editor-container');
        
        // 🚀 INITIALISATION EN MODE VIEWER
        const viewer = toastui.Editor.factory({
            el: container,
            viewer: true, 
            initialValue: document.getElementById('hidden-content').value,
            theme: isDark ? 'dark' : 'light'
        });

        // Application du thème sombre au démarrage
        if (isDark) {
            container.classList.add('toastui-editor-dark');
        }

        // ========================================================
        // 🛑 L'ARME FATALE : LE GARDIEN (MutationObserver)
        // ========================================================
        // Toast UI génère son HTML de manière asynchrone. Ce gardien surveille 
        // l'apparition des cases à cocher et les verrouille instantanément.
        const observer = new MutationObserver(function(mutations) {
            container.querySelectorAll('input[type="checkbox"]').forEach(function(checkbox) {
                // 1. On bloque l'état (grisé)
                checkbox.setAttribute('disabled', 'disabled');
                
                // 2. On désactive les événements JavaScript de Toast UI
                checkbox.onclick = function(e) {
                    e.preventDefault();
                    return false;
                };
            });
        });

        // On active le gardien sur l'éditeur
        observer.observe(container, { childList: true, subtree: true });
        
        // Sécurité supplémentaire : on lance un verrouillage manuel rapide
        setTimeout(() => {
            container.querySelectorAll('input[type="checkbox"]').forEach(cb => {
                cb.setAttribute('disabled', 'disabled');
            });
        }, 150);

        // 🚀 L'ÉCOUTEUR DE THÈME
        window.addEventListener('theme-changed', function(e) {
            let isDarkTheme = e.detail.theme === 'dark';
            
            if (isDarkTheme) {
                container.classList.add('toastui-editor-dark');
                if (viewer.setTheme) viewer.setTheme('dark');
            } else {
                container.classList.remove('toastui-editor-dark');
                if (viewer.setTheme) viewer.setTheme('light');
            }
        });

        // ========================================================
        // 🛑 BLOCAGE ABSOLU DES CLICS SUR LES CHECKBOXES
        // ========================================================
        // On intercepte mousedown, mouseup et click AVANT Toast UI (grâce au "true")
        ['mousedown', 'mouseup', 'click'].forEach(eventName => {
            container.addEventListener(eventName, function(e) {
                // Si l'utilisateur clique sur une case à cocher
                if (e.target.tagName.toLowerCase() === 'input' || e.target.type === 'checkbox') {
                    e.preventDefault();
                    e.stopPropagation();
                    return false;
                }
                
                // Si Toast UI tente de capter le clic sur le début de la ligne (la puce)
                if (e.target.classList.contains('task-list-item') && e.offsetX < 30) {
                    e.preventDefault();
                    e.stopPropagation();
                    return false;
                }
            }, true);
        });
    });
</script>
@endsection