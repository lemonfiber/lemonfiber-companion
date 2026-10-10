<?php

declare(strict_types=1);

namespace Modules\Sdk\Tests\Api;

use function array_map;

use Closure;

use function expect;
use function implode;
use function is_array;
use function it;
use function iterator_to_array;

use Lemonfiber\Sdk\Envelope\Envelope;
use Modules\Kernel\Api\AnAnswerOutOfContract;
use Modules\Kernel\Api\AnUpdate;
use Modules\Kernel\Api\APluginInstall;
use Modules\Kernel\Api\ThePlugins;
use Modules\Kernel\Api\WhatAProofSays;
use Modules\Kernel\Api\WhereItsSourceStands;
use Modules\Sdk\Api\PluginInstalls;
use Modules\Sdk\Api\PluginsAreUnreadable;
use Modules\Sdk\Api\UndoIsUnreadable;

use function sprintf;

use Tests\Support\APluginAsItArrives;
use Tests\Support\TheWordCarriedOut;

/**
 * A `plugins` envelope holding whatever the case under test is about.
 *
 * `plugins` is stood in for and not judged: each case spoils one field the
 * contract fixes, to show the reader refuses it, and the payloads a stack
 * does send are judged in `ExtendingTheStackContractTest`.
 *
 * @return Envelope<mixed>
 */
function pluginsSaying(mixed $data): Envelope
{
    return new Envelope(1, 'plugins', $data);
}

/**
 * The answer about Tdarr's install, with one change made to it.
 *
 * @param Closure(array<mixed>): array<mixed> $change
 *
 * @return Envelope<mixed>
 */
function anInstallAnswerWith(Closure $change): Envelope
{
    $answer = APluginAsItArrives::theAnswer(APluginAsItArrives::aReadingOnTheWire(), APluginAsItArrives::AGREEMENT);
    $data = $answer['data'];

    return pluginsSaying($change(is_array($data) ? $data : []));
}

/**
 * The install itself, with one change made to it.
 *
 * @param Closure(array<mixed>): array<mixed> $change
 *
 * @return Closure(array<mixed>): array<mixed>
 */
function theInstallWith(Closure $change): Closure
{
    return static function (array $data) use ($change): array {
        $install = $data['install'];
        $data['install'] = $change(is_array($install) ? $install : []);

        return $data;
    };
}

/**
 * The plugin the install would settle, with one change made to it.
 *
 * @param Closure(array<mixed>): array<mixed> $change
 *
 * @return Closure(array<mixed>): array<mixed>
 */
function thePluginWith(Closure $change): Closure
{
    return theInstallWith(static function (array $install) use ($change): array {
        $would = $install['would'];
        $install['would'] = $change(is_array($would) ? $would : []);

        return $install;
    });
}

/** How each proof of an install came out, as one line. */
function howTheProofsCameOut(ThePlugins $plugins): string
{
    return $plugins->either(
        listed: static fn(): TheWordCarriedOut => new TheWordCarriedOut('none'),
        install: static function (APluginInstall $install): TheWordCarriedOut {
            $said = [];

            foreach ($install->proofs() as $proof) {
                $lines = [];

                foreach ($proof->cameTo()->said() as $line) {
                    $lines[] = $line;
                }

                $said[] = sprintf('%s %s', $proof->cameTo()->says()->value, implode(',', $lines));
            }

            return new TheWordCarriedOut(implode('|', $said));
        },
        update: static fn(): TheWordCarriedOut => new TheWordCarriedOut('an update'),
        removal: static fn(): TheWordCarriedOut => new TheWordCarriedOut('a removal'),
    )->said;
}

