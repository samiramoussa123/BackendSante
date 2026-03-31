<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   // Dans le fichier migration créé
public function up()
{
    Schema::table('consultations', function (Blueprint $table) {
        $table->string('room_id')->nullable()->unique();
        $table->enum('type', ['presentiel', 'video'])->default('presentiel');
        $table->enum('statut_video', ['en_attente', 'en_cours', 'terminee'])->nullable();
        $table->timestamp('debut_video')->nullable();
        $table->timestamp('fin_video')->nullable();
    });
}

public function down()
{
    Schema::table('consultations', function (Blueprint $table) {
        $table->dropColumn(['room_id', 'type', 'statut_video', 'debut_video', 'fin_video']);
    });
}
};
