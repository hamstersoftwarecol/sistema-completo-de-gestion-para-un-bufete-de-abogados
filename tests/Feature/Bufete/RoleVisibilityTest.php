<?php

namespace Tests\Feature\Bufete;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_each_lawyer_only_sees_their_own_cases(): void
    {
        $senior = User::factory()->senior()->create();
        $junior = User::factory()->junior()->create();
        $other = User::factory()->senior()->create();

        $mine = $this->createCase($senior, $junior);
        $foreign = $this->createCase($other);

        $this->actingAs($junior)->get('/cases?scope=all')
            ->assertOk()
            ->assertSee($mine->case_number)
            ->assertDontSee($foreign->case_number);

        $this->actingAs($junior)->get("/cases/{$mine->id}")->assertOk();
        $this->actingAs($junior)->get("/cases/{$foreign->id}")->assertForbidden();
        $this->actingAs($senior)->get("/cases/{$foreign->id}")->assertForbidden();
        $this->actingAs($junior)->get("/clients/{$foreign->client_id}")->assertForbidden();
    }

    public function test_superadmin_sees_the_whole_firm(): void
    {
        $admin = User::factory()->superadmin()->create();
        $a = $this->createCase(User::factory()->senior()->create());
        $b = $this->createCase(User::factory()->junior()->create());

        $this->actingAs($admin)->get('/cases?scope=all')
            ->assertOk()
            ->assertSee($a->case_number)
            ->assertSee($b->case_number);

        $this->actingAs($admin)->get("/cases/{$a->id}")->assertOk();
        $this->actingAs($admin)->get("/clients/{$b->client_id}")->assertOk();
    }

    public function test_only_superadmin_can_access_administration(): void
    {
        $senior = User::factory()->senior()->create();
        $admin = User::factory()->superadmin()->create();

        foreach (['/admin/users', '/admin/master-data', '/admin/settings'] as $url) {
            $this->actingAs($senior)->get($url)->assertForbidden();
            $this->actingAs($admin)->get($url)->assertOk();
        }
    }

    public function test_main_pages_render_for_every_role(): void
    {
        foreach (['superadmin', 'senior', 'junior'] as $role) {
            $user = User::factory()->{$role}()->create();
            $case = $this->createCase($user);

            foreach (['/dashboard', '/clients', '/cases', "/cases/{$case->id}", "/cases/{$case->id}/print", '/calendar',
                '/hearings', '/appointments', '/payments', '/expenses', '/chat', '/ai', '/notifications', '/profile', '/buscar?q=caso'] as $url) {
                $response = $this->actingAs($user)->get($url);
                $this->assertContains($response->status(), [200], "{$role} → {$url} devolvió {$response->status()}");
            }
        }
    }

    public function test_inactive_users_cannot_log_in(): void
    {
        $user = User::factory()->inactive()->create();

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_public_registration_is_disabled(): void
    {
        $this->get('/register')->assertNotFound();
    }
}
