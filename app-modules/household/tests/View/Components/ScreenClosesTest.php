<?php

declare(strict_types=1);

namespace Modules\Household\Tests\View\Components;

use function __;
use function data_get;
use function expect;

use Illuminate\Support\Facades\Blade;

use function is_array;
use function is_string;
use function it;

use Modules\Household\Internal\WhereTheHouseIs;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\StackId;
use Modules\Wayfinding\Api\TheHouseholdsTabs;
use Native\Mobile\Edge\CallbackRegistry;
use Native\Mobile\Edge\NativeElementCollector;
use Native\Mobile\Edge\NativeTagPrecompiler;

use function str_repeat;

use Tests\TestCase;

use function uses;

// The application is booted here: a render needs the view factory, the
// component namespace and the precompiler.
uses(TestCase::class);

/** The house every bar here is drawn for. */
function aHouseTheBarIsFor(): StackId
{
    return StackId::of(Nonce::of(str_repeat('a', Nonce::SHORTEST)));
}

/**
 * The bar's items as drawn, each with its id, whether it is lit, its label and where it leads.
 *
 * @return list<array{id: mixed, lit: mixed, label: mixed, url: mixed}>
 */
function theMembersBarOn(?TheHouseholdsTabs $here): array
{
    $was = NativeTagPrecompiler::setActive(active: true);

    try {
        NativeElementCollector::reset();
        Blade::render(
            '<x-household::screen-closes :goes="$goes" :here="$here" />',
            ['goes' => WhereTheHouseIs::of(aHouseTheBarIsFor()), 'here' => $here],
        );
        $tree = NativeElementCollector::collect();
    } finally {
        NativeTagPrecompiler::setActive(active: $was);
    }

    $id = 1;
    $emitted = [];
    $hashes = [];
    $items = data_get($tree->toArray(new CallbackRegistry(), $id, '', 0, $emitted, $hashes), 'children');
    $drawn = [];

    foreach (is_array($items) ? $items : [] as $item) {
        $drawn[] = [
            'id' => data_get($item, 'props.id'),
            'lit' => data_get($item, 'props.active'),
            'label' => data_get($item, 'props.label'),
            'url' => data_get($item, 'props.url'),
        ];
    }

    return $drawn;
}

it('draws the four tabs, Home, Search, Requests and Profile, each leading to its screen for this house', function (): void {
    $bar = theMembersBarOn(TheHouseholdsTabs::Home);
    $expected = [];

    foreach (TheHouseholdsTabs::cases() as $tab) {
        $expected[] = [$tab->value, __($tab->said()), $tab->screen()->forTheStack(aHouseTheBarIsFor())];
    }

    $drawn = [];

    foreach ($bar as $item) {
        $drawn[] = [$item['id'], $item['label'], $item['url']];
    }

    expect($drawn)->toBe($expected);
});

it('lights the item of the tab the screen is under, and only that one', function (TheHouseholdsTabs $here): void {
    $lit = [];

    foreach (theMembersBarOn($here) as $item) {
        if ($item['lit'] === true && is_string($item['id'])) {
            $lit[] = $item['id'];
        }
    }

    expect($lit)->toBe([$here->value]);
})->with(TheHouseholdsTabs::cases());

it('lights nothing for a screen no tab draws', function (): void {
    $lit = [];

    foreach (theMembersBarOn(null) as $item) {
        if ($item['lit'] === true) {
            $lit[] = $item['id'];
        }
    }

    expect($lit)->toBe([]);
});
