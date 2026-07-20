<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stations', function (Blueprint $table) {
            $table->renameColumn('name', 'site_id');
            $table->string('end_id')->nullable()->after('site_id');
            $table->string('operadora')->nullable()->after('end_id');
        });
    }

    public function down(): void
    {
        Schema::table('stations', function (Blueprint $table) {
            $table->dropColumn(['end_id', 'operadora']);
            $table->renameColumn('site_id', 'name');
        });
    }
};