it('reads a proof answered as unproven with its reason, and one failing only as its plugin declared', function (): void {
    $withVerdict = static fn(array $cameTo): Envelope => anInstallAnswerWith(theInstallWith(static function (array $install) use ($cameTo): array {
        $install['proofs'] = [['proof' => 'answers', 'establishes' => 'It answers', 'asks' => 'GET /', 'why' => 'So', 'came_to' => $cameTo]];

        return $install;
    }));

    expect(howTheProofsCameOut(PluginInstalls::in($withVerdict(['outcome' => 'unproven', 'why' => 'The service did not settle']))))->toBe('unproven The service did not settle')
        ->and(howTheProofsCameOut(PluginInstalls::in($withVerdict(['outcome' => 'failing-as-declared', 'declared' => [['constraint' => 'status', 'fixture' => 'a', 'held' => '502', 'reason' => 'Recorded against an old release']]]))))
        ->toBe('failing-as-declared Recorded against an old release')
        ->and(WhatAProofSays::FailingAsDeclared->value)->toBe('failing-as-declared');
});

it('reads every way a source can stand', function (): void {
    foreach (['reachable' => [], 'unasked' => ['why' => 'Set not to ask']] as $word => $more) {
        $answer = APluginAsItArrives::theAnswer(null);
        $data = $answer['data'];
        $data = is_array($data) ? $data : [];
        $data['sources'] = [['plugin' => 'tdarr', 'from' => 'tdarr', 'standing' => ['standing' => $word, ...$more]]];
        $plugins = PluginInstalls::in(pluginsSaying($data));

        expect($plugins->sourceOf(APluginAsItArrives::held())->standing())->toBe(WhereItsSourceStands::from($word));
    }
});

it('reads each answer out of contract against the plugin whose adapter gave it, and a listing carrying none as none', function (): void {
    $plugins = PluginInstalls::in(pluginsSaying(APluginAsItArrives::theAnswer(null)['data']));
    $answers = array_map(
        static fn(AnAnswerOutOfContract $answer): string => sprintf('%s %s %s', $answer->capability(), $answer->operation(), $answer->why()),
        iterator_to_array($plugins->answersOutOfContractOf(APluginAsItArrives::held()), preserve_keys: false),
    );
    $data = APluginAsItArrives::theAnswer(null)['data'];
    $data = is_array($data) ? $data : [];
    unset($data['nonconforming']);

    expect($answers)->toBe(['transcoding queue It answered with a field its contract does not have'])
        ->and(iterator_to_array(PluginInstalls::in(pluginsSaying($data))->answersOutOfContractOf(APluginAsItArrives::held()), preserve_keys: false))->toBe([]);
});

it('reads a listing that asked no source, and a record that keeps no recipe, as saying nothing of either', function (): void {
    $answer = APluginAsItArrives::theAnswer(null);
    $data = $answer['data'];
    $data = is_array($data) ? $data : [];
    unset($data['sources']);
    $data['installed'] = [['plugin' => 'tdarr', 'version' => '2.1.0', 'services' => [], 'declared' => []]];
    $plugins = PluginInstalls::in(pluginsSaying($data));

    foreach ($plugins->installed() as $plugin) {
        expect($plugin->recipes()->count())->toBe(0)
            ->and($plugin->vouched()->wasReviewed())->toBeFalse()
            ->and($plugin->shown())->toBe('tdarr')
            ->and($plugins->sourceOf($plugin)->standing())->toBe(WhereItsSourceStands::NotSaid);
    }
});

/**
 * The answer about Tdarr's update, with one change made to the update.
 *
 * @param Closure(array<mixed>): array<mixed> $change
 *
 * @return Envelope<mixed>
 */
function anUpdateAnswerWith(Closure $change): Envelope
{
    $answer = APluginAsItArrives::theAnswerAbout('update', $change(APluginAsItArrives::anUpdateNotHeldOnTheWire()));

    return pluginsSaying($answer['data']);
}

/**
 * The answer about Tdarr's removal, with one change made to the removal.
 *
 * @param Closure(array<mixed>): array<mixed> $change
 *
 * @return Envelope<mixed>
 */
