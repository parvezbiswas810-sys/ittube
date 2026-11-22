<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('type', 40);
            $table->string('title', 160);
            $table->text('body')->nullable();
            $table->json('data')->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'is_read', 'created_at']);
        });
        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->boolean('new_subscriber')->default(true);
            $table->boolean('new_upload')->default(true);
            $table->boolean('comment_reply')->default(true);
            $table->boolean('channel_activity')->default(true);
            $table->boolean('admin_announcement')->default(true);
            $table->boolean('history_paused')->default(false);
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('notification_preferences'); Schema::dropIfExists('notifications'); }
};
