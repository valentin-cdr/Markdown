<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            // On crée directement la colonne JSON pour les tags
            $table->json('tags')->nullable()->after('title'); 
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            // En cas de retour en arrière, on supprime juste cette colonne
            $table->dropColumn('tags');
        });
    }
};