function aRemovalAnswerWith(Closure $change): Envelope
{
    $answer = APluginAsItArrives::theAnswerAbout('removal', $change(APluginAsItArrives::aRemovalReadingOnTheWire()));

    return pluginsSaying($answer['data']);
}

/**
 * Every answer about an update or a removal that cannot be read.
 *
 * @return array<string, Envelope<mixed>>
 */
function everyUpdateOrRemovalThatCannotBeRead(): array
{
    return [
        'an update that is not a table' => anInstallAnswerWith(static fn(array $data): array => [...$data, 'install' => null, 'update' => 'yes']),
        'an update with no install' => anUpdateAnswerWith(static fn(array $update): array => [...$update, 'install' => null]),
        'an update with no version it came from' => anUpdateAnswerWith(static fn(array $update): array => [...$update, 'from' => ' ']),
        'interrupts that are not a list' => anUpdateAnswerWith(static fn(array $update): array => [...$update, 'interrupts' => 'tdarr']),
        'a blank service it stops' => anUpdateAnswerWith(static fn(array $update): array => [...$update, 'interrupts' => ['']]),
        'no word on going back' => anUpdateAnswerWith(static fn(array $update): array => [...$update, 'went_back' => null]),
        'a reason it stopped that is not text' => anUpdateAnswerWith(static fn(array $update): array => [...$update, 'stopped' => 3]),
        'a restore that is not a table' => anUpdateAnswerWith(static fn(array $update): array => [...$update, 'restored' => 'yes']),
        'a restore with no word on running' => anUpdateAnswerWith(static fn(array $update): array => [...$update, 'restored' => ['version' => '2.1.0', 'placed' => true]]),
        'a restore with no word on placing' => anUpdateAnswerWith(static fn(array $update): array => [...$update, 'restored' => ['version' => '2.1.0', 'running' => true]]),
        'a removal with no word on whether it was removed' => aRemovalAnswerWith(static fn(array $removal): array => [...$removal, 'removed' => 'yes']),
        'a removal with no plugin' => aRemovalAnswerWith(static fn(array $removal): array => [...$removal, 'plugin' => null]),
        'capabilities left that are not a list' => aRemovalAnswerWith(static fn(array $removal): array => [...$removal, 'leaves' => 'transcode']),
        'a capability left that is not a table' => aRemovalAnswerWith(static fn(array $removal): array => [...$removal, 'leaves' => ['transcode']]),
        'a capability left with nothing filling it now' => aRemovalAnswerWith(static fn(array $removal): array => [...$removal, 'leaves' => [['capability' => 'transcode']]]),
    ];
}

it('refuses every update or removal it cannot read, rather than salvaging it', function (): void {
    foreach (everyUpdateOrRemovalThatCannotBeRead() as $which => $answer) {
        expect(static fn(): ThePlugins => PluginInstalls::in($answer))->toThrow(PluginsAreUnreadable::class, null, $which);
    }
});

it('reads an update whose old version needed no putting back, sent as null or left out, as nothing to restore', function (): void {
    foreach ([['restored' => null, 'stopped' => null], []] as $which => $said) {
        $plugins = PluginInstalls::in(anUpdateAnswerWith(static function (array $update) use ($said): array {
            unset($update['restored'], $update['stopped']);

            return [...$update, ...$said];
        }));
        $read = $plugins->either(
            listed: static fn(): TheWordCarriedOut => new TheWordCarriedOut('none'),
            install: static fn(): TheWordCarriedOut => new TheWordCarriedOut('an install'),
            update: static fn(AnUpdate $update): TheWordCarriedOut => new TheWordCarriedOut(sprintf('%s [%s]', $update->restored()->wasNeeded() ? 'restored' : 'nothing to restore', $update->stopped())),
            removal: static fn(): TheWordCarriedOut => new TheWordCarriedOut('a removal'),
        )->said;

        expect($read)->toBe('nothing to restore []', (string) $which);
    }
});

/**
 * Every answer about plugins that cannot be read, and what refuses it.
 *
 * @return array<string, array{Envelope<mixed>, class-string}>
 */
