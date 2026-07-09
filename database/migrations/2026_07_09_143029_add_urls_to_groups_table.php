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
            
            // On vérifie d'abord si la colonne existe pour éviter le crash
            if (!Schema::hasColumn('groups', 'briques_actives')) {
                $table->json('briques_actives')->nullable()->after('name'); 
            }

            if (!Schema::hasColumn('groups', 'superset_url')) {
                $table->text('superset_url')->nullable()->after('briques_actives');
            }

            if (!Schema::hasColumn('groups', 'dolibarr_url')) {
                $table->text('dolibarr_url')->nullable()->after('superset_url');
            }
            
        });
    }

    public function down()
    {
        Schema::table('groups', function (Blueprint $table) {
            $table->dropColumn('superset_url');
        });
    }
};
