<?php

declare(strict_types=1);

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\AnUpdate;
use Modules\Kernel\Api\APlugin;
use Modules\Kernel\Api\APluginInstall;
use Modules\Kernel\Api\APluginInstallAgreed;
use Modules\Kernel\Api\APluginRemoval;
use Modules\Kernel\Api\APluginRemovalAgreed;
use Modules\Kernel\Api\APluginSource;
use Modules\Kernel\Api\APluginUpdateAgreed;
use Modules\Kernel\Api\ARefusalInItsWords;
use Modules\Kernel\Api\ARunPutBack;
use Modules\Kernel\Api\ExtendingTheStack;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\HowExtendingItIsGoing;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\PluginLines;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\ThePlugins;
use Modules\Kernel\Api\WhatTheRefusalNamed;
use Modules\Kernel\Api\WhatWasFoundOfThePlugins;
use Modules\Sdk\Api\Extenders;
use Modules\Sdk\Api\PinnedClients;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Saloon\Http\PendingRequest;
use Tests\Support\APluginAsItArrives;
use Tests\Support\Fakes\AStackThatExtendsItself;
use Tests\Support\Fakes\SequencedEntropy;
use Tests\Support\TheWordCarriedOut;
use Tests\Support\WhatTheContractAccepts;

// The ExtendingTheStack contract, run against the adapter and against the fake.
//
// `G2`'s shape. The listing is answered at once; the rehearsal and the install
// answer with a handle, and asking after the handle answers with what the
// stack says of its plugins.

afterEach(function (): void {
    MockClient::destroyGlobal();
});

/** The stack plugins are installed on. */
function aStackToExtend(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('a', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('b', Fingerprint::CHARACTERS)),
    );
}

/** The session every case here asks on. */
function theSessionPluginsAreAskedOn(): Session
{
    return Session::of('a-session-not-a-secret');
}

/**
 * Lines the stack said, joined.
 */
function joinedPluginLines(PluginLines $lines): string
{
    $joined = [];

    foreach ($lines as $line) {
        $joined[] = $line;
    }

    return implode(',', $joined);
}

/** What putting an install back came to, folded to one line. */
function whatPuttingTheInstallBackCameTo(APluginInstall $install): string
{
    return $install->wasItPutBack(
        putBack: static function (ARunPutBack $report): TheWordCarriedOut {
            $went = [];

            foreach ($report->reversed() as $change) {
                $went[] = sprintf('%s/%s', $change->target(), $change->does()->value);
            }

            $left = [];

            foreach ($report->left() as $change) {
                $left[] = sprintf('%s/%s', $change->target(), $change->because());
            }

            return new TheWordCarriedOut(sprintf('put back %s left %s', implode(',', $went), implode(',', $left)));
        },
        notPutBack: static fn(): TheWordCarriedOut => new TheWordCarriedOut('not put back'),
    )->said;
}

/** Everything an install's account says, folded to lines. */
function everythingTheInstallSays(APluginInstall $install): string
{
    $lines = [];

    foreach ($install->changes() as $change) {
        $lines[] = sprintf('change %s/%s', $change->path(), $change->puts()->value);
    }

    foreach ($install->proofs() as $proof) {
        $lines[] = sprintf('proof %s/%s/%s/%s/%s/%s', $proof->proof(), $proof->establishes(), $proof->asks(), $proof->why(), $proof->cameTo()->says()->value, joinedPluginLines($proof->cameTo()->said()));
    }

    foreach ($install->contests() as $contest) {
        $lines[] = sprintf('contest %s/%s/%s', $contest->capability(), $contest->by(), joinedPluginLines($contest->claimants()));
    }

    foreach ($install->overrides() as $override) {
        $lines[] = sprintf('override %s/%s', $override->setting(), $override->why());
    }

    $checks = $install->checks();
    $lines[] = sprintf('checks %s %s/%s', $checks->wereAsked() ? 'asked' : 'not asked', joinedPluginLines($checks->broke()), joinedPluginLines($checks->unsettled()));
    $lines[] = whatPuttingTheInstallBackCameTo($install);
    $lines[] = sprintf('%s %s', $install->isAReading() ? 'a reading' : 'not a reading', $install->held() ? 'held' : 'not held');

    return implode("\n", $lines);
}

