<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Document;
use Illuminate\Http\Request;

class DocumentApiController extends Controller
{
    // 1. Récupérer TOUS les documents de la base de données
    public function index()
    {
        // 🚀 On désactive la sécurité des sessions web, et on prend absolument tout !
        $documents = Document::withoutGlobalScope('ancient_isolation')
                             ->latest()
                             ->get();

        return response()->json([
            'success' => true,
            'count'   => $documents->count(),
            'data'    => $documents
        ]);
    }

    // 2. Afficher n'importe quel document en détail via son ID
    public function show($id)
    {
        // On cherche par ID sans aucune restriction de groupe
        $document = Document::withoutGlobalScope('ancient_isolation')
                            ->find($id);

        if (!$document) {
            return response()->json([
                'success' => false,
                'message' => 'Document introuvable.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $document
        ]);
    }

    public function search(Request $request)
    {
        $q = $request->query('q');

        if (!$q) {
            return response()->json(['success' => false, 'message' => 'Mot-clé manquant.'], 400);
        }

        $documents = Document::withoutGlobalScope('ancient_isolation')
            ->where('title', 'LIKE', "%{$q}%")
            ->orWhere('content', 'LIKE', "%{$q}%")
            ->get();

        return response()->json([
            'success' => true,
            'count'   => $documents->count(),
            'data'    => $documents
        ]);
    }

    public function byGroup($groupKey)
    {
        $documents = Document::withoutGlobalScope('ancient_isolation')
            ->where('group_key', $groupKey)
            ->get();

        return response()->json([
            'success' => true,
            'count'   => $documents->count(),
            'data'    => $documents
        ]);
    }
    public function searchInGroup(Request $request, $groupKey)
    {
        $q = $request->query('q');

        if (!$q) {
            return response()->json(['success' => false, 'message' => 'Mot-clé manquant.'], 400);
        }

        $documents = Document::withoutGlobalScope('ancient_isolation')
            ->where('group_key', $groupKey)
            // 🛡️ On encadre la recherche titre/contenu dans une fonction (parenthèses en SQL)
            ->where(function ($query) use ($q) {
                $query->where('title', 'LIKE', "%{$q}%")
                      ->orWhere('content', 'LIKE', "%{$q}%");
            })
            ->get();

        return response()->json([
            'success' => true,
            'count'   => $documents->count(),
            'data'    => $documents
        ]);
    }
}