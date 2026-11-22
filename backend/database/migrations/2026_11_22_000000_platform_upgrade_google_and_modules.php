<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('google_id', 64)->nullable()->unique()->after('email');
            $table->string('google_avatar')->nullable()->after('google_id');
            $table->string('auth_provider', 20)->default('password')->after('google_avatar');
            $table->string('password')->nullable()->change();
        });

        Schema::create('device_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('token', 255);
            $table->string('platform', 20)->default('android');
            $table->string('device_name', 100)->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'token']);
        });

        Schema::create('copyright_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reporter_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('content_type', 20); // video, short, audio, channel
            $table->unsignedBigInteger('content_id')->nullable();
            $table->string('content_url', 500)->nullable();
            $table->string('rights_holder', 150);
            $table->string('reason', 40);
            $table->text('description')->nullable();
            $table->string('evidence_path')->nullable();
            $table->string('contact_name', 120)->nullable();
            $table->string('contact_email', 150)->nullable();
            $table->string('status', 20)->default('pending'); // pending, review, approved, rejected, disputed
            $table->text('admin_note')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('admin_users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['status', 'created_at']);
            $table->index(['content_type', 'content_id']);
        });

        Schema::create('copyright_matches', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->foreignId('video_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('match_type', 20)->default('audio'); // audio, video
            $table->decimal('confidence', 5, 2)->nullable();
            $table->string('source', 60)->nullable();
            $table->timestamps();
        });

        Schema::create('live_streams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('channel_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('thumbnail')->nullable();
            $table->string('status', 20)->default('scheduled');
            $table->string('visibility', 20)->default('public');
            $table->string('provider', 40)->nullable();
            $table->string('stream_key', 120)->nullable();
            $table->string('rtmp_url', 255)->nullable();
            $table->string('playback_url', 500)->nullable();
            $table->string('game_title', 120)->nullable();
            $table->timestamp('scheduled_for')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->unsignedInteger('peak_viewers')->default(0);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['status', 'scheduled_for']);
        });

        Schema::create('ad_placements', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('position', 20); // preroll, midroll, postroll
            $table->unsignedInteger('offset_seconds')->default(0);
            $table->string('ad_tag_url', 500)->nullable();
            $table->string('provider', 40)->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });

        Schema::create('monetization_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('status', 20)->default('ineligible'); // ineligible, review, eligible, enabled, suspended
            $table->boolean('ads_enabled')->default(false);
            $table->string('payout_method', 40)->nullable();
            $table->string('payout_reference')->nullable();
            $table->text('admin_note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monetization_profiles');
        Schema::dropIfExists('ad_placements');
        Schema::dropIfExists('live_streams');
        Schema::dropIfExists('copyright_matches');
        Schema::dropIfExists('copyright_reports');
        Schema::dropIfExists('device_tokens');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['google_id', 'google_avatar', 'auth_provider']);
        });
    }
};