/** What putting the installed version back would come to, or came to, folded to one line. */
function whatGoingBackSays(ARunPutBack $report): string
{
    $went = [];

    foreach ($report->reversed() as $change) {
        $went[] = sprintf('%s/%s', $change->target(), $change->does()->value);
    }

    $left = [];

    foreach ($report->left() as $change) {
        $left[] = sprintf('%s/%s', $change->target(), $change->because());
    }

    return sprintf('went back %s %s left %s', $report->rehearsed()->value, implode(',', $went), implode(',', $left));
}

/** Everything an update's account says, folded to lines. */
function everythingTheUpdateSays(AnUpdate $update): string
{
    $restored = $update->restored();

    return implode("\n", [
        sprintf('update %s %s>%s interrupts %s', $update->plugin(), $update->versions()->from(), $update->versions()->to(), joinedPluginLines($update->interrupts())),
        everythingTheInstallSays($update->install()),
        whatGoingBackSays($update->wentBack()),
        sprintf('stopped %s', $update->stopped()),
        $restored->wasNeeded()
            ? sprintf('restored %s %s %s', $restored->version(), $restored->isPlaced() ? 'placed' : 'not placed', $restored->isRunning() ? 'running' : 'not running')
            : 'nothing to restore',
        sprintf('%s %s', $update->isAReading() ? 'a reading' : 'not a reading', $update->held() ? 'held' : 'not held'),
    ]);
}

/** Everything a removal's account says, folded to lines. */
function everythingAPluginRemovalSays(APluginRemoval $removal): string
{
    $leaves = [];

    foreach ($removal->leaves() as $left) {
        $leaves[] = sprintf('%s/%s', $left->capability(), $left->filledBy());
    }

    return implode("\n", [
        sprintf('removal %s interrupts %s leaves %s', $removal->plugin(), joinedPluginLines($removal->interrupts()), implode(',', $leaves)),
        whatGoingBackSays($removal->wentBack()),
        sprintf('%s %s %s', $removal->isAReading() ? 'a reading' : 'not a reading', $removal->wasRemoved() ? 'removed' : 'not removed', $removal->isPartial() ? 'partly' : 'not partly'),
    ]);
}

/** Everything the stack says of its plugins, folded to lines, so two answers can be compared. */
function everythingThePluginsSay(ThePlugins $plugins): string
{
    $lines = [];

    foreach ($plugins->installed() as $plugin) {
        $vouched = $plugin->vouched();
        $standing = $plugins->sourceOf($plugin);
        $lines[] = sprintf(
            'plugin %s/%s/%s/%s/%s/%s/%s/%s/%s/%s/%s',
            $plugin->id(),
            $plugin->version(),
            $plugin->shown(),
            $vouched->source(),
            $vouched->revision(),
            $vouched->signed(),
            $vouched->wasReviewed() ? 'reviewed' : 'not reviewed',
            $vouched->upstream(),
            $vouched->licence(),
            $standing->standing()->value,
            $standing->why(),
        );

        foreach ($plugin->recipes() as $recipe) {
            $lines[] = sprintf('recipe %s/%s/%s', $recipe->id(), $recipe->title(), $recipe->why());

            foreach ($recipe->steps() as $step) {
                $lines[] = sprintf('step %s/%s/%s/%s/%s', $step->id(), $step->method(), $step->to(), $step->path(), $step->adapter());
            }

            foreach ($recipe->pairs() as $pair) {
                $lines[] = sprintf('pair %s/%s/%s/%s/%s/%s', $pair->value(), $pair->origin(), $pair->to(), $pair->approval(), $pair->release(), $pair->from());
            }
        }
    }

    $lines[] = sprintf('agreement %s', $plugins->agreement());
    $lines[] = $plugins->either(
        listed: static fn(): TheWordCarriedOut => new TheWordCarriedOut('the listing alone'),
        install: static fn(APluginInstall $install): TheWordCarriedOut => new TheWordCarriedOut(everythingTheInstallSays($install)),
        update: static fn(AnUpdate $update): TheWordCarriedOut => new TheWordCarriedOut(everythingTheUpdateSays($update)),
        removal: static fn(APluginRemoval $removal): TheWordCarriedOut => new TheWordCarriedOut(everythingAPluginRemovalSays($removal)),
    )->said;

    return implode("\n", $lines);
}

