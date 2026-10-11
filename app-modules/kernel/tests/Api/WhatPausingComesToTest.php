<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function array_map;
use function expect;
use function it;
use function iterator_to_array;

use Modules\Kernel\Api\ADownloadClientAsked;
use Modules\Kernel\Api\PausingSaysNothing;
use Modules\Kernel\Api\WhatADownloadClientSaid;
use Modules\Kernel\Api\WhatPausingComesTo;
use Modules\Kernel\Api\WhatToDoWithTheDownloads;
use Modules\Kernel\Api\WhetherItWasRehearsed;

use function sprintf;

it('carries what was asked, whether it was rehearsed, its offer, its caution and every client in order', function (): void {
    $report = WhatPausingComesTo::said(
        WhatToDoWithTheDownloads::Resume,
        WhetherItWasRehearsed::CarriedOut,
        'an-offer',
        '  The cap stops them again  ',
        ADownloadClientAsked::reached('qbittorrent', WhatADownloadClientSaid::Stopped, WhatADownloadClientSaid::Fetching),
        ADownloadClientAsked::unreached('nzbget', WhatADownloadClientSaid::Unsaid, '  connection refused  '),
    );
    $clients = iterator_to_array($report, preserve_keys: false);

    expect($report->asked())->toBe(WhatToDoWithTheDownloads::Resume)
        ->and($report->wasRehearsed())->toBe(WhetherItWasRehearsed::CarriedOut)
        ->and($report->agreement())->toBe('an-offer')
        ->and($report->caution())->toBe('The cap stops them again')
        ->and($report->hasClients())->toBeTrue()
        ->and(array_map(static fn(ADownloadClientAsked $client): string => $client->client(), $clients))->toBe(['qbittorrent', 'nzbget'])
        ->and($clients[0]->wasReached())->toBeTrue()
        ->and($clients[0]->was())->toBe(WhatADownloadClientSaid::Stopped)
        ->and($clients[0]->now())->toBe(WhatADownloadClientSaid::Fetching)
        ->and($clients[0]->whyItWasNotReached())->toBe('')
        ->and($clients[1]->wasReached())->toBeFalse()
        ->and($clients[1]->now())->toBe(WhatADownloadClientSaid::Unsaid)
        ->and($clients[1]->whyItWasNotReached())->toBe('connection refused');
});

it('says a stack running no download client has none', function (): void {
    expect(WhatPausingComesTo::said(WhatToDoWithTheDownloads::Pause, WhetherItWasRehearsed::Rehearsed, 'an-offer', '')->hasClients())->toBeFalse();
});

it('refuses an offer with no name, and a client with no name, whichever way it was reached', function (callable $made, string $field): void {
    expect($made)->toThrow(PausingSaysNothing::class, sprintf('`%s` blank', $field));
})->with([
    'a blank offer' => [static fn(): WhatPausingComesTo => WhatPausingComesTo::said(WhatToDoWithTheDownloads::Pause, WhetherItWasRehearsed::Rehearsed, ' ', ''), 'offer'],
    'a reached client named nothing' => [static fn(): ADownloadClientAsked => ADownloadClientAsked::reached(' ', WhatADownloadClientSaid::Fetching, WhatADownloadClientSaid::Stopped), 'client'],
    'an unreached client named nothing' => [static fn(): ADownloadClientAsked => ADownloadClientAsked::unreached('', WhatADownloadClientSaid::Fetching, 'gone'), 'client'],
]);

it('names lemonfiber\'s action for each way', function (): void {
    expect(WhatToDoWithTheDownloads::Pause->asked())->toBe('downloads-pause')
        ->and(WhatToDoWithTheDownloads::Resume->asked())->toBe('downloads-resume');
});
