<?php

declare(strict_types=1);

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\AnAmountOfRoom;
use Modules\Kernel\Api\AnUninstall;
use Modules\Kernel\Api\AnUninstallAgreed;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\HowMuchWasRead;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\NamedOnTheManifest;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\OneThingItReaches;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\SomethingItCannotTake;
use Modules\Kernel\Api\SomethingLeftBehind;
use Modules\Kernel\Api\SomethingNotLemonfibers;
use Modules\Kernel\Api\SomethingStillComing;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\TakingLemonfiberOff;
use Modules\Kernel\Api\WhatBecameOfTheUninstall;
use Modules\Kernel\Api\WhatGoesAndWhatStays;
use Modules\Kernel\Api\WhatIsNotLemonfibers;
use Modules\Kernel\Api\WhatIsStillComing;
use Modules\Kernel\Api\WhatItCannotTake;
use Modules\Kernel\Api\WhatItReaches;
use Modules\Kernel\Api\WhatSortItIs;
use Modules\Kernel\Api\WhatTakingItOffComesTo;
use Modules\Kernel\Api\WhatToKnowFirst;
use Modules\Kernel\Api\WhatWasFoundOfTheUninstall;
use Modules\Kernel\Api\WhatWasLeftBehind;
use Modules\Kernel\Api\WhereTakingItOffGot;
use Modules\Kernel\Api\WhetherToWait;
use Modules\Kernel\Api\WhichRemoval;
use Modules\Sdk\Api\Dismantlers;
use Modules\Sdk\Api\PinnedClients;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Saloon\Http\PendingRequest;
use Tests\Support\Fakes\AStackThatTakesItOff;
use Tests\Support\Fakes\SequencedEntropy;
use Tests\Support\WhatTheContractAccepts;

// The TakingLemonfiberOff contract, run against the adapter and against the fake.
//
// `G2`'s shape. The reading is answered at once; the removal answers with a
// handle, and asking after the handle answers with what it came to.

afterEach(function (): void {
    MockClient::destroyGlobal();
});

/** The stack lemonfiber is taken off. */
function aStackToTakeLemonfiberOff(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('a', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('b', Fingerprint::CHARACTERS)),
    );
}

/** The session every case here asks on. */
function theSessionLemonfiberIsTakenOffOn(): Session
{
    return Session::of('a-session-not-a-secret');
}

/** What removing the configuration comes to, in the kernel's terms, on a machine with something of everything. */
function theConfigurationReading(): WhatTakingItOffComesTo
{
    return WhatTakingItOffComesTo::read(
        WhichRemoval::Configuration,
        WhatGoesAndWhatStays::said(
            'Each service\'s configuration and lemonfiber\'s own state',
            'Your library and your downloads',
        ),
        WhatItReaches::of(
            OneThingItReaches::going('/srv/lemonfiber/config/gluetun', WhatSortItIs::Path, 'The VPN\'s settings', holdsACredential: true, size: AnAmountOfRoom::of(2048, 'bytes')),
            OneThingItReaches::kept('ghcr.io/example/shared:1', WhatSortItIs::Image, 'An image another project uses', holdsACredential: false, size: AnAmountOfRoom::unread(), because: 'Another project stands on it'),
        ),
        2048,
        WhatToKnowFirst::said(
            WhatIsNotLemonfibers::of(SomethingNotLemonfibers::at('photos', 12, 4096)),
            WhatIsStillComing::of(SomethingStillComing::named('A film', 40)),
            WhatItCannotTake::of(
                SomethingItCannotTake::found('Docker', 'lemonfiber did not install it', 'Uninstall Docker Desktop'),
                SomethingItCannotTake::notFound('Tailscale', 'It is a separate client', 'Remove the Tailscale app'),
            ),
            volume: 'The data location is on a network share',
            copyFirst: 'A copy of the configuration is taken first',
        ),
        HowMuchWasRead::notEverything('The container engine did not answer'),
        'configuration-2-lines',
    );
}

