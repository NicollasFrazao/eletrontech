<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\Licensing\Licensed;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    // Using a tool needs a licence that covers it (E5); Golden covers every tool.
    $this->actingAs(Licensed::golden());
});

it('shows the converter form with the units grouped by quantity', function (): void {
    $this->get('/tools/unit-converter')
        ->assertOk()
        ->assertSee('Unit converter')
        ->assertSee('<optgroup label="Apparent power">', false)
        ->assertSee('<option value="milliampere_hour"', false)
        ->assertSee('kilowatt (kW)');
});

it('serves the converter ready for a phone-sized screen', function (): void {
    $this->get('/tools/unit-converter')
        ->assertOk()
        ->assertSee('<meta name="viewport" content="width=device-width, initial-scale=1">', false)
        ->assertSee('@media (max-width: 40rem)', false)
        ->assertSee('min-height: 44px', false)
        ->assertSee('overflow-wrap: anywhere', false);
});

it('converts a value and shows the result on the page', function (): void {
    $this->post('/tools/unit-converter', ['value' => '1,5', 'from' => 'kilowatt', 'to' => 'watt'])
        ->assertOk()
        ->assertSee('1.5 kW = 1500 W');
});

it('marks a rounded result', function (): void {
    $this->post('/tools/unit-converter', ['value' => '1', 'from' => 'foot', 'to' => 'yard'])
        ->assertOk()
        ->assertSee('1 ft ≈ 0.333333333333 yd');
});

it('answers JSON to a client that asks for it', function (): void {
    $this->postJson('/tools/unit-converter', ['value' => '1', 'from' => 'ampere_hour', 'to' => 'coulomb'])
        ->assertOk()
        ->assertExactJson([
            'from' => ['value' => '1', 'unit' => ['id' => 'ampere_hour', 'name' => 'ampere-hour', 'symbol' => 'Ah']],
            'to' => ['value' => '3600', 'unit' => ['id' => 'coulomb', 'name' => 'coulomb', 'symbol' => 'C']],
            'isExact' => true,
        ]);
});

it('reports each kind of invalid input on its field', function (array $input, string $field): void {
    $this->postJson('/tools/unit-converter', $input)
        ->assertUnprocessable()
        ->assertJsonValidationErrors([$field]);
})->with([
    'missing value' => [['from' => 'volt', 'to' => 'volt'], 'value'],
    'not a number' => [['value' => 'abc', 'from' => 'volt', 'to' => 'kilovolt'], 'value'],
    'unknown source unit' => [['value' => '1', 'from' => 'parsec', 'to' => 'volt'], 'from'],
    'unknown target unit' => [['value' => '1', 'from' => 'volt', 'to' => 'parsec'], 'to'],
    'different quantities' => [['value' => '1', 'from' => 'volt', 'to' => 'ampere'], 'to'],
]);

it('sends the form back with the error and the typed value', function (): void {
    $this->from('/tools/unit-converter')
        ->post('/tools/unit-converter', ['value' => '1', 'from' => 'volt', 'to' => 'metre'])
        ->assertRedirect('/tools/unit-converter')
        ->assertSessionHasErrors(['to' => 'Both units must measure the same quantity.'])
        ->assertSessionHasInput('value', '1');
});
