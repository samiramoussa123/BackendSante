<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rendez_vous', function (Blueprint $table) {
            $table->timestamp('rappel_matin_envoye_le')->nullable()->after('etat');
            $table->timestamp('rappel_une_heure_envoye_le')->nullable()->after('rappel_matin_envoye_le');
        });
    }

    public function down(): void
    {
        Schema::table('rendez_vous', function (Blueprint $table) {
            $table->dropColumn(['rappel_matin_envoye_le', 'rappel_une_heure_envoye_le']);
        });
    }
};