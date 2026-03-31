<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('medecins', function (Blueprint $table) {
            $table->boolean('verifie_json')->default(false)->after('experience');
            $table->json('donnees_json')->nullable()->after('verifie_json');
        });
    }

    public function down()
    {
        Schema::table('medecins', function (Blueprint $table) {
            $table->dropColumn(['verifie_json', 'donnees_json']);
        });
    }
};