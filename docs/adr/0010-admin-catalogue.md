# 0010. The catalogue in the admin panel: packages with a price and days, tools renamed, writes through the licensing domain

- Status: Accepted
- Date: 2026-11-26
- Issues: #58 (E8, part of #11)

## Context

ADR 0006 made the tools, the packages and the tools of each package rows, inserted by a migration, so
that E8 could change them without a deployment, "prices included". Until now a package had no price,
and changing one meant a new migration. The 2015 admin area had no catalogue page: the packages were
edited in the database.

The licensing domain already holds the rules a package must meet: every package carries the everyday
tools (`Package::ESSENTIALS`), a package has at least one tool, and a purchase is for 7 to 365 days
(`UsageDays`). ADR 0009 put the Filament resources in Infrastructure, reading rows directly and writing
through use cases.

## Decision

- **A package is sold on terms: a price for a number of days.** `packages.price` (reais, two decimal
  places) and `packages.days` are new columns, read by the domain as `PackageTerms` (`Price`, 0.00 to
  99,999.99, and `UsageDays`, 7 to 365). The free package and custom ones have none (both columns
  null): the free one is not sold, and a custom package is priced by nobody yet. The ready-made
  packages of ADR 0006 start at 0.00 for 30 days, as nothing was charged in 2015.
- **The panel writes through the licensing domain.** `App\Application\Administration\CreatePackage`
  and `ChangePackage` take a `PackageInput` (name, tools, price, days) and build a `PackageDefinition`
  (a `CatalogueName` of 1 to 100 characters, and the tools with the essentials always added, at least
  one other) and `PackageTerms`; `PackageRepository::add()` and `change()` store them. A refused value
  stores nothing and is shown on its field. The form adds no rule of its own beyond "required".
- **What can be done to which package:**
  - ready-made: created, and changed (name, tools, price, days);
  - free: its name and tools changed; it has no price or days to set, and any sent are not read;
  - custom: not listed and not changed, since it belongs to a person (`ChangePackage` answers not
    found);
  - none is deleted: a licence may hold it (`licences.package_id` restricts the delete).
- **Tools are renamed, not created or deleted.** A tool is code (a calculator, the converter); a row
  without its code would open nothing, and deleting one would cascade out of every package and usage
  record. `RenameTool` changes the name, refusing a blank one, one longer than 100 characters, or one
  another tool has (`ToolNameTaken`), since the shop shows tools by name.
- The resources are `App\Infrastructure\Filament\Packages\PackageResource` (with `PackageForm`, the
  form and its error mapping) and `App\Infrastructure\Filament\Tools\ToolResource`.

## Alternatives considered

- **Price per day**, the price multiplied by the days bought. The 2015 shop charged nothing, so there
  is no rule to recover; a price for a stated period is what a package page can show plainly, and
  payment (#20) decides how a different number of days is charged.
- **Terms on `Package` itself.** Its constructor is at the four-parameter limit, and nothing in the
  licence rules reads the price; the terms travel apart, written by the panel.
- **Validating in the Filament form** (`minValue(7)`, `maxValue(365)`). It would state the rules a
  second time, where they can drift from the domain's; the form shapes the values and the domain decides.
- **Creating tools in the panel.** Nothing would answer for a tool that has no code.

## Consequences

- An administrator changes the catalogue without a migration; a new tool still comes with its code and
  a migration.
- The shop does not show the price or the days yet, and nothing is charged: payment is #20
  (tech-debt).
- A running licence sees a package's new tools at once, as it holds the package, not a copy of its
  tools.