/** Everything the work says, folded to one line. */
function everythingExtendingItSays(HowExtendingItIsGoing $going): string
{
    return $going->either(
        underway: static fn(Job $job): TheWordCarriedOut => new TheWordCarriedOut(sprintf('underway %s', $job->shown())),
        done: static fn(ThePlugins $plugins): TheWordCarriedOut => new TheWordCarriedOut(everythingThePluginsSay($plugins)),
        refused: static fn(ARefusalInItsWords $why): TheWordCarriedOut => new TheWordCarriedOut(sprintf('refused %s', $why->summary())),
        ended: static fn(): TheWordCarriedOut => new TheWordCarriedOut('ended'),
        met: static fn(Obstacle $why): TheWordCarriedOut => new TheWordCarriedOut($why->kind()->value),
    )->said;
}

/** Everything the listing says, folded to one line. */
function everythingTheListingSays(WhatWasFoundOfThePlugins $found): string
{
    return $found->either(
        found: static fn(ThePlugins $plugins): TheWordCarriedOut => new TheWordCarriedOut(everythingThePluginsSay($plugins)),
        met: static fn(Obstacle $why): TheWordCarriedOut => new TheWordCarriedOut($why->kind()->value),
    )->said;
}

/** The yes to the reading, the one value approved. */
function aYesToInstallingTdarr(): APluginInstallAgreed
{
    return APluginInstallAgreed::after(APluginAsItArrives::theReading(), APluginSource::typed('tdarr'), PluginLines::under('approved', APluginAsItArrives::APPROVAL));
}

/** Tdarr as the record holds it, with the source an update takes it from. */
function aTdarrThatCanBeUpdated(): APlugin
{
    foreach (APluginAsItArrives::theListing()->installed() as $plugin) {
        return $plugin;
    }

    throw new LogicException('The listing holds Tdarr.');
}

/** The yes to the update's reading, the one value approved. */
function aYesToUpdatingTdarr(): APluginUpdateAgreed
{
    return APluginUpdateAgreed::after(APluginAsItArrives::theUpdateReading(), aTdarrThatCanBeUpdated(), PluginLines::under('approved', APluginAsItArrives::APPROVAL));
}

/** The handle the stack answers an install with. */
function aPluginJob(): MockResponse
{
    return MockResponse::make((string) json_encode(['api_version' => 1, 'kind' => 'job', 'data' => ['job' => 'j-1', 'action' => 'plugin-install']]), 202);
}

/**
 * Both ways of extending it, each listing what is installed and answering the work with what it came to.
 *
 * @return array<string, Closure(): ExtendingTheStack>
 */
function everyWayOfExtendingIt(ThePlugins $came, MockResponse ...$answered): array
{
    return [
        'the fake' => static fn(): ExtendingTheStack => AStackThatExtendsItself::listing(
            APluginAsItArrives::theListing(),
            HowExtendingItIsGoing::underway(Job::named('j-1')),
            HowExtendingItIsGoing::done($came),
        ),
        'the adapter' => static function () use ($answered): ExtendingTheStack {
            MockClient::destroyGlobal();
            MockClient::global($answered);

            return new Extenders(new PinnedClients(), SequencedEntropy::counting());
        },
    ];
}

