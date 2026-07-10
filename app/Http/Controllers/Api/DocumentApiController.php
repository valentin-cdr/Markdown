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
}