<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('videos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('channel_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('title', 180);
            $table->string('slug', 220)->unique();
            $table->text('description')->nullable();
            $table->string('type', 10)->default('video'); // video, short
            $table->string('thumbnail')->nullable();
            $table->string('video_path')->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->unsignedInteger('duration')->default(0); // seconds
            $table->unsignedBigInteger('file_size')->default(0); // bytes
            $table->string('resolution', 20)->nullable();
            $table->string('status', 20)->default('processing'); // draft, processing, pending, published, rejected, suspended, failed
            $table->string('visibility', 20)->default('public'); // public, unlisted, private
            $table->unsignedBigInteger('views_count')->default(0);
            $table->unsignedBigInteger('likes_count')->default(0);
            $table->unsignedBigInteger('dislikes_count')->default(0);
            $table->unsignedBigInteger('comments_count')->default(0);
            $table->boolean('is_featured')->default(false);
            $table->boolean('allow_comments')->default(true);
            $table->text('rejection_reason')->nullable();
            $table->string('processing_error')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['type', 'status', 'visibility', 'published_at'], 'videos_feed_idx');
            $table->index(['channel_id', 'status']);
            $table->index('views_count');
        });
    }
    public function down(): void { Schema::dropIfExists('videos'); }
};
