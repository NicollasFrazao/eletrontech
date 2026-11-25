<?php

declare(strict_types=1);

use App\Infrastructure\Persistence\Eloquent\FileRecord;
use App\Infrastructure\Persistence\Eloquent\FolderRecord;
use App\Infrastructure\Persistence\Eloquent\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Tests\Support\Account\SignedUp;

/*
 * E7's rule: uploads can never be executed or read by another person.
 */
uses(RefreshDatabase::class);

beforeEach(function (): void {
    Storage::fake('local');
});

/**
 * Uploads a file as the person, into the folder given (none for the root).
 */
function upload(User $user, UploadedFile $file, ?int $folder = null): TestResponse
{
    return test()->actingAs($user)->from('/files')->post('/files', ['file' => $file, 'folder' => $folder]);
}

/**
 * A small PDF.
 */
function pdf(string $name = 'plan.pdf'): UploadedFile
{
    return UploadedFile::fake()->createWithContent($name, "%PDF-1.4\n1 0 obj << >> endobj\ntrailer << >>\n%%EOF\n");
}

it('uploads a file under a random key on the private disk and lists it', function (): void {
    $user = SignedUp::verified();

    upload($user, pdf('Kitchen plan.pdf'))->assertRedirect('/files')->assertSessionHasNoErrors();

    $file = FileRecord::query()->sole();
    expect($file->content_key)->toStartWith('files/')
        ->and($file->content_key)->not->toContain('Kitchen')
        ->and($file->type)->toBe('pdf');
    Storage::disk('local')->assertExists($file->content_key);
    $this->actingAs($user)->get('/files')->assertOk()->assertSee('Kitchen plan.pdf');
});

it('sends a download as an attachment that the browser does not sniff', function (): void {
    $user = SignedUp::verified();
    upload($user, UploadedFile::fake()->createWithContent('notes.txt', 'Kitchen: 2.5 mm wire, 20 A breaker'));
    $id = FileRecord::query()->sole()->id;

    $response = $this->actingAs($user)->get("/files/{$id}/download");

    $response->assertOk()
        ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertDownload('notes.txt');
    expect($response->getContent())->toBe('Kitchen: 2.5 mm wire, 20 A breaker');
});

it('refuses a type off the list and content that is not of its extension', function (UploadedFile $file): void {
    upload(SignedUp::verified(), $file)
        ->assertSessionHasErrors(['file' => 'This kind of file is not accepted, or its content does not match its extension.']);

    expect(FileRecord::query()->count())->toBe(0)
        ->and(Storage::disk('local')->allFiles())->toBe([]);
})->with([
    'php' => fn (): UploadedFile => UploadedFile::fake()->createWithContent('shell.php', '<?php system($_GET["c"]);'),
    'html' => fn (): UploadedFile => UploadedFile::fake()->createWithContent('page.html', '<html><script>alert(1)</script></html>'),
    'svg' => fn (): UploadedFile => UploadedFile::fake()
        ->createWithContent('logo.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>'),
    'php named pdf' => fn (): UploadedFile => UploadedFile::fake()->createWithContent('plan.pdf', '<?php system($_GET["c"]);'),
    'no extension' => fn (): UploadedFile => UploadedFile::fake()->createWithContent('README', 'text'),
    'html named txt' => fn (): UploadedFile => UploadedFile::fake()
        ->createWithContent('notes.txt', '<!DOCTYPE html><html><script>alert(1)</script></html>'),
]);

it('refuses a file larger than 10 MB', function (): void {
    upload(SignedUp::verified(), UploadedFile::fake()->create('big.pdf', 10 * 1024 + 1, 'application/pdf'))
        ->assertSessionHasErrors('file');

    expect(FileRecord::query()->count())->toBe(0);
});

it('keeps only the base name of a path sent as the file name', function (): void {
    $user = SignedUp::verified();

    upload($user, UploadedFile::fake()->createWithContent('../../../../etc/passwd.txt', 'root:x:0:0'))->assertSessionHasNoErrors();

    expect(FileRecord::query()->sole()->name)->toBe('passwd.txt');
    Storage::disk('local')->assertMissing('etc/passwd.txt');
});

it('finds nothing of another person\'s files and folders', function (string $method, string $uri): void {
    $ana = SignedUp::verified();
    upload($ana, pdf());
    $this->actingAs($ana)->post('/files/folders', ['name' => 'Plans']);
    $bia = SignedUp::verified('bia@example.com', '123.456.789-09');
    $uri = strtr($uri, ['{file}' => (string) FileRecord::query()->sole()->id, '{folder}' => (string) FolderRecord::query()->sole()->id]);

    $this->actingAs($bia)->call($method, $uri)->assertNotFound();

    expect(FileRecord::query()->count())->toBe(1)
        ->and(FolderRecord::query()->count())->toBe(1);
})->with([
    'file properties' => ['GET', '/files/{file}'],
    'file download' => ['GET', '/files/{file}/download'],
    'file delete' => ['DELETE', '/files/{file}'],
    'folder' => ['GET', '/files/folders/{folder}'],
    'folder zip' => ['GET', '/files/folders/{folder}/download'],
    'folder delete' => ['DELETE', '/files/folders/{folder}'],
]);

