<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('playlists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title', 120);
            $table->text('description')->nullable();
            $table->string('visibility', 20)->default('public');
            $table->unsignedInteger('videos_count')->default(0);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['user_id', 'created_at']);
        });
        Schema::create('playlist_videos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('playlist_id')->constrained()->cascadeOnDelete();
            $table->foreignId('video_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
            $table->unique(['playlist_id', 'video_id']);
        });
    }
    public function down(): void { Schema::dropIfExists('playlist_videos'); Schema::dropIfExists('playlists'); }
};
