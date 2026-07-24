@extends('layouts.app')
@section('title', 'Partager - ' . $document->title)

@section('content')
<main class="max-w-3xl w-full mx-auto mt-12 bg-white dark:bg-glossary-card p-8 rounded-2xl shadow-sm border border-gray-200 dark:border-glossary-border transition-colors duration-200">
    
    <a href="{{ route('home') }}" class="text-[var(--brand-primary)] text-sm font-medium hover:underline mb-4 inline-block">&larr; Retour à l'accueil</a>
    
    <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Partager le document : <br><span class="text-[var(--brand-primary)]">{{ $document->title }}</span></h2>

    @if(session('success'))
        <div class="bg-green-50 dark:bg-green-900/30 text-green-800 dark:text-green-300 p-4 rounded-lg mb-6">{{ session('success') }}</div>
    @endif
    @error('username')
        <div class="bg-red-50 dark:bg-red-900/30 text-red-800 dark:text-red-300 p-4 rounded-lg mb-6">{{ $message }}</div>
    @enderror

    <!-- Ajouter un accès -->
    <form action="{{ route('documents.share', $document->id) }}" method="POST" class="mb-10 bg-gray-50 dark:bg-glossary-base p-6 rounded-xl border border-gray-100 dark:border-glossary-border">
        @csrf
        <label class="block text-sm font-bold text-gray-700 dark:text-white mb-3">Inviter un collaborateur</label>
        
        <div class="flex flex-col sm:flex-row space-y-3 sm:space-y-0 sm:space-x-3 mb-4">
            <input type="text" name="username" required placeholder="Identifiant..." class="flex-1 bg-white dark:bg-glossary-card text-gray-900 dark:text-white border-gray-300 dark:border-glossary-border border p-3 rounded-lg focus:ring-[var(--brand-primary)] dark:focus:ring-[var(--brand-primary)] dark:focus:border-[var(--brand-primary)] dark:placeholder-glossary-muted transition-colors">
            <button type="submit" class="bg-[var(--brand-primary)] text-white px-6 py-3 rounded-lg font-medium hover:bg-[var(--brand-dark)] transition">Donner l'accès</button>
        </div>
        
        <!-- Checkbox pour autoriser la modification -->
        <label class="flex items-center space-x-2 text-sm text-gray-600 dark:text-glossary-muted cursor-pointer hover:text-gray-900 dark:hover:text-white transition-colors">
            <input type="checkbox" name="can_edit" class="rounded border-gray-300 dark:border-glossary-border text-[var(--brand-primary)] focus:ring-[var(--brand-primary)] dark:focus:ring-offset-glossary-base bg-white dark:bg-glossary-card transition-colors">
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
        
        <label class="block text-sm font-medium text-gray-700 dark:text-white mb-1.5">
            Lien d'intégration (Superset)
        </label>
        
        <div class="flex shadow-sm rounded-lg overflow-hidden border border-gray-300 dark:border-glossary-border">
            {{-- Champ texte en lecture seule --}}
            <input type="text" x-model="url" readonly
                class="flex-1 min-w-0 block w-full px-3 py-2 text-sm bg-gray-50 dark:bg-glossary-base text-gray-500 dark:text-glossary-muted border-0 focus:ring-0 select-all cursor-text">
            
            {{-- Bouton Copier avec Alpine.js --}}
            <button @click="navigator.clipboard.writeText(url).then(() => { copied = true; setTimeout(() => copied = false, 2000) })"
                    type="button"
                    class="inline-flex items-center px-4 py-2 border-l border-gray-300 dark:border-glossary-border bg-white dark:bg-glossary-card text-sm font-medium text-[var(--brand-primary)] hover:bg-gray-50 dark:hover:bg-glossary-base focus:outline-none transition-colors group">
                
                {{-- Icône "Copier" par défaut --}}
                <svg x-show="!copied" class="w-5 h-5 mr-1.5 text-gray-400 dark:text-glossary-muted group-hover:text-[var(--brand-primary)] transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                </svg>
                <span x-show="!copied">Copier</span>
                
                {{-- Icône "Copié !" (Check) --}}
                <svg x-show="copied" x-cloak class="w-5 h-5 mr-1.5 text-emerald-500 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                <span x-show="copied" x-cloak class="text-emerald-500 dark:text-emerald-400">Copié !</span>
            </button>
        </div>
    </div>

    <!-- Liste des accès -->
    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 mt-8">Personnes ayant accès</h3>
    <div class="border border-gray-200 dark:border-glossary-border rounded-lg overflow-hidden">
        @forelse($document->sharedWith as $guest)
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center p-4 border-b border-gray-100 dark:border-glossary-border last:border-0 bg-white dark:bg-glossary-card gap-4">
                
                <div>
                    <p class="font-medium text-gray-900 dark:text-white">{{ $guest->name }}</p>
                    <p class="text-xs text-gray-500 dark:text-glossary-muted">ID : {{ $guest->username }}</p>
                </div>
                
                <div class="flex items-center space-x-3 w-full sm:w-auto">
                    <!-- Sélecteur de permission interactif (sauvegarde auto au clic) -->
                    <form action="{{ route('documents.share.update', [$document->id, $guest->id]) }}" method="POST" class="m-0">
                        @csrf @method('PATCH')
                        <input type="hidden" name="can_edit" value="0">
                        
                        <label class="flex items-center cursor-pointer group select-none">
                            <span class="mr-3 text-sm transition-colors duration-300 {{ !$guest->pivot->can_edit ? 'text-gray-800 dark:text-white font-semibold' : 'text-gray-400 dark:text-glossary-muted' }}">
                                Lecteur
                            </span>

                            <div class="relative">
                                <input type="checkbox" name="can_edit" value="1" onchange="this.form.submit()" class="sr-only" {{ $guest->pivot->can_edit ? 'checked' : '' }}>
                                
                                <div class="block w-10 h-6 rounded-full transition-colors duration-300 ease-in-out {{ $guest->pivot->can_edit ? 'bg-[var(--brand-primary)]' : 'bg-gray-300 dark:bg-glossary-border' }}"></div>
                                
                                <div class="absolute left-1 top-1 bg-white w-4 h-4 rounded-full transition-transform duration-300 ease-in-out shadow-sm {{ $guest->pivot->can_edit ? 'transform translate-x-4' : '' }}"></div>
                            </div>
                            
                            <span class="ml-3 text-sm transition-colors duration-300 {{ $guest->pivot->can_edit ? 'text-[var(--brand-primary)] font-semibold' : 'text-gray-400 dark:text-glossary-muted' }}">
                                Éditeur
                            </span>

                        </label>
                    </form>

                    <!-- Bouton Révoquer -->
                    <form action="{{ route('documents.unshare', [$document->id, $guest->id]) }}" method="POST">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-red-500 dark:text-red-400 hover:text-red-700 dark:hover:text-red-300 text-sm font-medium bg-red-50 dark:bg-red-900/20 px-3 py-1.5 rounded-lg transition">Révoquer</button>
                    </form>
                </div>

            </div>
        @empty
            <div class="p-6 text-center text-sm text-gray-500 dark:text-glossary-muted bg-white dark:bg-glossary-card">Ce document n'est partagé avec personne.</div>
        @endforelse
    </div>
</main>
@endsection