function everyPluginsAnswerThatCannotBeRead(): array
{
    $unreadable = PluginsAreUnreadable::class;

    return [
        'a payload that is not a table' => [pluginsSaying('plugins'), $unreadable],
        'an install that is not a table' => [anInstallAnswerWith(static fn(array $data): array => [...$data, 'install' => 'yes']), $unreadable],
        'an agreement that is not text' => [anInstallAnswerWith(static fn(array $data): array => [...$data, 'agreement' => 3]), $unreadable],
        'an installed row that is not a table' => [anInstallAnswerWith(static fn(array $data): array => [...$data, 'installed' => ['tdarr']]), $unreadable],
        'a source row with no standing' => [anInstallAnswerWith(static fn(array $data): array => [...$data, 'sources' => [['plugin' => 'tdarr']]]), $unreadable],
        'a standing nobody reads' => [anInstallAnswerWith(static fn(array $data): array => [...$data, 'sources' => [['plugin' => 'tdarr', 'standing' => ['standing' => 'lost']]]]), $unreadable],
        'an answer out of contract that is not a table' => [anInstallAnswerWith(static fn(array $data): array => [...$data, 'nonconforming' => ['tdarr']]), $unreadable],
        'an answer out of contract saying nothing of why' => [anInstallAnswerWith(static fn(array $data): array => [...$data, 'nonconforming' => [['plugin' => 'tdarr', 'capability' => 'transcoding', 'operation' => 'queue', 'why' => ' ']]]), $unreadable],
        'nonconforming that is not a list' => [anInstallAnswerWith(static fn(array $data): array => [...$data, 'nonconforming' => 'none']), $unreadable],
        'the standing a stack never says' => [anInstallAnswerWith(static fn(array $data): array => [...$data, 'sources' => [['plugin' => 'tdarr', 'standing' => ['standing' => 'not_said']]]]), $unreadable],
        'no plugin it would settle' => [anInstallAnswerWith(theInstallWith(static fn(array $install): array => [...$install, 'would' => 'tdarr'])), $unreadable],
        'no word on whether it was recorded' => [anInstallAnswerWith(theInstallWith(static fn(array $install): array => [...$install, 'recorded' => 'no'])), $unreadable],
        'a change that is not a table' => [anInstallAnswerWith(theInstallWith(static fn(array $install): array => [...$install, 'changes' => ['a path']])), $unreadable],
        'a change putting something nobody reads' => [anInstallAnswerWith(theInstallWith(static fn(array $install): array => [...$install, 'changes' => [['path' => '/a', 'puts' => 'symlink']]])), $unreadable],
        'a contest that is not a table' => [anInstallAnswerWith(theInstallWith(static fn(array $install): array => [...$install, 'contests' => ['transcode']])), $unreadable],
        'claimants that are not a list' => [anInstallAnswerWith(theInstallWith(static fn(array $install): array => [...$install, 'contests' => [['capability' => 'transcode', 'by' => 'sonarr', 'claimants' => 'tdarr']]])), $unreadable],
        'a blank claimant' => [anInstallAnswerWith(theInstallWith(static fn(array $install): array => [...$install, 'contests' => [['capability' => 'transcode', 'by' => 'sonarr', 'claimants' => [' ']]]])), $unreadable],
        'an override that is not a table' => [anInstallAnswerWith(theInstallWith(static fn(array $install): array => [...$install, 'overrides' => ['sonarr.rename']])), $unreadable],
        'checks that are not a table' => [anInstallAnswerWith(theInstallWith(static fn(array $install): array => [...$install, 'verified' => 'fine'])), $unreadable],
        'a check broken with no reading now' => [anInstallAnswerWith(theInstallWith(static fn(array $install): array => [...$install, 'verified' => ['broke' => [['before' => null]], 'unsettled' => []]])), $unreadable],
        'a put back that is not a table' => [anInstallAnswerWith(theInstallWith(static fn(array $install): array => [...$install, 'reversed' => 'all of it'])), $unreadable],
        'a put back that leaves out what was left' => [anInstallAnswerWith(theInstallWith(static fn(array $install): array => [...$install, 'reversed' => ['rehearsed' => false, 'reversed' => []]])), UndoIsUnreadable::class],
        'no proofs' => [anInstallAnswerWith(theInstallWith(static fn(array $install): array => [...$install, 'proofs' => 'all held'])), $unreadable],
        'a proof that is not a table' => [anInstallAnswerWith(theInstallWith(static fn(array $install): array => [...$install, 'proofs' => ['answers']])), $unreadable],
        'a verdict that is not a table' => [anInstallAnswerWith(theInstallWith(static fn(array $install): array => [...$install, 'proofs' => [['proof' => 'a', 'establishes' => 'b', 'asks' => 'c', 'why' => 'd', 'came_to' => 'passed']]])), $unreadable],
        'a verdict nobody reads' => [anInstallAnswerWith(theInstallWith(static fn(array $install): array => [...$install, 'proofs' => [['proof' => 'a', 'establishes' => 'b', 'asks' => 'c', 'why' => 'd', 'came_to' => ['outcome' => 'maybe']]]])), $unreadable],
        'a verdict that says it was not asked' => [anInstallAnswerWith(theInstallWith(static fn(array $install): array => [...$install, 'proofs' => [['proof' => 'a', 'establishes' => 'b', 'asks' => 'c', 'why' => 'd', 'came_to' => ['outcome' => 'not_asked']]]])), $unreadable],
        'a failure with no faults' => [anInstallAnswerWith(theInstallWith(static fn(array $install): array => [...$install, 'proofs' => [['proof' => 'a', 'establishes' => 'b', 'asks' => 'c', 'why' => 'd', 'came_to' => ['outcome' => 'failed']]]])), $unreadable],
        'a blank fault' => [anInstallAnswerWith(theInstallWith(static fn(array $install): array => [...$install, 'proofs' => [['proof' => 'a', 'establishes' => 'b', 'asks' => 'c', 'why' => 'd', 'came_to' => ['outcome' => 'failed', 'faults' => ['']]]]])), $unreadable],
        'declared failures that are not a list' => [anInstallAnswerWith(theInstallWith(static fn(array $install): array => [...$install, 'proofs' => [['proof' => 'a', 'establishes' => 'b', 'asks' => 'c', 'why' => 'd', 'came_to' => ['outcome' => 'failing-as-declared']]]])), $unreadable],
        'a declared failure that is not a table' => [anInstallAnswerWith(theInstallWith(static fn(array $install): array => [...$install, 'proofs' => [['proof' => 'a', 'establishes' => 'b', 'asks' => 'c', 'why' => 'd', 'came_to' => ['outcome' => 'failing-as-declared', 'declared' => ['x']]]]])), $unreadable],
        'recipes that are not a list' => [anInstallAnswerWith(thePluginWith(static fn(array $would): array => [...$would, 'recipes' => 'one'])), $unreadable],
        'a recipe that is not a table' => [anInstallAnswerWith(thePluginWith(static fn(array $would): array => [...$would, 'recipes' => ['link']])), $unreadable],
        'a recipe with no steps' => [anInstallAnswerWith(thePluginWith(static fn(array $would): array => [...$would, 'recipes' => [['id' => 'a', 'title' => 'b', 'pairs' => []]]])), $unreadable],
        'a step that is not a table' => [anInstallAnswerWith(thePluginWith(static fn(array $would): array => [...$would, 'recipes' => [['id' => 'a', 'title' => 'b', 'steps' => ['ask'], 'pairs' => []]]])), $unreadable],
        'a pair that is not a table' => [anInstallAnswerWith(thePluginWith(static fn(array $would): array => [...$would, 'recipes' => [['id' => 'a', 'title' => 'b', 'steps' => [], 'pairs' => ['key']]]])), $unreadable],
        'a name that is not text' => [anInstallAnswerWith(thePluginWith(static fn(array $would): array => [...$would, 'name' => 3])), $unreadable],
        'a review that is neither yes nor no' => [anInstallAnswerWith(thePluginWith(static fn(array $would): array => [...$would, 'declared' => ['reviewed' => 'yes']])), $unreadable],
        'a blank version' => [anInstallAnswerWith(thePluginWith(static fn(array $would): array => [...$would, 'version' => ' '])), $unreadable],
    ];
}

