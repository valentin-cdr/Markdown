@extends('layouts.app')
@section('title', 'Partager - ' . $document->title)

@section('content')
<main class="max-w-3xl w-full mx-auto mt-12 bg-white dark:bg-gray-800 p-8 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 transition-colors duration-200">
    
    <a href="{{ route('home') }}" class="text-indigo-600 dark:text-indigo-400 text-sm font-medium hover:underline mb-4 inline-block">&larr; Retour à l'accueil</a>
    
    <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Partager le document : <br><span class="text-indigo-600 dark:text-indigo-400">{{ $document->title }}</span></h2>

    @if(session('success'))
        <div class="bg-green-50 dark:bg-green-900/30 text-green-800 dark:text-green-300 p-4 rounded-lg mb-6">{{ session('success') }}</div>
    @endif
    @error('username')
        <div class="bg-red-50 dark:bg-red-900/30 text-red-800 dark:text-red-300 p-4 rounded-lg mb-6">{{ $message }}</div>
    @enderror

    <!-- Ajouter un accès -->
    <form action="{{ route('documents.share', $document->id) }}" method="POST" class="mb-10 bg-gray-50 dark:bg-gray-900 p-6 rounded-xl border border-gray-100 dark:border-gray-700">
        @csrf
        <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-3">Inviter un collaborateur</label>
        
        <div class="flex flex-col sm:flex-row space-y-3 sm:space-y-0 sm:space-x-3 mb-4">
            <input type="text" name="username" required placeholder="Identifiant..." class="flex-1 bg-white dark:bg-gray-800 text-gray-900 dark:text-white border-gray-300 dark:border-gray-600 border p-3 rounded-lg focus:ring-indigo-500">
            <button type="submit" class="bg-indigo-600 text-white px-6 py-3 rounded-lg font-medium hover:bg-indigo-700 transition">Donner l'accès</button>
        </div>
        
        <!-- Checkbox pour autoriser la modification -->
        <label class="flex items-center space-x-2 text-sm text-gray-600 dark:text-gray-400 cursor-pointer">
            <input type="checkbox" name="can_edit" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 bg-white dark:bg-gray-800">
            <span>Autoriser cet utilisateur à modifier le document</span>
        </label>
    </form>

    {{-- 🚀 BLOC COPIER LE LIEN VERS LA PAGE SHOW (Superset) ── --}}
    <div x-data="{ 
            copied: false, 
            {{-- On génère le lien absolu vers la page de lecture du document --}}
            url: '{{ route('documents.show', $document->id) }}' 
        }" 
        class="w-full max-w-lg mt-6">
        
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
            Lien d'intégration (Superset)
        </label>
        
        <div class="flex shadow-sm rounded-lg overflow-hidden border border-gray-300 dark:border-gray-600">
            {{-- Champ texte en lecture seule --}}
            <input type="text" x-model="url" readonly
                class="flex-1 min-w-0 block w-full px-3 py-2 text-sm bg-gray-50 dark:bg-gray-900 text-gray-500 dark:text-gray-400 border-0 focus:ring-0 select-all cursor-text">
            
            {{-- Bouton Copier avec Alpine.js --}}
            <button @click="navigator.clipboard.writeText(url).then(() => { copied = true; setTimeout(() => copied = false, 2000) })"
                    type="button"
                    class="inline-flex items-center px-4 py-2 border-l border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none transition-colors group">
                
                {{-- Icône "Copier" par défaut --}}
                <svg x-show="!copied" class="w-5 h-5 mr-1.5 text-gray-400 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                </svg>
                <span x-show="!copied">Copier</span>
                
                {{-- Icône "Copié !" (Check) --}}
                <svg x-show="copied" x-cloak class="w-5 h-5 mr-1.5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                <span x-show="copied" x-cloak class="text-emerald-500">Copié !</span>
            </button>
        </div>
    </div>

    <!-- Liste des accès -->
    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Personnes ayant accès</h3>
    <div class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
        @forelse($document->sharedWith as $guest)
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center p-4 border-b border-gray-100 dark:border-gray-700 last:border-0 bg-white dark:bg-gray-800 gap-4">
                
                <div>
                    <p class="font-medium text-gray-900 dark:text-white">{{ $guest->name }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">ID : {{ $guest->username }}</p>
                </div>
                
                <div class="flex items-center space-x-3 w-full sm:w-auto">
                    <!-- Sélecteur de permission interactif (sauvegarde auto au clic) -->
                    <form action="{{ route('documents.share.update', [$document->id, $guest->id]) }}" method="POST" class="m-0">
                        @csrf @method('PATCH')
                        <input type="hidden" name="can_edit" value="0">
                        
                        <label class="flex items-center cursor-pointer group select-none">
                            <span class="mr-3 text-sm transition-colors duration-300 {{ !$guest->pivot->can_edit ? 'text-gray-800 dark:text-gray-200 font-semibold' : 'text-gray-400 dark:text-gray-500' }}">
                                Lecteur
                            </span>

                            <div class="relative">
                                <input type="checkbox" name="can_edit" value="1" onchange="this.form.submit()" class="sr-only" {{ $guest->pivot->can_edit ? 'checked' : '' }}>
                                
                                <div class="block w-10 h-6 rounded-full transition-colors duration-300 ease-in-out {{ $guest->pivot->can_edit ? 'bg-indigo-500' : 'bg-gray-300 dark:bg-gray-600' }}"></div>
                                
                                <div class="absolute left-1 top-1 bg-white w-4 h-4 rounded-full transition-transform duration-300 ease-in-out shadow-sm {{ $guest->pivot->can_edit ? 'transform translate-x-4' : '' }}"></div>
                            </div>
                            
                            <span class="ml-3 text-sm transition-colors duration-300 {{ $guest->pivot->can_edit ? 'text-indigo-600 dark:text-indigo-400 font-semibold' : 'text-gray-400 dark:text-gray-500' }}">
                                Éditeur
                            </span>

                        </label>
                    </form>

                    <!-- Bouton Révoquer -->
                    <form action="{{ route('documents.unshare', [$document->id, $guest->id]) }}" method="POST">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-red-500 dark:text-red-400 hover:text-red-700 text-sm font-medium bg-red-50 dark:bg-red-900/20 px-3 py-1.5 rounded-lg transition">Révoquer</button>
                    </form>
                </div>

            </div>
        @empty
            <div class="p-6 text-center text-sm text-gray-500 dark:text-gray-400">Ce document n'est partagé avec personne.</div>
        @endforelse
    </div>
</main>
@endsection