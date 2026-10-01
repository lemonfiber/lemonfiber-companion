<?php

declare(strict_types=1);

namespace Modules\Sdk\Tests\Api;

use function array_slice;
use function count;
use function expect;
use function implode;
use function is_array;
use function it;
use function iterator_to_array;

use Lemonfiber\Sdk\Envelope\Envelope;
use Lemonfiber\Sdk\Exception\UnexpectedKind;
use Modules\Kernel\Api\AMove;
use Modules\Kernel\Api\EnvelopeIsNotRead;
use Modules\Kernel\Api\TheAdoption;
use Modules\Kernel\Api\TheImport;
use Modules\Kernel\Api\TheRecords;
use Modules\Kernel\Api\TheReplacement;
use Modules\Kernel\Api\TheStandingBeside;
use Modules\Sdk\Api\MoveIsUnreadable;
use Modules\Sdk\Api\WhatMovingInCameTo;

use function sprintf;
use function str_starts_with;

use Tests\Support\TheWordCarriedOut;
use Tests\Support\WhatTheContractAccepts;

use function var_export;

/**
 * An envelope of one kind, holding whatever the case under test is about.
 *
 * Each kind written out, so the kind every body is built under is one a
 * reading of this file can see. `repair` is stood in for and not judged: it is the kind none of the four readers opens, built to be refused.
 *
 * @return Envelope<mixed>
 */
function aMoveSaying(string $kind, mixed $data, int $version = 1): Envelope
{
    return match ($kind) {
        'adoption' => new Envelope($version, 'adoption', $data),
        'import' => new Envelope($version, 'import', $data),
        'beside' => new Envelope($version, 'beside', $data),
        'replacement' => new Envelope($version, 'replacement', $data),
        default => new Envelope($version, 'repair', $data),
    };
}

/**
 * Each way of moving in with two of everything, applied, so a refusal can be asked to name the second.
 *
 * @return array<string, array<string, mixed>>
 */
function everyMoveWithTwoOfEverything(): array
{
    $upgrade = ['service' => 'sonarr', 'existing' => '3.0', 'ours' => '4.0', 'verdict' => 'newer', 'because' => 'Its database is upgraded', 'backup_first' => true, 'refused' => false];
    $record = ['service' => 'sonarr', 'kind' => 'quality profiles', 'name' => 'HD-1080p'];
    $limit = ['what' => 'sonarr', 'because' => 'its indexers could not be read'];

    return [
        'adoption' => ['project' => 'media', 'stance' => 'applied', 'upgrades' => [$upgrade, [...$upgrade, 'service' => 'radarr', 'backup_first' => false, 'refused' => true]], 'back_up' => ['/srv/sonarr', '/srv/radarr'], 'backed_up' => '/srv/copy.tar'],
        'import' => ['project' => 'media', 'stance' => 'applied', 'carried' => [$record, [...$record, 'name' => 'SD']], 'would_carry' => [$record, $record], 'not_carried' => [$limit, [...$limit, 'what' => 'radarr']]],
        'beside' => ['stance' => 'applied', 'ports' => [['service' => 'sonarr', 'from' => 8989, 'to' => 8990], ['service' => 'radarr', 'from' => 7878, 'to' => 7879]], 'written' => '/srv/beside.yml'],
        'replacement' => ['project' => 'media', 'stance' => 'applied', 'would_stop' => ['sonarr', 'radarr'], 'stopped' => ['sonarr', 'radarr'], 'still_running' => ['tautulli', 'bazarr']],
    ];
}

/**
 * One move with one value replaced, or taken away where the value is `absent`.
 *
 * @param array<mixed>     $data
 * @param list<int|string> $path
 * @return array<mixed>
 */
function aMoveWith(array $data, array $path, mixed $value): array
{
    $key = $path[0];

    if (count($path) > 1) {
        $inside = $data[$key];
        $data[$key] = aMoveWith(is_array($inside) ? $inside : [], array_slice($path, 1), $value);

        return $data;
    }

    if ($value === 'absent') {
        unset($data[$key]);

        return $data;
    }

    $data[$key] = $value;

    return $data;
}