/** The lines every answer about Tdarr opens with. */
function whatEveryAnswerSaysOfTdarr(string $standing, string $why): string
{
    return implode("\n", [
        sprintf('plugin tdarr/2.1.0/Tdarr/tdarr//the catalogue key SHA256:abc/reviewed/https://github.com/HaveAGitGat/Tdarr/GPL-3.0/%s/%s', $standing, $why),
        'recipe link/Point Tdarr at the library/So it can transcode what arrives',
        'step ask/POST/sonarr//api/v3/notification/servarr',
        'step tell/POST/hooks.example.com//hook/',
        'pair api_key/credential-store/tdarr///',
        'pair library/stack-service/hooks.example.com/library@hooks.example.com//',
    ]);
}

it('lists what is installed, each with what vouches for it and how its source stands', function (): void {
    $listed = MockResponse::make((string) json_encode(APluginAsItArrives::theAnswer(null)));

    foreach (everyWayOfExtendingIt(APluginAsItArrives::theListing(), $listed) as $which => $make) {
        expect(everythingTheListingSays($make()->installedOn(aStackToExtend(), theSessionPluginsAreAskedOn())))->toBe(implode("\n", [
            whatEveryAnswerSaysOfTdarr('unreachable', 'The catalogue did not answer'),
            'agreement ',
            'the listing alone',
        ]), $which);
    }
});

it('rehearses an install, and follows it to the reading and its name', function (): void {
    $read = MockResponse::make((string) json_encode(APluginAsItArrives::theAnswer(APluginAsItArrives::aReadingOnTheWire(), APluginAsItArrives::AGREEMENT)));

    foreach (everyWayOfExtendingIt(APluginAsItArrives::theReading(), aPluginJob(), $read) as $which => $make) {
        $extending = $make();

        expect(everythingExtendingItSays($extending->rehearseInstalling(aStackToExtend(), theSessionPluginsAreAskedOn(), APluginSource::typed('tdarr'))))->toBe('underway j-1', $which)
            ->and(everythingExtendingItSays($extending->whatBecameOf(aStackToExtend(), theSessionPluginsAreAskedOn(), Job::named('j-1'))))->toBe(implode("\n", [
                whatEveryAnswerSaysOfTdarr('not_said', ''),
                sprintf('agreement %s', APluginAsItArrives::AGREEMENT),
                'change /srv/lemonfiber/plugins/tdarr/plugin.toml/document',
                'proof answers/It answers on its port/GET /api/status/A service that does not answer is not running/not_asked/',
                'contest transcode/sonarr/tdarr,unmanic',
                'override sonarr.rename/Tdarr renames what it transcodes',
                'checks not asked /',
                'not put back',
                'a reading not held',
            ]), $which);
    }
});

it('installs, and follows the work to an install that went back', function (): void {
    $done = MockResponse::make((string) json_encode(APluginAsItArrives::theAnswer(APluginAsItArrives::putBackOnTheWire(), APluginAsItArrives::AGREEMENT)));

    foreach (everyWayOfExtendingIt(APluginAsItArrives::thePutBack(), aPluginJob(), $done) as $which => $make) {
        $extending = $make();

        expect(everythingExtendingItSays($extending->install(aStackToExtend(), theSessionPluginsAreAskedOn(), aYesToInstallingTdarr())))->toBe('underway j-1', $which)
            ->and(everythingExtendingItSays($extending->whatBecameOf(aStackToExtend(), theSessionPluginsAreAskedOn(), Job::named('j-1'))))->toEndWith(implode("\n", [
                'agreement ',
                'change /srv/lemonfiber/plugins/tdarr/plugin.toml/document',
                'proof answers/It answers on its port/GET /api/status/A service that does not answer is not running/failed/It answered 502',
                'contest transcode/sonarr/tdarr,unmanic',
                'override sonarr.rename/Tdarr renames what it transcodes',
                'checks not asked /',
                'put back tdarr/delete left tdarr-data/The volume was in use',
                'not a reading not held',
            ]), $which);
    }
});

