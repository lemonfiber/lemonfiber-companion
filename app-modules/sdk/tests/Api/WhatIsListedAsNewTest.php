<?php

declare(strict_types=1);

namespace Modules\Sdk\Tests\Api;

use function expect;
use function it;

use Lemonfiber\Sdk\Envelope\Envelope;
use Modules\Kernel\Api\TheNewsOfAStack;
use Modules\Sdk\Api\NewsIsUnreadable;
use Modules\Sdk\Api\WhatIsListedAsNew;

/**
 * A `news-items` envelope listing these requests and nothing else.
 *
 * `news-items` is stood in for and not judged: each case spoils one entry the contract fixes, to show the reader refuses it by its position, and the payloads a stack does send are judged in `ReadingNewsContractTest`.
 *
 * @param list<mixed> $requests
 *
 * @return Envelope<mixed>
 */
function newsListing(array $requests): Envelope
{
    return new Envelope(1, 'news-items', ['updates' => [], 'requests' => $requests, 'problems' => [], 'unread' => []]);
}

it('refuses an entry it cannot read by its position, counted from the first as nought', function (): void {
    $request = ['number' => 12, 'title' => 'Dune', 'by' => 'Anna'];
    $news = newsListing([$request, [...$request, 'number' => 'thirteen']]);

    expect(static fn(): TheNewsOfAStack => WhatIsListedAsNew::in($news))
        ->toThrow(NewsIsUnreadable::class, 'Entry 1 of `requests` in a news envelope has no readable `number`.');
});
