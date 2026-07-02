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

        // 🚀 INTERCEPTION : On utilise input() pour être sûr de capter la valeur du sélecteur
        if ($isAdmin && request()->has('group')) {
            $requestedGroup = request()->input('group'); // 👈 Remplacé query() par input()
            
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
        // (Le cache se reconstruira proprement tout seul suite au cache:clear)
        $groupBrandConfig = \Illuminate\Support\Facades\Cache::remember('groups_config', 3600, function () {
            return \App\Models\Group::all()->keyBy('key')->toArray();
        });

        $matchingGroups = array_intersect($userGroups, array_keys($groupBrandConfig));

        if (!empty($matchingGroups)) {
            return reset($matchingGroups); 
        }

        return null;
    }
}