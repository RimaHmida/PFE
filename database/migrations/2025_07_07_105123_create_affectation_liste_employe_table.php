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
        Schema::create('affectation_liste_employe', function (Blueprint $table) {
            $table->id();

            // Références vers la liste d'affectation et l'employé
            $table->unsignedBigInteger('affectation_liste_id');
            $table->unsignedBigInteger('employe_id');

            // Colonnes supplémentaires
            $table->date('date_debut_reelle')->nullable();
            $table->date('date_fin_reelle')->nullable();

            $table->unsignedBigInteger('remplace_par_employe_id')->nullable();

            $table->timestamps();

            // Contraintes de clé étrangère
            $table->foreign('affectation_liste_id')
                  ->references('id')->on('affectation_listes')
                  ->onDelete('cascade');

            $table->foreign('employe_id')
                  ->references('id')->on('employes')
                  ->onDelete('cascade');

            $table->foreign('remplace_par_employe_id')
                  ->references('id')->on('employes')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('affectation_liste_employe', function (Blueprint $table) {
            $table->dropForeign(['affectation_liste_id']);
            $table->dropForeign(['employe_id']);
            $table->dropForeign(['remplace_par_employe_id']);
        });

        Schema::dropIfExists('affectation_liste_employe');
    }
};
