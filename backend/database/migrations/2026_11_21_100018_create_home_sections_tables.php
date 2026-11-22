<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('home_sections', function (Blueprint $table) {
            $table->id();
            $table->string('title', 100);
            $table->string('type', 30)->default('custom'); // featured, trending, recent, popular, category, custom, shorts
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('item_limit')->default(12);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('home_section_videos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_id')->constrained('home_sections')->cascadeOnDelete();
            $table->foreignId('video_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('position')->default(0);
            $table->unique(['section_id', 'video_id']);
        });
    }
    public function down(): void { Schema::dropIfExists('home_section_videos'); Schema::dropIfExists('home_sections'); }
};