it('installs, and follows the work to an install that held', function (): void {
    $done = MockResponse::make((string) json_encode(APluginAsItArrives::theAnswer(APluginAsItArrives::installedOnTheWire(), APluginAsItArrives::AGREEMENT)));

    foreach (everyWayOfExtendingIt(APluginAsItArrives::theInstall(), aPluginJob(), $done) as $which => $make) {
        $extending = $make();
        $extending->install(aStackToExtend(), theSessionPluginsAreAskedOn(), aYesToInstallingTdarr());

        expect(everythingExtendingItSays($extending->whatBecameOf(aStackToExtend(), theSessionPluginsAreAskedOn(), Job::named('j-1'))))->toEndWith(implode("\n", [
            'proof answers/It answers on its port/GET /api/status/A service that does not answer is not running/passed/',
            'contest transcode/sonarr/tdarr,unmanic',
            'override sonarr.rename/Tdarr renames what it transcodes',
            'checks asked /',
            'not put back',
            'not a reading held',
        ]), $which);
    }
});

it('rehearses an update, and follows it to the reading of the install it carries and of going back', function (): void {
    $read = MockResponse::make((string) json_encode(APluginAsItArrives::theAnswerAbout('update', APluginAsItArrives::anUpdateReadingOnTheWire())));

    foreach (everyWayOfExtendingIt(APluginAsItArrives::theUpdateReading(), aPluginJob(), $read) as $which => $make) {
        $extending = $make();

        expect(everythingExtendingItSays($extending->rehearseUpdating(aStackToExtend(), theSessionPluginsAreAskedOn(), aTdarrThatCanBeUpdated())))->toBe('underway j-1', $which)
            ->and(everythingExtendingItSays($extending->whatBecameOf(aStackToExtend(), theSessionPluginsAreAskedOn(), Job::named('j-1'))))->toEndWith(implode("\n", [
                sprintf('agreement %s', APluginAsItArrives::AGREEMENT),
                'update tdarr 2.1.0>2.2.0 interrupts tdarr',
                'change /srv/lemonfiber/plugins/tdarr/plugin.toml/document',
                'proof answers/It answers on its port/GET /api/status/A service that does not answer is not running/not_asked/',
                'contest transcode/sonarr/tdarr,unmanic',
                'override sonarr.rename/Tdarr renames what it transcodes',
                'checks not asked /',
                'not put back',
                'a reading not held',
                'went back rehearsed tdarr/delete left /srv/lemonfiber/config/tdarr/What the service wrote stays for the new version',
                'stopped ',
                'nothing to restore',
                'a reading not held',
            ]), $which);
    }
});

it('updates, and follows the work to a new version that did not hold and the old one placed but not running', function (): void {
    $done = MockResponse::make((string) json_encode(APluginAsItArrives::theAnswerAbout('update', APluginAsItArrives::anUpdateNotHeldOnTheWire())));

    foreach (everyWayOfExtendingIt(APluginAsItArrives::theUpdateNotHeld(), aPluginJob(), $done) as $which => $make) {
        $extending = $make();

        expect(everythingExtendingItSays($extending->update(aStackToExtend(), theSessionPluginsAreAskedOn(), aYesToUpdatingTdarr())))->toBe('underway j-1', $which)
            ->and(everythingExtendingItSays($extending->whatBecameOf(aStackToExtend(), theSessionPluginsAreAskedOn(), Job::named('j-1'))))->toEndWith(implode("\n", [
                'agreement ',
                'update tdarr 2.1.0>2.2.0 interrupts tdarr',
                'change /srv/lemonfiber/plugins/tdarr/plugin.toml/document',
                'proof answers/It answers on its port/GET /api/status/A service that does not answer is not running/failed/It answered 502',
                'contest transcode/sonarr/tdarr,unmanic',
                'override sonarr.rename/Tdarr renames what it transcodes',
                'checks not asked /',
                'put back tdarr/delete left tdarr-data/The volume was in use',
                'not a reading not held',
                'went back carried_out tdarr/delete left /srv/lemonfiber/config/tdarr/What the service wrote stays for the new version',
                'stopped The container would not start',
                'restored 2.1.0 placed not running',
                'not a reading not held',
            ]), $which);
    }
});

