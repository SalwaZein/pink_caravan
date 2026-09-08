<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\DemoStaff;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * The login page's "demo accounts" panel and UsersSeeder both read
 * App\Support\DemoStaff. These tests keep that promise honest: the panel had
 * previously fallen four accounts behind the seeder.
 */
class DemoAccountsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_every_listed_demo_account_exists_with_that_role_and_clinics(): void
    {
        $this->assertNotEmpty(DemoStaff::ACCOUNTS);

        foreach (DemoStaff::all() as $account) {
            $user = User::where('email', $account['email'])->first();

            $this->assertNotNull($user, "Demo account {$account['email']} is listed but was never seeded.");
            $this->assertSame($account['name'], $user->name);
            $this->assertSame($account['role'], $user->getRoleNames()->first(), "Wrong role for {$account['email']}.");
            $this->assertTrue(Hash::check(DemoStaff::PASSWORD, $user->password));

            $this->assertEqualsCanonicalizing(
                $account['clinics'],
                $user->clinics->pluck('code')->all(),
                "Wrong clinic assignment for {$account['email']}.",
            );
        }
    }

    public function test_no_seeded_staff_account_is_missing_from_the_list(): void
    {
        $listed = collect(DemoStaff::all())->pluck('email')->sort()->values();
        $seeded = User::orderBy('email')->pluck('email')->sort()->values();

        $this->assertEquals($listed->all(), $seeded->all(), 'A seeded account is not shown on the login page.');
    }

    public function test_the_login_page_shows_every_account_with_its_role(): void
    {
        config(['app.debug' => true]);

        $page = $this->get('/login')->assertOk();

        foreach (DemoStaff::all() as $account) {
            $page->assertSee($account['email']);
            $page->assertSee(__('pc.role_'.$account['role']));
        }
    }

    public function test_the_panel_is_hidden_when_debug_is_off(): void
    {
        config(['app.debug' => false]);

        $this->get('/login')
            ->assertOk()
            ->assertDontSee('anish@focp.ae')
            ->assertDontSee(__('pc.demo_accounts'));
    }
}
