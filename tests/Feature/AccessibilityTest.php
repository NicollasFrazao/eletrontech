<?php

declare(strict_types=1);

use Dom\Element;
use Dom\HTMLDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Tests\Support\Account\SignedUp;
use Tests\Support\Licensing\Licensed;

/*
 * E9 (#64): every field has a label, every error is tied to its field, every action is reachable with the
 * keyboard and shows a focus ring, and the colours meet WCAG AA, across the user area, the tools and the shop.
 */
uses(RefreshDatabase::class);

beforeEach(function (): void {
    Storage::fake('local');
});

/**
 * A validation error on every field the pages have, so each page renders all of its error messages at once.
 */
function everyFieldRefused(): ViewErrorBag
{
    $fields = [
        'name', 'cpf', 'phone_number', 'phone_type', 'email', 'password', 'password_confirmation',
        'current_password', 'deactivation_password', 'image', 'title', 'starts_at', 'description', 'export',
        'days', 'purchase_password', 'package_id', 'tools', 'file', 'value', 'from', 'to', 'length', 'width',
        'units.length', 'units.width', 'inputs', 'subject', 'message',
    ];

    return (new ViewErrorBag)->put('default', new MessageBag(array_fill_keys($fields, 'Refused.')));
}

/**
 * The page parsed as HTML5.
 */
function parsedPage(string $html): HTMLDocument
{
    return HTMLDocument::createFromString($html, LIBXML_NOERROR);
}

/**
 * The fields a person fills in: every input, select and textarea but the hidden ones and the buttons.
 *
 * @return list<Element>
 */
function fillableFields(HTMLDocument $page): array
{
    return array_values(array_filter(
        iterator_to_array($page->querySelectorAll('input, select, textarea')),
        fn (Element $field): bool => ! in_array($field->getAttribute('type'), ['hidden', 'submit', 'button'], true),
    ));
}

/**
 * Whether a field has an accessible name: a label pointing at it, a label around it or an aria-label.
 */
function hasAccessibleName(HTMLDocument $page, Element $field): bool
{
    $id = $field->getAttribute('id');

    return ($id !== null && $page->querySelector('label[for="'.$id.'"]') instanceof Element)
        || $field->closest('label') instanceof Element
        || trim((string) $field->getAttribute('aria-label')) !== '';
}

/**
 * Whether an error is announced: tied to a field by aria-describedby, or an alert of the whole form.
 */
function isAnnounced(HTMLDocument $page, Element $error): bool
{
    $id = $error->getAttribute('id');

    return $error->getAttribute('role') === 'alert'
        || ($id !== null && $page->querySelector('[aria-describedby~="'.$id.'"]') instanceof Element);
}

/**
 * The WCAG contrast ratio of two colours written as #rrggbb.
 */
function contrastRatio(string $foreground, string $background): float
{
    $luminance = function (string $colour): float {
        $channels = array_map(function (string $hex): float {
            $value = hexdec($hex) / 255;

            return $value <= 0.03928 ? $value / 12.92 : (($value + 0.055) / 1.055) ** 2.4;
        }, str_split(ltrim($colour, '#'), 2));

        return 0.2126 * $channels[0] + 0.7152 * $channels[1] + 0.0722 * $channels[2];
    };
    $lighter = max($luminance($foreground), $luminance($background));
    $darker = min($luminance($foreground), $luminance($background));

    return ($lighter + 0.05) / ($darker + 0.05);
}

