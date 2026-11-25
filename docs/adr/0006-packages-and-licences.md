# 0006. Packages and licences in the domain, one gate for every tool, reference data in migrations

- Status: Accepted
- Date: 2026-11-26
- Issues: #8 (E5, tool packages, licences and the shop)

## Context

In 2015 a person reached the tools through a **licence** (`usuario_pacote`): one package between a
start and an end date. A new account got seven days of a free package; the **Eletron Tech Shop**
(`ETS/`) sold the other packages, ready-made ones (`tb_pacote`) or a **custom** one whose tools the
person picked (`ic_custom`, one per person, `cd_usuario` unique), for 7 to 365 days, confirmed with the
password. Nothing was charged. Each use of a tool was a row of `tb_utilizacao`.

The original got several things wrong:

- the purchase page wrote the account's password, Base64-decoded, into the page, and compared it in
  the browser;
- every query was built by concatenating strings;
- a purchase deleted the licence before it knew the new one would be written;
- the tools page hid the tools the licence did not cover, but nothing on the server refused them.

The 2015 database is not in the repository, so the tools of each package are lost. Only their names
survive, in the shop's images: Basic, Advanced, Golden, Light, Measure, Tester, Draw, Custom. The
monograph says the packages hold "tools separated by categories".

Since E1 to E3 the calculators and the unit converter have been open to anyone.

## Decision

- **The domain** (`app/Domain/Licensing/`):
  - `Package`: an id, a name, a `PackageKind` (`Free`, `ReadyMade`, `Custom`) and at least one
    `ToolId`;
  - `Licence`: a holder, a package, `startsAt` and `endsAt`;
  - `UsageDays`: 7 to 365.
- **The rules live on `Licence`:**
  - `initial()` gives the free package for seven days.
  - `renewedWith()` handles a purchase. Buying the **same package while it runs adds the days to its
    end**; any other package, or the same one after it ended, **replaces the licence from now**.
    2015 always replaced it; the issue asks for extension, and extending only the same running
    package keeps both. A custom package bought again with other tools counts as another package.
  - `remainingDaysAt()` counts a part of a day as a day, as 2015 did.
  - `allows(tool, now)` is the one access rule: the licence is running and its package includes the
    tool.
  - The free package cannot be bought (2015 left it out of the shop).
- **One gate decides every tool:**
  - `use-tool` is defined in `LicensingServiceProvider` and answered by `AuthoriseToolUse`. It returns
    allowed, not licensed (403, with a message) or unknown tool (404).
  - The tool routes require `auth`, `verified` and `can:use-tool,…`: the calculation route
    (`can:use-tool,calculator`) and the converter's conversion. The calculator list and the
    converter form stay open, as the catalogue the shop describes.
  - A successful use is recorded by `RecordToolUse` in `tool_uses`. The activity log records
    purchases (`package_bought`), not each calculation.
- **The purchase** (`BuyPackage`) confirms the password on the server (`PasswordConfirmation`), then
  checks the days, the package and the tools, and only then writes the licence. A custom package is
  bought by its tools, never by an id, so nobody can buy someone else's.
- **Sign-up** is `SignUp`: `RegisterAccount` then `GrantInitialLicence`. `RegisterAccount` stays
  about the account.
- **Tools, packages and their tools are rows:**
  - tables: `tools` (keyed by the calculator's identifier), `packages`, `package_tool` and
    `licences` (one per person);
  - the admin panel (E8, #11) manages them, prices included;
  - they are **inserted by a migration**, not a seeder: a sign-up needs the free package, so every
    database, the tests' included, has it from its schema.
- **The packages, rebuilt from their names** (to be confirmed by the owner):

  | Package | Tools |
  | --- | --- |
  | **Basic** (free) | basic calculator, unit converter, rule of three, binary converter, area and perimeter, surface area and volume |
  | **Advanced** | general power, breaker and wiring, motor efficiency, electrical consumption, Bernoulli |
  | **Light** | general lighting, lighting power, electrical consumption |
  | **Measure** | outlet count, block count, floor and wall tiles, BTU sizing |
  | **Tester** | capacitance, conductance, impedance, inductance, resistor colour code, Ohm's law, conductor resistance, series and parallel resistance |
  | **Golden** | every tool |

  **Draw** and the floor-plan editor arrive together in E6 (#9): a package whose only tool does not
  exist yet would sell nothing.
- **The pages are plain Blade** until E9:
  - the shop: packages with their tools, "see more" with the purchase form, the custom package;
  - the licence page: package, period, days left, tools.

## Alternatives considered

- **Packages in code**, like the calculator catalogue. E8 has to edit packages and prices without a
  deployment.
- **A seeder for the reference data.** `RefreshDatabase` does not seed, and production would need a
  manual step before the first sign-up worked.
- **A policy class.** A policy is per model, and a tool is not an Eloquent model the controllers see.
  A gate with the tool's identifier is the one place the issue asks for.
- **Several licences at once** (one per package). 2015 had one, and "which licence opens this tool"
  would need a rule of its own.
- **Recording every calculation in the activity log**, as 2015 did. It would bury the account's
  actions under thousands of rows; `tool_uses` keeps them apart for the panel's statistics.

## Consequences

- An account made before E5 has no licence, so no tool opens until it buys a package. The only such
  accounts are development ones.
- The feature tests of the tools sign in with a licence (`tests/Support/Licensing/Licensed.php`).
- Prices are not modelled yet (E8). Payment is the future issue #20.
- The package contents above are an assumption recorded here and in tech-debt, until the owner
  confirms or changes them.
