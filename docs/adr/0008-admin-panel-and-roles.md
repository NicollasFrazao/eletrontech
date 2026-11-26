# 0008. The admin panel with Filament, behind a role column on the account

- Status: Accepted
- Date: 2026-11-26
- Issues: #56 (E8, first step of #11)

## Context

The 2015 version had an admin area (`et-admin/`: users, activity log, statistics, reports), reached
by accounts with `ic_admin = 1` on `tb_usuario`. E8 (#11) rebuilds it with Filament, as the project
overview planned, and every later E8 issue adds pages to the same panel. This first step needs the
panel to exist and to open for administrators only, so the question is how an account is known to be
an administrator.

The accounts of ADR 0005 live in the framework's `users` table, mapped to the domain's `Account` by
`EloquentAccountRepository`; the guard, the session and the sign-in page are the application's.

## Decision

- **The role is one column on the account's row:** `users.role`, an enum of `member` (the default,
  so every existing and new account is one) and `administrator`. The domain names it `Role`
  (`app/Domain/Account/`), read through `AccountRepository::roleOf()`.
- **The rule is in the domain:** `Role::mayAdminister(Access)` allows an administrator whose email is
  confirmed. An unconfirmed address may belong to someone who never proved they own it, so an
  administrator is held to the same confirmation the tools require.
- **One use case decides:** `App\Application\Administration\AuthoriseAdministration` loads the open
  account and its role; a closed or unknown account is refused, not an error.
- **Filament asks that use case.** The `User` model implements `FilamentUser`, and its
  `canAccessPanel()` calls `AuthoriseAdministration`; Filament's `Authenticate` middleware answers
  **403** when it says no. The panel (`App\Providers\Filament\AdminPanelProvider`, `/admin`, guard
  `web`) has **no sign-in page of its own**: a guest is sent to the application's `/login`, so there
  is one sign-in, with its throttling and its session rules (ADR 0005).
- **Granting the role** has no screen yet: it is set in the database (`UPDATE users SET role =
  'administrator' ...`) until the panel manages users (#11).
- Filament's assets are published by `php artisan filament:upgrade` after every `composer install`
  (`post-autoload-dump`) and are not committed.

## Alternatives considered

- **A boolean `is_administrator` column**, a copy of `ic_admin`. Equally small, but a second role
  (support, a read-only auditor) would need another column and rules about combinations; a named
  role grows by one enum case.
- **A `roles` table with a pivot** (or `spatie/laravel-permission`). Many roles per account and
  per-permission checks are more than one panel with one kind of user needs, and they add two tables,
  a package and their caching to every request that asks.
- **A field on `Account` itself.** The entity's constructor is at the four-parameter limit; the role is
  read only where access is decided, so the repository hands it out on its own instead of widening
  `Account` or `Access`.
- **Filament's own sign-in page.** A second sign-in form, without the application's rate limit,
  activity log and deactivation rules.

## Consequences

- An account reaches `/admin` only as a confirmed administrator; a member, confirmed or not, gets 403,
  and a guest goes to `/login`. Feature tests cover each case.
- Later E8 pages (users, activity log, statistics) go in this panel; ADR 0001 still applies to them:
  Filament resources sit at the edge and call Application use cases for anything with a rule.
- Making someone an administrator needs database access until user management exists (tech-debt).
