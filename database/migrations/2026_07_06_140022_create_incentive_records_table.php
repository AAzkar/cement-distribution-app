<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incentive_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_rep_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('incentive_rule_id')->nullable()->constrained('incentive_rules')->nullOnDelete();
            $table->date('record_date');
            $table->decimal('metric_value', 14, 2)->default(0);
            $table->decimal('calculated_amount', 14, 2)->default(0);
            $table->decimal('override_amount', 14, 2)->nullable();
            $table->decimal('final_amount', 14, 2)->default(0);
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['sales_rep_id', 'incentive_rule_id', 'record_date'], 'incentive_record_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incentive_records');
    }
};
