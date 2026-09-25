<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;
use function iterator_to_array;

use Modules\Kernel\Api\AWord;
use Modules\Kernel\Api\AWordInUse;
use Modules\Kernel\Api\LookingFor;
use Modules\Kernel\Api\WhatElseItIsCalled;
use Modules\Kernel\Api\WordsSayNothing;

use function sprintf;

it('keeps the word, both glosses and every other name', function (): void {
    $word = AWord::explained('seeding', 'Sharing a finished download', 'Uploading pieces to other peers', 'sharing', 'uploading');

    expect([$word->word(), $word->short(), $word->deep(), [...$word->alsoCalled()]])
        ->toBe(['seeding', 'Sharing a finished download', 'Uploading pieces to other peers', ['sharing', 'uploading']]);
});

it('takes no longer gloss and no other name', function (): void {
    $word = AWord::explained('pin', 'The version a service is held at', '');

    expect([$word->deep(), [...$word->alsoCalled()]])->toBe(['', []]);
});

it('keeps the other names as a list however they are handed', function (): void {
    expect(iterator_to_array(AWord::explained('pin', 'Held at', '', ...['a' => 'lock', 'b' => 'hold'])->alsoCalled(), preserve_keys: true))->toBe(['lock', 'hold'])
        ->and(AWord::explained('pin', 'Held at', '', 'lock')->alsoCalled())->toHaveCount(1);
});

it('refuses a blank word, short gloss, longer gloss or other name', function (string $field, string $word, string $short, string $deep, string ...$alsoCalled): void {
    expect(fn(): AWord => AWord::explained($word, $short, $deep, ...$alsoCalled))->toThrow(WordsSayNothing::class, sprintf('`%s`', $field));
})->with([
    ['word', ' ', 'Held at', ''],
    ['short', 'pin', "\t", ''],
    ['deep', 'pin', 'Held at', ' '],
    ['also_called', 'pin', 'Held at', '', 'lock', ' '],
]);

it('answers a search by its name or any other name, whatever the case, and not by its glosses', function (): void {
    $word = AWord::explained('seeding', 'Sharing a finished download', 'Uploading pieces', 'Sharing');

    expect($word->answers(LookingFor::text('SEED')))->toBeTrue()
        ->and($word->answers(LookingFor::text('shar')))->toBeTrue()
        ->and($word->answers(LookingFor::text('download')))->toBeFalse()
        ->and($word->answers(LookingFor::text('pieces')))->toBeFalse();
});

it('is found by every form it is written in, and keeps everything else it says', function (): void {
    $word = AWord::explained('grab', 'Taking a release from an indexer', 'Sent to the download client', 'snatch')->writtenAs(WhatElseItIsCalled::formsOf('grabbed', 'grabbing'));

    expect($word->explains(AWordInUse::named('Grabbed')))->toBeTrue()
        ->and($word->explains(AWordInUse::named('grabbing')))->toBeTrue()
        ->and($word->explains(AWordInUse::named('snatch')))->toBeTrue()
        ->and($word->explains(AWordInUse::named('grabber')))->toBeFalse()
        ->and($word->answers(LookingFor::text('bbing')))->toBeTrue()
        ->and([$word->word(), $word->short(), $word->deep(), iterator_to_array($word->alsoCalled(), preserve_keys: false)])
        ->toBe(['grab', 'Taking a release from an indexer', 'Sent to the download client', ['snatch']]);
});

it('is found by no form until it is given some', function (): void {
    expect(AWord::explained('grab', 'Taking a release', '')->explains(AWordInUse::named('grabbed')))->toBeFalse();
});

it('refuses a blank form, naming the forms', function (): void {
    expect(fn(): AWord => AWord::explained('grab', 'Taking a release', '')->writtenAs(WhatElseItIsCalled::formsOf('grabbed', ' ')))
        ->toThrow(WordsSayNothing::class, 'forms');
});
