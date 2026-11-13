<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function array_map;
use function expect;
use function it;

use Modules\Kernel\Api\KindOfRequest;

it('names each kind for the catalogue without a space, and only something asked for as unsaid', function (): void {
    expect(array_map(static fn(KindOfRequest $kind): array => [$kind->word(), $kind->wasSaid(), $kind->saidToTheMember()], KindOfRequest::cases()))->toBe([
        ['film', true, 'household.request_kind.film'],
        ['series', true, 'household.request_kind.series'],
        ['album', true, 'household.request_kind.album'],
        ['artist', true, 'household.request_kind.artist'],
        ['book', true, 'household.request_kind.book'],
        ['author', true, 'household.request_kind.author'],
        ['book_series', true, 'household.request_kind.book_series'],
        ['something', false, 'household.request_kind.something'],
    ]);
});

it('is read from the core\'s media words, book series with its space', function (): void {
    expect(KindOfRequest::tryFrom('book series'))->toBe(KindOfRequest::BookSeries)
        ->and(KindOfRequest::tryFrom('book_series'))->toBeNull();
});
