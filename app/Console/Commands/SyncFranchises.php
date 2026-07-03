<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\Group;

class SyncFranchises extends Command
{
    // La commande à taper dans le terminal
    protected $signature = 'retd:sync-franchises';

    // La description de la commande
    protected $description = 'Récupère les franchises depuis l\'API externe et les synchronise en BDD';

    public function handle()
    {
        $this->info('Début de la synchronisation...');

        // 1. Récupération des identifiants dans le .env
        $url = env('RETD_API_URL');
        $token = env('RETD_API_TOKEN');

        if (!$url || !$token) {
            $this->error('Erreur : RETD_API_URL ou RETD_API_TOKEN n\'est pas configuré dans le .env');
            return Command::FAILURE;
        }

        // 2. Appel de l'API (avec clean SSL pour la préprod si besoin)
        $response = Http::withToken($token)
            ->withoutVerifying() 
            ->acceptJson()
            ->get($url . '/franchises');

        if ($response->failed()) {
            $this->error('Impossible de joindre l\'API. Code erreur : ' . $response->status());
            return Command::FAILURE;
        }

        // 3. Extraction des données
        $franchises = $response->json('data') ?? $response->json();

        if (empty($franchises)) {
            $this->warn('Aucune franchise retournée par l\'API.');
            return Command::SUCCESS;
        }

        // 4. Synchronisation en base de données
        $bar = $this->output->createProgressBar(count($franchises));
        $bar->start();

        foreach ($franchises as $f) {
            Group::updateOrCreate(
                ['key' => $f['slug']], 
                [
                    'name'         => $f['nom'],
                    'scroll_light' => $f['couleurs']['primaire'] ?? '#f97316',
                    'scroll_dark'  => $f['couleurs']['secondaire'] ?? '#f97316',
                    'gradient'     => '',        // Pour calmer la colonne gradient
                    'theme'        => 'default', // Pour calmer la colonne theme
                ]
            );
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Synchronisation réussie ! ' . count($franchises) . ' franchises synchronisées.');

        return Command::SUCCESS;
    }
}