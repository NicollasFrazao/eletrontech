---
type: doc
name: testing-strategy
description: Pest, unit and feature tests, the coverage gate and what each layer is tested with
category: testing
generated: 2026-11-02
status: filled
scaffoldVersion: "2.0.0"
---

# Testing strategy

Pest 4 on PHPUnit 12. `composer test:coverage` (CI job `test`) runs every test with pcov and fails
under **80% line coverage** of `app/`.

## Suites

| Folder | Boots Laravel | For |
| --- | --- | --- |
| `tests/Unit/` | No | Domain and Application code, built with `new`; the local PHP_CodeSniffer sniffs |
| `tests/Feature/` | Yes (`Tests\TestCase`, set in `tests/Pest.php`) | HTTP routes, Artisan commands, Infrastructure against the database |

Most tests belong in `tests/Unit/`: a domain calculation has inputs and outputs and needs no
container. A feature test proves the wiring (route → handler → response), not every case of the rule.

## Database

Feature tests run on **MySQL 8.4**, the image of `docker-compose.yml`, in a database of their own,
`eletrontech_testing` (`phpunit.xml`), so a test run never wipes the app's data; `RefreshDatabase`
where a test writes. MySQL creates that database when its volume is new (`docker/mysql/initdb/`); a
volume from before E4 needs `docker compose down -v` once. In CI the `test` job runs the same image as
a service. Unit tests never touch the database.

## Writing tests

- One behaviour per test, named as a sentence: `it('rejects a room with a negative width')`.
- Arrange, act, assert, separated by blank lines.
- Calculators: test the values of the technical documentation's examples and the NBR 5410 tables
  they rely on, including the boundaries (the smallest and largest valid input, the first invalid one).