/**
 * The payload a stack sends for that, with the removal state given.
 *
 * @param  array<string, mixed> $removal
 * @return array<string, mixed>
 */
function whatAStackSaysOfTakingItOff(array $removal): array
{
    return [
        'api_version' => 1,
        'kind' => 'uninstall',
        'data' => [
            'manifest' => [
                'tier' => 'configuration',
                'removes' => 'Each service\'s configuration and lemonfiber\'s own state',
                'keeps' => 'Your library and your downloads',
                'items' => [
                    ['name' => '/srv/lemonfiber/config/gluetun', 'sort' => 'path', 'what' => 'The VPN\'s settings', 'secret' => true, 'bytes' => 2048],
                    ['name' => 'ghcr.io/example/shared:1', 'sort' => 'image', 'what' => 'An image another project uses', 'secret' => false, 'kept' => 'Another project stands on it'],
                ],
                'bytes' => 2048,
                'foreign' => [['at' => 'photos', 'files' => 12, 'bytes' => 4096]],
                'coming' => [['name' => 'A film', 'progress' => 40]],
                'outside' => [
                    ['what' => 'Docker', 'why' => 'lemonfiber did not install it', 'by_hand' => 'Uninstall Docker Desktop', 'found' => true],
                    ['what' => 'Tailscale', 'why' => 'It is a separate client', 'by_hand' => 'Remove the Tailscale app', 'found' => false],
                ],
                'confidence' => ['complete' => false, 'unread' => ['The container engine did not answer']],
                'agreement' => 'configuration-2-lines',
                'volume' => 'The data location is on a network share',
                'backup' => 'A copy of the configuration is taken first',
            ],
            'removal' => $removal,
        ],
    ];
}

/** A line carried out of an `either()` arm. */
final readonly class WhatTakingItOffCameTo
{
    public function __construct(public string $said) {}
}

/**
 * Each line a reading reaches, folded to a line of its own.
 *
 * @return list<string>
 */
function everyLineTakingItOffReaches(WhatTakingItOffComesTo $manifest): array
{
    $lines = [];

    foreach ($manifest->items() as $item) {
        $lines[] = sprintf(
            '%s/%s/%s/%s/%s/%s',
            $item->name(),
            $item->sort()->value,
            $item->what(),
            $item->holdsACredential() ? 'secret' : 'plain',
            $item->size()->either(
                known: static fn(int $bytes): WhatTakingItOffCameTo => new WhatTakingItOffCameTo((string) $bytes),
                unread: static fn(): WhatTakingItOffCameTo => new WhatTakingItOffCameTo('unread'),
            )->said,
            $item->whyItIsKept(),
        );
    }

    return $lines;
}

/** Where a removal got, folded to one line. */
function whereTakingItOffGotTo(WhereTakingItOffGot $got): string
{
    return $got->either(
        surveyed: static fn(): WhatTakingItOffCameTo => new WhatTakingItOffCameTo('surveyed'),
        rehearsed: static fn(): WhatTakingItOffCameTo => new WhatTakingItOffCameTo('rehearsed'),
        complete: static fn(NamedOnTheManifest $gone, NamedOnTheManifest $credentials): WhatTakingItOffCameTo
            => new WhatTakingItOffCameTo(sprintf('complete %s / %s', implode(',', iterator_to_array($gone, preserve_keys: false)), implode(',', iterator_to_array($credentials, preserve_keys: false)))),
        partial: static function (NamedOnTheManifest $gone, NamedOnTheManifest $credentials, WhatWasLeftBehind $left): WhatTakingItOffCameTo {
            $things = [];

            foreach ($left as $thing) {
                $things[] = sprintf('%s/%s/%s', $thing->name(), $thing->why(), $thing->byHand());
            }

            return new WhatTakingItOffCameTo(sprintf(
                'partial %s / %s / %s',
                implode(',', iterator_to_array($gone, preserve_keys: false)),
                implode(',', iterator_to_array($credentials, preserve_keys: false)),
                implode(',', $things),
            ));
        },
    )->said;
}

