<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function count;
use function expect;
use function it;
use function iterator_to_array;

use Modules\Kernel\Api\QualitySaysNothing;
use Modules\Kernel\Api\WhatAChoiceMayBeMadeFrom;
use Modules\Kernel\Api\WordsToChooseFrom;

it('keeps each list as the stack wrote it, in its order', function (): void {
    $offered = WhatAChoiceMayBeMadeFrom::of(
        WordsToChooseFrom::under('presets', '720p', '1080p'),
        WordsToChooseFrom::under('formats', 'mp3', 'flac'),
        WordsToChooseFrom::under('media_types', 'tv', 'movies', 'music'),
    );

    expect([iterator_to_array($offered->presets(), preserve_keys: false), iterator_to_array($offered->formats(), preserve_keys: false), iterator_to_array($offered->kinds(), preserve_keys: false)])
        ->toBe([['720p', '1080p'], ['mp3', 'flac'], ['tv', 'movies', 'music']])
        ->and([count($offered->presets()), count($offered->formats()), count($offered->kinds())])->toBe([2, 2, 3]);
});

it('refuses a blank word, naming the list it was under', function (): void {
    expect(static fn(): WordsToChooseFrom => WordsToChooseFrom::under('presets', '720p', ' '))
        ->toThrow(QualitySaysNothing::class, 'its `presets` blank')
        ->and(static fn(): WordsToChooseFrom => WordsToChooseFrom::under('formats', ''))
        ->toThrow(QualitySaysNothing::class, 'its `formats` blank');
});

it('reindexes the words it is given, so a named spread keeps their order', function (): void {
    expect(iterator_to_array(WordsToChooseFrom::under('media_types', ...['a' => 'tv', 'b' => 'movies']), preserve_keys: true))->toBe(['tv', 'movies']);
});
