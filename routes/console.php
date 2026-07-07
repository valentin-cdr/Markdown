<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule; // 👈 1. Ajouter cette ligne obligatoire

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');


// --------------------------------------------------------
// 🤖 TES TÂCHES AUTOMATIQUES
// --------------------------------------------------------

// 👈 2. Remplacer 'app:ton-nom-de-commande' par ta vraie commande
Schedule::command('retd:sync-franchises')->everyFiveMinutes();