/** Everything an uninstall says, folded to one line, so two answers can be compared. */
function everythingTheUninstallSays(AnUninstall $uninstall): string
{
    $manifest = $uninstall->manifest();
    $lines = everyLineTakingItOffReaches($manifest);

    foreach ($manifest->foreign() as $foreign) {
        $lines[] = sprintf('foreign %s/%d/%d', $foreign->where(), $foreign->files(), $foreign->bytes());
    }

    foreach ($manifest->coming() as $coming) {
        $lines[] = sprintf('coming %s/%d', $coming->name(), $coming->progress());
    }

    foreach ($manifest->outside() as $outside) {
        $lines[] = sprintf('outside %s/%s/%s/%s', $outside->what(), $outside->why(), $outside->byHand(), $outside->wasFound() ? 'found' : 'not found');
    }

    foreach ($manifest->confidence() as $unread) {
        $lines[] = sprintf('unread %s', $unread);
    }

    $lines[] = sprintf(
        '%s|%s|%s|%d|%s|%s|%s|%s',
        $manifest->tier()->value,
        $manifest->removes(),
        $manifest->keeps(),
        $manifest->bytes(),
        $manifest->confidence()->isComplete() ? 'complete' : 'incomplete',
        $manifest->agreement(),
        $manifest->volume(),
        $manifest->copyFirst(),
    );
    $lines[] = whereTakingItOffGotTo($uninstall->removal());

    return implode("\n", $lines);
}

/** Everything the work says, folded to one line. */
function everythingTheWorkSays(WhatBecameOfTheUninstall $became): string
{
    return $became->either(
        underway: static fn(Job $job): WhatTakingItOffCameTo => new WhatTakingItOffCameTo(sprintf('underway %s', $job->shown())),
        answered: static fn(AnUninstall $uninstall): WhatTakingItOffCameTo => new WhatTakingItOffCameTo(everythingTheUninstallSays($uninstall)),
        ended: static fn(): WhatTakingItOffCameTo => new WhatTakingItOffCameTo('ended'),
        refused: static fn(string $because): WhatTakingItOffCameTo => new WhatTakingItOffCameTo(sprintf('refused %s', $because)),
        met: static fn(Obstacle $why): WhatTakingItOffCameTo => new WhatTakingItOffCameTo($why->value),
    )->said;
}

/** Everything a reading says, folded to one line. */
function everythingTheUninstallReadingSays(WhatWasFoundOfTheUninstall $found): string
{
    return $found->either(
        found: static fn(AnUninstall $uninstall): WhatTakingItOffCameTo => new WhatTakingItOffCameTo(everythingTheUninstallSays($uninstall)),
        met: static fn(Obstacle $why): WhatTakingItOffCameTo => new WhatTakingItOffCameTo($why->value),
    )->said;
}

/** The yes to the configuration reading, the volume acknowledged, waiting for what is coming down. */
function aYesToTheConfigurationReading(): AnUninstallAgreed
{
    return AnUninstallAgreed::after(AnUninstall::of(theConfigurationReading(), WhereTakingItOffGot::surveyed()), WhetherToWait::ForTheDownloads, acknowledgedTheVolume: true);
}

/**
 * Both ways of taking it off, each reading the configuration and then answering the handle and the removal.
 *
 * @return array<string, Closure(): TakingLemonfiberOff>
 */
function everyWayOfTakingItOff(WhereTakingItOffGot $got, MockResponse ...$answered): array
{
    return [
        'the fake' => static fn(): TakingLemonfiberOff => AStackThatTakesItOff::reading(
            AnUninstall::of(theConfigurationReading(), WhereTakingItOffGot::surveyed()),
            WhatBecameOfTheUninstall::underway(Job::named('j-1')),
            WhatBecameOfTheUninstall::answered(AnUninstall::of(theConfigurationReading(), $got)),
        ),
        'the adapter' => static function () use ($answered): TakingLemonfiberOff {
            MockClient::destroyGlobal();
            MockClient::global($answered);

            return new Dismantlers(new PinnedClients(), SequencedEntropy::counting());
        },
    ];
}

