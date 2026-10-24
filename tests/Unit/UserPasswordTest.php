<?php

namespace Tests\Unit;

use App\Models\User;
use Tests\TestCase;

class UserPasswordTest extends TestCase
{
    public function test_empty_app_password_is_rejected_without_error(): void
    {
        $user = new User(['password' => 'secret']);

        $this->assertFalse($user->checkAppPassword(null));
        $this->assertFalse($user->checkAppPassword(''));
        $this->assertTrue($user->checkAppPassword('secret'));
    }
}