it('rehearses a removal, and follows it to what it would leave unfilled', function (): void {
    $read = MockResponse::make((string) json_encode(APluginAsItArrives::theAnswerAbout('removal', APluginAsItArrives::aRemovalReadingOnTheWire())));

    foreach (everyWayOfExtendingIt(APluginAsItArrives::theRemovalReading(), aPluginJob(), $read) as $which => $make) {
        $extending = $make();

        expect(everythingExtendingItSays($extending->rehearseRemoving(aStackToExtend(), theSessionPluginsAreAskedOn(), aTdarrThatCanBeUpdated())))->toBe('underway j-1', $which)
            ->and(everythingExtendingItSays($extending->whatBecameOf(aStackToExtend(), theSessionPluginsAreAskedOn(), Job::named('j-1'))))->toEndWith(implode("\n", [
                sprintf('agreement %s', APluginAsItArrives::AGREEMENT),
                'removal tdarr interrupts tdarr leaves transcode/tdarr',
                'went back rehearsed tdarr/delete left /srv/lemonfiber/config/tdarr/What the service wrote stays for the new version',
                'a reading not removed not partly',
            ]), $which);
    }
});

it('removes, and follows the work to a removal that went only part of the way, or all of it', function (): void {
    foreach ([
        'partly' => [APluginAsItArrives::thePartialRemoval(), APluginAsItArrives::aPartialRemovalOnTheWire(), 'not a reading not removed partly'],
        'all of it' => [APluginAsItArrives::theRemoval(), APluginAsItArrives::aRemovalOnTheWire(), 'not a reading removed not partly'],
    ] as $case => [$came, $onTheWire, $said]) {
        $done = MockResponse::make((string) json_encode(APluginAsItArrives::theAnswerAbout('removal', $onTheWire)));

        foreach (everyWayOfExtendingIt($came, aPluginJob(), $done) as $which => $make) {
            $extending = $make();

            expect(everythingExtendingItSays($extending->remove(aStackToExtend(), theSessionPluginsAreAskedOn(), APluginRemovalAgreed::after(APluginAsItArrives::theRemovalReading(), aTdarrThatCanBeUpdated()))))->toBe('underway j-1', $which)
                ->and(everythingExtendingItSays($extending->whatBecameOf(aStackToExtend(), theSessionPluginsAreAskedOn(), Job::named('j-1'))))->toEndWith(implode("\n", [
                    'agreement ',
                    'removal tdarr interrupts tdarr leaves transcode/tdarr',
                    'went back carried_out tdarr/delete left /srv/lemonfiber/config/tdarr/What the service wrote stays for the new version',
                    $said,
                ]), sprintf('%s / %s', $case, $which));
        }
    }
});

it('asks an update with the plugin and the source it came from, and a removal with the plugin alone, each act under a key', function (): void {
    $sent = [];
    MockClient::destroyGlobal();
    MockClient::global([
        '*' => static function (PendingRequest $asked) use (&$sent): MockResponse {
            $sent[] = [$asked->getRequest()->resolveEndpoint(), $asked->body()?->all(), $asked->headers()->get('Idempotency-Key') !== null];

            return aPluginJob();
        },
    ]);

    $extending = new Extenders(new PinnedClients(), SequencedEntropy::counting());
    $extending->rehearseUpdating(aStackToExtend(), theSessionPluginsAreAskedOn(), aTdarrThatCanBeUpdated());
    $extending->update(aStackToExtend(), theSessionPluginsAreAskedOn(), aYesToUpdatingTdarr());
    $extending->rehearseRemoving(aStackToExtend(), theSessionPluginsAreAskedOn(), aTdarrThatCanBeUpdated());
    $extending->remove(aStackToExtend(), theSessionPluginsAreAskedOn(), APluginRemovalAgreed::after(APluginAsItArrives::theRemovalReading(), aTdarrThatCanBeUpdated()));

    expect($sent)->toBe([
        ['/api/actions/plugin-update', ['offer' => null, 'plugin' => 'tdarr', 'source' => 'tdarr', 'approved' => [], 'inputs' => []], true],
        ['/api/actions/plugin-update', ['offer' => APluginAsItArrives::AGREEMENT, 'plugin' => 'tdarr', 'source' => 'tdarr', 'approved' => [APluginAsItArrives::APPROVAL], 'inputs' => []], true],
        ['/api/actions/plugin-remove', ['offer' => null, 'plugin' => 'tdarr'], true],
        ['/api/actions/plugin-remove', ['offer' => APluginAsItArrives::AGREEMENT, 'plugin' => 'tdarr'], true],
    ]);
});

