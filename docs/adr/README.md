# Architecture decision records

One file per decision that shaped the code and would be expensive to reverse. Each says what was
decided, what else was considered, and what it costs. A superseded record stays, marked as such.

| # | Decision | Status |
| --- | --- | --- |
| [0001](0001-layered-architecture.md) | Layered architecture with Laravel only at the edges, enforced by Deptrac | Accepted |
| [0002](0002-exact-arithmetic-for-quantities.md) | Exact arithmetic for physical quantities with brick/math; amends 0001 so the Domain may use `Brick\Math` | Accepted |
| [0003](0003-calculators-behind-one-contract.md) | Calculators as interchangeable domain services behind one `Calculator` contract, with a catalogue, an input schema and NBR 5410 tables as data | Accepted |
| [0004](0004-arithmetic-expressions-without-eval.md) | Arithmetic expressions evaluated exactly by a recursive-descent parser of our own, never `eval`; a text input kind for the calculator contract | Accepted |
| [0005](0005-accounts.md) | Accounts in the domain (CPF, phone, deactivation for 30 days, closure that erases), Laravel's guard and password broker at the edge | Accepted |
| [0006](0006-packages-and-licences.md) | Packages and licences in the domain (initial seven days, extension of the same running package), one `use-tool` gate for every tool, reference data in migrations | Accepted |
| [0007](0007-events-and-files.md) | Events and files owned by their person (another person's is not found), contents under random keys, an allow-list checked on the content, downloads as attachments, PDF with Dompdf | Accepted |
