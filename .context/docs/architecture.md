---
type: doc
name: architecture
description: The four layers, their dependency rules, where each kind of class goes, and why
category: architecture
generated: 2026-11-02
status: filled
scaffoldVersion: "2.0.0"
---

# Architecture

Laravel only at the edges. The calculations, the licence rules and the floor-plan geometry are plain
PHP objects that a test can build with `new`; the framework delivers requests to them and stores
what they return. The decision and its alternatives are in
[ADR 0001](/docs/adr/0001-layered-architecture.md).

## Layers

| Layer | Folder | Holds | May depend on |
| --- | --- | --- | --- |
| Domain | `app/Domain/` | Entities, value objects, domain services, repository interfaces, domain errors | Itself, PHP and brick/math |
| Application | `app/Application/` | Use cases (one handler per command or query), input and output DTOs | Domain, brick/math |
| Infrastructure | `app/Infrastructure/` | Eloquent models and repositories, file storage, mail, queues, PDF export | Domain, Application, vendor packages |
| Http | `app/Http/` | Controllers, form requests, API resources, middleware | Domain, Application, vendor packages |
| Console | `app/Console/` | Artisan commands | Domain, Application, vendor packages |
| Providers | `app/Providers/` | The composition root: binds each domain interface to its implementation | Everything |