/** Records, each as one word. */
function theRecordsRead(TheRecords $records): string
{
    $said = [];

    foreach ($records as $record) {
        $said[] = sprintf('%s/%s/%s', $record->service(), $record->kind(), $record->name());
    }

    return implode(',', $said);
}

/** Everything a move carries, folded to one line. */
function everythingTheMoveSays(AMove $move): string
{
    return sprintf('%s|%s|%s|%s', $move->by()->value, $move->stance()->value, $move->refusal(), $move->either(
        adopting: static function (TheAdoption $adoption): TheWordCarriedOut {
            $upgrades = [];

            foreach ($adoption as $upgrade) {
                $what = $upgrade->what();
                $upgrades[] = sprintf('%s %s>%s %s %s %s %s', $what->service(), $upgrade->existing(), $upgrade->ours(), $upgrade->verdict(), $what->because(), var_export($what->wantsACopyFirst(), return: true), var_export($what->isRefused(), return: true));
            }

            return new TheWordCarriedOut(sprintf('%s|%s|%s|%s', $adoption->project(), implode(',', $upgrades), implode(',', iterator_to_array($adoption->backUp(), preserve_keys: false)), $adoption->backedUp()));
        },
        importing: static function (TheImport $import): TheWordCarriedOut {
            $left = [];

            foreach ($import->notCarried() as $limit) {
                $left[] = sprintf('%s: %s', $limit->what(), $limit->because());
            }

            return new TheWordCarriedOut(sprintf('%s|%s|%s|%s', $import->project(), theRecordsRead($import->carried()), theRecordsRead($import->wouldCarry()), implode(',', $left)));
        },
        standingBeside: static function (TheStandingBeside $beside): TheWordCarriedOut {
            $ports = [];

            foreach ($beside->ports() as $moved) {
                $ports[] = sprintf('%s %d>%d', $moved->service(), $moved->from(), $moved->to());
            }

            return new TheWordCarriedOut(sprintf('%s|%s', implode(',', $ports), $beside->written()));
        },
        replacing: static fn(TheReplacement $replacement): TheWordCarriedOut => new TheWordCarriedOut(sprintf(
            '%s|%s|%s|%s',
            $replacement->project(),
            implode(',', iterator_to_array($replacement->wouldStop(), preserve_keys: false)),
            implode(',', iterator_to_array($replacement->stopped(), preserve_keys: false)),
            implode(',', iterator_to_array($replacement->stillRunning(), preserve_keys: false)),
        )),
    )->said);
}

it('stands in for a stack with payloads the contract would accept', function (): void {
    $moves = everyMoveWithTwoOfEverything();

    expect(WhatTheContractAccepts::complaintsAbout('AdoptionEnvelope', ['api_version' => 1, 'kind' => 'adoption', 'data' => $moves['adoption']]))->toBe([])
        ->and(WhatTheContractAccepts::complaintsAbout('ImportEnvelope', ['api_version' => 1, 'kind' => 'import', 'data' => $moves['import']]))->toBe([])
        ->and(WhatTheContractAccepts::complaintsAbout('BesideEnvelope', ['api_version' => 1, 'kind' => 'beside', 'data' => $moves['beside']]))->toBe([])
        ->and(WhatTheContractAccepts::complaintsAbout('ReplacementEnvelope', ['api_version' => 1, 'kind' => 'replacement', 'data' => $moves['replacement']]))->toBe([]);
});

it('reads every way of moving in by the kind it arrived as, with everything it carries', function (): void {
    $moves = everyMoveWithTwoOfEverything();

    expect(everythingTheMoveSays(WhatMovingInCameTo::in(aMoveSaying('adoption', $moves['adoption']))))
        ->toBe('adopt|applied||media|sonarr 3.0>4.0 newer Its database is upgraded true false,radarr 3.0>4.0 newer Its database is upgraded false true|/srv/sonarr,/srv/radarr|/srv/copy.tar')
        ->and(everythingTheMoveSays(WhatMovingInCameTo::in(aMoveSaying('import', $moves['import']))))
        ->toBe('import|applied||media|sonarr/quality profiles/HD-1080p,sonarr/quality profiles/SD|sonarr/quality profiles/HD-1080p,sonarr/quality profiles/HD-1080p|sonarr: its indexers could not be read,radarr: its indexers could not be read')
        ->and(everythingTheMoveSays(WhatMovingInCameTo::in(aMoveSaying('beside', $moves['beside']))))
        ->toBe('beside|applied||sonarr 8989>8990,radarr 7878>7879|/srv/beside.yml')
        ->and(everythingTheMoveSays(WhatMovingInCameTo::in(aMoveSaying('replacement', $moves['replacement']))))
        ->toBe('replace|applied||media|sonarr,radarr|sonarr,radarr|tautulli,bazarr');
});

