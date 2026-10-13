<?php

declare(strict_types=1);

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\HowExtendingItIsGoing;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\TheAnswersOutOfContract;
use Modules\Kernel\Api\TheInstalledPlugins;
use Modules\Kernel\Api\ThePlugins;
use Modules\Kernel\Api\ThePluginSources;
use Modules\Kernel\Api\Whose;
use Modules\Operator\Internal\Screens\WhatExtendsThisStack;
use Tests\Support\APluginAsItArrives;
use Tests\Support\AroundThePhone;
use Tests\Support\Fakes\ACameraInMemory;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\AppsSettingsThatOpen;
use Tests\Support\Fakes\AStackThatExtendsItself;
use Tests\Support\Fakes\StacksInMemory;
use Tests\Support\WhatTheDeviceWouldDraw;

function aMachineWhosePluginIsProved(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('d', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('d', Fingerprint::CHARACTERS)),
    );
}

function thePluginsOnTheMachineProved(AStackThatExtendsItself $extending): WhatExtendsThisStack
{
    $stack = aMachineWhosePluginIsProved();
    $keychain = AKeychainInMemory::working();
    $keychain->keep($stack->id(), Session::of('a-session-not-a-secret'), Whose::theOperator());

    $screen = new WhatExtendsThisStack($extending, $keychain, AroundThePhone::holding(StacksInMemory::holding($stack)), new AppsSettingsThatOpen(), AroundThePhone::listening(), ACameraInMemory::reading('tdarr'));
    $screen->setParams(['stack' => $stack->id()->stored()]);
    $screen->answer();

    return $screen;
}

/** @param array<string, string> $with */
function aLineAboutProving(string $key, array $with = []): string
{
    $said = __($key, $with);

    return is_string($said) ? $said : '';
}

function aStackWhosePluginIsProved(HowExtendingItIsGoing ...$answers): AStackThatExtendsItself
{
    return AStackThatExtendsItself::listing(APluginAsItArrives::theListing(), ...$answers);
}

it('offers proving a plugin again only where answers outside its contract are kept against it', function (): void {
    $kept = WhatTheDeviceWouldDraw::by(thePluginsOnTheMachineProved(aStackWhosePluginIsProved()));
    $clean = AStackThatExtendsItself::listing(ThePlugins::listed(TheInstalledPlugins::these(APluginAsItArrives::held()), ThePluginSources::these(), TheAnswersOutOfContract::these()));
    $screen = thePluginsOnTheMachineProved($clean);
    $screen->actOnTheRow('prove', 0);

    expect($kept->offers())->toContain(aLineAboutProving('plugins.prove_it', ['name' => 'Tdarr']))
        ->and(WhatTheDeviceWouldDraw::by($screen)->offers())->not->toContain(aLineAboutProving('plugins.prove_it', ['name' => 'Tdarr']))
        ->and($clean->asked())->toBe(['list']);
});

it('reads what the proof would ask and which capability a pass would clear, before the yes', function (): void {
    $extending = aStackWhosePluginIsProved(
        HowExtendingItIsGoing::underway(Job::named('j-1')),
        HowExtendingItIsGoing::done(APluginAsItArrives::theProof()),
    );
    $screen = thePluginsOnTheMachineProved($extending);
    $screen->actOnTheRow('prove', 0);
    $screen->whileItRuns();
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect($extending->asked())->toBe(['list', 'read proving:tdarr', 'after:j-1'])
        ->and($drawn->said())->toContain(
            aLineAboutProving('plugins.proof_reading'),
            aLineAboutProving('plugins.a_pass_clears'),
            aLineAboutProving('plugins.answered_out_of_contract', ['capability' => 'transcoding', 'operation' => 'queue', 'why' => 'It answered with a field its contract does not have']),
            'It answers on its port',
            'GET /api/status',
        )
        ->and($drawn->offers())->toContain(aLineAboutProving('plugins.prove'));
});

it('carries back the offer the reading named, and says a proof that held cleared what was kept', function (): void {
    $extending = aStackWhosePluginIsProved(
        HowExtendingItIsGoing::underway(Job::named('j-1')),
        HowExtendingItIsGoing::done(APluginAsItArrives::theProof()),
        HowExtendingItIsGoing::done(APluginAsItArrives::theProof(asked: true, cleared: true)),
    );
    $screen = thePluginsOnTheMachineProved($extending);
    $screen->actOnTheRow('prove', 0);
    $screen->whileItRuns();
    $screen->answer();
    $screen->agree();
    $said = WhatTheDeviceWouldDraw::by($screen)->said();

    expect($extending->asked())->toBe(['list', 'read proving:tdarr', 'after:j-1', sprintf('prove:tdarr:%s', APluginAsItArrives::AGREEMENT)])
        ->and($said)->toContain(aLineAboutProving('plugins.proved_it'), aLineAboutProving('plugins.nothing_kept_against_it'))
        ->and($said)->not->toContain(aLineAboutProving('plugins.proof_reading'), aLineAboutProving('plugins.not_proved'));
});

it('says the adapters are being asked again while the proof agreed to runs', function (): void {
    $extending = aStackWhosePluginIsProved(
        HowExtendingItIsGoing::underway(Job::named('j-1')),
        HowExtendingItIsGoing::done(APluginAsItArrives::theProof()),
        HowExtendingItIsGoing::underway(Job::named('j-2')),
    );
    $screen = thePluginsOnTheMachineProved($extending);
    $screen->actOnTheRow('prove', 0);
    $screen->whileItRuns();
    $screen->answer();
    $screen->agree();

    expect(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(aLineAboutProving('plugins.working.prove'))
        ->and($screen->awaitsAnOutcome())->toBeTrue();
});

it('leaves a capability a failed proof kept shown as unfilled with the stack\'s reason, and never as passed', function (): void {
    $extending = aStackWhosePluginIsProved(
        HowExtendingItIsGoing::underway(Job::named('j-1')),
        HowExtendingItIsGoing::done(APluginAsItArrives::theProof()),
        HowExtendingItIsGoing::done(APluginAsItArrives::theProof(asked: true)),
    );
    $screen = thePluginsOnTheMachineProved($extending);
    $screen->actOnTheRow('prove', 0);
    $screen->whileItRuns();
    $screen->answer();
    $screen->agree();
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect($drawn->said())->toContain(
        aLineAboutProving('plugins.not_proved'),
        aLineAboutProving('plugins.kept_against_it'),
        aLineAboutProving('plugins.answered_out_of_contract', ['capability' => 'transcoding', 'operation' => 'queue', 'why' => 'It answered with a field its contract does not have']),
        aLineAboutProving('plugins.proof.failed'),
    )
        ->and($drawn->said())->not->toContain(aLineAboutProving('plugins.proved_it'))
        ->and($drawn->offers())->not->toContain(aLineAboutProving('plugins.prove'));
});

it('asks nothing for a row it does not have, or for a word that is not a row\'s act', function (): void {
    $extending = aStackWhosePluginIsProved();
    $screen = thePluginsOnTheMachineProved($extending);
    $screen->actOnTheRow('prove', 4);
    $screen->actOnTheRow('install', 0);
    $screen->actOnTheRow('dance', 0);

    expect($extending->asked())->toBe(['list']);
});
