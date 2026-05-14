<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('video_projects', function (Blueprint $table) {
            $table->float('audio_start')->default(0)->after('audio_duration');
            $table->float('audio_end')->nullable()->after('audio_start');
        });
    }

    public function down(): void
    {
        Schema::table('video_projects', function (Blueprint $table) {
            $table->dropColumn(['audio_start', 'audio_end']);
        });
    }
};
