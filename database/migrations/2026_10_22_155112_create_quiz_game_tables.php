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
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_id')->constrained()->cascadeOnDelete();
            $table->text('prompt');
            $table->unsignedInteger('position');
            $table->timestamps();

            $table->unique(['quiz_id', 'position']);
        });

        Schema::create('question_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->string('label', 1);
            $table->text('text');
            $table->unsignedTinyInteger('position');
            $table->boolean('is_correct')->default(false);
            $table->timestamps();

            $table->unique(['question_id', 'label']);
            $table->unique(['question_id', 'position']);
            $table->unique(['question_id', 'id']);
        });

        Schema::create('game_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_id')->constrained()->restrictOnDelete();
            $table->foreignId('host_id')->constrained('users')->restrictOnDelete();
            $table->string('code', 6)->unique();
            $table->string('status', 20)->default('waiting');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index(['quiz_id', 'created_at']);
            $table->index('host_id');
        });

        Schema::create('players', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_room_id')->constrained()->restrictOnDelete();
            $table->string('nickname', 40);
            $table->string('avatar')->nullable();
            $table->unsignedInteger('score')->default(0);
            $table->timestamps();

            $table->unique(['game_room_id', 'nickname']);
        });

        Schema::create('player_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_id')->constrained()->restrictOnDelete();
            $table->foreignId('question_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('question_option_id');
            $table->boolean('is_correct')->default(false);
            $table->unsignedInteger('response_time_ms');
            $table->unsignedInteger('points_earned')->default(0);
            $table->timestamp('answered_at');
            $table->timestamps();

            $table->unique(['player_id', 'question_id']);
            $table->index('question_id');
            $table->foreign(['question_id', 'question_option_id'])
                ->references(['question_id', 'id'])
                ->on('question_options')
                ->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('player_answers');
        Schema::dropIfExists('players');
        Schema::dropIfExists('game_rooms');
        Schema::dropIfExists('question_options');
        Schema::dropIfExists('questions');
    }
};