it('asks the listing at its endpoint, the rehearsal with the source alone, and the install with the reading and each approval, each act under a key', function (): void {
    $sent = [];
    MockClient::destroyGlobal();
    MockClient::global([
        '*' => static function (PendingRequest $asked) use (&$sent): MockResponse {
            $sent[] = [
                $asked->getRequest()->resolveEndpoint(),
                $asked->body()?->all(),
                $asked->headers()->get('Idempotency-Key') !== null,
            ];

            return aPluginJob();
        },
    ]);

    $extending = new Extenders(new PinnedClients(), SequencedEntropy::counting());
    $extending->installedOn(aStackToExtend(), theSessionPluginsAreAskedOn());
    $extending->rehearseInstalling(aStackToExtend(), theSessionPluginsAreAskedOn(), APluginSource::typed('  https://example.com/tdarr.git@v2  '));
    $extending->install(aStackToExtend(), theSessionPluginsAreAskedOn(), aYesToInstallingTdarr());

    // The SDK sends every argument the action takes, and this app gives no
    // recipe inputs, so `inputs` is always empty.
    expect($sent)->toBe([
        ['/api/plugins', null, false],
        ['/api/actions/plugin-install', ['offer' => null, 'source' => 'https://example.com/tdarr.git@v2', 'approved' => [], 'inputs' => []], true],
        ['/api/actions/plugin-install', ['offer' => APluginAsItArrives::AGREEMENT, 'source' => 'tdarr', 'approved' => [APluginAsItArrives::APPROVAL], 'inputs' => []], true],
    ]);
});

it('carries only the approvals the reading lists, each once', function (): void {
    $agreed = APluginInstallAgreed::after(
        APluginAsItArrives::theReading(),
        APluginSource::typed('tdarr'),
        PluginLines::under('approved', 'secrets@elsewhere', APluginAsItArrives::APPROVAL, APluginAsItArrives::APPROVAL),
    );

    expect(joinedPluginLines($agreed->approved()))->toBe(APluginAsItArrives::APPROVAL)
        ->and($agreed->agreement())->toBe(APluginAsItArrives::AGREEMENT);
});

it('tells a session that has ended from a stack that is not answering', function (): void {
    $table = [
        [MockResponse::make('{"error":"no"}', 401), Obstacle::of(KindOfObstacle::CredentialWasRefused)],
        [MockResponse::make('{"error":"gone"}', 500), Obstacle::of(KindOfObstacle::StackDidNotAnswer)],
        [MockResponse::make('not json at all', 202), Obstacle::of(KindOfObstacle::StackDidNotAnswer)],
    ];

    foreach ($table as [$answered, $why]) {
        $ways = [
            'the fake' => static fn(): ExtendingTheStack => AStackThatExtendsItself::met($why),
            'the adapter' => everyWayOfExtendingIt(APluginAsItArrives::theListing(), $answered, $answered)['the adapter'],
        ];

        foreach ($ways as $which => $make) {
            $extending = $make();

            expect(everythingTheListingSays($extending->installedOn(aStackToExtend(), theSessionPluginsAreAskedOn())))
                ->toBe($why->kind()->value, sprintf('%s / %s', $which, $why->kind()->value))
                ->and(everythingExtendingItSays($extending->rehearseInstalling(aStackToExtend(), theSessionPluginsAreAskedOn(), APluginSource::typed('tdarr'))))
                ->toBe($why->kind()->value, sprintf('%s / %s', $which, $why->kind()->value));
        }
    }
});