it('reads a removal, every line of it, with how much could be read', function (): void {
    $read = MockResponse::make((string) json_encode(whatAStackSaysOfTakingItOff(['state' => 'surveyed'])));

    foreach (everyWayOfTakingItOff(WhereTakingItOffGot::surveyed(), $read) as $which => $make) {
        expect(everythingTheUninstallReadingSays($make()->surveyed(aStackToTakeLemonfiberOff(), theSessionLemonfiberIsTakenOffOn(), WhichRemoval::Configuration)))->toBe(implode("\n", [
            '/srv/lemonfiber/config/gluetun/path/The VPN\'s settings/secret/2048/',
            'ghcr.io/example/shared:1/image/An image another project uses/plain/unread/Another project stands on it',
            'foreign photos/12/4096',
            'coming A film/40',
            'outside Docker/lemonfiber did not install it/Uninstall Docker Desktop/found',
            'outside Tailscale/It is a separate client/Remove the Tailscale app/not found',
            'unread The container engine did not answer',
            'configuration|Each service\'s configuration and lemonfiber\'s own state|Your library and your downloads|2048|incomplete|configuration-2-lines|The data location is on a network share|A copy of the configuration is taken first',
            'surveyed',
        ]), $which);
    }
});

it('takes it off, and follows the work to what it left', function (): void {
    $handle = MockResponse::make((string) json_encode(['api_version' => 1, 'kind' => 'job', 'data' => ['job' => 'j-1', 'action' => 'uninstall']]), 202);
    $done = MockResponse::make((string) json_encode(whatAStackSaysOfTakingItOff([
        'state' => 'partial',
        'gone' => ['/srv/lemonfiber/config/gluetun'],
        'credentials' => ['The VPN key'],
        'left' => [['name' => '/srv/lemonfiber/config/sonarr', 'why' => 'Permission denied', 'by_hand' => 'sudo rm -r /srv/lemonfiber/config/sonarr']],
    ])));
    $got = WhereTakingItOffGot::partial(
        NamedOnTheManifest::under('gone', '/srv/lemonfiber/config/gluetun'),
        NamedOnTheManifest::under('credentials', 'The VPN key'),
        WhatWasLeftBehind::of(SomethingLeftBehind::named('/srv/lemonfiber/config/sonarr', 'Permission denied', 'sudo rm -r /srv/lemonfiber/config/sonarr')),
    );

    foreach (everyWayOfTakingItOff($got, $handle, $done) as $which => $make) {
        $removing = $make();
        $started = $removing->takeItOff(aStackToTakeLemonfiberOff(), theSessionLemonfiberIsTakenOffOn(), aYesToTheConfigurationReading());
        $finished = $removing->whatBecameOf(aStackToTakeLemonfiberOff(), theSessionLemonfiberIsTakenOffOn(), Job::named('j-1'));

        expect(everythingTheWorkSays($started))->toBe('underway j-1', $which)
            ->and(everythingTheWorkSays($finished))->toEndWith('partial /srv/lemonfiber/config/gluetun / The VPN key / /srv/lemonfiber/config/sonarr/Permission denied/sudo rm -r /srv/lemonfiber/config/sonarr');
    }
});

it('reads a removal that finished, and one that was only rehearsed', function (): void {
    $complete = MockResponse::make((string) json_encode(whatAStackSaysOfTakingItOff(['state' => 'complete', 'gone' => ['a', 'b'], 'credentials' => []])));
    $rehearsed = MockResponse::make((string) json_encode(whatAStackSaysOfTakingItOff(['state' => 'confirmed'])));
    $removing = everyWayOfTakingItOff(WhereTakingItOffGot::surveyed(), $complete, $rehearsed)['the adapter']();

    expect(everythingTheWorkSays($removing->whatBecameOf(aStackToTakeLemonfiberOff(), theSessionLemonfiberIsTakenOffOn(), Job::named('j-1'))))->toEndWith("\ncomplete a,b / ")
        ->and(everythingTheWorkSays($removing->whatBecameOf(aStackToTakeLemonfiberOff(), theSessionLemonfiberIsTakenOffOn(), Job::named('j-1'))))->toEndWith("\nrehearsed");
});