it('does not store into another person\'s folder', function (): void {
    $ana = SignedUp::verified();
    $this->actingAs($ana)->post('/files/folders', ['name' => 'Plans']);
    $folder = FolderRecord::query()->sole()->id;
    $bia = SignedUp::verified('bia@example.com', '123.456.789-09');

    upload($bia, pdf(), $folder)->assertSessionHasErrors(['folder' => 'This folder does not exist.']);
    $this->actingAs($bia)->post('/files/folders', ['name' => 'Inside', 'parent' => $folder])->assertSessionHasErrors('parent');

    expect(FileRecord::query()->count())->toBe(0)
        ->and(Storage::disk('local')->allFiles())->toBe([]);
});

it('creates nested folders, zips one with its contents and deletes it with them', function (): void {
    $user = SignedUp::verified();
    $this->actingAs($user)->post('/files/folders', ['name' => 'Plans'])->assertRedirect();
    $plans = FolderRecord::query()->sole()->id;
    $this->actingAs($user)->post('/files/folders', ['name' => 'Kitchen', 'parent' => $plans]);
    $kitchen = FolderRecord::query()->where('name', 'Kitchen')->sole()->id;
    upload($user, UploadedFile::fake()->createWithContent('list.txt', 'list'), $plans);
    upload($user, UploadedFile::fake()->createWithContent('outlets.csv', "room,outlets\nkitchen,6\n"), $kitchen);

    $this->actingAs($user)->get("/files/folders/{$kitchen}")->assertOk()->assertSeeInOrder(['Plans', 'Kitchen', 'outlets.csv']);
    $zip = $this->actingAs($user)->get("/files/folders/{$plans}/download")->assertOk()->assertDownload('Plans.zip');
    $path = tempnam(sys_get_temp_dir(), 'test');
    file_put_contents($path, $zip->getContent());
    $archive = new ZipArchive;
    $archive->open($path);
    $names = [$archive->getNameIndex(0), $archive->getNameIndex(1)];
    $outlets = $archive->getFromName('Kitchen/outlets.csv');
    $archive->close();
    unlink($path);

    $this->actingAs($user)->delete("/files/folders/{$plans}")->assertRedirect('/files');

    expect($names)->toBe(['list.txt', 'Kitchen/outlets.csv'])
        ->and($outlets)->toBe("room,outlets\nkitchen,6\n")
        ->and(FolderRecord::query()->count())->toBe(0)
        ->and(FileRecord::query()->count())->toBe(0)
        ->and(Storage::disk('local')->allFiles())->toBe([]);
});

it('shows a file\'s properties and deletes it with its content', function (): void {
    $user = SignedUp::verified();
    upload($user, pdf());
    $file = FileRecord::query()->sole();

    $this->actingAs($user)->get("/files/{$file->id}")->assertOk()->assertSee('PDF')->assertSee('plan.pdf');
    $this->actingAs($user)->delete("/files/{$file->id}")->assertRedirect('/files');

    Storage::disk('local')->assertMissing($file->content_key);
    expect(FileRecord::query()->count())->toBe(0);
});

it('refuses a name taken in the folder and a name that cannot be a folder\'s', function (): void {
    $user = SignedUp::verified();
    upload($user, pdf());

    $this->actingAs($user)->post('/files/folders', ['name' => 'PLAN.PDF'])
        ->assertSessionHasErrors(['name' => 'This folder already holds something with this name.']);
    $this->actingAs($user)->post('/files/folders', ['name' => '..'])->assertSessionHasErrors('name');
    upload($user, pdf())->assertSessionHasErrors(['file' => 'This folder already holds something with this name.']);

    expect(Storage::disk('local')->allFiles())->toHaveCount(1);
});

it('refuses a file that would pass the quota', function (): void {
    $user = SignedUp::verified();
    FileRecord::query()->create([
        'user_id' => $user->id,
        'folder_id' => null,
        'name' => 'archive.pdf',
        'type' => 'pdf',
        'size' => 100 * 1024 * 1024,
        'content_key' => 'files/existing',
    ]);

    upload($user, pdf())->assertSessionHasErrors(['file' => 'Your files would take more than 100 MB.']);
});

it('keeps the file manager to signed-in, confirmed accounts', function (): void {
    $this->get('/files')->assertRedirect('/login');
    $this->actingAs(SignedUp::unverified())->get('/files')->assertRedirect('/email/verify');
});
