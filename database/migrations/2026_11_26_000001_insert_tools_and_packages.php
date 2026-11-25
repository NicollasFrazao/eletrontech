<?php

declare(strict_types=1);

use App\Domain\Licensing\Package;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;

/*
 * The tools of E1 to E3 and the 2015 packages. Reference data, not demo data: a sign-up needs the free
 * package, so every database has it from its migrations (the admin panel, E8, edits them later). The
 * 2015 database is not in the repository; the tools of each package follow its names, grouped by what
 * an electrician does with them, as docs/adr/0006-packages-and-licences.md explains. The floor-plan editor and the Draw package arrive
 * with the editor (E6).
 */
return new class extends Migration
{
    /**
     * Every tool, by identifier, with its name.
     */
    private const array TOOLS = [
        'area-and-perimeter' => 'Area and perimeter',
        'basic-calculator' => 'Basic calculator',
        'bernoulli-equation' => "Bernoulli's equation",
        'binary-converter' => 'Binary converter',
        'block-count' => 'Block count',
        'breaker-and-wiring' => 'Breaker and wiring',
        'btu-sizing' => 'BTU sizing',
        'conductor-resistance' => 'Conductor resistance',
        'electrical-capacitance' => 'Electrical capacitance',
        'electrical-conductance' => 'Electrical conductance',
        'electrical-consumption' => 'Electrical consumption',
        'electrical-impedance' => 'Electrical impedance',
        'electrical-inductance' => 'Electrical inductance',
        'floor-and-wall-tiles' => 'Floor and wall tiles',
        'general-lighting' => 'General lighting',
        'general-power' => 'General power',
        'lighting-power' => 'Lighting power',
        'motor-efficiency' => 'Motor efficiency',
        'ohms-law' => "Ohm's law",
        'outlet-count' => 'Outlet count',
        'resistor-colour-code' => 'Resistor colour code',
        'rule-of-three' => 'Rule of three',
        'series-parallel-resistance' => 'Series and parallel resistance',
        'surface-area-and-volume' => 'Surface area and volume',
        'unit-converter' => 'Unit converter',
    ];

    /**
     * The packages: name, kind and the tools of their theme ('*' for every tool). Each also gets the
     * everyday tools of Package::ESSENTIALS. The first is the free one.
     */
    private const array PACKAGES = [
        ['Basic', 'free', ['ohms-law', 'electrical-consumption']],
        ['Advanced', 'ready_made', [
            'general-power', 'outlet-count', 'general-lighting', 'lighting-power', 'breaker-and-wiring',
            'motor-efficiency', 'electrical-consumption',
        ]],
        ['Golden', 'ready_made', '*'],
        ['Light', 'ready_made', ['general-lighting', 'lighting-power', 'electrical-consumption']],
        ['Measure', 'ready_made', [
            'area-and-perimeter', 'surface-area-and-volume', 'floor-and-wall-tiles', 'block-count', 'btu-sizing',
            'bernoulli-equation',
        ]],
        ['Tester', 'ready_made', [
            'ohms-law', 'series-parallel-resistance', 'conductor-resistance', 'resistor-colour-code',
            'electrical-capacitance', 'electrical-conductance', 'electrical-impedance', 'electrical-inductance',
            'binary-converter',
        ]],
    ];

    /**
     * Inserts the tools and the packages.
     */
    public function up(): void
    {
        DB::table('tools')->insert(array_map(
            static fn (string $id, string $name): array => ['id' => $id, 'name' => $name],
            array_keys(self::TOOLS),
            self::TOOLS,
        ));

        $now = Date::now();

        foreach (self::PACKAGES as [$name, $kind, $tools]) {
            $id = DB::table('packages')->insertGetId([
                'name' => $name,
                'kind' => $kind,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            DB::table('package_tool')->insert(array_map(
                static fn (string $tool): array => ['package_id' => $id, 'tool_id' => $tool],
                $tools === '*' ? array_keys(self::TOOLS) : array_values(array_unique([...Package::ESSENTIALS, ...$tools])),
            ));
        }
    }

    /**
     * Removes the packages that are not someone's custom one, and the tools.
     */
    public function down(): void
    {
        DB::table('packages')->whereNull('owner_id')->delete();
        DB::table('tools')->delete();
    }
};
