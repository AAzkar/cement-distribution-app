<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cheques_received', function (Blueprint $table) {
            $table->foreignId('cashbook_entry_id')->nullable()->after('handover_id')
                ->constrained('cashbook_entries')->nullOnDelete();
        });

        Schema::table('cheques_issued', function (Blueprint $table) {
            $table->foreignId('cashbook_entry_id')->nullable()->after('supplier_id')
                ->constrained('cashbook_entries')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('cheques_received', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cashbook_entry_id');
        });

        Schema::table('cheques_issued', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cashbook_entry_id');
        });
    }
};
