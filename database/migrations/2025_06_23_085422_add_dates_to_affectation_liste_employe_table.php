<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('affectation_liste_employe', function (Blueprint $table) {
            $table->date('date_debut_reelle')->nullable();
            $table->date('date_fin_reelle')->nullable();
        });
    }

    public function down(): void {
        Schema::table('affectation_liste_employe', function (Blueprint $table) {
            $table->dropColumn(['date_debut_reelle', 'date_fin_reelle']);
        });
    }
};
