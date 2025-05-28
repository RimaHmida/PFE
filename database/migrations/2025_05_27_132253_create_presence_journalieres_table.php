<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('presence_journalieres', function (Blueprint $table) {
            $table->id();

            $table->foreignId('affectation_liste_id')
                ->constrained('affectation_listes')
                ->onDelete('cascade');

            $table->foreignId('employe_id')
                ->constrained()
                ->onDelete('cascade');

            $table->date('date');

            $table->boolean('present')->default(false);

            $table->foreignId('recorded_by')
                ->constrained('users')
                ->onDelete('cascade');

            $table->boolean('validated_by_manager')->default(false);

            $table->timestamps();

            $table->unique(['affectation_liste_id', 'employe_id', 'date'], 'unique_presence_entry');
        });
    }
    

    public function down(): void
    {
        Schema::dropIfExists('presence_journalieres');
    }
};