it('refuses every answer about plugins it cannot read, rather than salvaging it', function (): void {
    foreach (everyPluginsAnswerThatCannotBeRead() as $which => [$answer, $refusal]) {
        expect(static fn(): ThePlugins => PluginInstalls::in($answer))->toThrow($refusal, null, $which);
    }
});

it('reads an adapter that is not a table as no adapter', function (): void {
    $plugins = PluginInstalls::in(anInstallAnswerWith(thePluginWith(static fn(array $would): array => [...$would, 'recipes' => [['id' => 'a', 'title' => 'b', 'steps' => [['id' => 's', 'method' => 'GET', 'to' => 'x', 'path' => '/', 'adapter' => 'servarr']], 'pairs' => []]]])));
    $adapters = $plugins->either(
        listed: static fn(): TheWordCarriedOut => new TheWordCarriedOut('none'),
        install: static function (APluginInstall $install): TheWordCarriedOut {
            foreach ($install->would()->recipes() as $recipe) {
                foreach ($recipe->steps() as $step) {
                    return new TheWordCarriedOut(sprintf('[%s]', $step->adapter()));
                }
            }

            return new TheWordCarriedOut('no step');
        },
        update: static fn(): TheWordCarriedOut => new TheWordCarriedOut('an update'),
        removal: static fn(): TheWordCarriedOut => new TheWordCarriedOut('a removal'),
    )->said;

    expect($adapters)->toBe('[]');
});

