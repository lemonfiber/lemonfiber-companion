<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function array_keys;
use function count;
use function expect;
use function it;
use function iterator_to_array;

use Modules\Kernel\Api\AnAmountOfRoom;
use Modules\Kernel\Api\HowMuchWasRead;
use Modules\Kernel\Api\NamedOnTheManifest;
use Modules\Kernel\Api\OneThingItReaches;
use Modules\Kernel\Api\SomethingItCannotTake;
use Modules\Kernel\Api\SomethingLeftBehind;
use Modules\Kernel\Api\SomethingNotLemonfibers;
use Modules\Kernel\Api\SomethingStillComing;
use Modules\Kernel\Api\UninstallSaysNothing;
use Modules\Kernel\Api\WhatGoesAndWhatStays;
use Modules\Kernel\Api\WhatIsNotLemonfibers;
use Modules\Kernel\Api\WhatIsStillComing;
use Modules\Kernel\Api\WhatItCannotTake;
use Modules\Kernel\Api\WhatItReaches;
use Modules\Kernel\Api\WhatSortItIs;
use Modules\Kernel\Api\WhatTakingItOffComesTo;
use Modules\Kernel\Api\WhatToKnowFirst;
use Modules\Kernel\Api\WhatWasLeftBehind;
use Modules\Kernel\Api\WhichRemoval;

use function sprintf;

/** The services' reading, with the words and the figure given. */
function theServicesReading(string $removes = 'The containers', string $keeps = 'Everything else', int $bytes = 0, string $agreement = 'services-0'): WhatTakingItOffComesTo
{
    return WhatTakingItOffComesTo::read(
        WhichRemoval::Services,
        WhatGoesAndWhatStays::said(
            $removes,
            $keeps,
        ),
        WhatItReaches::of(),
        $bytes,
        WhatToKnowFirst::said(
            WhatIsNotLemonfibers::of(),
            WhatIsStillComing::of(),
            WhatItCannotTake::of(),
        ),
        HowMuchWasRead::everything(),
        $agreement,
    );
}

it('says nothing of a volume or a copy the stack said nothing about', function (): void {
    $read = theServicesReading();

    expect($read->volume())->toBe('')
        ->and($read->copyFirst())->toBe('')
        ->and($read->bytes())->toBe(0);
});

it('answers each question from the part it was read with', function (): void {
    $items = WhatItReaches::of();
    $foreign = WhatIsNotLemonfibers::of();
    $coming = WhatIsStillComing::of();
    $outside = WhatItCannotTake::of();
    $confidence = HowMuchWasRead::everything();
    $read = WhatTakingItOffComesTo::read(
        WhichRemoval::Configuration,
        WhatGoesAndWhatStays::said('The configuration', 'The library'),
        $items,
        2048,
        WhatToKnowFirst::said($foreign, $coming, $outside, volume: 'On a network share', copyFirst: 'A copy is taken first'),
        $confidence,
        'configuration-2048',
    );

    expect($read->tier())->toBe(WhichRemoval::Configuration)
        ->and($read->removes())->toBe('The configuration')
        ->and($read->keeps())->toBe('The library')
        ->and($read->items())->toBe($items)
        ->and($read->bytes())->toBe(2048)
        ->and($read->foreign())->toBe($foreign)
        ->and($read->coming())->toBe($coming)
        ->and($read->outside())->toBe($outside)
        ->and($read->confidence())->toBe($confidence)
        ->and($read->agreement())->toBe('configuration-2048')
        ->and($read->volume())->toBe('On a network share')
        ->and($read->copyFirst())->toBe('A copy is taken first');
});

it('refuses a word it owes left blank, and a figure below none', function (): void {
    expect(fn(): WhatTakingItOffComesTo => theServicesReading(removes: ' '))->toThrow(UninstallSaysNothing::class, 'its `removes` blank')
        ->and(fn(): WhatTakingItOffComesTo => theServicesReading(keeps: ''))->toThrow(UninstallSaysNothing::class, 'its `keeps` blank')
        ->and(fn(): WhatTakingItOffComesTo => theServicesReading(agreement: ' '))->toThrow(UninstallSaysNothing::class, 'its `agreement` blank')
        ->and(fn(): WhatTakingItOffComesTo => theServicesReading(bytes: -1))->toThrow(UninstallSaysNothing::class, 'its `bytes` at -1');
});

it('counts what each list holds', function (): void {
    $size = AnAmountOfRoom::unread();

    expect(count(WhatItReaches::of(OneThingItReaches::going('a', WhatSortItIs::Container, 'A', holdsACredential: false, size: $size))))->toBe(1)
        ->and(count(WhatIsNotLemonfibers::of(SomethingNotLemonfibers::at('a', 0, 0), SomethingNotLemonfibers::at('b', 0, 0))))->toBe(2)
        ->and(count(WhatIsStillComing::of(SomethingStillComing::named('a', 0))))->toBe(1)
        ->and(count(WhatItCannotTake::of()))->toBe(0)
        ->and(count(WhatWasLeftBehind::of(SomethingLeftBehind::named('a', 'b', 'c'))))->toBe(1)
        ->and(count(NamedOnTheManifest::under('gone', 'a', 'b')))->toBe(2)
        ->and(count(HowMuchWasRead::notEverything('a')))->toBe(1);
});

it('keeps each list in the stack\'s order', function (): void {
    expect(iterator_to_array(NamedOnTheManifest::under('gone', 'b', 'a'), preserve_keys: false))->toBe(['b', 'a'])
        ->and(iterator_to_array(HowMuchWasRead::everything('b', 'a'), preserve_keys: false))->toBe(['b', 'a']);
});

