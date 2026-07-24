@extends('layouts.app')
@section('title', 'Lecture : ' . $document->title)

@section('styles')
    <link rel="stylesheet" href="https://uicdn.toast.com/editor/latest/toastui-editor.min.css" />
    <link rel="stylesheet" href="https://uicdn.toast.com/editor/latest/theme/toastui-editor-dark.min.css" />
    
<style>
        /* [ ... VOS STYLES DE STRUCTURE RESTENT IDENTIQUES ... ] */
        .toastui-editor-defaultUI { border-radius: 0.75rem !important; border: 1px solid #e5e7eb !important; font-family: inherit !important; overflow: hidden; box-shadow: none !important; }
        .toastui-editor-defaultUI .toastui-editor-status-bar { display: none !important; }
        .toastui-editor-defaultUI .ProseMirror h1, .toastui-editor-defaultUI .ProseMirror h2, .toastui-editor-contents h1, .toastui-editor-contents h2 { border-bottom: none !important; padding-bottom: 0 !important; }
        .toastui-editor-defaultUI .ProseMirror, .toastui-editor-contents { min-height: 400px !important; padding: 2rem !important; color: #111827 !important; }
        .toastui-editor-defaultUI .toastui-editor-tabs { height: 34px !important; line-height: 34px !important; }
        .toastui-editor-defaultUI .toastui-editor-tabs .tab-item { height: 34px !important; line-height: 34px !important; padding: 0 16px !important; }
        
        .toastui-editor-defaultUI, .toastui-editor-defaultUI .toastui-editor-main, .toastui-editor-defaultUI .toastui-editor-ww-container, .toastui-editor-defaultUI .toastui-editor-md-container, .toastui-editor-defaultUI .toastui-editor-md-preview { background-color: #ffffff !important; }
        .toastui-editor-defaultUI .toastui-editor-toolbar, .toastui-editor-defaultUI .toastui-editor-tabs { background-color: #f9fafb !important; border-bottom-color: #e5e7eb !important; }
        
        /* 🚀 COULEUR DYNAMIQUE : Mode Clair */
        .toastui-editor-defaultUI .toastui-editor-tabs .tab-item.active { background-color: #ffffff !important; color: var(--brand-primary) !important; }
        .toastui-editor-defaultUI .ProseMirror strong, .toastui-editor-contents strong, .toastui-editor-defaultUI .toastui-editor-md-strong { font-weight: 800 !important; color: #000000 !important; }

        /* THEME SOMBRE */
        .dark .toastui-editor-defaultUI { border-color: #2A2A2A !important; }
        .dark .toastui-editor-defaultUI, .dark .toastui-editor-defaultUI .toastui-editor-main, .dark .toastui-editor-defaultUI .toastui-editor-ww-container, .dark .toastui-editor-defaultUI .toastui-editor-md-container, .dark .toastui-editor-dark, .dark .toastui-editor-defaultUI .toastui-editor-md-preview { background-color: #1A1A1A !important; }
        .dark .toastui-editor-defaultUI .toastui-editor-toolbar, .dark .toastui-editor-defaultUI .toastui-editor-tabs { background-color: #0F0F0F !important; border-bottom-color: #2A2A2A !important; }
        .dark .toastui-editor-defaultUI .toastui-editor-tabs { background-color: transparent !important; border-bottom: none !important; margin-bottom: -1px !important; position: relative; z-index: 10; }
        .dark .toastui-editor-defaultUI .toastui-editor-tabs .tab-item { color: #8B99A8 !important; background-color: transparent !important; border: none !important; }
        
        /* 🚀 COULEUR DYNAMIQUE : Onglets Mode Sombre */
        .dark .toastui-editor-defaultUI .toastui-editor-tabs .tab-item.active {
            background-color: #1A1A1A !important; 
            color: var(--brand-primary) !important; 
            border: 1px solid #2A2A2A !important; 
            border-bottom: 1px solid #1A1A1A !important;
            border-top-left-radius: 6px;
            border-top-right-radius: 6px;
        }

        .dark .toastui-editor-defaultUI .toastui-editor-md-splitter { background-color: #2A2A2A !important; }
        .dark .toastui-editor-defaultUI .ProseMirror, .dark .toastui-editor-contents, .dark .toastui-editor-defaultUI .toastui-editor-md-preview { color: #e2e8f0 !important; }
        .dark .toastui-editor-defaultUI .ProseMirror h1, .dark .toastui-editor-defaultUI .ProseMirror h2, .dark .toastui-editor-defaultUI .ProseMirror h3, .dark .toastui-editor-defaultUI .ProseMirror h4, .dark .toastui-editor-contents h1, .dark .toastui-editor-contents h2, .dark .toastui-editor-contents h3, .dark .toastui-editor-contents h4, .dark .toastui-editor-defaultUI .toastui-editor-md-heading { color: #ffffff !important; }
        .dark .toastui-editor-defaultUI .ProseMirror strong, .dark .toastui-editor-contents strong, .dark .toastui-editor-defaultUI .toastui-editor-md-strong { font-weight: 800 !important; color: #ffffff !important; }

        /* 🚀 COULEUR DYNAMIQUE : Liens Mode Sombre */
        .dark .toastui-editor-contents a, .dark .toastui-editor-defaultUI .ProseMirror a, .dark .toastui-editor-defaultUI .toastui-editor-md-meta { 
            color: var(--brand-primary) !important; 
        }

        /* 🚀 COULEUR DYNAMIQUE : Puces des listes */
        .toastui-editor-defaultUI ul > li::marker, .toastui-editor-contents ul > li::marker { content: "• " !important; color: var(--brand-primary) !important; font-size: 1.2rem !important; }
        .dark .toastui-editor-defaultUI ul > li::marker, .dark .toastui-editor-contents ul > li::marker { color: var(--brand-primary) !important; }

        .toastui-editor-defaultUI ul ul > li::marker, .toastui-editor-contents ul ul > li::marker { content: "◦ " !important; color: var(--brand-dark) !important; font-size: 1.2rem !important; }
        .dark .toastui-editor-defaultUI ul ul > li::marker, .dark .toastui-editor-contents ul ul > li::marker { color: var(--brand-dark) !important; }

        .toastui-editor-defaultUI ul ul ul > li::marker, .toastui-editor-contents ul ul ul > li::marker { content: "▪ " !important; color: #8B99A8 !important; font-size: 1.1rem !important; }
        .dark .toastui-editor-defaultUI ul ul ul > li::marker, .dark .toastui-editor-contents ul ul ul > li::marker { color: #8B99A8 !important; }

        .force-tui-split-width { max-width: 80rem !important; }
        .toastui-editor-defaultUI * { scrollbar-width: thin !important; scrollbar-color: #cbd5e1 transparent !important; }
        .dark .toastui-editor-defaultUI * { scrollbar-color: #2A2A2A transparent !important; }
        .toastui-editor-defaultUI *::-webkit-scrollbar { width: 6px !important; height: 6px !important; }
        .toastui-editor-defaultUI *::-webkit-scrollbar-track { background: transparent !important; }
        .toastui-editor-defaultUI *::-webkit-scrollbar-thumb { background-color: #cbd5e1 !important; border-radius: 20px !important; }
        .toastui-editor-defaultUI *::-webkit-scrollbar-thumb:hover { background-color: #9ca3af !important; }
        .dark .toastui-editor-defaultUI *::-webkit-scrollbar-thumb { background-color: #2A2A2A !important; }
        .dark .toastui-editor-defaultUI *::-webkit-scrollbar-thumb:hover { background-color: #8B99A8 !important; }

        .toastui-editor-contents .task-list-item { cursor: text !important; }
        .toastui-editor-contents input[type="checkbox"], .toastui-editor-contents .task-list-item-checkbox { pointer-events: none !important; cursor: default !important; }
    </style>
@endsection

@section('header-extra')
    <span class="text-gray-300 dark:text-glossary-border mx-2">|</span>
    <span class="text-sm text-gray-500 dark:text-glossary-muted">Lecture seule</span>
@endsection

@section('content')
<main id="main-wrapper" class="max-w-6xl w-full mx-auto p-6 flex-1 transition-all duration-300">
    <div class="bg-white dark:bg-glossary-card rounded-2xl shadow-sm border border-gray-200 dark:border-glossary-border p-6 transition-colors duration-200">

        <div class="mb-5 flex justify-between items-start gap-4">
            {{-- BLOC GAUCHE : Label + Titre --}}
            <div class="flex-1 min-w-0">
                <label class="block text-xs font-semibold text-gray-400 dark:text-glossary-muted uppercase tracking-wider mb-2">Titre du document</label>
                <h1 class="text-gray-900 dark:text-white text-xl font-bold transition-colors truncate">{{ $document->title }}</h1>
            </div>
        </div>

        <div class="mb-6">
            <label class="block text-xs font-semibold text-gray-400 dark:text-glossary-muted uppercase tracking-wider mb-2">Contenu</label>
            <div id="editor-container" class="toastui-editor-defaultUI bg-white dark:bg-glossary-card w-full"></div>
            <input type="hidden" id="hidden-content" value="{{ $document->content }}">
        </div>

        <div class="flex justify-between items-center border-t border-gray-100 dark:border-glossary-border pt-5">
            <div class="text-sm text-gray-500 dark:text-glossary-muted italic">
                Modifié le {{ $document->updated_at->format('d/m/Y') }}
            </div>
            <div class="flex space-x-3">
                <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('home') }}" 
                class="bg-gray-100 dark:bg-glossary-base text-gray-700 dark:text-white py-2.5 px-5 rounded-xl text-sm transition hover:bg-gray-200 dark:hover:bg-glossary-border">
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

        // L'ÉCOUTEUR DE THÈME
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