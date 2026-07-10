<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Document;
use Illuminate\Http\Request;

class DocumentApiController extends Controller
{
    // 1. Récupérer la liste des documents (avec possibilité de filtrer par groupe)
    public function index(Request $request)
    {
        $query = Document::query();

        // Si l'API demande un groupe précis (ex: /api/documents?group=on-air)
        if ($request->has('group')) {
            $query->where('group_key', $request->query('group'));
        }

        $documents = $query->latest()->get();

        return response()->json([
            'success' => true,
            'count' => $documents->count(),
            'data' => $documents
        ]);
    }

    // 2. Afficher un seul document en détail
    public function show($id)
    {
        $document = Document::find($id);

        if (!$document) {
            return response()->json([
                'success' => false,
                'message' => 'Document introuvable.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $document
        ]);
    }
}