<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('game_rooms', function (Blueprint $table) {
            $table->unsignedInteger('current_question_position')->default(0);
            $table->timestamp('current_question_started_at')->nullable();
            $table->string('channel_token', 64)->nullable()->unique();
        });

        DB::table('game_rooms')
            ->whereNull('channel_token')
            ->orderBy('id')
            ->eachById(function (object $room): void {
                DB::table('game_rooms')
                    ->where('id', $room->id)
                    ->update(['channel_token' => Str::random(64)]);
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('game_rooms', function (Blueprint $table) {
            $table->dropUnique(['channel_token']);
            $table->dropColumn([
                'channel_token',
                'current_question_started_at',
                'current_question_position',
            ]);
        });
    }
};
