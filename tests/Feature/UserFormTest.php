<?php

namespace Tests\Feature;

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class UserFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_duplicate_username_is_a_validation_error_not_a_crash(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true, 'is_active' => true]));
        $hasan = User::factory()->create(['username' => 'hasan']);

        Livewire::test(CreateUser::class)
            ->fillForm(['name' => 'حسن', 'username' => 'hasan', 'email' => 'aaa@aaa.com'])
            ->call('create')
            ->assertHasFormErrors(['username' => 'unique']);
        $this->assertSame(1, User::where('username', 'hasan')->count());

        // Saving a user with their own unchanged username is still allowed.
        Livewire::test(EditUser::class, ['record' => $hasan->getKey()])
            ->call('save')
            ->assertHasNoFormErrors();
    }
}
