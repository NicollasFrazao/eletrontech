<?php

declare(strict_types=1);

use App\Application\Calculation\ListCalculators;
use App\Infrastructure\Persistence\Eloquent\ToolUseRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Lang;
use Tests\Support\Account\SignedUp;
use Tests\Support\Licensing\Licensed;

/*
 * E9 (#65): the calculators as pages. The list by group, and a form per calculator built from its input schema,
 * posted to the same route as the JSON API and guarded by the same `use-tool` gate.
 */
uses(RefreshDatabase::class);

it('lists every calculator under its group, each linking to its page', function (): void {
    $page = $this->get('/tools/calculators')
        ->assertOk()
        ->assertSeeInOrder(['Electrical', 'Outlet count (NBR 5410)', 'Geometry', 'Area and perimeter', 'Mathematics',
            'Basic calculator', 'Physics', 'Bernoulli&#039;s equation', 'Construction', 'Blocks for a wall'], false)
        ->getContent();

    foreach (app(ListCalculators::class)->handle()->calculators as $calculator) {
        expect($page)->toContain('href="'.route('tools.calculators.show', $calculator->id).'"');
    }
});

it('still answers the list as JSON to a client that asks for it', function (): void {
    $this->getJson('/tools/calculators')
        ->assertOk()
        ->assertJsonPath('calculators.0.group', 'electrical')
        ->assertJsonPath('calculators.20.group', 'physics');
});

it('names every calculator, group, input and option in the translations', function (): void {
    foreach (app(ListCalculators::class)->handle()->calculators as $calculator) {
        expect(Lang::has('calculators.names.'.$calculator->id))->toBeTrue($calculator->id)
            ->and(Lang::has('calculators.groups.'.$calculator->group))->toBeTrue($calculator->group);

        foreach ($calculator->inputs as $input) {
            expect(Lang::has('calculators.fields.'.$input->name))->toBeTrue($input->name);

            foreach ($input->accepts->options as $option) {
                expect(Lang::has('calculators.options.'.$option))->toBeTrue($option);
            }
        }
    }
});

it('renders a calculator of each group from its schema and shows the result lines', function (
    string $calculator,
    array $fields,
    array $input,
    array $result,
): void {
    $this->get("/tools/calculators/{$calculator}")
        ->assertOk()
        ->assertSee('action="'.route('tools.calculators.calculate', $calculator).'"', false)
        ->assertSeeInOrder($fields, false);

    $this->actingAs(Licensed::golden())
        ->post("/tools/calculators/{$calculator}", $input)
        ->assertOk()
        ->assertSee('Result')
        ->assertSeeInOrder($result, false);

    expect(ToolUseRecord::query()->pluck('tool_id')->all())->toBe([$calculator]);
})->with([
    'electrical' => [
        'outlet-count',
        ['<select id="room_type" name="room_type" required', '<option value="kitchen"', 'Kitchen',
            '<input id="length" name="length"', 'inputmode="decimal"', '<select name="units[length]"',
            '<option value="metre" selected', 'foot'],
        ['room_type' => 'kitchen', 'length' => '3', 'width' => '2,5', 'units' => ['width' => 'metre']],
        ['<dt>Outlets</dt>', '<dt>Total load</dt>', 'VA', 'Steps', '<dt>Area</dt>', '7.5 m²'],
    ],
    'geometry' => [
        'area-and-perimeter',
        ['<select id="shape" name="shape" required', 'Rectangle', 'Length (optional)', '<select name="units[length]"'],
        ['shape' => 'rectangle', 'length' => '200', 'width' => '3', 'units' => ['length' => 'centimetre']],
        ['<dt>Area</dt>', '6 m²', '<dt>Perimeter</dt>', '10 m'],
    ],
    'mathematics' => [
        'binary-converter',
        ['<input id="value" name="value"', '<select id="base" name="base" required', 'Hexadecimal'],
        ['value' => 'FF', 'base' => 'hexadecimal'],
        ['<dt>Binary</dt>', '11111111', '<dt>Decimal</dt>', '255'],
    ],
    'physics' => [
        'bernoulli-equation',
        ['Density', 'Pressure at point 1 (optional)', 'Velocity at point 2 (optional)'],
        ['density' => '1000', 'pressure_1' => '0', 'velocity_1' => '0', 'height_1' => '0', 'velocity_2' => '0', 'height_2' => '0'],
        ['<dt>Pressure at point 2</dt>', '0 Pa'],
    ],
    'construction' => [
        'btu-sizing',
        ['Area', '<select name="units[area]"', 'People', 'Appliances'],
        ['area' => '10', 'people' => '1', 'appliances' => '0'],
        ['<dt>Cooling capacity</dt>'],
    ],
]);

