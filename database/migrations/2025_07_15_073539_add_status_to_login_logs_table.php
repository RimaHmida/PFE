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
    Schema::table('login_logs', function (Blueprint $table) {
        $table->string('status')->default('success'); // success ou failed
        $table->text('message')->nullable(); // pour ajouter les raisons si besoin
    });
}

public function down()
{
    Schema::table('login_logs', function (Blueprint $table) {
        $table->dropColumn(['status', 'message']);
    });
}

};