- Use Pest datasets for tables of inputs and expected outputs.
- Literals are fine in tests (no magic-number rule there).
- **Property-based tests** for laws that hold for every input (a conversion and its inverse, Ohm's
  law both ways, rounding within half a unit): `Tests\Support\Property::check($runs, $generators,
  $assertion)` runs the assertion with [Eris](https://github.com/giorgiosironi/eris) generators and
  shrinks a failure to a small counterexample. The seed changes on every run; a failure prints
  `ERIS_SEED=...`, and `ERIS_SEED=<seed> composer test` reproduces it.
- A bug fix comes with a test that fails before the fix.

## Current tests

- `tests/Feature/SmokeTest.php` — the home page and `/up` answer 200, and the home page carries its
  phone layout (viewport, the `40rem` breakpoint, 44 px touch targets).
- `tests/Feature/PhoneLayoutTest.php` — every page of the user area and the shop (sign-up, sign-in,
  profile and preferences, the shop, a package, the custom package, the licence, the agenda, the file
  manager) carries the phone layout of the shared account layout, and the desktop rules stay as they were.
- `tests/Feature/ContextCommandsTest.php` — `context:export` and `context:check` on a temporary
  directory: export, drift report, missing surface, missing index.
- `tests/Unit/CodingStandardTest.php` — the local sniffs on the fixtures in `phpcs/tests/fixtures/`.
- `tests/Unit/Domain/Quantity/` — units and the catalogue, exact conversions (every kind of unit,
  against known values), arithmetic within and between quantities, `Precision`, `NumberParser`, and
  `ConversionPropertiesTest.php`, the property-based round trips and laws, and
  `FluidAndMaterialLawsTest.php` for the quantities E3 added (volume, dynamic and hydrostatic pressure,
  Ohm's second law).
- `tests/Unit/Application/UnitConversion/` — the converter use cases.
- `tests/Feature/UnitConverterTest.php` — the converter page, its phone layout and its JSON answer,
  and each validation error on its field.
- `tests/Unit/Domain/Calculator/` — the contract (`CalculatorContractTest.php` runs every calculator
  through the same checks: distinct input names, a unit for each quantity, options for each choice,
  refusal of empty inputs), `Inputs`, `RealNumbers`, and `Electrical/`, one file per calculator with
  worked examples whose source (NBR 5410 section, IEC standard or textbook) is cited at the top. The
  2015 bugs each have a case named after them. E3 added `Electrical/` files for Ohm's law, conductor
  resistance and series/parallel resistance, and `Geometry/`, `Mathematics/`, `Physics/` and
  `Construction/`, one file per tool, with the formula at the top. `Mathematics/ExpressionTest.php`
  covers the grammar, every refused text and a property-based check against exact arithmetic.
- `tests/Unit/Application/Calculation/` — reading typed values and units by the schema, rounding,
  and the schema listing.
- `tests/Feature/CalculatorsTest.php` — the catalogue as registered, the JSON endpoints (an expression
  with a decimal comma, a number in hexadecimal, units on a construction tool) and each kind of refused
  value on its field, including a value longer than 255 characters.
- `tests/Feature/CalculatorPagesTest.php` — the pages (E9, #65): the list by group, one calculator of
  each group rendered from its schema and calculated, a band colour and a rounded value in the result, a
  tool the licence does not cover, a refused value shown on its field with what was typed, every name
  translated, and the phone layout.
- `tests/Unit/Domain/Account/` — every value object of a sign-up (valid CPFs and each way to get one
  wrong, phones, emails, names, the 72-byte password limit) and the account's rules (deactivation,
  reactivation and expiry at the deadline). `tests/Unit/Application/Account/` — every use case on the
  in-memory adapters of `tests/Support/Account/` (`AccountKit` wires them), including the closure of
  an expired account by a sign-in or by a sign-up with its email.
- `tests/Feature/Accounts/` — every account flow over HTTP on MySQL: sign-up (each refused field,
  a taken email or CPF), sign-in (same answer for a wrong password and an unknown email, the limit
  of five attempts, remember-me, reactivation and closure), email confirmation (signed link, forged
  link), password recovery, the profile (details, password with remember-token rotation,
  preferences, deactivation, a session whose account was closed) and the pictures (sizes, formats
  refused, the replaced file deleted). `tests/Support/Account/SignedUp.php` makes accounts through the
  real use cases.
- `tests/Unit/Domain/Licensing/` — the licence's rules: the initial seven days, extension of the same
  running package, replacement by another package or after the end, a custom package with other
  tools, the days left (a part of a day counts), the access rule at the edges of the period, the
  7-to-365 days. `tests/Unit/Application/Licensing/` — the use cases on the in-memory adapters of
  `tests/Support/Licensing/` (`LicensingKit` wires them): every refused purchase leaves the licence
  as it was.
- `tests/Feature/Licensing/` — the `use-tool` gate over HTTP (the free package's tools open and are
  recorded, other tools and an ended licence refused, an unknown tool not found, guests and
  unconfirmed accounts sent away) and the shop (the packages, a purchase with the password, an
  extension, a custom package, each refused purchase on its field, someone else's custom package and
  a forged buyer id). The tool tests sign in with `tests/Support/Licensing/Licensed.php`.
- `tests/Unit/Domain/Files/`, `tests/Unit/Domain/Agenda/` — every name that could be read as a path,
  every refused type (including a script named `.pdf` and a page named `.txt`), the limits, the event's
  fields. `tests/Unit/Application/Files/` and `Agenda/` — the use cases on the in-memory adapters of
  `tests/Support/Files/` and `Agenda/`: another person's file or folder is not found, a refused file
  leaves no content behind, a zip holds the tree, an export lands in "Events".
- `tests/Feature/Files/`, `tests/Feature/Agenda/` — over HTTP on MySQL with a fake disk: uploads under
  random keys, downloads as attachments with `nosniff`, every refused type, a path sent as a file name,
  every route on another person's file, folder or event answering 404, nested folders zipped and
  deleted with their contents, the quota, and the PDF and TXT exports.
- `tests/Unit/Application/Administration/` — the panel's access rule and the administrator's actions
  on an account (deactivate without the password, keep the deadline of one deactivated already,
  reactivate, close with its pictures deleted, refuse one's own account, a closed or unknown account
  not found), on the in-memory adapters.
- `tests/Feature/Administration/` — `/admin` over HTTP (an administrator, a member, an unconfirmed
  account, a guest, a closed account) and the account pages over Livewire on MySQL: a member refused,
  closed accounts not listed, the search by name, email, CPF with or without punctuation and id, the
  edit through `UpdateProfile` (CPF and email fixed, a refused phone on its field), deactivate and
  reactivate, delete as closure, from the list and from the edit page, and no deactivate or delete on
  one's own row.
- `tests/Support/Results.php` reads a calculator's result by name: `exact()` (the fraction),
  `shown()` (12 significant digits), `unit()`.

## Coverage

Line coverage of `app/` only. Mutation testing (Infection) is not in CI yet; see
[tech-debt.md](tech-debt.md).
