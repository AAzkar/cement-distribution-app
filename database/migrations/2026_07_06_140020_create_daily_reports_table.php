<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_reports', function (Blueprint $table) {
            $table->id();
            $table->date('report_date');
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->cascadeOnDelete();
            $table->decimal('opening_balance', 14, 2)->default(0);
            $table->decimal('total_inflows', 14, 2)->default(0);
            $table->decimal('total_outflows', 14, 2)->default(0);
            $table->decimal('closing_balance', 14, 2)->default(0);
            $table->json('cheques_summary')->nullable();
            $table->enum('status', ['draft', 'submitted', 'approved', 'locked'])->default('draft');
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->unique(['report_date', 'warehouse_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_reports');
    }
};
