<?php

declare(strict_types=1);

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\ARefusalInItsWords;
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
use Modules\Kernel\Api\WhatTheRefusalNamed;
use Modules\Kernel\Api\Whose;
use Modules\Operator\Internal\Screens\WhatExtendsThisStack;
use Tests\Support\APluginAsItArrives;
use Tests\Support\ARecipeRunAsItArrives;
use Tests\Support\AroundThePhone;
use Tests\Support\Fakes\ACameraInMemory;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\AppsSettingsThatOpen;
use Tests\Support\Fakes\AStackThatExtendsItself;
use Tests\Support\Fakes\StacksInMemory;
use Tests\Support\WhatTheDeviceWouldDraw;

/** Where in Tdarr's recipes the one it installs with sits. */
const THE_INSTALL_RECIPE = 0;

/** Where in Tdarr's recipes the demand recipe that asks for nothing sits. */
const THE_DEMAND_RECIPE = 1;

/** Where in Tdarr's recipes the demand recipe that asks for a value sits. */
const THE_ASKING_RECIPE = 2;

function aMachineWhoseRecipeIsRun(): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat('e', Nonce::SHORTEST))),
        StackName::of('The loft'),
        Address::of('https://192.168.1.42:8443'),
        Fingerprint::of(str_repeat('e', Fingerprint::CHARACTERS)),
    );
}

function thePluginsWhoseRecipeIsRun(AStackThatExtendsItself $extending): WhatExtendsThisStack
{
    $stack = aMachineWhoseRecipeIsRun();
    $keychain = AKeychainInMemory::working();
    $keychain->keep($stack->id(), Session::of('a-session-not-a-secret'), Whose::theOperator());

    $screen = new WhatExtendsThisStack($extending, $keychain, AroundThePhone::holding(StacksInMemory::holding($stack)), new AppsSettingsThatOpen(), AroundThePhone::listening(), ACameraInMemory::reading('tdarr'));
    $screen->setParams(['stack' => $stack->id()->stored()]);
    $screen->answer();

    return $screen;
}

/** @param array<string, string> $with */
function aLineAboutRunning(string $key, array $with = []): string
{
    $said = __($key, $with);

    return is_string($said) ? $said : '';
}

function aStackWhoseRecipeIsRun(HowExtendingItIsGoing ...$answers): AStackThatExtendsItself
{
    return AStackThatExtendsItself::listing(
        ThePlugins::listed(TheInstalledPlugins::these(ARecipeRunAsItArrives::thePlugin()), ThePluginSources::these(), TheAnswersOutOfContract::these()),
        ...$answers,
    );
}

/** A stack that rehearses the run, as the operator is then shown it. */
function aStackThatRehearsesTheRun(HowExtendingItIsGoing ...$after): AStackThatExtendsItself
{
    return aStackWhoseRecipeIsRun(
        HowExtendingItIsGoing::underway(Job::named('j-1')),
        HowExtendingItIsGoing::done(ARecipeRunAsItArrives::theAnswer(ran: false)),
        ...$after,
    );
}

/** The screen once the run has been rehearsed and the rehearsal read. */
function theRunRehearsed(AStackThatExtendsItself $extending): WhatExtendsThisStack
{
    $screen = thePluginsWhoseRecipeIsRun($extending);
    $screen->runOnTheRow(0, THE_DEMAND_RECIPE);
    $screen->whileItRuns();
    $screen->answer();

    return $screen;
}

it('lists each recipe a plugin runs only when asked, offering the run of one that asks for nothing', function (): void {
    $drawn = WhatTheDeviceWouldDraw::by(thePluginsWhoseRecipeIsRun(aStackWhoseRecipeIsRun()));

    expect($drawn->said())->toContain('Ask Plex to look again', 'So what Tdarr finished shows up', 'Pin a library to a node')
        ->and($drawn->offers())->toContain(aLineAboutRunning('plugins.run.run_it', ['title' => 'Ask Plex to look again']))
        ->and($drawn->offers())->not->toContain(aLineAboutRunning('plugins.run.run_it', ['title' => 'Pin a library to a node']))
        ->and($drawn->offers())->not->toContain(aLineAboutRunning('plugins.run.run_it', ['title' => 'Point Tdarr at the library']));
});

