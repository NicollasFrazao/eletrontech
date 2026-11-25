<?php

declare(strict_types=1);

use App\Infrastructure\Persistence\Eloquent\EventRecord;
use App\Infrastructure\Persistence\Eloquent\FileRecord;
use App\Infrastructure\Persistence\Eloquent\FolderRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\Support\Account\SignedUp;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Storage::fake('local');
});

it('adds an event and shows it in its month, and not in another', function (): void {
    $user = SignedUp::verified();

    $this->actingAs($user)->from('/agenda')
        ->post('/agenda/events', ['title' => 'Site visit', 'starts_at' => '2026-12-01T14:30', 'description' => 'Bring the meter'])
        ->assertRedirect('/agenda');

    $this->actingAs($user)->get('/agenda?month=2026-12')->assertOk()->assertSee('2026-12-01 14:30')->assertSee('Site visit');
    $this->actingAs($user)->get('/agenda?month=2027-01')->assertOk()->assertDontSee('Site visit');
    $this->actingAs($user)->get('/agenda?month=2026-13')->assertNotFound();
});

it('puts each refused value on its field', function (string $field, string $value, string $message): void {
    $form = ['title' => 'Visit', 'starts_at' => '2026-12-01T14:30', 'description' => '', $field => $value];

    $this->actingAs(SignedUp::verified())->post('/agenda/events', $form)->assertSessionHasErrors([$field => $message]);

    expect(EventRecord::query()->count())->toBe(0);
})->with([
    ['title', str_repeat('a', 51), 'Type a title of up to 50 characters.'],
    ['starts_at', '2026-02-30T10:00', 'Choose a valid date and time.'],
    ['description', str_repeat('a', 501), 'Use up to 500 characters.'],
]);

it('edits, searches and deletes an event', function (): void {
    $user = SignedUp::verified();
    $this->actingAs($user)->post('/agenda/events', ['title' => 'Visit', 'starts_at' => '2026-12-01T14:30']);
    $id = EventRecord::query()->sole()->id;

    $this->actingAs($user)->get("/agenda/events/{$id}/edit")->assertOk()->assertSee('value="2026-12-01T14:30"', false);
    $form = ['title' => 'Panel swap', 'starts_at' => '2026-12-02T09:00', 'description' => 'Main board'];
    $this->actingAs($user)->put("/agenda/events/{$id}", $form)
        ->assertRedirect('/agenda');
    $this->actingAs($user)->get('/agenda?search=board')->assertSee('Panel swap');
    $this->actingAs($user)->delete("/agenda/events/{$id}")->assertRedirect('/agenda');

    expect(EventRecord::query()->count())->toBe(0);
});

it('finds nothing of another person\'s events', function (string $method, string $uri): void {
    $ana = SignedUp::verified();
    $this->actingAs($ana)->post('/agenda/events', ['title' => 'Visit', 'starts_at' => '2026-12-01T14:30']);
    $id = EventRecord::query()->sole()->id;
    $bia = SignedUp::verified('bia@example.com', '123.456.789-09');

    $this->actingAs($bia)->call($method, str_replace('{id}', (string) $id, $uri), ['title' => 'Mine', 'starts_at' => '2026-12-01T14:30'])
        ->assertNotFound();
    $this->actingAs($bia)->get('/agenda?search=Visit')->assertDontSee('2026-12-01 14:30');

    expect(EventRecord::query()->sole()->title)->toBe('Visit');
})->with([
    ['GET', '/agenda/events/{id}/edit'],
    ['PUT', '/agenda/events/{id}'],
    ['DELETE', '/agenda/events/{id}'],
]);

it('exports the events to PDF and TXT files in the folder "Events"', function (): void {
    $user = SignedUp::verified();
    $this->actingAs($user)->post('/agenda/events', ['title' => 'Site visit', 'starts_at' => '2026-12-01T14:30', 'description' => 'Meter']);

    $this->actingAs($user)->post('/agenda/export/pdf')->assertRedirect('/files');
    $this->travel(1)->seconds();
    $this->actingAs($user)->post('/agenda/export/txt')->assertRedirect('/files');

    $folder = FolderRecord::query()->sole();
    $files = FileRecord::query()->orderBy('id')->get();
    expect($folder->name)->toBe('Events')
        ->and($files->pluck('folder_id')->unique()->all())->toBe([$folder->id])
        ->and($files->pluck('type')->all())->toBe(['pdf', 'txt'])
        ->and(Storage::disk('local')->get($files[0]->content_key))->toStartWith('%PDF-')
        ->and(Storage::disk('local')->get($files[1]->content_key))->toBe("2026-12-01 14:30 - Site visit\nMeter\n");
    $this->actingAs($user)->get("/files/{$files[1]->id}/download")->assertOk()->assertDownload($files[1]->name);
});

it('answers not found for an export format that does not exist', function (): void {
    $this->actingAs(SignedUp::verified())->post('/agenda/export/docx')->assertNotFound();
});

it('keeps the agenda to signed-in, confirmed accounts', function (): void {
    $this->get('/agenda')->assertRedirect('/login');
    $this->actingAs(SignedUp::unverified())->get('/agenda')->assertRedirect('/email/verify');
});
