<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('affectation_listes', function (Blueprint $table) {
            $table->boolean('validated_by_manager')->default(false)->after('date_fin');
            $table->timestamp('validated_at')->nullable()->after('validated_by_manager');
        });
    }

    public function down(): void
    {
        Schema::table('affectation_listes', function (Blueprint $table) {
            $table->dropColumn(['validated_by_manager', 'validated_at']);
        });
    }
};
