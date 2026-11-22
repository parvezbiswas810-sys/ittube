<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('video_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('video_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->string('session_key', 64)->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->boolean('completed')->default(false);
            $table->timestamp('viewed_at')->useCurrent();
            $table->index(['video_id', 'viewed_at']);
            $table->index(['user_id', 'viewed_at']);
        });
    }
    public function down(): void { Schema::dropIfExists('video_views'); }
};
