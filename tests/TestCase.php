<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function login($user = null)
    {
        $this->be($user ?? User::factory()->create());
    }

    protected function expectAuthException()
    {
        $this->expectException('Illuminate\Auth\AuthenticationException');
    }
}
