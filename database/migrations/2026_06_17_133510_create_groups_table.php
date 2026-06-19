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
        Schema::create('groups', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique(); // ex: 'retd', 'onAir'
            $table->string('name'); // ex: 'RETD'
            $table->string('gradient'); // ex: 'from-amber-600 to-orange-500...'
            $table->string('theme'); // ex: 'amber'
            $table->string('scroll_light'); // ex: '#f59e0b'
            $table->string('scroll_dark'); // ex: '#fbbf24'
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('groups');
    }
};
