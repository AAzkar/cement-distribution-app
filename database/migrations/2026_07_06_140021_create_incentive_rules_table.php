<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incentive_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('metric', ['sales', 'collections', 'invoice_count']);
            $table->enum('rule_type', ['slab', 'fixed', 'percentage']);
            $table->decimal('min_target', 14, 2)->nullable();
            $table->json('slabs')->nullable();
            $table->enum('allowance_type', ['fuel', 'food', 'other', 'bonus']);
            $table->decimal('fixed_amount', 14, 2)->nullable();
            $table->decimal('percentage', 5, 2)->nullable();
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->foreignId('zone_id')->nullable()->constrained('zones')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incentive_rules');
    }
};
