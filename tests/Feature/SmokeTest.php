<?php

declare(strict_types=1);

it('serves the home page', function (): void {
    $this->get('/')
        ->assertOk()
        ->assertSee('EletronTech')
        ->assertSee('v1 tag')
        ->assertSee('/tools/unit-converter', false);
});

it('serves the home page ready for a phone-sized screen', function (): void {
    $this->get('/')
        ->assertOk()
        ->assertSee('<meta name="viewport" content="width=device-width, initial-scale=1">', false)
        ->assertSee('@media (max-width: 40rem)', false)
        ->assertSee('min-height: 44px', false);
});

it('answers the health check', function (): void {
    $this->get('/up')->assertOk();
});
