<?php

namespace Tests\Feature;

use App\Models\Reply;
use App\Models\Thread;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReplyTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function guests_can_not_add_reply()
    {
        $this->withoutExceptionHandling();

        $this->expectException('Illuminate\Auth\AuthenticationException');

        $this->post('threads/1/replies', []);
    }

    /** @test */
    public function users_can_leave_a_reply()
    {
        $this->login();

        $reply = Reply::factory()->raw();

        $thread = Thread::factory()->create();

        $this->post(route('threads.replies.store', $thread), $reply)
            ->assertJsonPath('reply.user.id', auth()->id());

        $this->get($thread->path())
            ->assertSee($reply['body']);
    }

    /** @test */
    public function reply_requires_body()
    {
        $this->login();

        $reply = Reply::factory()->raw(['body' => null]);

        $thread = Thread::factory()->create();

        $this->post(route('threads.replies.store', $thread), $reply)
            ->assertSessionHasErrors('body');
    }

    /** @test */
    public function unauthenticated_users_can_not_update_reply()
    {
        $this->withoutExceptionHandling();

        $this->expectException('Illuminate\Auth\AuthenticationException');

        $this->patch('replies/1', []);
    }


    /** @test */
    public function unauthorized_users_can_not_update_reply()
    {
        $this->login();

        $reply = Reply::factory()->create();

        $this->patch(route('replies.update', $reply->id))
            ->assertStatus(403);
    }

    /** @test */
    public function users_can_update_reply()
    {
        $this->login();

        $reply = Reply::factory()->create(['user_id' => auth()->id()]);

        $body = 'update body';

        $this->patch(route('replies.update', $reply->id), [ 'body' => $body]);

        $this->assertEquals($body, $reply->fresh()->body);
    }

    /** @test */
    public function unauthenticated_users_can_not_delete_reply()
    {
        $this->withoutExceptionHandling();

        $this->expectException('Illuminate\Auth\AuthenticationException');

        $this->delete('replies/1', []);
    }


    /** @test */
    public function unauthorized_users_can_not_delete_reply()
    {
        $this->login();

        $reply = Reply::factory()->create();

        $this->delete(route('replies.delete', $reply->id))
            ->assertStatus(403);
    }


    /** @test */
    public function users_can_delete_reply()
    {
        $this->login();

        $reply = Reply::factory()->create(['user_id' => auth()->id()]);

        $this->delete(route('replies.delete', $reply->id))
            ->assertStatus(204);

        $this->assertDatabaseMissing('replies', ['id' => $reply->id]);
    }
}
