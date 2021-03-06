<?php

namespace Tests\Unit;

use App\Models\Activity;
use App\Models\Reply;
use App\Models\Thread;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ActivityTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_records_activity_when_a_thread_is_created()
    {
        $this->login();

        $thread = Thread::factory()->create();

        $this->assertDatabaseHas('activities', [
            'user_id' => auth()->id(),
            'subject_id' => $thread->id,
            'subject_type' => 'App\Models\Thread',
            'type' => 'created_thread'
        ]);

        $activity = Activity::first();

        $this->assertEquals($activity->subject->id, $thread->id);
    }

    /** @test */
    public function it_records_activities_when_a_reply_is_created()
    {
        $this->login();

        Reply::factory()->create();

        $this->assertEquals(2, Activity::count());
    }

    /** @test */
    public function it_returns_activity_feed_for_any_user()
    {
        $this->login();

        Thread::factory(2)->create(['user_id' => auth()->id()]);

        auth()->user()->activities()->first()->update(['created_at' => Carbon::now()->subWeek()]);

        $feed = Activity::feed(auth()->user());

        $this->assertTrue($feed->keys()->contains(Carbon::now()->format('Y-m-d')));
        $this->assertTrue($feed->keys()->contains(Carbon::now()->subWeek()->format('Y-m-d')));
    }

    /** @test */
    public function it_records_activity_if_reply_is_favorited()
    {
        $this->login();

        $reply = Reply::factory()->create(['user_id' => auth()->id()]);

        $reply->favorite();

        $this->assertDatabaseHas('activities', [
            'user_id' => auth()->id(),
            'subject_id' => $reply->favorites->first()->id,
            'subject_type' => 'App\Models\Favorite',
            'type' => 'created_favorite'
        ]);

    }

    /** @test */
    public function it_removes_activity_if_reply_is_unfavorited()
    {
        $this->login();

        $reply = Reply::factory()->create(['user_id' => auth()->id()]);

        $reply->favorite();

        $reply->unfavorite();

        $this->assertDatabaseMissing('activities', ['type' => 'created_favorite']);
    }

    /** @test */
    public function it_removes_favorite_activity_if_reply_is_deleted()
    {
        $this->login();

        $reply = Reply::factory()->create(['user_id' => auth()->id()]);

        $reply->favorite();

        $reply->delete();

        $this->assertDatabaseMissing('activities', ['type' => 'created_favorite']);
    }
}
