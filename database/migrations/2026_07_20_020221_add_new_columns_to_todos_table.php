<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('todos', function (Blueprint $table) {
            $table->date('due_date')->nullable()->after('completed');
            $table->dateTime('reminder_date')->nullable()->after('due_date');
            $table->string('repeat_type')->nullable()->after('reminder_date');
            $table->text('notes')->nullable()->after('repeat_type');
        });
    }

    public function down(): void
    {
        Schema::table('todos', function (Blueprint $table) {
            $table->dropColumn(['due_date', 'reminder_date', 'repeat_type', 'notes']);
        });
    }
};
