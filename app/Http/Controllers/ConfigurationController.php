<?php

namespace App\Http\Controllers;

use App\Models\Group;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ConfigurationController extends Controller
{
    // 🎨 Modèles de couleurs préconfigurés pour simplifier la vie de l'utilisateur
    private $presets = [
        'amber' => [
            'gradient'     => 'from-amber-600 to-orange-500 dark:from-amber-400 dark:to-orange-400',
            'scroll_light' => '#f59e0b',
            'scroll_dark'  => '#fbbf24',
        ],
        'blue' => [
            'gradient'     => 'from-blue-600 to-cyan-500 dark:from-blue-400 dark:to-cyan-400',
            'scroll_light' => '#3b82f6',
            'scroll_dark'  => '#60a5fa',
        ],
        'emerald' => [
            'gradient'     => 'from-emerald-600 to-teal-500 dark:from-emerald-400 dark:to-teal-300',
            'scroll_light' => '#10b981',
            'scroll_dark'  => '#34d399',
        ],
        'purple' => [
            'gradient'     => 'from-purple-600 to-indigo-500 dark:from-purple-400 dark:to-indigo-400',
            'scroll_light' => '#a855f7',
            'scroll_dark'  => '#c084fc',
        ],
        'rose' => [
            'gradient'     => 'from-rose-600 to-red-500 dark:from-rose-400 dark:to-red-400',
            'scroll_light' => '#f43f5e',
            'scroll_dark'  => '#fb7185',
        ],
    ];

    public function index()
    {
        $groups = Group::all();
        return view('settings.groups', compact('groups'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'key'   => 'required|alpha_dash|unique:groups,key',
            'name'  => 'required|string|max:250',
            'theme' => 'required',
        ]);

        $data = $request->all();

        // Si l'utilisateur a choisi un thème préconfiguré, on remplit automatiquement le reste !
        if ($request->theme !== 'custom' && isset($this->presets[$request->theme])) {
            $preset = $this->presets[$request->theme];
            $data['gradient']     = $preset['gradient'];
            $data['scroll_light'] = $preset['scroll_light'];
            $data['scroll_dark']  = $preset['scroll_dark'];
        } else {
            // Validation stricte si mode personnalisé
            $request->validate([
                'gradient'     => 'required',
                'scroll_light' => 'required',
                'scroll_dark'  => 'required',
            ]);
        }

        Group::create($data);
        Cache::forget('groups_config'); // Nettoyage du cache global

        return redirect()->back()->with('success', 'Nouveau groupe créé avec succès !');
    }

    public function update(Request $request, Group $group)
    {
        $request->validate([
            'name'  => 'required|string|max:250',
            'theme' => 'required',
        ]);

        $data = $request->all();

        if ($request->theme !== 'custom' && isset($this->presets[$request->theme])) {
            $preset = $this->presets[$request->theme];
            $data['gradient']     = $preset['gradient'];
            $data['scroll_light'] = $preset['scroll_light'];
            $data['scroll_dark']  = $preset['scroll_dark'];
        }

        $group->update($data);
        Cache::forget('groups_config');

        return redirect()->back()->with('success', 'Groupe mis à jour avec succès !');
    }

    public function destroy(Group $group)
    {
        $group->delete();
        Cache::forget('groups_config');

        return redirect()->back()->with('success', 'Groupe supprimé de l\'application.');
    }

   public function switchGroup($key = null)
    {
        if (!in_array('retd', session('keycloak_groups', []))) {
            abort(403, 'Accès refusé.');
        }

        $finalKey = $key ?? request('group') ?? request('key');

        // Si on demande global ou retd, on enregistre explicitement 'retd'
        if (empty($finalKey) || $finalKey === 'global' || $finalKey === 'retd') {
            $targetKey = 'retd';
        } else {
            $targetKey = $finalKey;
        }

        session([
            'admin_forced_group' => $targetKey,
            'active_group_key'   => $targetKey,
            'forced_group_key'   => $targetKey
        ]);

        session()->save();

        return redirect()->back();
    }
}