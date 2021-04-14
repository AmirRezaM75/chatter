<?php

namespace Tests\Feature;

use App\Models\Thread;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ThreadRestrictionTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function guests_can_not_lock_thread()
    {
        $this->withoutExceptionHandling();

        $this->expectAuthException();

        $this->post(url('threads/1/lock'));
    }

    /** @test */
    public function users_can_not_lock_thread()
    {
        $this->login();

        $thread = Thread::factory()->create();

        $this->post(route('threads.lock', $thread))
            ->assertStatus(302);

        $this->assertFalse($thread->fresh()->locked);
    }


    /** @test */
    public function admin_can_lock_thread()
    {
        $this->login(User::factory()->admin()->create());

        $thread = Thread::factory()->create();

        $this->post(route('threads.lock', $thread));

        $this->assertTrue($thread->fresh()->locked);
    }
}