it('labels every field, ties every error to its field and keeps every action on the keyboard', function (
    Closure $uri,
    bool $isSignedIn,
): void {
    if ($isSignedIn) {
        $this->actingAs(SignedUp::verified());
    }

    $html = $this->withSession(['errors' => everyFieldRefused()])->get($uri())->assertOk()->getContent();
    $page = parsedPage($html);

    foreach (fillableFields($page) as $field) {
        expect(hasAccessibleName($page, $field))->toBeTrue('No label for '.$field->getAttribute('name'));
    }

    foreach ($page->querySelectorAll('.error') as $error) {
        expect(isAnnounced($page, $error))->toBeTrue('Error not tied to a field: '.$error->textContent);
    }

    foreach ($page->querySelectorAll('[aria-describedby]') as $described) {
        foreach (explode(' ', (string) $described->getAttribute('aria-describedby')) as $id) {
            expect($page->getElementById($id))->not->toBeNull('aria-describedby points at nothing: '.$id);
        }
    }

    $ids = array_map(
        fn (Element $element): string => (string) $element->getAttribute('id'),
        iterator_to_array($page->querySelectorAll('[id]')),
    );
    expect(array_unique($ids))->toHaveCount(count($ids));

    foreach ($page->querySelectorAll('a') as $link) {
        expect($link->hasAttribute('href'))->toBeTrue('A link without href cannot be reached with the keyboard');
    }

    expect($page->querySelector('[onclick], [tabindex]:not([tabindex="0"]):not([tabindex="-1"])'))->toBeNull();
})->with([
    'sign-up' => [fn (): string => '/register', false],
    'sign-in' => [fn (): string => '/login', false],
    'forgot password' => [fn (): string => '/forgot-password', false],
    'reset password' => [fn (): string => '/reset-password/token?email=ana@example.com', false],
    'home' => [fn (): string => '/', false],
    'unit converter' => [fn (): string => '/tools/unit-converter', false],
    'calculators' => [fn (): string => '/tools/calculators', false],
    'one calculator' => [fn (): string => '/tools/calculators/lighting-power', false],
    'profile' => [fn (): string => '/profile', true],
    'shop' => [fn (): string => '/shop', true],
    'package' => [fn (): string => '/shop/packages/'.Licensed::packageId('Light'), true],
    'custom package' => [fn (): string => '/shop/custom', true],
    'licence' => [fn (): string => '/licence', true],
    'agenda' => [fn (): string => '/agenda', true],
    'file manager' => [fn (): string => '/files', true],
    'feedback' => [fn (): string => '/feedback', true],
]);

it('ties a refused sign-up field to its message and marks it invalid', function (): void {
    $this->from('/register')
        ->followingRedirects()
        ->post('/register', ['name' => ''])
        ->assertOk()
        ->assertSee('aria-invalid="true" aria-describedby="name-error"', false)
        ->assertSee('<p class="error" id="name-error">', false);
});

it('ties a refused value of the unit converter to its field', function (): void {
    $this->actingAs(Licensed::golden())
        ->from('/tools/unit-converter')
        ->followingRedirects()
        ->post('/tools/unit-converter', ['value' => '1', 'from' => 'volt', 'to' => 'metre'])
        ->assertOk()
        ->assertSee('aria-invalid="true" aria-describedby="to-error"', false)
        ->assertSee('<p class="error" id="to-error">Both units must measure the same quantity.</p>', false);
});

it('meets WCAG AA contrast and shows a focus ring on every page shell', function (string $uri, array $text, array $focus): void {
    $page = $this->get($uri)->assertOk()->getContent();

    foreach ($text as [$css, $foreground, $background]) {
        expect($page)->toContain($css)
            ->and(contrastRatio($foreground, $background))->toBeGreaterThanOrEqual(4.5);
    }

    foreach ($focus as [$css, $ring, $background]) {
        expect($page)->toContain($css)
            ->and(contrastRatio($ring, $background))->toBeGreaterThanOrEqual(3.0);
    }
})->with([
    'account layout' => [
        '/login',
        [
            ['color: #0f172a', '#0f172a', '#f8fafc'],
            ['a { color: #0369a1; }', '#0369a1', '#f8fafc'],
            ['.error { color: #b91c1c;', '#b91c1c', '#f8fafc'],
            ['color: #14532d', '#14532d', '#dcfce7'],
            ['body.night { background: #0f172a; color: #e2e8f0; }', '#e2e8f0', '#0f172a'],
            ['body.night a { color: #38bdf8; }', '#38bdf8', '#0f172a'],
            ['body.night .error { color: #fca5a5; }', '#fca5a5', '#0f172a'],
        ],
        [
            [':focus-visible { outline: 3px solid #0369a1;', '#0369a1', '#f8fafc'],
            ['body.night :focus-visible { outline-color: #38bdf8; }', '#38bdf8', '#0f172a'],
        ],
    ],
    'home' => [
        '/',
        [['a { color: #38bdf8; }', '#38bdf8', '#0f172a'], ['color: #e2e8f0', '#e2e8f0', '#0f172a']],
        [[':focus-visible { outline: 3px solid #38bdf8;', '#38bdf8', '#0f172a']],
    ],
    'unit converter' => [
        '/tools/unit-converter',
        [['.error { color: #fca5a5; }', '#fca5a5', '#0f172a'], ['color: #e2e8f0', '#e2e8f0', '#0f172a']],
        [[':focus-visible { outline: 3px solid #38bdf8;', '#38bdf8', '#0f172a']],
    ],
]);
