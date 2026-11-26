# 0009. User management in the admin panel: Filament resources in Infrastructure, changes through use cases

- Status: Accepted
- Date: 2026-11-28
- Issues: #57 (E8, part of #11)

## Context

The 2015 admin area managed users in `et-admin/Gestao de Usuarios.php`: a search by code, name, email
or CPF, an edit form, an activate/deactivate toggle (`AtivarDesativar.php`, 30 days like the owner's
own deactivation) and a delete (`Excluir.php`) that removed the row, its packages, folders, events
and files. It wrote to `tb_usuario` directly.

ADR 0008 put the panel at `/admin` and said its pages call Application use cases for anything with a
rule. A Filament resource is built on an Eloquent model, and the models live in Infrastructure
(`App\Infrastructure\Persistence\Eloquent\User`), which Http may not use (ADR 0001).

## Decision

- **The Filament resource sits in `app/Infrastructure/Filament/`** (`Accounts/AccountResource`, its
  list and edit pages, `AccountActions`). Infrastructure may use the models, the framework and the
  Application layer, so no Deptrac rule is widened; `AdminPanelProvider` registers it.
- **Reading is the resource's, writing is the use cases'.** The list queries the open rows of
  `users` (closed ones are not listed, as the repository never finds them) and searches by id, name,
  email and CPF (with or without its punctuation). Every change goes through `app/Application/`:
  - **edit**: `Account\UpdateProfile`, the owner's own use case, so the name and the phone are
    validated the same way and the CPF and the email stay fixed;
  - **deactivate**: `Administration\DeactivateAccount`, the owner's 30 days without the password
    (the administrator does not know it); signing in before the deadline still reactivates it;
  - **reactivate**: `Administration\ReactivateAccount`, which clears the deadline;
  - **delete**: `Administration\CloseAccount`, which closes the account with `AccountCloser`, as an
    expired one is closed: the CPF, email, phone, password and pictures are erased and the name and
    the activity log kept (ADR 0005).
- **An administrator cannot deactivate or delete their own account.** The actions are hidden on
  their own row and the use cases refuse it (`OwnAccountRefused`), so the last administrator cannot
  lock themselves out.
- No account is created in the panel: a person signs up, and gets the initial licence that way.

## Alternatives considered

- **The resource in `app/Http/`** with an exception for the model: widens the layer rule for one
  class, which ADR 0001 forbids without a new decision, and nothing is gained over Infrastructure.
- **A hard delete of the row**, as in 2015. It would cascade to the activity log, the licences and the
  tool-use records the later admin pages (activity log, statistics) read; closing erases what
  identifies the person and keeps the records anonymous.
- **Reusing `Account\DeactivateAccount`**, which asks for the owner's password. Making the password
  optional would weaken the owner's confirmation; a separate use case keeps both rules plain.

## Consequences

- Each action has a feature test over Livewire, and a member gets 403 on the account pages.
- The activity log records the action on the account it concerns (`profile_updated`, `deactivated`,
  `reactivated`, `closed`), not which administrator did it.
- A session the person has open when an administrator deactivates or deletes the account is not ended
  by it (tech-debt).
- Granting the administrator role is still not in the panel (tech-debt, ADR 0008).