it('hands on a refusal in the stack\'s own words, whether it meets the asking or the work', function (): void {
    $refused = MockResponse::make((string) json_encode([
        'api_version' => 1,
        'kind' => 'error',
        'data' => [
            'code' => 'PLUGIN-2',
            'severity' => 'error',
            'summary' => 'This plugin declares native content, which this build refuses',
            'meaning' => 'Nothing was installed and nothing was written.',
            'state' => 'guided',
            'remedies' => [],
        ],
    ]), 400);
    $ways = [
        'the fake' => static fn(): ExtendingTheStack => AStackThatExtendsItself::listing(
            APluginAsItArrives::theListing(),
            HowExtendingItIsGoing::refused(ARefusalInItsWords::said('This plugin declares native content, which this build refuses', '', WhatTheRefusalNamed::nothing())),
            HowExtendingItIsGoing::refused(ARefusalInItsWords::said('This plugin declares native content, which this build refuses', '', WhatTheRefusalNamed::nothing())),
        ),
        'the adapter' => everyWayOfExtendingIt(APluginAsItArrives::theListing(), $refused, $refused)['the adapter'],
    ];

    foreach ($ways as $which => $make) {
        $extending = $make();

        expect(everythingExtendingItSays($extending->rehearseInstalling(aStackToExtend(), theSessionPluginsAreAskedOn(), APluginSource::typed('./native'))))
            ->toBe('refused This plugin declares native content, which this build refuses', $which)
            ->and(everythingExtendingItSays($extending->whatBecameOf(aStackToExtend(), theSessionPluginsAreAskedOn(), Job::named('j-1'))))
            ->toBe('refused This plugin declares native content, which this build refuses', $which);
    }
});

it('says the stack has no outcome for work it no longer knows, and work still going is underway', function (): void {
    $going = MockResponse::make((string) json_encode(['api_version' => 1, 'kind' => 'job', 'data' => ['job' => 'j-1', 'action' => 'plugin-install']]), 202);
    $extending = everyWayOfExtendingIt(APluginAsItArrives::theListing(), MockResponse::make('{"error":"no such job"}', 404), $going)['the adapter']();

    expect(everythingExtendingItSays($extending->whatBecameOf(aStackToExtend(), theSessionPluginsAreAskedOn(), Job::named('j-1'))))->toBe('ended')
        ->and(everythingExtendingItSays($extending->whatBecameOf(aStackToExtend(), theSessionPluginsAreAskedOn(), Job::named('j-1'))))->toBe('underway j-1')
        ->and(everythingExtendingItSays(AStackThatExtendsItself::listing(APluginAsItArrives::theListing())->whatBecameOf(aStackToExtend(), theSessionPluginsAreAskedOn(), Job::named('j-1'))))->toBe('ended');
});

it('stands in for a stack with payloads the contract would accept', function (): void {
    foreach ([
        APluginAsItArrives::theAnswer(null),
        APluginAsItArrives::theAnswer(APluginAsItArrives::aReadingOnTheWire(), APluginAsItArrives::AGREEMENT),
        APluginAsItArrives::theAnswer(APluginAsItArrives::putBackOnTheWire(), APluginAsItArrives::AGREEMENT),
        APluginAsItArrives::theAnswer(APluginAsItArrives::installedOnTheWire(), APluginAsItArrives::AGREEMENT),
        APluginAsItArrives::theAnswerAbout('update', APluginAsItArrives::anUpdateReadingOnTheWire()),
        APluginAsItArrives::theAnswerAbout('update', APluginAsItArrives::anUpdateNotHeldOnTheWire()),
        APluginAsItArrives::theAnswerAbout('removal', APluginAsItArrives::aRemovalReadingOnTheWire()),
        APluginAsItArrives::theAnswerAbout('removal', APluginAsItArrives::aPartialRemovalOnTheWire()),
        APluginAsItArrives::theAnswerAbout('removal', APluginAsItArrives::aRemovalOnTheWire()),
    ] as $answer) {
        expect(WhatTheContractAccepts::complaintsAbout('PluginsEnvelope', $answer))
            ->toBe([], "The payload this suite stands in for a stack with is not one a stack would send.\n");
    }
});
