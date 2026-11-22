<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('channels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('name', 100);
            $table->string('handle', 60)->unique();
            $table->string('avatar')->nullable();
            $table->string('banner')->nullable();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('subscribers_count')->default(0);
            $table->unsignedBigInteger('videos_count')->default(0);
            $table->unsignedBigInteger('total_views')->default(0);
            $table->boolean('is_verified')->default(false);
            $table->boolean('is_featured')->default(false);
            $table->string('status', 20)->default('active'); // active, suspended
            $table->timestamps();
            $table->softDeletes();
            $table->index(['status', 'is_featured']);
        });
    }
    public function down(): void { Schema::dropIfExists('channels'); }
};
