<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('groups', function (Blueprint $table) {
            // Ajoute une colonne pour l'URL, nullable car tous les groupes n'ont pas Superset
            $table->text('superset_url')->nullable()->after('briques_actives');
            $table->text('dolibarr_url')->nullable()->after('superset_url'); 
        });
    }

    public function down()
    {
        Schema::table('groups', function (Blueprint $table) {
            $table->dropColumn('superset_url');
        });
    }
};