it('names a band colour in the result and marks a rounded value', function (): void {
    $this->actingAs(Licensed::golden());

    $this->post('/tools/calculators/resistor-colour-code', ['resistance' => '4700'])
        ->assertOk()
        ->assertSeeInOrder(['<dt>First band</dt>', 'Yellow', '<dt>Second band</dt>', 'Violet'], false);

    $this->post('/tools/calculators/basic-calculator', ['expression' => '2 / 3'])
        ->assertOk()
        ->assertSee('≈ 0.666666666667')
        ->assertSee('(rounded)')
        ->assertSee('value="2 / 3"', false);
});

it('refuses a calculator the licence does not cover, and records nothing', function (): void {
    $this->actingAs(SignedUp::verified())
        ->post('/tools/calculators/breaker-and-wiring', ['supply_system' => 'single_phase'])
        ->assertForbidden()
        ->assertSee('Your licence does not cover this tool, or it has ended.');

    expect(ToolUseRecord::query()->count())->toBe(0);
});

it('shows a refused value on its field, keeping what was typed', function (): void {
    $this->actingAs(Licensed::golden());

    $this->from('/tools/calculators/lighting-power')
        ->post('/tools/calculators/lighting-power', ['length' => '3', 'width' => '-2', 'units' => ['width' => 'foot']])
        ->assertRedirect('/tools/calculators/lighting-power')
        ->assertSessionHasErrors(['width' => 'The value must be greater than zero.']);

    $this->from('/tools/calculators/lighting-power')
        ->followingRedirects()
        ->post('/tools/calculators/lighting-power', ['length' => '3', 'width' => '-2', 'units' => ['width' => 'foot']])
        ->assertOk()
        ->assertSeeInOrder([
            '<input id="width" name="width" value="-2"',
            'aria-invalid="true"',
            '<option value="foot" selected',
            '<p class="error">The value must be greater than zero.</p>',
        ], false);

    expect(ToolUseRecord::query()->count())->toBe(0);
});

it('shows a problem with the combination of values above the form', function (): void {
    $this->actingAs(Licensed::golden());

    $this->from('/tools/calculators/electrical-conductance')
        ->followingRedirects()
        ->post('/tools/calculators/electrical-conductance', ['voltage' => '10'])
        ->assertOk()
        ->assertSee('<p class="error" role="alert">'.__('calculators.errors.wrong_number_of_values').'</p>', false);
});

it('opens a calculator\'s form to everyone and invites a visitor to sign in', function (): void {
    $this->get('/tools/calculators/ohms-law')
        ->assertOk()
        ->assertSee('Ohm&#039;s law', false)
        ->assertSee('href="'.route('login').'"', false);

    $this->post('/tools/calculators/ohms-law', ['voltage' => '10', 'current' => '2'])->assertRedirect('/login');
});

it('answers 404 for the page of a calculator that does not exist', function (): void {
    $this->get('/tools/calculators/time-machine')->assertNotFound();
});

it('serves the list and a calculator ready for a phone-sized screen', function (string $uri): void {
    $this->get($uri)
        ->assertOk()
        ->assertSee('<meta name="viewport" content="width=device-width, initial-scale=1">', false)
        ->assertSee('@media (max-width: 40rem)', false)
        ->assertSee('min-height: 44px', false)
        ->assertSee('overflow-wrap: anywhere', false);
})->with([
    'list' => '/tools/calculators',
    'calculator' => '/tools/calculators/general-lighting',
]);
