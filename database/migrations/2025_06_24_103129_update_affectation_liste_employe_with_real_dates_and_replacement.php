<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('affectation_liste_employe', function (Blueprint $table) {
            if (!Schema::hasColumn('affectation_liste_employe', 'date_debut_reelle')) {
                $table->date('date_debut_reelle')->nullable();
            }

            if (!Schema::hasColumn('affectation_liste_employe', 'date_fin_reelle')) {
                $table->date('date_fin_reelle')->nullable();
            }

            if (!Schema::hasColumn('affectation_liste_employe', 'remplace_par_employe_id')) {
                $table->unsignedBigInteger('remplace_par_employe_id')->nullable();
                $table->foreign('remplace_par_employe_id')
                    ->references('id')->on('employes')
                    ->onDelete('set null');
            }
        });
    }

    public function down(): void {
        Schema::table('affectation_liste_employe', function (Blueprint $table) {
            $table->dropForeign(['remplace_par_employe_id']);
            $table->dropColumn(['date_debut_reelle', 'date_fin_reelle', 'remplace_par_employe_id']);
        });
    }
};
