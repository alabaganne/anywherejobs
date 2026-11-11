<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\ConfirmEmailChange;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class WorkspaceLanguageTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Tests\Fixtures\DemoSeeder::class);
        $this->user = User::where('email', 'student@example.com')->firstOrFail();
    }

    public function test_the_language_is_saved_on_the_account(): void
    {
        $this->actingAs($this->user)->post(route('locale.update'), ['locale' => 'fr'])->assertRedirect();
        $this->assertSame('fr', $this->user->fresh()->locale);

        $this->actingAs($this->user)->post(route('locale.update'), ['locale' => 'de'])->assertSessionHasErrors('locale');
    }

    public function test_signed_in_pages_follow_the_account_language_and_public_pages_stay_english(): void
    {
        $this->user->forceFill(['locale' => 'fr'])->save();

        $this->actingAs($this->user)->get('/app/dashboard')
            ->assertInertia(fn (Assert $page) => $page->where('locale', 'fr'));
        $this->actingAs($this->user)->get('/opportunities')
            ->assertInertia(fn (Assert $page) => $page->where('locale', 'en'));
    }

    public function test_flash_messages_and_validation_errors_come_back_in_french(): void
    {
        $this->user->forceFill(['locale' => 'fr'])->save();

        $this->actingAs($this->user)->delete('/app/settings/email')
            ->assertSessionHas('toast.message', 'Changement d’adresse e-mail annulé.');

        $this->actingAs($this->user)->post(route('saved-searches.store'), [])
            ->assertSessionHasErrors(['name' => 'Le champ nom est obligatoire.']);
    }

    public function test_flash_messages_stay_english_for_english_accounts(): void
    {
        $this->actingAs($this->user)->delete('/app/settings/email')
            ->assertSessionHas('toast.message', 'Email change cancelled.');
    }

    public function test_emails_to_a_french_account_are_in_french(): void
    {
        $this->user->forceFill(['locale' => 'fr', 'pending_email' => 'new@example.com'])->save();
        config(['mail.default' => 'array']);

        $this->user->notify(new ConfirmEmailChange);

        $message = Mail::mailer('array')->getSymfonyTransport()->messages()[0]->getOriginalMessage();
        $this->assertStringContainsString('Confirmez votre nouvelle adresse e-mail', $message->getSubject());
        $this->assertStringContainsString('Confirmer le changement', $message->getHtmlBody());
    }
}