it('reads every stance as given, and a turned-away one with the stack\'s reason', function (string $kind): void {
    $data = everyMoveWithTwoOfEverything()[$kind];

    foreach (['unchanged', 'pending', 'applied'] as $stance) {
        expect(WhatMovingInCameTo::in(aMoveSaying($kind, [...$data, 'stance' => $stance, 'refusal' => 'A sentence beside it']))->stance()->value)->toBe($stance, $kind);
    }

    $blocked = WhatMovingInCameTo::in(aMoveSaying($kind, [...$data, 'stance' => 'blocked', 'refusal' => 'There is no single setup here']));

    expect([$blocked->stance()->value, $blocked->refusal()])->toBe(['blocked', 'There is no single setup here'])
        ->and(WhatMovingInCameTo::in(aMoveSaying($kind, [...$data, 'refusal' => 'A sentence beside it']))->refusal())->toBe('');
})->with(['adoption', 'import', 'beside', 'replacement']);

it('refuses a stance it has no word for, or a move turned away without a reason', function (string $kind, string $field, mixed $stance, mixed $refusal): void {
    $data = aMoveWith([...everyMoveWithTwoOfEverything()[$kind], 'stance' => $stance], ['refusal'], $refusal);

    expect(fn(): AMove => WhatMovingInCameTo::in(aMoveSaying($kind, $data)))
        ->toThrow(MoveIsUnreadable::class, sprintf('The %s envelope has no readable `%s`', $kind, $field));
})->with([
    ['adoption', 'stance', 'done', 'absent'], ['import', 'stance', 7, 'absent'], ['beside', 'stance', ' ', 'absent'],
    ['replacement', 'refusal', 'blocked', 'absent'], ['adoption', 'refusal', 'blocked', ' '], ['import', 'refusal', 'blocked', null],
]);

it('reads text the stack may leave out as nothing, where it is absent or null', function (string $kind, string $field, mixed $said, string $read): void {
    $move = WhatMovingInCameTo::in(aMoveSaying($kind, aMoveWith(everyMoveWithTwoOfEverything()[$kind], [$field], $said)));

    expect(str_starts_with(everythingTheMoveSays($move), $read))->toBeTrue(everythingTheMoveSays($move));
})->with([
    ['adoption', 'project', 'absent', 'adopt|applied|||sonarr'], ['adoption', 'project', null, 'adopt|applied|||sonarr'],
    ['import', 'project', null, 'import|applied|||sonarr'], ['replacement', 'project', 'absent', 'replace|applied|||sonarr'],
    ['beside', 'written', 'absent', 'beside|applied||sonarr 8989>8990,radarr 7878>7879|'],
    ['beside', 'written', null, 'beside|applied||sonarr 8989>8990,radarr 7878>7879|'],
]);

it('reads a copy that was not written as none', function (mixed $said): void {
    $move = WhatMovingInCameTo::in(aMoveSaying('adoption', aMoveWith(everyMoveWithTwoOfEverything()['adoption'], ['backed_up'], $said)));

    expect(everythingTheMoveSays($move))->toEndWith('|/srv/sonarr,/srv/radarr|');
})->with(['absent', null]);

it('refuses a payload with no data, naming the kind', function (string $kind): void {
    expect(fn(): AMove => WhatMovingInCameTo::in(aMoveSaying($kind, 'nothing')))->toThrow(MoveIsUnreadable::class, sprintf('The %s envelope has no readable `data`', $kind));
})->with(['adoption', 'import', 'beside', 'replacement']);