"PHP" means the language and its bundled classes (`DateTimeImmutable`, `RuntimeException`,
`Stringable`): they belong to no layer, so every layer may use them. `Brick\Math` (exact numbers)
is the `Math` layer, open to every layer for the same reason
([ADR 0002](/docs/adr/0002-exact-arithmetic-for-quantities.md)). Everything else outside `App\`
(Laravel, Symfony, Carbon, any other Composer package) is the `Vendor` layer of `deptrac.yaml`.

The rules are checked by **Deptrac** (`composer deptrac`, CI job `deptrac`). A violation is fixed by
moving the code or adding an interface in the domain, never by widening the rule; widening it needs a
new ADR.

## Where things go

- **A calculation** (breaker sizing, lighting, outlet count): a class implementing
  `App\Domain\Calculator\Calculator` in `app/Domain/Calculator/<Area>/`, registered in the catalogue,
  taking and returning value objects (`Power`, `Current`, `Length`), never floats with a unit in the
  variable name. Its validation errors are `InvalidInput`. See "Calculators" below.
- **A rule that needs stored data** (is this tool in the user's licence?): the domain defines the
  repository interface; Infrastructure implements it with Eloquent; a provider binds them.
- **A user action** (acquire a package, save a room): an Application handler with an input DTO. The
  controller builds the DTO from a form request, calls the handler, maps the result to a view or a
  resource. Controllers hold no rules.
- **Eloquent models** live in `app/Infrastructure/Persistence/Eloquent/` and never leave it: a
  repository maps them to domain entities. `User` is the row of an account, which the framework's
  guard, password broker and email verification read; `EloquentAccountRepository` maps it to the
  domain's `Account` (see "Accounts" below).
- **Framework glue** (middleware, jobs, mailables, notifications): Infrastructure or Http, by who
  calls it.

## Physical quantities

`app/Domain/Quantity/` is the kernel every calculator builds on
([ADR 0002](/docs/adr/0002-exact-arithmetic-for-quantities.md)):

- **Value objects**, one final class per quantity, all extending the abstract `Quantity`: `Voltage`,
  `Current`, `Power`, `ApparentPower`, `ReactivePower`, `Resistance`, `Conductance`, `Capacitance`,
  `Inductance`, `MagneticFlux`, `Charge`, `Energy`, `Frequency`, `Duration`, `Length`, `Area`,
  `LuminousFlux`, `Illuminance`, `Volume`, `Pressure`, `Velocity`, `Density`, `Resistivity`, plus the
  dimensionless `PowerFactor` (E2 added six that its calculators needed, E3 the last five). Each stores an exact
  `BigRational` in the coherent SI unit and has a named constructor and reader in it
  (`Power::fromWatts()`, `->toWatts()`); `Quantity::of($value, $unit)` and `->in($unit)` take any unit
  of the dimension.
- **Arithmetic** within a kind (`plus`, `minus`, `multipliedBy` a number, `dividedBy` a number,
  `ratioTo`, comparisons) is on `Quantity`, with `static` parameters so PHPStan refuses a voltage plus a
  current. Arithmetic **between** kinds exists only where it means something and lives on the
  subclasses: `Voltage::timesCurrent(): Power`, `Power::dividedByVoltage(): Current`, Ohm's law,
  `Resistance::reciprocal(): Conductance`, `Current::dividedByVoltage(): Conductance`,
  `Capacitance::timesVoltage(): Charge`, `Length::timesLength(): Area`,
  `ApparentPower::timesPowerFactor(): Power`, `Power::timesDuration(): Energy`,
  `Illuminance::timesArea(): LuminousFlux`, `MagneticFlux::dividedByCurrent(): Inductance` (flux
  linkage over current), `Area::timesLength(): Volume`, `Density::dynamicPressureAt(Velocity)` and
  `Density::hydrostaticPressureAt(Length)` (½ρv² and ρg₀h, with standard gravity),
  `Resistivity::resistanceOf(Length, Area)` (Ohm's second law), and their inverses.
- **Units**: `BaseUnit` (SI and the other units the 2015 converter offered, each with its exact size),
  `Prefix` (pico to giga), `Unit` (a base unit with a prefix it is offered with) and `UnitCatalogue`
  (every unit by identifier, such as `kilowatt`, or by `Dimension`).
- **Display**: `Precision` rounds half up to significant digits or decimal places; it is the only
  place that rounds. `NumberParser` reads a typed number with a digit limit.
- Quantities accept negative values; whether a negative input is valid is the calculator's rule.

The unit converter is the first tool on top of it: `App\Application\UnitConversion\ConvertQuantity`
(input DTO in, result DTO of strings out), `ListConvertibleUnits`, and
`App\Http\Controllers\Tools\UnitConverterController` at `/tools/unit-converter` (a form, or JSON
for a client that asks for it).

## Calculators

`app/Domain/Calculator/` is the contract every tool implements
([ADR 0003](/docs/adr/0003-calculators-behind-one-contract.md)):

- `Calculator` (Strategy): `id()`, `inputs()` (the input schema, a list of `InputField`: a quantity
  with its default unit, a pure number, a choice among an enum's cases, or a text the calculator reads
  itself; required or optional) and `calculate(Inputs): Result`. `CalculatorCatalog` finds one by
  identifier; the provider registers them in the order they are offered.
- `Inputs` hands each value out typed and validated (`positiveQuantity()`, `wholeNumber()`,
  `choice()`, `text()`, `missing()` for the tools that solve for the values left empty) and throws
  `InvalidInput` with the field and an `InputProblem` (`NoSolution` when the values admit no answer). `Result` holds the answers and the intermediate
  values as `ResultValue`s, exact, each with the unit it is shown in.
- `RealNumbers` gives π and square roots to 32 significant digits; `FixedUnit` names a unit in code.
- `Electrical/` holds the twelve tools of E2, one class each, with their value objects
  (`RoomDimensions`, `TurnCount`, `Efficiency`, `EnergyTariff`, `ResistorBands`) and enums
  (`SupplySystem`, `CircuitPurpose`, `RoomType`, `LightingSystem`, ...). Tables are data in their own
  classes: `Electrical/Nbr5410/` (`LightingLoad`, `OutletRequirements`, `ConductorTable`,
  `BreakerRatings`) and `LuminaireTables` (lumen-method factors). E3 added `OhmsLaw`,
  `ConductorResistance` and `SeriesParallelResistance` there (the rest of the 2015 resistor tool).
- E3's general tools ([ADR 0004](/docs/adr/0004-arithmetic-expressions-without-eval.md) for the
  expressions): `Geometry/` (`AreaAndPerimeter` and `SurfaceAreaAndVolume`, one small value object per
  shape behind `PlaneFigure` and `SolidFigure`), `Mathematics/` (`BasicCalculator` on `Expression`, a
  recursive-descent parser over exact fractions; `BinaryConverter`; `RuleOfThree`), `Physics/`
  (`BernoulliEquation` with `FlowPoint`) and `Construction/` (`FloorAndWallTiles` and `BlockCount`
  sharing `MaterialCount` and `WasteAllowance`; `BtuSizing`).

`App\Application\Calculation\Calculate` runs any of them: it reads the typed values by the schema, calls
the calculator and rounds each number to 12 significant digits; `DescribeCalculator` describes one schema
and `ListCalculators` all of them, each with the `CalculatorGroup` its calculator declares.
`App\Http\Controllers\Tools\CalculatorController` serves them as pages (E9, #65: the list by group at
`GET /tools/calculators`, a form built from the schema at `GET /tools/calculators/{id}`, the result lines
after `POST /tools/calculators/{id}`) and as JSON to a client that asks for it, mapping `InvalidInput` to a
validation error on its field. The labels are in `lang/en/calculators.php`: the calculator names, the groups,
one `fields` vocabulary shared by inputs and result values, and the options of every choice.

## Accounts

`app/Domain/Account/` holds the account of E4 (#7), decided in
[ADR 0005](/docs/adr/0005-accounts.md):

- `Account` (an id, then `PersonalDetails`, `Access` and `Profile`, immutable value objects it
  replaces), the value objects of a sign-up (`Cpf`, `PhoneNumber`, `ContactPhone`, `EmailAddress`,
  `PersonName`, `PlainPassword`, `HashedPassword`) and `InvalidAccountData`, which names the
  `AccountField` it refused. Deactivation is a reactivation deadline 30 days ahead; `signIn()` says
  whether a sign-in reactivates the account or finds it expired.
- Ports: `AccountRepository` (only open accounts are found; `close()` erases the CPF, email, phone,
  password and pictures and keeps the name), `ActivityLog`, `PasswordHasher`, `Clock`, `ImageStore`.
- `app/Application/Account/`: one handler per action (`RegisterAccount`, `SignIn`, `SignOut`,
  `VerifyEmail`, `ResetPassword`, `ChangePassword`, `UpdateProfile`, `UpdatePreferences`,
  `ReplaceProfileImage`, `ShowProfileImage`, `DeactivateAccount`, `ViewProfile`), with the services
  they share: `AccountCloser`, `PasswordWriter`, `ActivityRecorder`.
- `app/Infrastructure/`: `Persistence/EloquentAccountRepository` and `EloquentActivityLog`
  (`activities`), `Account/FrameworkPasswordHasher` (bcrypt), `SystemClock` and `GdImageStore`
  (pictures re-encoded with GD on the private `local` disk).
- `app/Http/Controllers/Account/`: sign-up, session, email verification, password reset, profile
  and profile pictures, with their form requests in `app/Http/Requests/Account/`. The framework's
  guard, password broker and signed links stay at this edge; the controllers take the account id
  from the session only. `AccountServiceProvider` binds the ports.

## Packages and licences

`app/Domain/Licensing/` holds the packages and licences of E5 (#8), decided in
[ADR 0006](/docs/adr/0006-packages-and-licences.md):

- `Package` (free, ready-made or custom, with its `ToolId`s), `Licence` (a holder, a package, a
  period: `initial()`, `renewedWith()`, `remainingDaysAt()`, and `allows()`, the one access rule),
  `UsageDays` (7 to 365), `Tool`; for the admin panel's writes, `CatalogueName`, `Price`,
  `PackageTerms` (a price for some days) and `PackageDefinition` (a name and tools, the essentials
  added). Ports: `PackageRepository`, `ToolRepository`, `LicenceRepository`,
  `ToolUseLog`.
- `app/Application/Licensing/`: `GrantInitialLicence`, `BuyPackage` (with `PasswordConfirmation`),
  `AuthoriseToolUse`, `RecordToolUse`, `ShowLicence`, `ShowShop`. `Application/Account/SignUp`
  registers the account and grants its initial licence.
- `app/Infrastructure/Persistence/`: `EloquentPackageRepository`, `EloquentToolRepository`,
  `EloquentLicenceRepository`, `EloquentToolUseLog` (`tools`, `packages`, `package_tool`, `licences`,
  `tool_uses`; the tools and packages are inserted by a migration).
- `app/Http/Controllers/Shop/`: the shop, the purchase and the licence page. The tool routes require
  `can:use-tool,…`; `LicensingServiceProvider` binds the ports and defines the gate.

## Events and files

`app/Domain/Agenda/` and `app/Domain/Files/` hold the events and the file manager of E7 (#10),
decided in [ADR 0007](/docs/adr/0007-events-and-files.md):

- Agenda: `Event`, `EventDetails`, the `EventRepository` and `EventDocument` ports. Use cases in
  `app/Application/Agenda/`: `RecordEvent` (add, update, delete), `ShowAgenda` (a month, a search, one
  event), `ExportEvents` (PDF or TXT into the folder "Events").
- Files: `Folder`, `StoredFile`, `EntryName`, `FileType` (the allow-list), `StorageLimit`, and the
  ports `FileIndex`, `FileStore`, `Archiver`. Use cases in `app/Application/Files/`: `UploadFile`,
  `CreateFolder`, `ShowFolder`, `DownloadFile`, `DownloadFolder`, `DeleteEntry`, with `FileWriter`,
  which every stored file goes through.
- Infrastructure: `EloquentEventRepository`, `EloquentFileIndex` (`events`, `folders`, `files`),
  `Files/DiskFileStore` (private disk, random keys), `Files/ZipArchiver`,
  `Agenda/DompdfEventDocument`. `FilesServiceProvider` binds them.
- Http: `Controllers/Agenda/EventController`, `Controllers/Files/FolderController` and
  `FileController`. Every download goes out through `Attachment`.

## Admin panel

E8 (#11) starts with the Filament panel at `/admin`, decided in
[ADR 0008](/docs/adr/0008-admin-panel-and-roles.md):

- `App\Domain\Account\Role` (`Member`, `Administrator`), stored in `users.role` and read with
  `AccountRepository::roleOf()`; `Role::mayAdminister()` allows a confirmed administrator only.
- `App\Application\Administration\AuthoriseAdministration` is the one check; the `User` model
  implements Filament's `FilamentUser`, and `canAccessPanel()` calls it, so anyone else gets 403.
- `App\Providers\Filament\AdminPanelProvider` configures the panel (the dashboard and the
  resources) with no sign-in page of its own: a guest is sent to the application's `/login`.
- User management ([ADR 0009](/docs/adr/0009-admin-user-management.md)): the Filament resource
  `App\Infrastructure\Filament\Accounts\AccountResource` lists and searches the open accounts (id,
  name, email, CPF) and changes them only through use cases: `Account\UpdateProfile` for the name and
  the phone, and `App\Application\Administration\DeactivateAccount`, `ReactivateAccount` and
  `CloseAccount` (delete = close, as ADR 0005 closes an expired account). An administrator cannot
  deactivate or delete their own account (`OwnAccountRefused`).
- The catalogue ([ADR 0010](/docs/adr/0010-admin-catalogue.md)): `Infrastructure\Filament\Packages\PackageResource`
  lists the free and ready-made packages and creates and changes them through
  `Administration\CreatePackage` and `ChangePackage` (a `PackageInput` read into the domain's
  `PackageDefinition` and `PackageTerms`: name, tools with the essentials added, price, 7 to 365 days);
  `Infrastructure\Filament\Tools\ToolResource` renames tools through `Administration\RenameTool`.
- Feedback (E9, #63): `Infrastructure\Filament\Feedback\FeedbackResource` lists the messages of the
  feedback form, newest first, with their author, date and subject, searched by author or text and
  filtered by subject; it only reads them (see "Feedback" below).
- Filament resources live in `app/Infrastructure/Filament/`, since they are built on the Eloquent
  models; they read rows directly and write through Application use cases.

## Feedback

`app/Domain/Feedback/` holds the feedback form of E9 (#63). The 2015 form posted to a remote URL; v2
keeps each message in the application, with its author and the moment it was sent:

- `Feedback` (a `FeedbackSubject`, the seven subjects of 2015, and a message of 1 to 500 characters),
  `InvalidFeedback`, which names the `FeedbackField` it refused, and the port `FeedbackBox`.
- `App\Application\Feedback\SendFeedback` reads a `FeedbackInput` into a `Feedback` and keeps it with
  the author and the `Clock`'s now.
- `Infrastructure/Persistence/EloquentFeedbackBox` writes `feedback` (`FeedbackRecord`, which belongs to
  its author's `User`); `FeedbackServiceProvider` binds the port.
- `Http/Controllers/Feedback/FeedbackController` serves the form at `GET /feedback` and sends it with
  `POST /feedback` (signed in, email confirmed, six a minute), linked from the profile. The
  administrators read the messages in the admin panel (see "Admin panel" above).

## Errors

Domain and Application code throws its own exception classes, one per failure the caller can act
on (`InvalidDimension`, `LicenceExpired`). PHPStan treats every exception in `App\` as **checked**
(`phpstan.neon`, `exceptions.checkedExceptionRegexes`): a method that throws one, or lets one escape,
declares it with `@throws`, so the caller sees it in the signature. Http maps them to responses.

## Current state

E0 laid the foundation: the folders, the rules, the gate. E1 (#4) added the physical quantities
(`app/Domain/Quantity/`) and the unit converter, the first use case (`app/Application/UnitConversion/`)
and the first controller (`app/Http/Controllers/Tools/`). E2 (#5) added the calculator contract and
the twelve electrical calculators (`app/Domain/Calculator/`), the use case that runs any of them
(`app/Application/Calculation/`) and their JSON endpoint. E3 (#6) added twelve general calculators on
the same contract, so every 2015 calculation tool, web or mobile, is in the catalogue (the mobile
spirit level reads a sensor and is not a calculation; see [tech-debt.md](tech-debt.md)). E4 (#7) added
the accounts (see "Accounts" above), the first repository and the first pages behind sign-in. E5 (#8)
added the packages, the licences and the shop (see "Packages and licences" above): every tool is now
behind the `use-tool` gate. E8 (#11) started with the admin panel, its user management and the catalogue of tools and packages (see "Admin panel" above). E9 (#12) added the calculator pages, the accessibility pass and the feedback form (see "Feedback" above). The rest
is the context-base tooling in `app/Console/` (`context:export`, `context:check`) and the framework
defaults.
