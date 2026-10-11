<?php

declare(strict_types=1);

namespace Modules\Sdk\Tests\Api;

use function expect;
use function it;

use Modules\Sdk\Api\SearchIsUnreadable;

it('names the field a search or an ask answer left out', function (): void {
    expect(SearchIsUnreadable::missing('titles.0.id')->getMessage())
        ->toBe('The search or ask answer has no readable `titles.0.id`. This answer did not come from a lemonfiber of a version this app can read.');
});

it('names every word it reads, each as a word, when refusing one it does not', function (): void {
    expect(SearchIsUnreadable::word('stands', 'gone', 'here', 'askable')->getMessage())
        ->toBe('The search or ask answer says `stands` is `gone`, and this app reads `here`, `askable`. Drawing it as the nearest one would be a guess about the title.');
});