it('refuses a field of the move itself that is missing or not what the contract says', function (string $kind, string $field, mixed $said): void {
    expect(fn(): AMove => WhatMovingInCameTo::in(aMoveSaying($kind, aMoveWith(everyMoveWithTwoOfEverything()[$kind], [$field], $said))))
        ->toThrow(MoveIsUnreadable::class, sprintf('The %s envelope has no readable `%s`', $kind, $field));
})->with([
    ['adoption', 'upgrades', 'absent'], ['adoption', 'back_up', 'nothing'], ['adoption', 'project', ' '], ['adoption', 'backed_up', 7],
    ['import', 'carried', 'absent'], ['import', 'would_carry', 'nothing'], ['import', 'not_carried', 'absent'], ['import', 'project', 7],
    ['beside', 'ports', 'nothing'], ['beside', 'written', ''],
    ['replacement', 'would_stop', 'absent'], ['replacement', 'stopped', 'nothing'], ['replacement', 'still_running', 'absent'], ['replacement', 'project', ''],
]);

it('refuses an entry that does not say what it owes, naming the list, the second entry and the field', function (string $kind, string $list, string $field, mixed $said): void {
    expect(fn(): AMove => WhatMovingInCameTo::in(aMoveSaying($kind, aMoveWith(everyMoveWithTwoOfEverything()[$kind], [$list, 1, $field], $said))))
        ->toThrow(MoveIsUnreadable::class, sprintf('Entry 1 of `%s` in the %s envelope has no readable `%s`', $list, $kind, $field));
})->with([
    ['adoption', 'upgrades', 'service', ' '], ['adoption', 'upgrades', 'because', 'absent'], ['adoption', 'upgrades', 'backup_first', 'yes'],
    ['adoption', 'upgrades', 'refused', 'absent'], ['adoption', 'upgrades', 'existing', ''], ['adoption', 'upgrades', 'ours', 4],
    ['adoption', 'upgrades', 'verdict', 'absent'],
    ['import', 'carried', 'service', ''], ['import', 'carried', 'kind', 'absent'], ['import', 'would_carry', 'name', ' '],
    ['import', 'not_carried', 'what', 'absent'], ['import', 'not_carried', 'because', ''],
    ['beside', 'ports', 'service', ' '], ['beside', 'ports', 'from', '8989'], ['beside', 'ports', 'to', 'absent'],
]);

it('refuses an entry that is not one, naming the list and the first thing it owes', function (string $kind, string $list, string $field): void {
    expect(fn(): AMove => WhatMovingInCameTo::in(aMoveSaying($kind, aMoveWith(everyMoveWithTwoOfEverything()[$kind], [$list, 1], 'spoiled'))))
        ->toThrow(MoveIsUnreadable::class, sprintf('Entry 1 of `%s` in the %s envelope has no readable `%s`', $list, $kind, $field));
})->with([
    ['adoption', 'upgrades', 'service'], ['import', 'carried', 'service'], ['import', 'would_carry', 'service'],
    ['import', 'not_carried', 'what'], ['beside', 'ports', 'service'],
]);

it('refuses a name in a list that is blank or is not a name, naming the list and the second entry', function (string $kind, string $list, mixed $said): void {
    expect(fn(): AMove => WhatMovingInCameTo::in(aMoveSaying($kind, aMoveWith(everyMoveWithTwoOfEverything()[$kind], [$list, 1], $said))))
        ->toThrow(MoveIsUnreadable::class, sprintf('Entry 1 of `%s` in the %s envelope has no readable `%s`', $list, $kind, $list));
})->with([
    ['adoption', 'back_up', ' '], ['replacement', 'would_stop', 7], ['replacement', 'stopped', ''], ['replacement', 'still_running', ['tautulli']],
]);

it('refuses a kind that is none of the four ways of moving in', function (): void {
    expect(fn(): AMove => WhatMovingInCameTo::in(aMoveSaying('repair', everyMoveWithTwoOfEverything()['replacement'])))->toThrow(UnexpectedKind::class);
});

it('refuses an envelope in a version this app does not read', function (): void {
    expect(fn(): AMove => WhatMovingInCameTo::in(aMoveSaying('adoption', everyMoveWithTwoOfEverything()['adoption'], version: 99)))->toThrow(EnvelopeIsNotRead::class);
});
