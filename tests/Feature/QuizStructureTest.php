<?php

namespace Tests\Feature;

use App\Models\GameRoom;
use App\Models\GameRoomQuestion;
use App\Models\Player;
use App\Models\PlayerAnswer;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuizStructureTest extends TestCase
{
    use RefreshDatabase;

    public function test_quiz_relations_retrieve_questions_options_rooms_players_and_answers(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $quiz = Quiz::factory()->for($admin, 'admin')->create();
        $question = $quiz->questions()->create([
            'prompt' => '¿Cuál es la capital de Argentina?',
            'position' => 1,
        ]);
        $option = $question->options()->create([
            'label' => 'A',
            'text' => 'Buenos Aires',
            'position' => 1,
            'is_correct' => true,
        ]);
        foreach (['B', 'C', 'D'] as $position => $label) {
            $question->options()->create([
                'label' => $label,
                'text' => fake()->word(),
                'position' => $position + 2,
            ]);
        }
        $room = $quiz->gameRooms()->create([
            'host_id' => $admin->id,
            'code' => 'ABC123',
        ]);
        $player = $room->players()->create(['nickname' => 'Luna']);
        $answer = $player->answers()->create([
            'question_id' => $question->id,
            'question_option_id' => $option->id,
            'response_time_ms' => 1200,
            'answered_at' => now(),
        ]);

        $this->assertSame($admin->id, $quiz->fresh()->admin->id);
        $this->assertSame($quiz->id, $admin->fresh()->quizzes->sole()->id);
        $this->assertSame($question->id, $quiz->fresh()->questions->sole()->id);
        $this->assertSame(4, $question->fresh()->options->count());
        $this->assertSame($option->id, $question->fresh()->options->first()->id);
        $this->assertSame($quiz->id, $room->fresh()->quiz->id);
        $this->assertSame($admin->id, $room->fresh()->host->id);
        $this->assertSame($room->id, $admin->fresh()->hostedRooms->sole()->id);
        $this->assertSame($player->id, $room->fresh()->players->sole()->id);
        $this->assertSame($room->id, $player->fresh()->gameRoom->id);
        $this->assertSame($question->id, $answer->fresh()->question->id);
        $this->assertSame($option->id, $answer->fresh()->option->id);
    }

    public function test_question_positions_are_unique_within_a_quiz(): void
    {
        $quiz = Quiz::factory()->create();
        Question::factory()->for($quiz)->create(['position' => 1]);

        $this->expectException(QueryException::class);

        Question::factory()->for($quiz)->create(['position' => 1]);
    }

    public function test_question_option_labels_and_positions_are_unique_within_a_question(): void
    {
        $question = Question::factory()->create();
        QuestionOption::factory()->for($question)->create([
            'label' => 'A',
            'position' => 1,
        ]);

        $this->expectException(QueryException::class);

        QuestionOption::factory()->for($question)->create([
            'label' => 'A',
            'position' => 2,
        ]);
    }

    public function test_question_option_positions_are_unique_within_a_question(): void
    {
        $question = Question::factory()->create();
        QuestionOption::factory()->for($question)->create([
            'label' => 'A',
            'position' => 1,
        ]);

        $this->expectException(QueryException::class);

        QuestionOption::factory()->for($question)->create([
            'label' => 'B',
            'position' => 1,
        ]);
    }

    public function test_room_codes_are_unique(): void
    {
        $room = GameRoom::factory()->create(['code' => 'ARENA1']);

        $this->expectException(QueryException::class);

        GameRoom::factory()->for($room->quiz)->create(['code' => 'ARENA1']);
    }

    public function test_nicknames_are_unique_per_room_but_can_be_reused_in_another_room(): void
    {
        $room = GameRoom::factory()->create();
        $otherRoom = GameRoom::factory()->create();
        Player::factory()->for($room)->create(['nickname' => 'Luna']);
        $sameNicknameInOtherRoom = Player::factory()->for($otherRoom)->create(['nickname' => 'Luna']);

        $this->assertModelExists($sameNicknameInOtherRoom);

        $this->expectException(QueryException::class);

        Player::factory()->for($room)->create(['nickname' => 'Luna']);
    }

    public function test_player_can_submit_only_one_answer_per_match_question_snapshot(): void
    {
        $room = GameRoom::factory()->create();
        $player = Player::factory()->for($room)->create();
        $question = Question::factory()->for($room->quiz)->create();
        $option = QuestionOption::factory()->for($question)->create();
        $snapshot = $this->createQuestionSnapshot($room, $question);
        PlayerAnswer::factory()->create([
            'player_id' => $player->id,
            'game_room_question_id' => $snapshot->id,
            'question_id' => $question->id,
            'question_option_id' => $option->id,
        ]);

        $this->expectException(QueryException::class);

        PlayerAnswer::factory()->create([
            'player_id' => $player->id,
            'game_room_question_id' => $snapshot->id,
            'question_id' => $question->id,
            'question_option_id' => $option->id,
        ]);
    }

    public function test_player_answer_factory_selects_an_option_belonging_to_its_question(): void
    {
        $answer = PlayerAnswer::factory()->create();

        $this->assertSame($answer->question_id, $answer->option->question_id);
    }

    public function test_answer_must_reference_an_existing_match_question_snapshot(): void
    {
        $room = GameRoom::factory()->create();
        $player = Player::factory()->for($room)->create();
        $question = Question::factory()->for($room->quiz)->create();
        $snapshot = $this->createQuestionSnapshot($room, $question);

        $this->expectException(QueryException::class);

        PlayerAnswer::factory()->create([
            'player_id' => $player->id,
            'game_room_question_id' => $snapshot->id + 1,
            'question_id' => $question->id,
            'question_option_id' => null,
        ]);
    }

    public function test_deleting_a_question_preserves_its_historical_answer_snapshot(): void
    {
        $room = GameRoom::factory()->create();
        $player = Player::factory()->for($room)->create();
        $question = Question::factory()->for($room->quiz)->create();
        $option = QuestionOption::factory()->for($question)->create();
        $snapshot = $this->createQuestionSnapshot($room, $question);
        PlayerAnswer::factory()->create([
            'player_id' => $player->id,
            'game_room_question_id' => $snapshot->id,
            'question_id' => $question->id,
            'question_option_id' => $option->id,
        ]);

        $question->delete();

        $this->assertModelMissing($question);
        $this->assertDatabaseHas('game_room_questions', [
            'id' => $snapshot->id,
            'prompt' => $question->prompt,
        ]);
        $this->assertDatabaseHas('player_answers', [
            'game_room_question_id' => $snapshot->id,
            'question_id' => null,
            'question_option_id' => null,
        ]);

        try {
            $room->quiz->delete();
            $this->fail('A quiz used by a game room should be protected from deletion.');
        } catch (QueryException) {
            $this->assertModelExists($room->quiz);
        }
    }

    private function createQuestionSnapshot(GameRoom $room, Question $question): GameRoomQuestion
    {
        return GameRoomQuestion::create([
            'game_room_id' => $room->id,
            'question_id' => $question->id,
            'position' => $question->position,
            'prompt' => $question->prompt,
            'points' => $question->points,
            'time_limit_seconds' => $question->time_limit_seconds,
            'options' => $question->options->map(fn (QuestionOption $option): array => [
                'id' => $option->id,
                'label' => $option->label,
                'text' => $option->text,
                'is_correct' => $option->is_correct,
            ])->all(),
        ]);
    }
}
