<?php

declare(strict_types=1);

use App\Domain\Account\Role;
use App\Infrastructure\Filament\Accounts\EditAccount;
use App\Infrastructure\Filament\Accounts\ListAccounts;
use App\Infrastructure\Persistence\Eloquent\User;
use Filament\Actions\DeleteAction;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\Support\Account\SignedUp;

/*
 * E8's user management (the 2015 `et-admin/Gestao de Usuarios.php`): search, edit, deactivate and
 * reactivate, delete, each through the account use cases.
 */
uses(RefreshDatabase::class);

/**
 * A confirmed administrator, signed in to the panel: ana@example.com.
 */
function signedInAdministrator(): User
{
    $user = SignedUp::verified();
    $user->forceFill(['role' => Role::Administrator->value])->save();
    test()->actingAs($user->refresh());

    return $user;
}

/**
 * A confirmed member: bia@example.com, CPF 111.444.777-35.
 */
function aMember(): User
{
    return SignedUp::verified('bia@example.com', '111.444.777-35');
}

/**
 * The activity types recorded for an account, oldest first.
 *
 * @return list<string>
 */
function activityTypesOf(User $user): array
{
    return DB::table('activities')->where('user_id', $user->id)->orderBy('id')->pluck('type')->all();
}

it('refuses the account pages to a member and sends a guest to sign in', function (): void {
    $member = aMember();

    $this->actingAs($member)->get('/admin/accounts')->assertForbidden();
    $this->get("/admin/accounts/{$member->id}/edit")->assertForbidden();

    auth()->logout();
    $this->get('/admin/accounts')->assertRedirect('/login');
});

it('lists the open accounts to an administrator, not the closed ones', function (): void {
    signedInAdministrator();
    $member = aMember();
    $closed = SignedUp::verified('caio@example.com', '390.533.447-05');
    $closed->forceFill(['closed_at' => now()])->save();

    $this->get('/admin/accounts')->assertOk()->assertSee('Accounts');

    Livewire::test(ListAccounts::class)
        ->assertCanSeeTableRecords([$member])
        ->assertCanNotSeeTableRecords([$closed]);
});

it('searches by name, email, CPF with or without its punctuation, and id', function (string $search): void {
    $administrator = signedInAdministrator();
    $member = aMember();
    $member->forceFill(['name' => 'Bia Lima'])->save();

    Livewire::test(ListAccounts::class)
        ->searchTable($search === 'id' ? (string) $member->id : $search)
        ->assertCanSeeTableRecords([$member])
        ->assertCanNotSeeTableRecords([$administrator]);
})->with(['name' => 'Lima', 'email' => 'bia@', 'cpf' => '111.444', 'cpf digits' => '11144477735', 'id' => 'id']);

it('edits the name and the phone through the profile use case, never the CPF or the email', function (): void {
    signedInAdministrator();
    $member = aMember();

    Livewire::test(EditAccount::class, ['record' => $member->getRouteKey()])
        ->assertSchemaStateSet(['name' => 'Ana Souza', 'cpf' => '11144477735', 'email' => 'bia@example.com'])
        ->fillForm([
            'name' => ' Bia  Lima ',
            'phone_number' => '(13) 3222-1234',
            'phone_type' => 'home',
            'cpf' => '52998224725',
            'email' => 'other@example.com',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $member->refresh();
    expect([$member->name, $member->phone_number, $member->phone_type, $member->cpf, $member->email])
        ->toBe(['Bia Lima', '1332221234', 'home', '11144477735', 'bia@example.com'])
        ->and(activityTypesOf($member))->toContain('profile_updated');
});

it('shows a refused value on its field and saves nothing', function (): void {
    signedInAdministrator();
    $member = aMember();

    Livewire::test(EditAccount::class, ['record' => $member->getRouteKey()])
        ->fillForm(['name' => 'Bia', 'phone_number' => '1332221234', 'phone_type' => 'mobile'])
        ->call('save')
        ->assertHasFormErrors(['phone_number']);

    expect($member->refresh()->phone_number)->toBe('13998765432');
});

it('deactivates an account for 30 days and reactivates it', function (): void {
    signedInAdministrator();
    $member = aMember();
    $this->freezeSecond();

    Livewire::test(ListAccounts::class)
        ->assertActionHidden(TestAction::make('reactivate')->table($member))
        ->callAction(TestAction::make('deactivate')->table($member))
        ->assertNotified('The account was deactivated.');

    expect($member->refresh()->reactivation_deadline?->equalTo(now()->addDays(30)))->toBeTrue();

    Livewire::test(ListAccounts::class)
        ->assertActionHidden(TestAction::make('deactivate')->table($member))
        ->callAction(TestAction::make('reactivate')->table($member))
        ->assertNotified('The account was reactivated.');

    expect($member->refresh()->reactivation_deadline)->toBeNull()
        ->and(activityTypesOf($member))->toContain('deactivated', 'reactivated');
});

it('deletes an account: closed, with its CPF, email, phone and password erased and its name kept', function (): void {
    signedInAdministrator();
    $member = aMember();

    Livewire::test(ListAccounts::class)
        ->callAction(TestAction::make(DeleteAction::getDefaultName())->table($member))
        ->assertNotified();

    $member->refresh();
    expect($member->closed_at)->not->toBeNull()
        ->and([$member->name, $member->cpf, $member->email, $member->phone_number, $member->password])
        ->toBe(['Ana Souza', null, null, null, null])
        ->and(activityTypesOf($member))->toContain('closed');

    $this->get("/admin/accounts/{$member->id}/edit")->assertNotFound();
});

it('deactivates and deletes from the edit page too', function (): void {
    signedInAdministrator();
    $member = aMember();

    Livewire::test(EditAccount::class, ['record' => $member->getRouteKey()])
        ->callAction('deactivate');

    expect($member->refresh()->reactivation_deadline)->not->toBeNull();

    Livewire::test(EditAccount::class, ['record' => $member->getRouteKey()])
        ->callAction(DeleteAction::getDefaultName())
        ->assertRedirect('/admin/accounts');

    expect($member->refresh()->closed_at)->not->toBeNull();
});

it('offers no deactivate or delete on the administrator\'s own account', function (): void {
    $administrator = signedInAdministrator();

    Livewire::test(ListAccounts::class)
        ->assertActionHidden(TestAction::make('deactivate')->table($administrator))
        ->assertActionHidden(TestAction::make(DeleteAction::getDefaultName())->table($administrator));

    expect($administrator->refresh()->reactivation_deadline)->toBeNull()
        ->and($administrator->closed_at)->toBeNull();
});
