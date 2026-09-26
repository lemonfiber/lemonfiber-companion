<?php

declare(strict_types=1);

namespace Modules\Sdk\Tests\Api;

use function array_diff_key;
use function expect;
use function it;

use Lemonfiber\Sdk\Envelope\Envelope;
use Modules\Kernel\Api\ABundle;
use Modules\Sdk\Api\BundleIsUnreadable;
use Modules\Sdk\Api\TheBundle;
use Tests\Support\WhatABundleSays;
use Tests\Support\WhatTheContractAccepts;

/** A bundle's payload, in the envelope it arrives in, read. */
function aBundleRead(mixed $data): ABundle
{
    return TheBundle::in(new Envelope(1, 'bundle', $data));
}

it('stands in for a stack with a payload the contract would accept', function (): void {
    expect(WhatTheContractAccepts::complaintsAbout('BundleEnvelope', WhatABundleSays::envelope()))->toBe([]);
});

it('reads every part of a described bundle, every file whole', function (): void {
    expect(WhatABundleSays::of(aBundleRead(WhatABundleSays::payload())))
        ->toBe(WhatABundleSays::of(WhatABundleSays::described()));
});

it('reads a bundle with a path as written there, whatever else it says', function (): void {
    expect(aBundleRead(WhatABundleSays::payload(['path' => WhatABundleSays::WOULD_GO, 'would_go' => '/elsewhere']))->where()->isWritten())->toBeTrue()
        ->and(WhatABundleSays::of(aBundleRead(WhatABundleSays::payload(['path' => WhatABundleSays::WOULD_GO, 'would_go' => null]))))
        ->toBe(WhatABundleSays::of(WhatABundleSays::written()));
});

it('reads a bundle naming nowhere as one the stack did not place', function (): void {
    expect(WhatABundleSays::of(aBundleRead(WhatABundleSays::payload(['would_go' => null, 'path' => null]))))
        ->toStartWith('48213|unsaid|')
        ->and(WhatABundleSays::of(aBundleRead(array_diff_key(WhatABundleSays::payload(), ['would_go' => true]))))
        ->toStartWith('48213|unsaid|');
});

it('reads filenames shown, a bundle revealing nothing, and one missing nothing', function (): void {
    $bundle = aBundleRead(WhatABundleSays::payloadWhoseContents([
        'terms' => ['window' => 'the last 50 lines of each service', 'filenames' => true, 'revealed' => []],
        'missing' => [],
    ]));

    expect($bundle->terms()->filenames()->value)->toBe('shown')
        ->and($bundle->terms()->window())->toBe('the last 50 lines of each service')
        ->and($bundle->terms()->revealed()->count())->toBe(0)
        ->and($bundle->missing()->count())->toBe(0);
});

it('reads a file holding nothing as a file holding nothing, and a bundle of no bytes', function (): void {
    $bundle = aBundleRead([...WhatABundleSays::payloadWhoseContents(['pieces' => [['name' => 'empty.txt', 'body' => '']]]), 'bytes' => 0]);

    $pieces = [...$bundle->pieces()];

    expect($bundle->bytes())->toBe(0)
        ->and($pieces)->toHaveCount(1)
        ->and($pieces[0]->name())->toBe('empty.txt')
        ->and($pieces[0]->body())->toBe('');
});