it('asks the reading by its tier, and the removal with the tier, the yes, the reading and the wait, under a key', function (): void {
    $sent = [];
    MockClient::destroyGlobal();
    MockClient::global([
        '*' => static function (PendingRequest $asked) use (&$sent): MockResponse {
            $sent[] = [
                $asked->getRequest()->resolveEndpoint(),
                $asked->query()->all(),
                $asked->body()?->all(),
                $asked->headers()->get('Idempotency-Key') !== null,
            ];

            return MockResponse::make((string) json_encode(['api_version' => 1, 'kind' => 'job', 'data' => ['job' => 'j-1', 'action' => 'uninstall']]), 202);
        },
    ]);

    $removing = new Dismantlers(new PinnedClients(), SequencedEntropy::counting());
    $removing->surveyed(aStackToTakeLemonfiberOff(), theSessionLemonfiberIsTakenOffOn(), WhichRemoval::Media);
    $removing->takeItOff(aStackToTakeLemonfiberOff(), theSessionLemonfiberIsTakenOffOn(), aYesToTheConfigurationReading());
    $removing->takeItOff(aStackToTakeLemonfiberOff(), theSessionLemonfiberIsTakenOffOn(), AnUninstallAgreed::after(
        AnUninstall::of(theConfigurationReading(), WhereTakingItOffGot::surveyed()),
        WhetherToWait::GoAheadNow,
        acknowledgedTheVolume: true,
    ));

    expect($sent)->toBe([
        ['/api/uninstall', ['tier' => 'media'], null, false],
        ['/api/actions/uninstall', [], ['tier' => 'configuration', 'confirm' => true, 'offer' => 'configuration-2-lines', 'wait' => true], true],
        ['/api/actions/uninstall', [], ['tier' => 'configuration', 'confirm' => true, 'offer' => 'configuration-2-lines', 'wait' => false], true],
    ]);
});

it('tells a session that has ended from a stack that is not answering', function (): void {
    $table = [
        [MockResponse::make('{"error":"no"}', 401), Obstacle::CredentialWasRefused],
        [MockResponse::make('{"error":"gone"}', 500), Obstacle::StackDidNotAnswer],
        [MockResponse::make('not json at all', 202), Obstacle::StackDidNotAnswer],
    ];

    foreach ($table as [$answered, $why]) {
        $ways = [
            'the fake' => static fn(): TakingLemonfiberOff => AStackThatTakesItOff::met($why),
            'the adapter' => everyWayOfTakingItOff(WhereTakingItOffGot::surveyed(), $answered, $answered)['the adapter'],
        ];

        foreach ($ways as $which => $make) {
            $removing = $make();

            expect(everythingTheUninstallReadingSays($removing->surveyed(aStackToTakeLemonfiberOff(), theSessionLemonfiberIsTakenOffOn(), WhichRemoval::Stop)))
                ->toBe($why->value, sprintf('%s / %s', $which, $why->value))
                ->and(everythingTheWorkSays($removing->takeItOff(aStackToTakeLemonfiberOff(), theSessionLemonfiberIsTakenOffOn(), aYesToTheConfigurationReading())))
                ->toBe($why->value, sprintf('%s / %s', $which, $why->value));
        }
    }
});

