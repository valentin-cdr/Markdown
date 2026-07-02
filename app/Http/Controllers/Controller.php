<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use App\Models\Group;
use Illuminate\Support\Facades\Cache;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    /**
     * 🚀 Fonction globale pour récupérer la clé de l'environnement actuel côté backend
     */
    protected function getActiveGroupKey()
    {
        if (!auth()->check()) return null;

        $userGroups = (array) session('keycloak_groups', []);
        $isAdmin = in_array('retd', $userGroups);

        // 🚀 INTERCEPTION ICI : On met à jour la session AVANT que les autres contrôleurs ne cherchent les documents !
        if ($isAdmin && request()->has('group')) {
            $requestedGroup = request()->query('group');
            if (empty($requestedGroup)) {
                session()->forget('admin_forced_group');
            } else {
                session(['admin_forced_group' => $requestedGroup]);
            }
        }

        // 1. Si l'admin a forcé un groupe via le sélecteur, c'est ce groupe qui gagne
        if ($isAdmin && session()->has('admin_forced_group')) {
            return session('admin_forced_group');
        }

        // 2. Sinon, on prend le vrai groupe Keycloak de l'utilisateur
        $groupBrandConfig = \Illuminate\Support\Facades\Cache::remember('groups_config', 3600, function () {
            return \App\Models\Group::all()->keyBy('key')->toArray();
        });

        $matchingGroups = array_intersect($userGroups, array_keys($groupBrandConfig));

        if (!empty($matchingGroups)) {
            return reset($matchingGroups); // Retourne par ex: 'onAir' ou 'retd'
        }

        return null;
    }
}