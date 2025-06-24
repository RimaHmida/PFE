<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('conges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employe_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['maladie', 'justifié', 'non justifié']);
            $table->date('date_debut');
            $table->date('date_fin');
            $table->string('description')->nullable();
            $table->string('document')->nullable(); // only for maladie or justifié
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('conges');
    }
};