it('hands on a refusal in the stack\'s own words, whenever it arrives', function (): void {
    $refused = MockResponse::make('The reading you agreed to no longer stands', 400, ['Content-Type' => 'text/plain']);
    $ways = [
        'the fake' => static fn(): TakingLemonfiberOff => AStackThatTakesItOff::reading(
            AnUninstall::of(theConfigurationReading(), WhereTakingItOffGot::surveyed()),
            WhatBecameOfTheUninstall::refused('The reading you agreed to no longer stands'),
            WhatBecameOfTheUninstall::refused('The reading you agreed to no longer stands'),
        ),
        'the adapter' => everyWayOfTakingItOff(WhereTakingItOffGot::surveyed(), $refused, $refused)['the adapter'],
    ];

    foreach ($ways as $which => $make) {
        $removing = $make();

        expect(everythingTheWorkSays($removing->takeItOff(aStackToTakeLemonfiberOff(), theSessionLemonfiberIsTakenOffOn(), aYesToTheConfigurationReading())))
            ->toBe('refused The reading you agreed to no longer stands', $which)
            ->and(everythingTheWorkSays($removing->whatBecameOf(aStackToTakeLemonfiberOff(), theSessionLemonfiberIsTakenOffOn(), Job::named('j-1'))))
            ->toBe('refused The reading you agreed to no longer stands', $which);
    }
});

it('says the stack did not answer where a refusal carries no sentence, or the stack itself failed', function (): void {
    $silent = MockResponse::make('', 409, ['Content-Type' => 'text/plain']);
    $failed = MockResponse::make('It broke', 503, ['Content-Type' => 'text/plain']);
    $removing = everyWayOfTakingItOff(WhereTakingItOffGot::surveyed(), $silent, $failed)['the adapter']();

    expect(everythingTheWorkSays($removing->takeItOff(aStackToTakeLemonfiberOff(), theSessionLemonfiberIsTakenOffOn(), aYesToTheConfigurationReading())))
        ->toBe(Obstacle::StackDidNotAnswer->value)
        ->and(everythingTheWorkSays($removing->takeItOff(aStackToTakeLemonfiberOff(), theSessionLemonfiberIsTakenOffOn(), aYesToTheConfigurationReading())))
        ->toBe(Obstacle::StackDidNotAnswer->value);
});

it('says the stack has no outcome for work it no longer knows, or that ended before it finished, and work still going is underway', function (): void {
    $ended = MockResponse::make((string) json_encode(['api_version' => 1, 'kind' => 'job', 'data' => ['job' => 'j-1', 'action' => 'uninstall']]), 200);
    $going = MockResponse::make((string) json_encode(['api_version' => 1, 'kind' => 'job', 'data' => ['job' => 'j-1', 'action' => 'uninstall']]), 202);
    $ways = [
        'the fake' => static fn(): TakingLemonfiberOff => AStackThatTakesItOff::reading(AnUninstall::of(theConfigurationReading(), WhereTakingItOffGot::surveyed())),
        'the adapter' => everyWayOfTakingItOff(WhereTakingItOffGot::surveyed(), MockResponse::make('{"error":"no such job"}', 404))['the adapter'],
    ];

    foreach ($ways as $which => $make) {
        expect(everythingTheWorkSays($make()->whatBecameOf(aStackToTakeLemonfiberOff(), theSessionLemonfiberIsTakenOffOn(), Job::named('j-1'))))->toBe('ended', $which);
    }

    $removing = everyWayOfTakingItOff(WhereTakingItOffGot::surveyed(), $ended, $going)['the adapter']();

    expect(everythingTheWorkSays($removing->whatBecameOf(aStackToTakeLemonfiberOff(), theSessionLemonfiberIsTakenOffOn(), Job::named('j-1'))))->toBe('ended')
        ->and(everythingTheWorkSays($removing->whatBecameOf(aStackToTakeLemonfiberOff(), theSessionLemonfiberIsTakenOffOn(), Job::named('j-1'))))->toBe('underway j-1');
});

it('stands in for a stack with payloads the contract would accept', function (): void {
    foreach ([
        ['state' => 'surveyed'],
        ['state' => 'confirmed'],
        ['state' => 'complete', 'gone' => [], 'credentials' => []],
        ['state' => 'partial', 'gone' => [], 'credentials' => [], 'left' => [['name' => 'a', 'why' => 'b', 'by_hand' => 'c']]],
    ] as $removal) {
        expect(WhatTheContractAccepts::complaintsAbout('UninstallEnvelope', whatAStackSaysOfTakingItOff($removal)))
            ->toBe([], "The payload this suite stands in for a stack with is not one a stack would send.\n");
    }
});
