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
    Schema::create('affectation_liste_employe', function (Blueprint $table) {
        $table->id();
        $table->foreignId('employe_id')->constrained()->onDelete('cascade');
        $table->foreignId('affectation_liste_id')->constrained()->onDelete('cascade');
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('affectation_liste_employe');
    }
};