it('refuses a bundle it cannot show whole, naming what was wrong', function (mixed $data, string $said): void {
    expect(static fn(): ABundle => aBundleRead($data))->toThrow(BundleIsUnreadable::class, $said);
})->with([
    'no payload' => ['a word', 'no readable `data`'],
    'no size' => [array_diff_key(WhatABundleSays::payload(), ['bytes' => true]), 'no readable `bytes`'],
    'a size that is not a number' => [WhatABundleSays::payload(['bytes' => '48213']), 'no readable `bytes`'],
    'a size below nothing' => [WhatABundleSays::payload(['bytes' => -1]), 'no readable `bytes`'],
    'no contents' => [WhatABundleSays::payload(['contents' => 'none']), 'no readable `contents`'],
    'a place that is not text' => [WhatABundleSays::payload(['would_go' => 7]), 'no readable `would_go`'],
    'a place that is blank' => [WhatABundleSays::payload(['would_go' => ' ']), 'no readable `would_go`'],
    'a path that is blank' => [WhatABundleSays::payload(['path' => '']), 'no readable `path`'],
    'no terms' => [WhatABundleSays::payloadWhoseContents(['terms' => null]), 'no readable `terms`'],
    'no word on filenames' => [WhatABundleSays::payloadWhoseContents(['terms' => ['window' => 'w', 'revealed' => []]]), 'no readable `filenames`'],
    'filenames that are not a yes or no' => [WhatABundleSays::payloadWhoseContents(['terms' => ['window' => 'w', 'filenames' => 'no', 'revealed' => []]]), 'no readable `filenames`'],
    'no window' => [WhatABundleSays::payloadWhoseContents(['terms' => ['filenames' => false, 'revealed' => []]]), 'no readable `window`'],
    'a blank window' => [WhatABundleSays::payloadWhoseContents(['terms' => ['window' => ' ', 'filenames' => false, 'revealed' => []]]), 'no readable `window`'],
    'a window that is not text' => [WhatABundleSays::payloadWhoseContents(['terms' => ['window' => 200, 'filenames' => false, 'revealed' => []]]), 'no readable `window`'],
    'no list of what it reveals' => [WhatABundleSays::payloadWhoseContents(['terms' => ['window' => 'w', 'filenames' => false]]), 'no readable `revealed`'],
    'a revealed setting with no name' => [WhatABundleSays::payloadWhoseContents(['terms' => ['window' => 'w', 'filenames' => false, 'revealed' => ['SONARR_URL', ' ']]]), 'Entry 1 of the bundle\'s `revealed`'],
    'a revealed setting that is not text' => [WhatABundleSays::payloadWhoseContents(['terms' => ['window' => 'w', 'filenames' => false, 'revealed' => [3]]]), 'Entry 0 of the bundle\'s `revealed`'],
    'no pieces' => [WhatABundleSays::payloadWhoseContents(['pieces' => null]), 'no readable `pieces`'],
    'pieces that are not a list' => [WhatABundleSays::payloadWhoseContents(['pieces' => ['one' => ['name' => 'a', 'body' => 'b']]]), 'no readable `pieces`'],
    'a piece that is not a table' => [WhatABundleSays::payloadWhoseContents(['pieces' => ['diagnosis.txt']]), 'Entry 0 of the bundle\'s `pieces`'],
    'a piece with no body' => [WhatABundleSays::payloadWhoseContents(['pieces' => [['name' => 'a', 'body' => 'b'], ['name' => 'diagnosis.txt']]]), 'Entry 1 of the bundle\'s `pieces`'],
    'a piece whose body is not text' => [WhatABundleSays::payloadWhoseContents(['pieces' => [['name' => 'diagnosis.txt', 'body' => 4]]]), 'Entry 0 of the bundle\'s `pieces`'],
    'a piece with no name' => [WhatABundleSays::payloadWhoseContents(['pieces' => [['body' => 'b']]]), 'Entry 0 of the bundle\'s `pieces`'],
    'a piece with a blank name' => [WhatABundleSays::payloadWhoseContents(['pieces' => [['name' => ' ', 'body' => 'b']]]), 'Entry 0 of the bundle\'s `pieces`'],
    'a piece whose name is not text' => [WhatABundleSays::payloadWhoseContents(['pieces' => [['name' => 1, 'body' => 'b']]]), 'Entry 0 of the bundle\'s `pieces`'],
    'no list of what is missing' => [WhatABundleSays::payloadWhoseContents(['missing' => 'nothing']), 'no readable `missing`'],
    'something missing with no name' => [WhatABundleSays::payloadWhoseContents(['missing' => ['']]), 'Entry 0 of the bundle\'s `missing`'],
    'no word on when it was taken' => [WhatABundleSays::payloadWhoseContents(['taken' => 'today']), 'no readable `taken`'],
    'no moment' => [WhatABundleSays::payloadWhoseContents(['taken' => ['lemonfiber' => '1.4.0', 'stack' => '2026.09']]), 'no readable `at`'],
    'no lemonfiber version' => [WhatABundleSays::payloadWhoseContents(['taken' => ['at' => 'now', 'stack' => '2026.09']]), 'no readable `lemonfiber`'],
    'a blank stack version' => [WhatABundleSays::payloadWhoseContents(['taken' => ['at' => 'now', 'lemonfiber' => '1.4.0', 'stack' => ' ']]), 'no readable `stack`'],
]);
