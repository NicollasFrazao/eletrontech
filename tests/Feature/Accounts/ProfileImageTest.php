<?php

declare(strict_types=1);

use App\Domain\Account\ImageKind;
use App\Domain\Account\UnreadableImage;
use App\Infrastructure\Account\GdImageStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Support\Account\SignedUp;

uses(RefreshDatabase::class);

/**
 * The width and height of a stored JPEG.
 *
 * @return array{int, int}
 */
function jpegSize(string $bytes): array
{
    $info = getimagesizefromstring($bytes);

    return $info === false ? [0, 0] : [$info[0], $info[1]];
}

it('stores a photo cut square and scaled to 512 pixels, off the public folder, and serves it to its owner', function (): void {
    $disk = Storage::fake('local');
    $user = SignedUp::verified();

    $this->actingAs($user)->post('/profile/images/photo', ['image' => UploadedFile::fake()->image('me.png', 1200, 800)])
        ->assertRedirect('/profile')
        ->assertSessionHas('status', 'Your profile photo was replaced.');

    $path = (string) $user->refresh()->photo_path;
    expect($path)->toStartWith('profile-images/')->toEndWith('.jpg')->not->toContain('me')
        ->and(jpegSize((string) $disk->get($path)))->toBe([512, 512]);
    $this->get('/profile/images/photo')->assertOk()->assertHeader('Content-Type', 'image/jpeg');
    $this->assertDatabaseHas('activities', ['user_id' => $user->id, 'type' => 'photo_replaced']);
});

it('deletes the photo a new one replaces', function (): void {
    $disk = Storage::fake('local');
    $user = SignedUp::verified();
    $this->actingAs($user)->post('/profile/images/photo', ['image' => UploadedFile::fake()->image('a.jpg', 600, 600)]);
    $first = (string) $user->refresh()->photo_path;

    $this->post('/profile/images/photo', ['image' => UploadedFile::fake()->image('b.jpg', 600, 600)]);

    expect($disk->exists($first))->toBeFalse()
        ->and($disk->allFiles('profile-images'))->toHaveCount(1);
});

it('cuts a cover to 3:1 and never scales a small picture up', function (): void {
    $disk = Storage::fake('local');
    $store = new GdImageStore($disk);

    $large = $store->store(ImageKind::Cover, UploadedFile::fake()->image('c.jpg', 3000, 3000)->getContent());
    $small = $store->store(ImageKind::Cover, UploadedFile::fake()->image('s.jpg', 300, 300)->getContent());

    expect(jpegSize((string) $disk->get($large)))->toBe([1500, 500])
        ->and(jpegSize((string) $disk->get($small)))->toBe([300, 100]);
});

it('refuses an upload that is not a JPEG, PNG or WebP picture', function (): void {
    Storage::fake('local');

    $this->actingAs(SignedUp::verified())
        ->post('/profile/images/cover', ['image' => UploadedFile::fake()->create('notes.pdf', 10, 'application/pdf')])
        ->assertSessionHasErrors('image');
});

it('refuses bytes that do not decode, and a format it does not accept', function (string $bytes): void {
    expect(fn (): string => new GdImageStore(Storage::fake('local'))->store(ImageKind::Photo, $bytes))->toThrow(UnreadableImage::class);
})->with([
    'text' => ['<?php echo 1;'],
    'gif' => [base64_decode('R0lGODlhAQABAAAAACw=', true)],
    'truncated png' => [substr(UploadedFile::fake()->image('t.png', 50, 50)->getContent(), 0, 60)],
]);

it('answers 404 for a picture the account does not have, and reads a missing file as none', function (): void {
    $disk = Storage::fake('local');
    $user = SignedUp::verified();

    $this->actingAs($user)->get('/profile/images/cover')->assertNotFound();
    expect(new GdImageStore($disk)->read('profile-images/missing.jpg'))->toBeNull();
});