it('says what a recipe asking for a value asks for, and that it is run at the web console or the terminal', function (): void {
    $extending = aStackWhoseRecipeIsRun();
    $screen = thePluginsWhoseRecipeIsRun($extending);
    $screen->runOnTheRow(0, THE_ASKING_RECIPE);
    $screen->runOnTheRow(0, THE_INSTALL_RECIPE);
    $screen->runOnTheRow(5, THE_DEMAND_RECIPE);
    $screen->runOnTheRow(0, 9);
    $screen->actOnTheRow('run', 0);

    expect(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(
        aLineAboutRunning('plugins.run.asks_for', ['ask' => 'Which node should take the library']),
        aLineAboutRunning('plugins.run.elsewhere'),
    )->and($extending->asked())->toBe(['list']);
});

it('rehearses the run first, labelled a rehearsal, with every step and each pair it carries off the machine on a switch of its own, all off', function (): void {
    $extending = aStackThatRehearsesTheRun();
    $screen = theRunRehearsed($extending);
    $drawn = WhatTheDeviceWouldDraw::by($screen);

    expect($extending->asked())->toBe(['list', 'rehearse running:tdarr:resync', 'after:j-1'])
        ->and($drawn->said())->toContain(
            aLineAboutRunning('plugins.run.reading', ['title' => 'Ask Plex to look again']),
            aLineAboutRunning('plugins.step', ['method' => 'POST', 'to' => 'plex.tv', 'path' => '/refresh']),
            aLineAboutRunning('plugins.pair', ['value' => 'token', 'to' => 'plex.tv']),
            aLineAboutRunning('plugins.run.approvals_each_time'),
        )
        ->and($drawn->offers())->toContain(aLineAboutRunning('plugins.approve', ['value' => 'token', 'to' => 'plex.tv']), aLineAboutRunning('plugins.run.run'))
        ->and($screen->approved)->toBe([]);
});

it('runs carrying back the offer its rehearsal named and exactly the values approved, and shows each step it came to', function (): void {
    $extending = aStackThatRehearsesTheRun(
        HowExtendingItIsGoing::done(ARecipeRunAsItArrives::theAnswer(ran: true)),
    );
    $screen = theRunRehearsed($extending);
    $screen->approve(0);
    $screen->agree();
    $said = WhatTheDeviceWouldDraw::by($screen)->said();

    expect($extending->asked())->toBe(['list', 'rehearse running:tdarr:resync', 'after:j-1', sprintf('run:tdarr:resync:%s:%s', APluginAsItArrives::AGREEMENT, ARecipeRunAsItArrives::APPROVAL)])
        ->and($said)->toContain(
            aLineAboutRunning('plugins.run.held', ['title' => 'Ask Plex to look again']),
            aLineAboutRunning('plugins.run.step', ['step' => 'nudge', 'to' => 'plex.tv']),
            aLineAboutRunning('plugins.run.came.answered'),
        )
        ->and($said)->not->toContain(aLineAboutRunning('plugins.run.reading', ['title' => 'Ask Plex to look again']));
});

it('runs approving nothing where the operator approved nothing', function (): void {
    $extending = aStackThatRehearsesTheRun(HowExtendingItIsGoing::underway(Job::named('j-2')));
    $screen = theRunRehearsed($extending);
    $screen->agree();

    expect($extending->asked())->toContain(sprintf('run:tdarr:resync:%s:', APluginAsItArrives::AGREEMENT))
        ->and(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(aLineAboutRunning('plugins.working.run'));
});

it('says a run whose step was held did not hold, with the stack\'s reason and the step\'s', function (): void {
    $extending = aStackThatRehearsesTheRun(HowExtendingItIsGoing::done(ARecipeRunAsItArrives::theAnswer(ran: true, held: false)));
    $screen = theRunRehearsed($extending);
    $screen->agree();

    expect(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(
        aLineAboutRunning('plugins.run.not_held', ['title' => 'Ask Plex to look again']),
        'Plex would not answer',
        aLineAboutRunning('plugins.run.came.unreachable'),
        'No answer in time',
    );
});

it('says the stack did not run it, in its own words, where it refused', function (): void {
    $refusal = ARefusalInItsWords::said('The offer has moved', '', WhatTheRefusalNamed::nothing(), 'Rehearse it again');
    $extending = aStackThatRehearsesTheRun(HowExtendingItIsGoing::refused($refusal));
    $screen = theRunRehearsed($extending);
    $screen->agree();

    expect(WhatTheDeviceWouldDraw::by($screen)->said())->toContain(aLineAboutRunning('plugins.refused.run'), 'The offer has moved');
});

it('asks for no install approval for a pair only a demand recipe carries', function (): void {
    $approvals = [];

    foreach (ARecipeRunAsItArrives::thePlugin()->approvals() as $approval) {
        $approvals[] = $approval;
    }

    expect($approvals)->toBe([APluginAsItArrives::APPROVAL]);
});
