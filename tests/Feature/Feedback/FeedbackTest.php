<?php

declare(strict_types=1);

use App\Domain\Account\Role;
use App\Infrastructure\Filament\Feedback\ListFeedback;
use App\Infrastructure\Persistence\Eloquent\FeedbackRecord;
use App\Infrastructure\Persistence\Eloquent\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Date;
use Livewire\Livewire;
use Tests\Support\Account\SignedUp;

/*
 * E9 (#63): the feedback form of 2015 posted to a remote URL; now the message is kept with the account and
 * the date, and the administrators read it in the admin panel.
 */
uses(RefreshDatabase::class);

/**
 * A confirmed account made an administrator: bia@example.com.
 */
function feedbackAdministrator(): User
{
    $user = SignedUp::verified('bia@example.com', '111.444.777-35');
    $user->forceFill(['role' => Role::Administrator->value])->save();

    return $user->refresh();
}

it('shows the form with the subjects to a signed-in person', function (): void {
    $this->actingAs(SignedUp::verified())
        ->get('/feedback')
        ->assertOk()
        ->assertSee('Feedback')
        ->assertSee('<option value="suggestion"', false)
        ->assertSee('Something went wrong');
});

it('sends a guest to sign in and an unconfirmed account to confirm its email', function (): void {
    $this->get('/feedback')->assertRedirect('/login');
    $this->post('/feedback', ['subject' => 'praise', 'message' => 'Great'])->assertRedirect('/login');

    $this->actingAs(SignedUp::unverified())->get('/feedback')->assertRedirect('/email/verify');
    expect(FeedbackRecord::query()->count())->toBe(0);
});

it('keeps the message with the account and the date', function (): void {
    Date::setTestNow('2026-11-29 15:45:00');
    $ana = SignedUp::verified();

    $this->actingAs($ana)
        ->post('/feedback', ['subject' => 'suggestion', 'message' => '  A dark theme for the calculators.  '])
        ->assertRedirect('/feedback')
        ->assertSessionHas('status', 'Thank you! Your message was sent to the administrators.');

    $record = FeedbackRecord::query()->sole();
    expect($record->user_id)->toBe($ana->id)
        ->and($record->subject)->toBe('suggestion')
        ->and($record->message)->toBe('A dark theme for the calculators.')
        ->and($record->sent_at->format('Y-m-d H:i:s'))->toBe('2026-11-29 15:45:00');
});

it('puts each refused value on its field and keeps nothing', function (array $form, string $field, string $message): void {
    $this->actingAs(SignedUp::verified())
        ->from('/feedback')
        ->post('/feedback', [...['subject' => 'question', 'message' => 'How do I export?'], ...$form])
        ->assertRedirect('/feedback')
        ->assertSessionHasErrors([$field => $message]);

    expect(FeedbackRecord::query()->count())->toBe(0);
})->with([
    'no subject' => [['subject' => ''], 'subject', 'The subject field is required.'],
    'unknown subject' => [['subject' => 'complaint'], 'subject', 'Choose one of the subjects.'],
    'no message' => [['message' => ''], 'message', 'The message field is required.'],
    'only spaces' => [['message' => '    '], 'message', 'The message field is required.'],
    '501 characters' => [['message' => str_repeat('a', 501)], 'message', 'Write a message of up to 500 characters.'],
    'not text' => [['message' => ['a']], 'message', 'The message field must be a string.'],
]);

it('lists the messages to an administrator, newest first, with their author and date', function (): void {
    $ana = SignedUp::verified();
    Date::setTestNow('2026-11-28 09:00:00');
    $this->actingAs($ana)->post('/feedback', ['subject' => 'praise', 'message' => 'The converter is great.']);
    Date::setTestNow('2026-11-29 15:45:00');
    $this->actingAs($ana)->post('/feedback', ['subject' => 'bug', 'message' => 'The export button does nothing.']);

    $this->actingAs(feedbackAdministrator())
        ->get('/admin/feedback')
        ->assertOk()
        ->assertSeeInOrder(['2026-11-29 15:45', 'The export button does nothing.', '2026-11-28 09:00', 'The converter is great.'])
        ->assertSee('ana@example.com')
        ->assertSee('Something went wrong');

    Livewire::test(ListFeedback::class)
        ->assertCanSeeTableRecords(FeedbackRecord::query()->orderByDesc('sent_at')->get(), inOrder: true)
        ->searchTable('converter')
        ->assertCountTableRecords(1)
        ->searchTable('')
        ->filterTable('subject', 'bug')
        ->assertCanSeeTableRecords(FeedbackRecord::query()->where('subject', 'bug')->get())
        ->assertCountTableRecords(1);
});

it('refuses the list to a member and sends a guest to sign in', function (): void {
    $this->actingAs(SignedUp::verified())->get('/admin/feedback')->assertForbidden();

    auth()->logout();
    $this->get('/admin/feedback')->assertRedirect('/login');
});

it('has no page to write or edit a message in the panel', function (): void {
    $this->actingAs(feedbackAdministrator())->get('/admin/feedback/create')->assertNotFound();
});
