<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_room_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_room_id')->constrained()->restrictOnDelete();
            $table->foreignId('question_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('position');
            $table->text('prompt');
            $table->unsignedInteger('points');
            $table->unsignedSmallInteger('time_limit_seconds')->nullable();
            $table->json('options');
            $table->timestamps();

            $table->unique(['game_room_id', 'position']);
        });

        Schema::table('player_answers', function (Blueprint $table) {
            $table->foreignId('game_room_question_id')->nullable();
            $table->string('selected_option_label', 1)->nullable();
            $table->text('selected_option_text')->nullable();
        });

        DB::table('game_rooms')
            ->where('status', '!=', 'waiting')
            ->orderBy('id')
            ->eachById(function (object $room): void {
                $questions = DB::table('questions')
                    ->where('quiz_id', $room->quiz_id)
                    ->orderBy('position')
                    ->get();

                foreach ($questions as $question) {
                    $options = DB::table('question_options')
                        ->where('question_id', $question->id)
                        ->orderBy('position')
                        ->get(['id', 'label', 'text', 'is_correct'])
                        ->map(fn (object $option): array => [
                            'id' => (int) $option->id,
                            'label' => $option->label,
                            'text' => $option->text,
                            'is_correct' => (bool) $option->is_correct,
                        ])
                        ->all();

                    $snapshotId = DB::table('game_room_questions')->insertGetId([
                        'game_room_id' => $room->id,
                        'question_id' => $question->id,
                        'position' => $question->position,
                        'prompt' => $question->prompt,
                        'points' => $question->points,
                        'time_limit_seconds' => $question->time_limit_seconds,
                        'options' => json_encode($options, JSON_THROW_ON_ERROR),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    $optionSnapshots = collect($options)->keyBy('id');
                    $playerIds = DB::table('players')
                        ->where('game_room_id', $room->id)
                        ->select('id');

                    DB::table('player_answers')
                        ->where('question_id', $question->id)
                        ->whereIn('player_id', $playerIds)
                        ->orderBy('id')
                        ->eachById(function (object $answer) use ($snapshotId, $optionSnapshots): void {
                            $optionSnapshot = $optionSnapshots->get($answer->question_option_id);

                            DB::table('player_answers')
                                ->where('id', $answer->id)
                                ->update([
                                    'game_room_question_id' => $snapshotId,
                                    'selected_option_label' => $optionSnapshot['label'] ?? null,
                                    'selected_option_text' => $optionSnapshot['text'] ?? null,
                                ]);
                        });
                }
            });

        Schema::table('player_answers', function (Blueprint $table) {
            $table->dropUnique(['player_id', 'question_id']);
            $table->dropForeign(['question_id', 'question_option_id']);
            $table->dropForeign(['question_id']);
            $table->unsignedBigInteger('question_id')->nullable()->change();
            $table->unsignedBigInteger('question_option_id')->nullable()->change();
            $table->foreign('game_room_question_id')->references('id')->on('game_room_questions')->restrictOnDelete();
            $table->foreign('question_id')->references('id')->on('questions')->nullOnDelete();
            $table->foreign('question_option_id')->references('id')->on('question_options')->nullOnDelete();
            $table->unique(['player_id', 'game_room_question_id']);
        });
    }

    public function down(): void
    {
        throw new RuntimeException(
            'This migration contains historical question snapshots. Rolling it back would discard match history; create a forward migration instead.',
        );
    }
};