it('refuses a line that goes, or is kept, with a word left blank', function (): void {
    $size = AnAmountOfRoom::unread();

    expect(fn(): OneThingItReaches => OneThingItReaches::going(' ', WhatSortItIs::Path, 'A', holdsACredential: false, size: $size))->toThrow(UninstallSaysNothing::class, 'its `name` blank')
        ->and(fn(): OneThingItReaches => OneThingItReaches::going('a', WhatSortItIs::Path, ' ', holdsACredential: false, size: $size))->toThrow(UninstallSaysNothing::class, 'its `what` blank')
        ->and(fn(): OneThingItReaches => OneThingItReaches::kept('a', WhatSortItIs::Path, 'A', holdsACredential: false, size: $size, because: ' '))->toThrow(UninstallSaysNothing::class, 'its `kept` blank');
});

it('marks a kept line kept, and a line that goes as going with no reason', function (): void {
    $size = AnAmountOfRoom::unread();
    $kept = OneThingItReaches::kept('a', WhatSortItIs::Image, 'A', holdsACredential: true, size: $size, because: 'Shared');
    $going = OneThingItReaches::going('a', WhatSortItIs::Image, 'A', holdsACredential: false, size: $size);

    expect([$kept->isKept(), $kept->whyItIsKept(), $kept->holdsACredential()])->toBe([true, 'Shared', true])
        ->and([$going->isKept(), $going->whyItIsKept(), $going->holdsACredential()])->toBe([false, '', false]);
});

it('refuses something not ours with no place, or a count below none, and takes none of either', function (): void {
    expect(fn(): SomethingNotLemonfibers => SomethingNotLemonfibers::at(' ', 0, 0))->toThrow(UninstallSaysNothing::class, 'its `foreign.at` blank')
        ->and(fn(): SomethingNotLemonfibers => SomethingNotLemonfibers::at('a', -1, 0))->toThrow(UninstallSaysNothing::class, 'its `foreign.files` at -1')
        ->and(fn(): SomethingNotLemonfibers => SomethingNotLemonfibers::at('a', 0, -1))->toThrow(UninstallSaysNothing::class, 'its `foreign.bytes` at -1')
        ->and(SomethingNotLemonfibers::at('a', 0, 0)->files())->toBe(0);
});

it('takes a download from none to all of it along, and refuses one outside that or with no name', function (): void {
    expect(SomethingStillComing::named('a', 0)->progress())->toBe(0)
        ->and(SomethingStillComing::named('a', 100)->progress())->toBe(100)
        ->and(fn(): SomethingStillComing => SomethingStillComing::named('a', -1))->toThrow(UninstallSaysNothing::class, 'its `coming.progress` at -1')
        ->and(fn(): SomethingStillComing => SomethingStillComing::named('a', 101))->toThrow(UninstallSaysNothing::class, 'its `coming.progress` at 101')
        ->and(fn(): SomethingStillComing => SomethingStillComing::named(' ', 1))->toThrow(UninstallSaysNothing::class, 'its `coming.name` blank');
});

it('refuses something it cannot take, found or not, with a word left blank', function (): void {
    foreach (['outside.what' => [' ', 'b', 'c'], 'outside.why' => ['a', ' ', 'c'], 'outside.by_hand' => ['a', 'b', ' ']] as $field => $words) {
        expect(fn(): SomethingItCannotTake => SomethingItCannotTake::found(...$words))->toThrow(UninstallSaysNothing::class, sprintf('its `%s` blank', $field))
            ->and(fn(): SomethingItCannotTake => SomethingItCannotTake::notFound(...$words))->toThrow(UninstallSaysNothing::class, sprintf('its `%s` blank', $field));
    }
});

it('refuses something left behind with a word left blank, and a name on the manifest left blank', function (): void {
    foreach (['left.name' => [' ', 'b', 'c'], 'left.why' => ['a', ' ', 'c'], 'left.by_hand' => ['a', 'b', ' ']] as $field => $words) {
        expect(fn(): SomethingLeftBehind => SomethingLeftBehind::named(...$words))->toThrow(UninstallSaysNothing::class, sprintf('its `%s` blank', $field));
    }

    expect(fn(): NamedOnTheManifest => NamedOnTheManifest::under('credentials', 'a', ' '))->toThrow(UninstallSaysNothing::class, 'its `credentials` blank')
        ->and(fn(): HowMuchWasRead => HowMuchWasRead::everything('a', ''))->toThrow(UninstallSaysNothing::class, 'its `confidence.unread` blank');
});

it('keeps what it could not read in order, even handed it by name', function (): void {
    // A variadic collected from named arguments has string keys, and the list
    // is read by position, so the reindexing is load-bearing rather than tidy.
    $read = HowMuchWasRead::notEverything(first: 'The downloads folder', then: 'The music library');

    expect(array_keys(iterator_to_array($read)))->toBe([0, 1])
        ->and(iterator_to_array($read))->toBe(['The downloads folder', 'The music library']);
});

it('keeps the names a removal reports in order, even handed them by name', function (): void {
    $named = NamedOnTheManifest::under('gone', first: 'jellyfin', then: 'sonarr');

    expect(array_keys(iterator_to_array($named)))->toBe([0, 1])
        ->and(iterator_to_array($named))->toBe(['jellyfin', 'sonarr']);
});
