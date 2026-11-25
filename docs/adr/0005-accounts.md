# 0005. Accounts in the domain, Laravel's guard and password broker at the edge

- Status: Accepted
- Date: 2026-11-25
- Issues: #7 (E4, accounts, profile and security)

## Context

The 2015 accounts were the weakest part of the original (`Login.php`, the `php/` endpoints,
`EnviarConfirmacao.php`, `RecuperarSenha.php`, `Perfil.php`, `AlterarSenha.php`,
`DesativarConta.php`, `EditarDados.php`):

- passwords stored as Base64, capped at 12 characters, and **emailed back** by the recovery page;
- SQL built by concatenating strings, with `mysql_escape_string` in places, and no CSRF protection;
- a "keep me signed in" cookie that held the email and the Base64 password;
- no throttling on sign-in, only a fixed `sleep(3)`;
- a confirmation link carrying a Base64 user id and date, unsigned, so anyone could forge one;
- `AlterarSenha.php` changed the password without asking for the current one; `DesativarConta.php`
  and `EditarDados.php` took the target user id from the request;
- uploads checked by extension only, kept under their original names, under the web root.

The behaviour worth keeping: sign-up with a CPF and a phone, email confirmation, password recovery,
a profile with a photo, a cover, night mode and a tutorial toggle, and **deactivation for 30 days**
(`ic_ativado`, `dt_reativar`): signing in before the date reactivates the account, after it the
account is gone, and a sign-up with the same email or CPF releases it.

The framework already solves the parts that are hard to get right (sessions, the remember-me
cookie, signed URLs, hashed and expiring reset tokens, rate limiting), and its guard reads a
`users` table. The rules above are ours and belong in the domain (ADR 0001).

## Decision

- **The account is a domain entity** in `app/Domain/Account/`: `Account` (identity, then
  `PersonalDetails`, `Access` and `Profile`, each an immutable value object it replaces), with
  value objects that check what a person types: `Cpf` (check digits, a repeated digit refused),
  `PhoneNumber` (area code 11–99 without zero, a landline of 8 digits or a mobile of 9 starting
  with 9) and `ContactPhone` (a mobile type needs a mobile number), `EmailAddress` (trimmed,
  lower-cased), `PersonName` (2 to 100 characters), `PlainPassword` (8 characters to **72 bytes**,
  the part bcrypt reads) and `HashedPassword`. A refused value is `InvalidAccountData` naming its
  `AccountField`, so the edge puts the message on the right form field.
- **What the sign-up collects:** name, CPF, one phone with its kind, email and password. The 2015
  sex and birth date are not collected: no feature uses them (security.md, decided by the owner).
- **Deactivation is a reactivation deadline on `Access`,** 30 days ahead. `Account::signIn()` says
  what a sign-in does: nothing, reactivate, or **expired**. An expired account is closed by
  `AccountCloser`, which a sign-up holding its email or CPF also calls before registering.
- **Closing erases, it does not delete:** `AccountRepository::close()` clears the CPF, email,
  phone, password, remember token and picture paths, sets `closed_at`, and keeps the **name and the
  activity log** (decided by the owner), for the admin panel. A closed account is never loaded as an
  `Account` again, so there is no "closed" status for a use case to forget to check.
- **One use case per action** in `app/Application/Account/`: `RegisterAccount`, `SignIn`,
  `SignOut`, `VerifyEmail`, `ResetPassword`, `ChangePassword` (asks for the current password),
  `UpdateProfile` (name and phone; the CPF is fixed and the email is not changed here),
  `UpdatePreferences`, `ReplaceProfileImage`, `ShowProfileImage`, `DeactivateAccount` (asks for the
  password) and `ViewProfile` (with the ten latest actions). Every action is recorded in the
  `ActivityLog` (`ActivityType`, the 2015 `tb_atividade`).
- **A sign-in does not tell which emails have an account:** an unknown email, a wrong password and an
  expired account are the same `InvalidCredentials`, and an unknown email still spends one hash
  check (`PasswordHasher::spendCheck()`), so it takes as long. The password-recovery form answers
  the same text whether or not the email has an account.
- **The framework stays at the edge.** The account lives in the framework's `users` table
  (`EloquentAccountRepository` maps the `User` model, which `implements MustVerifyEmail`), so the
  session guard, the remember-me cookie, the password broker (`password_reset_tokens`) and the
  signed verification link (`EmailVerificationRequest`) work unchanged; once they have checked a
  token or a signature, the controller calls the use case. Controllers take the account id from the
  session only, never from the request.
- **Security at the edge:** the session is regenerated on sign-in and invalidated with a new CSRF
  token on sign-out and deactivation; sign-in allows **5 failed attempts per email and address** a
  minute (`RateLimiter`), and the sign-up, recovery, verification and upload routes are throttled;
  a new password replaces the remember token, so old "keep me signed in" cookies stop working.
- **Pictures** go through the `ImageStore` port. `GdImageStore` accepts JPEG, PNG and WebP only (by
  content, not extension), refuses more than 40 million pixels before decoding, cuts the centre to
  the kind's proportions (photo 1:1 up to 512 px, cover 3:1 up to 1500 × 500), scales down only, and
  re-encodes as JPEG, which drops EXIF and anything else the file carried. Files get random names on
  the private `local` disk and are served by `ProfileImageController` to their owner only. GD joins
  the Docker image and the CI's PHP.
- **The feature tests run on MySQL 8.4** (`eletrontech_testing`, created by `docker/mysql/initdb/`;
  a MySQL service in the CI `test` job), as tech-debt said to do with the first Infrastructure
  repository: its queries and the schema it relies on run on the database production runs.
- **PHPStan ignores one message**, with its reason in `phpstan.neon`: Larastan reports the
  `Query\Builder` methods an Eloquent builder forwards as static, and strict-rules reads each call as
  `staticMethod.dynamicCall`. The same entry bway carries.

## Alternatives considered

- **A Laravel starter kit (Breeze, Fortify).** It brings its own controllers, validation and user
  model, with the rules in the framework layer; the CPF, the phone, deactivation and the activity
  log would be bolted on beside it. The parts of it worth having (guard, broker, signed links) are
  used directly.
- **A separate `accounts` table** with the `users` table only for the guard. Two rows per person to
  keep in step, for no rule that needs it; the repository already keeps Eloquent out of the domain.
- **Deleting a closed account's row.** It would take the activity log with it (or leave orphan rows),
  and the admin panel (E8) reads that log.
- **A "closed" status on `Account`.** Every use case would have to refuse it; making closed accounts
  unloadable puts the rule in one place, the repository's queries.
- **Keeping sex and birth date** as 2015 did: personal data with no feature that reads it.
- **Imagick for the pictures.** A larger system library, with more formats and so more decoders
  exposed to uploads; GD reads the three formats accepted.

## Consequences

- Every account flow has a feature test on MySQL, and every rule a unit test on in-memory adapters
  (`tests/Support/Account/`).
- A test that boots Laravel needs the database running (`make up`); a MySQL volume created before E4
  has no `eletrontech_testing` database: `docker compose down -v` once.
- The email cannot be changed yet; a change needs its own confirmation flow (tech-debt).
- The interface is English only and unstyled beyond the basics, like the converter's, until E9 (#12).
- E5 (#8) grants the initial seven-day licence at sign-up, through `RegisterAccount`; E8 (#11) reads
  the activity log.
