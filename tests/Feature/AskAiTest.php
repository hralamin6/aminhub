<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('loads the askai database assistant page for authenticated user', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => now()->timestamp])
        ->get(route('app.askai'))
        ->assertSuccessful()
        ->assertSee('Database AI Assistant')
        ->assertSee('TABLE EXPLORER');
});

it('can initialize the askai component', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $this->actingAs($user);

    Livewire::test('app.⚡askai.askai')
        ->assertSet('selectedConversationId', null)
        ->assertSet('isProcessing', false)
        ->assertSet('aiProvider', 'groq')
        ->assertSet('responseLanguage', 'bn');
});
