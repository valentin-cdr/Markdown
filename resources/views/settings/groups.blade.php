@extends('layouts.app')

@section('title', 'Configuration des Groupes')

@section('content')
{{-- Changement ici : max-w-4xl -> max-w-6xl pour élargir l'interface --}}
<div class="max-w-6xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Configuration des Couleurs de Groupes</h1>

    {{-- Message de succès --}}
    @if(session('success'))
        <div class="p-4 mb-6 text-sm text-green-800 rounded-lg bg-green-50 dark:bg-gray-800 dark:text-green-400" role="alert">
            {{ session('success') }}
        </div>
    @endif

    {{-- Liste des groupes existants --}}
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg mb-8 overflow-hidden">
        <div class="px-4 py-5 border-b border-gray-200 dark:border-gray-700 sm:px-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">Groupes Actuels</h3>
        </div>
        <ul class="divide-y divide-gray-200 dark:divide-gray-700">
            @forelse($groups as $group)
                <li class="px-4 py-4 flex items-center justify-between sm:px-6">
                    <div class="flex items-center space-x-4">
                        {{-- Aperçu de la couleur --}}
                        <div class="w-8 h-8 rounded-full bg-gradient-to-r {{ $group->gradient }} shadow-sm"></div>
                        <div>
                            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $group->name }} ({{ $group->key }})</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Scroll Clair: {{ $group->scroll_light }} | Scroll Sombre: {{ $group->scroll_dark }}</p>
                        </div>
                    </div>
                    
                    <div class="flex items-center space-x-4">
                        <button type="button" onclick="openEditModal({{ json_encode($group) }})" class="text-sm font-medium text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300">
                            Modifier
                        </button>
                        <form action="{{ route('settings.groups.destroy', $group->id) }}" method="POST" onsubmit="return confirm('Voulez-vous vraiment supprimer ce groupe ?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-sm font-medium text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300">
                                Supprimer
                            </button>
                        </form>
                    </div>
                </li>
            @empty
                <li class="px-4 py-4 sm:px-6 text-sm text-gray-500 dark:text-gray-400">Aucun groupe configuré.</li>
            @endforelse
        </ul>
    </div>

    {{-- Formulaire d'ajout --}}
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
        <div class="px-4 py-5 border-b border-gray-200 dark:border-gray-700 sm:px-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">Ajouter un nouveau groupe</h3>
        </div>
        <div class="px-4 py-5 sm:p-6">
            <form action="{{ route('settings.groups.store') }}" method="POST">
                @csrf
                
                {{-- Inputs cachés techniques gérés par JS, la BDD reçoit toujours 'custom' et le bon dégradé --}}
                <input type="hidden" name="theme" value="custom">
                <input type="hidden" name="gradient" id="create-gradient">

                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Clé (ex: onAir)</label>
                        <input type="text" name="key" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nom Affiché (ex: OnAir)</label>
                        <input type="text" name="name" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Couleur Principale (Mode Clair)</label>
                        {{-- On enlève border et p-0 sur l'input, et on tue les bordures internes de Chrome/Firefox --}}
                        <input type="color" name="scroll_light" id="create-scroll-light" value="#374151" required 
                            class="mt-1 block w-full h-7 cursor-pointer rounded-md shadow-sm border-0 p-0 overflow-hidden [&::-webkit-color-swatch-wrapper]:p-0 [&::-webkit-color-swatch]:border-none [&::-moz-color-swatch]:border-none">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Couleur Secondaire (Mode Sombre)</label>
                        <input type="color" name="scroll_dark" id="create-scroll-dark" value="#374151" required 
                            class="mt-1 block w-full h-7 cursor-pointer rounded-md shadow-sm border-0 p-0 overflow-hidden [&::-webkit-color-swatch-wrapper]:p-0 [&::-webkit-color-swatch]:border-none [&::-moz-color-swatch]:border-none">
                    </div>
                </div>

                <div class="mt-6 flex justify-center">
                    <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Sauvegarder le groupe
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- MODALE DE MODIFICATION --}}
<div id="edit-modal" class="fixed inset-0 z-50 hidden bg-gray-900/60 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-lg w-full overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Modifier le Groupe</h3>
            <button onclick="closeEditModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 text-2xl leading-none">&times;</button>
        </div>
        
        <form id="edit-form" method="POST">
            @csrf @method('PUT')
            
            <input type="hidden" name="theme" value="custom">
            <input type="hidden" name="gradient" id="edit-gradient">

            <div class="p-6 space-y-6">
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    {{-- Ligne 1 : Clé & Nom --}}
                    <div class="flex flex-col justify-end">
                        <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1 h-5">Clé (Non modifiable)</label>
                        <input type="text" id="edit-key-display" disabled class="block w-full h-10 rounded-md border-gray-300 bg-gray-50 text-gray-500 shadow-sm sm:text-sm dark:bg-gray-900 dark:border-gray-700">
                    </div>

                    <div class="flex flex-col justify-end">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 h-5">Nom Affiché</label>
                        <input type="text" name="name" id="edit-name" required class="block w-full h-10 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    </div>

                    {{-- Ligne 2 : Les Couleurs réalignées par le bas (flex items-end) --}}
                    <div class="flex flex-col justify-between h-[76px]">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 leading-tight">Couleur Principale (Mode Clair)</label>
                        <input type="color" name="scroll_light" id="edit-scroll-light" required 
                            class="block w-full h-7 cursor-pointer rounded-md shadow-sm border-0 p-0 overflow-hidden [&::-webkit-color-swatch-wrapper]:p-0 [&::-webkit-color-swatch]:border-none [&::-moz-color-swatch]:border-none">
                    </div>

                    <div class="flex flex-col justify-between h-[76px]">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 leading-tight">Couleur Secondaire (Mode Sombre)</label>
                        <input type="color" name="scroll_dark" id="edit-scroll-dark" required 
                            class="block w-full h-7 cursor-pointer rounded-md shadow-sm border-0 p-0 overflow-hidden [&::-webkit-color-swatch-wrapper]:p-0 [&::-webkit-color-swatch]:border-none [&::-moz-color-swatch]:border-none">
                    </div>
                </div>
            </div>
            
            <div class="px-6 py-4 bg-gray-50 dark:bg-gray-900/30 text-right space-x-3">
                <button type="button" onclick="closeEditModal()" class="inline-flex justify-center py-2 px-4 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600 dark:hover:bg-gray-600">Annuler</button>
                <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">Mettre à jour</button>
            </div>
        </form>
    </div>
</div>

<script>
    // Calcule automatiquement le dégradé à partir des deux sélecteurs HTML5
    function autoCalculateGradient(prefix) {
        const lightColor = document.getElementById(`${prefix}-scroll-light`).value;
        const darkColor = document.getElementById(`${prefix}-scroll-dark`).value;

        // Génère la syntaxe de classes arbitraires Tailwind CSS
        const computedGradient = `from-[${lightColor}] to-[${darkColor}] dark:from-[${darkColor}] dark:to-[${lightColor}]`;
        
        document.getElementById(`${prefix}-gradient`).value = computedGradient;
    }

    // Écoute en direct les changements de palette de l'utilisateur
    document.addEventListener('DOMContentLoaded', function() {
        ['create', 'edit'].forEach(prefix => {
            const lightInput = document.getElementById(`${prefix}-scroll-light`);
            const darkInput = document.getElementById(`${prefix}-scroll-dark`);

            if (lightInput && darkInput) {
                // Calcule une première fois au chargement
                autoCalculateGradient(prefix);

                // Recalcule à chaque mouvement de la souris sur la palette
                lightInput.addEventListener('input', () => autoCalculateGradient(prefix));
                darkInput.addEventListener('input', () => autoCalculateGradient(prefix));
            }
        });
    });

    // Ouvre la modale et applique les valeurs
    function openEditModal(group) {
        document.getElementById('edit-modal').classList.remove('hidden');
        document.getElementById('edit-form').action = `/configuration/groups/${group.id}`;
        
        document.getElementById('edit-key-display').value = group.key;
        document.getElementById('edit-name').value = group.name;
        document.getElementById('edit-scroll-light').value = group.scroll_light;
        document.getElementById('edit-scroll-dark').value = group.scroll_dark;
        
        // Relance le calcul immédiat pour la modale d'édition
        autoCalculateGradient('edit');
    }

    function closeEditModal() {
        document.getElementById('edit-modal').classList.add('hidden');
    }
</script>
@endsection