it('reads every check an install broke or left unsettled by its title, an agreement sent as null as none, and a record that declared nothing as unreviewed', function (): void {
    $finding = static fn(string $title): array => ['now' => ['title' => $title, 'check' => 'c', 'category' => 'services', 'origin' => 'written', 'verdict' => ['outcome' => 'pass']]];
    $plugins = PluginInstalls::in(anInstallAnswerWith(static function (array $data) use ($finding): array {
        $install = $data['install'];
        $install = is_array($install) ? $install : [];
        $install['recorded'] = true;
        $install['verified'] = ['broke' => [$finding('Sonarr answers')], 'unsettled' => [$finding('The VPN is up')]];
        $would = $install['would'];
        $would = is_array($would) ? $would : [];
        unset($would['declared']);
        $install['would'] = $would;

        return [...$data, 'install' => $install, 'agreement' => null];
    }));
    $read = $plugins->either(
        listed: static fn(): TheWordCarriedOut => new TheWordCarriedOut('none'),
        install: static function (APluginInstall $install): TheWordCarriedOut {
            $lines = [];

            foreach ($install->checks()->broke() as $line) {
                $lines[] = sprintf('broke %s', $line);
            }

            foreach ($install->checks()->unsettled() as $line) {
                $lines[] = sprintf('unsettled %s', $line);
            }

            $lines[] = $install->would()->vouched()->wasReviewed() ? 'reviewed' : 'not reviewed';
            $lines[] = $install->held() ? 'held' : 'not held';

            return new TheWordCarriedOut(implode('|', $lines));
        },
        update: static fn(): TheWordCarriedOut => new TheWordCarriedOut('an update'),
        removal: static fn(): TheWordCarriedOut => new TheWordCarriedOut('a removal'),
    )->said;

    expect($read)->toBe('broke Sonarr answers|unsettled The VPN is up|not reviewed|not held')
        ->and($plugins->agreement())->toBe('');
});
