<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminShellPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_pages_load_for_authenticated_admin(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'password' => Hash::make('password'),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('admin-app', false)
            ->assertSee('Yudisium FT UNMUL');

        foreach ([
            route('admin.events.index'),
            route('admin.events.create'),
            route('admin.participants.index'),
            route('admin.study-programs.index'),
            route('admin.categories.index'),
            route('admin.checkin.manual.index'),
            route('admin.checkin.scanner.index'),
            route('monitoring.mahasiswa'),
            route('monitoring.private'),
        ] as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }
    }
}
