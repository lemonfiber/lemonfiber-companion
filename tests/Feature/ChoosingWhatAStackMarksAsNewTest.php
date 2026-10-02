<?php

declare(strict_types=1);

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\News\Api\KindOfNews;
use Tests\Support\APhoneOnItsStackSettings;
use Tests\Support\WhatTheDeviceWouldDraw;

// Marked as new, on a stack's settings page: one switch for each of updates,
// requests and problems, all on until the operator switches one off, and each
// stack's choice its own.

/** A stack whose settings page is open. Named for this file. */
function aStackWhoseKindsAreSwitched(string $seed): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat($seed, Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.47'),
        Fingerprint::of(str_repeat($seed, Fingerprint::CHARACTERS)),
    );
}

/**
 * Each kind's switch as the page draws it, on or off.
 *
 * @return array<string, bool>
 */
function theSwitchesOn(APhoneOnItsStackSettings $phone, Stack $stack): array
{
    $switches = [];

    foreach ($phone->pageOf($stack)->kindsOfNews() as $kind) {
        $switches[$kind->kind] = $kind->isMarked;
    }

    return $switches;
}

it('offers a switch for each kind under Marked as new, every one on until it is switched off', function (): void {
    $home = aStackWhoseKindsAreSwitched('a');
    $phone = new APhoneOnItsStackSettings($home);
    $drawn = WhatTheDeviceWouldDraw::by($phone->pageOf($home));

    expect($drawn->said())->toContain(__('news.kinds_heading'), __('news.kinds_explained'))
        ->and($drawn->offers())->toContain(__('news.kind.update'), __('news.kind.request'), __('news.kind.problem'))
        ->and(theSwitchesOn($phone, $home))->toBe(['update' => true, 'request' => true, 'problem' => true]);
});

it('switches a kind off and on again, and keeps the choice on that stack alone', function (): void {
    $home = aStackWhoseKindsAreSwitched('a');
    $other = aStackWhoseKindsAreSwitched('b');
    $phone = new APhoneOnItsStackSettings($home, $other);

    $phone->pageOf($home)->markAsNew('request', isMarked: false);
    $off = theSwitchesOn($phone, $home);
    $phone->pageOf($home)->markAsNew('request', isMarked: true);

    expect($off)->toBe(['update' => true, 'request' => false, 'problem' => true])
        ->and(theSwitchesOn($phone, $other))->toBe(['update' => true, 'request' => true, 'problem' => true])
        ->and(theSwitchesOn($phone, $home))->toBe(['update' => true, 'request' => true, 'problem' => true])
        ->and($phone->marking->isMarked($home->id(), KindOfNews::Request))->toBeTrue();
});

it('sets a kind as its switch stands, however often the switch reports the same', function (): void {
    $home = aStackWhoseKindsAreSwitched('a');
    $phone = new APhoneOnItsStackSettings($home);

    foreach ([false, false, true, true, false] as $stands) {
        $phone->pageOf($home)->markAsNew('update', isMarked: $stands);
    }

    expect(theSwitchesOn($phone, $home))->toBe(['update' => false, 'request' => true, 'problem' => true]);
});

it('changes nothing for a kind the page never drew', function (): void {
    $home = aStackWhoseKindsAreSwitched('a');
    $phone = new APhoneOnItsStackSettings($home);

    $phone->pageOf($home)->markAsNew('alert', isMarked: false);

    expect(theSwitchesOn($phone, $home))->toBe(['update' => true, 'request' => true, 'problem' => true]);
});
