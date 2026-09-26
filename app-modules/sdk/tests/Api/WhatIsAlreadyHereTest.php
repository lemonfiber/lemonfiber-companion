<?php

declare(strict_types=1);

namespace Modules\Sdk\Tests\Api;

use function array_slice;
use function count;
use function expect;
use function implode;
use function is_array;
use function it;

use Lemonfiber\Sdk\Envelope\Envelope;
use Modules\Kernel\Api\EnvelopeIsNotRead;
use Modules\Kernel\Api\ThePortsItPublishes;
use Modules\Kernel\Api\TheSurvey;
use Modules\Sdk\Api\MigrationIsUnreadable;
use Modules\Sdk\Api\WhatIsAlreadyHere;

use function sprintf;

use Tests\Support\WhatTheContractAccepts;

use function var_export;

/**
 * A `migration` envelope holding whatever the case under test is about.
 *
 * @return Envelope<mixed>
 */
function surveySaying(mixed $data, int $version = 1): Envelope
{
    return new Envelope($version, 'migration', $data);
}

/**
 * A survey with two of everything, so a refusal can be asked to name the second.
 *
 * @return array<string, mixed>
 */
function aSurveyOfTwoOfEverything(): array
{
    $service = ['service' => 'sonarr', 'ports' => [8989, 9898], 'running' => true, 'adoptable' => false];
    $project = ['project' => 'media', 'services' => [$service, $service]];
    $limit = ['what' => 'media/tautulli', 'because' => 'lemonfiber does not run it'];

    return [
        'read' => true,
        'standing' => [$project, $project],
        'conflicts' => [['port' => 8989, 'wanted_by' => 'sonarr', 'held_by' => 'media'], ['port' => 7878, 'wanted_by' => 'radarr', 'held_by' => 'media']],
        'unsupported' => [$limit, $limit],
        'carrying' => [
            ['service' => 'sonarr', 'existing' => '4', 'ours' => '4', 'verdict' => 'same', 'because' => 'Nothing changes', 'backup_first' => false, 'refused' => true],
            ['service' => 'radarr', 'existing' => '5', 'ours' => '5', 'verdict' => 'same', 'because' => 'Nothing changes', 'backup_first' => true, 'refused' => false],
        ],
        'not_carried' => [$limit, $limit],
        'modes' => [
            ['mode' => 'adopt', 'what' => 'Manages what is here', 'disturbs' => false, 'preselected' => true],
            ['mode' => 'replace', 'what' => 'Stops the old one', 'disturbs' => true, 'preselected' => false],
        ],
        'beside' => [['service' => 'sonarr', 'from' => 8989, 'to' => 8990], ['service' => 'radarr', 'from' => 7878, 'to' => 7879]],
        'linking' => [
            'links' => false,
            'because' => 'Two filesystems',
            'cost' => 'A second copy of each import',
            'remedy' => 'One mount for both',
            'filesystems' => ['ext4', 'nfs'],
            'forced' => false,
        ],
    ];
}

/**
 * That survey with one value replaced, or taken away where the value is `absent`.
 *
 * @param array<mixed>     $data
 * @param list<int|string> $path
 * @return array<mixed>
 */
function aSurveyWith(array $data, array $path, mixed $value): array
{
    $key = $path[0];

    if (count($path) > 1) {
        $inside = $data[$key];
        $data[$key] = aSurveyWith(is_array($inside) ? $inside : [], array_slice($path, 1), $value);

        return $data;
    }

    if ($value === 'absent') {
        unset($data[$key]);

        return $data;
    }

    $data[$key] = $value;

    return $data;
}

/** One word carried out of an arm. */
final readonly class WhatTheSurveyCarried
{
    public function __construct(public string $said) {}
}

/** Every port a service publishes, as one line. */
function thePortsRead(ThePortsItPublishes $ports): string
{
    $said = [];

    foreach ($ports as $port) {
        $said[] = sprintf('%d', $port);
    }

    return implode(',', $said);
}

/**
 * Every service of every project, a line each.
 *
 * @return list<string>
 */
function everyServiceRead(TheSurvey $survey): array
{
    $lines = [];

    foreach ($survey->standing() as $project) {
        foreach ($project as $service) {
            $lines[] = sprintf('%s/%s|%s|%s|%s', $project->project(), $service->service(), thePortsRead($service->ports()), var_export($service->isRunning(), return: true), var_export($service->isAdoptable(), return: true));
        }
    }

    return $lines;
}

/** Everything a survey carries about what is here and adopting it, folded to one line per thing. */
function theSurveyRead(TheSurvey $survey): string
{
    $lines = [var_export($survey->looked(), return: true), ...everyServiceRead($survey)];

    foreach ($survey->carrying() as $one) {
        $lines[] = sprintf('%s|%s|%s', $one->service(), var_export($one->wantsACopyFirst(), return: true), var_export($one->isRefused(), return: true));
    }

    return implode("\n", $lines);
}

it('stands in for a stack with a payload the contract would accept', function (): void {
    expect(WhatTheContractAccepts::complaintsAbout('MigrationEnvelope', ['api_version' => 1, 'kind' => 'migration', 'data' => aSurveyOfTwoOfEverything()]))->toBe([]);
});

it('reads every service of every project, and every flag as it came', function (): void {
    expect(theSurveyRead(WhatIsAlreadyHere::in(surveySaying(aSurveyOfTwoOfEverything()))))->toBe(
        "true\n"
        . "media/sonarr|8989,9898|true|false\n"
        . "media/sonarr|8989,9898|true|false\n"
        . "media/sonarr|8989,9898|true|false\n"
        . "media/sonarr|8989,9898|true|false\n"
        . "sonarr|false|true\n"
        . 'radarr|true|false',
    )->and(theSurveyRead(WhatIsAlreadyHere::in(surveySaying([...aSurveyOfTwoOfEverything(), 'read' => false, 'standing' => []]))))->toStartWith('false');
});

it('reads a layout that the stack reports nothing about, or null, as one that links', function (mixed $linking): void {
    $survey = WhatIsAlreadyHere::in(surveySaying(aSurveyWith(aSurveyOfTwoOfEverything(), ['linking'], $linking)));

    expect($survey->linking()->either(
        costs: static fn(): WhatTheSurveyCarried => new WhatTheSurveyCarried('costs'),
        links: static fn(): WhatTheSurveyCarried => new WhatTheSurveyCarried('links'),
    )->said)->toBe('links');
})->with(['absent', null]);

it('refuses an envelope in a version this app does not read', function (): void {
    expect(fn(): TheSurvey => WhatIsAlreadyHere::in(surveySaying(aSurveyOfTwoOfEverything(), version: 99)))->toThrow(EnvelopeIsNotRead::class);
});

it('refuses a payload with no data', function (): void {
    expect(fn(): TheSurvey => WhatIsAlreadyHere::in(surveySaying('nothing')))->toThrow(MigrationIsUnreadable::class, 'no readable `data`');
});

it('refuses a field of the survey itself that is missing or not what the contract says', function (string $field, mixed $said): void {
    expect(fn(): TheSurvey => WhatIsAlreadyHere::in(surveySaying(aSurveyWith(aSurveyOfTwoOfEverything(), [$field], $said))))
        ->toThrow(MigrationIsUnreadable::class, sprintf('The migration envelope has no readable `%s`', $field));
})->with([
    ['read', 'absent'], ['read', 'yes'], ['standing', 'absent'], ['standing', 'everything'], ['conflicts', 'absent'],
    ['unsupported', 'nothing'], ['carrying', 'absent'], ['not_carried', 'nothing'], ['modes', 'absent'],
    ['beside', 'nothing'], ['linking', 'spoiled'],
]);

it('refuses an entry that does not say what it owes, naming the list, the second entry and the field', function (string $list, string $field, mixed $said): void {
    expect(fn(): TheSurvey => WhatIsAlreadyHere::in(surveySaying(aSurveyWith(aSurveyOfTwoOfEverything(), [$list, 1, $field], $said))))
        ->toThrow(MigrationIsUnreadable::class, sprintf('Entry 1 of `%s` in the migration envelope has no readable `%s`', $list, $field));
})->with([
    ['conflicts', 'port', 'eighty'], ['conflicts', 'wanted_by', ' '], ['conflicts', 'held_by', 'absent'],
    ['unsupported', 'what', ' '], ['unsupported', 'because', 7],
    ['not_carried', 'what', 'absent'], ['not_carried', 'because', ''],
    ['modes', 'mode', ' '], ['modes', 'what', 'absent'], ['modes', 'disturbs', 'no'], ['modes', 'preselected', 'absent'],
    ['beside', 'service', ''], ['beside', 'from', '8989'], ['beside', 'to', 'absent'],
    ['carrying', 'service', ' '], ['carrying', 'because', 'absent'], ['carrying', 'backup_first', 1], ['carrying', 'refused', 'absent'],
    ['standing', 'project', ' '], ['standing', 'services', 'absent'],
]);

it('refuses an entry that is not one, naming the list and the first thing it owes', function (string $list, string $field): void {
    expect(fn(): TheSurvey => WhatIsAlreadyHere::in(surveySaying(aSurveyWith(aSurveyOfTwoOfEverything(), [$list, 1], 'spoiled'))))
        ->toThrow(MigrationIsUnreadable::class, sprintf('Entry 1 of `%s` in the migration envelope has no readable `%s`', $list, $field));
})->with([
    ['standing', 'project'], ['conflicts', 'port'], ['unsupported', 'what'], ['not_carried', 'what'],
    ['modes', 'mode'], ['beside', 'service'], ['carrying', 'service'],
]);

it('refuses a service that does not say what it owes, naming its project and its place in it', function (string $field, mixed $said): void {
    $path = $field === 'the row' ? ['standing', 1, 'services', 1] : ['standing', 1, 'services', 1, $field];

    expect(fn(): TheSurvey => WhatIsAlreadyHere::in(surveySaying(aSurveyWith(aSurveyOfTwoOfEverything(), $path, $said))))
        ->toThrow(MigrationIsUnreadable::class, sprintf('Service 1 of project 1 in the migration envelope has no readable `%s`', $field === 'the row' ? 'service' : $field));
})->with([
    ['the row', 'sonarr'], ['service', ' '], ['ports', 'absent'], ['ports', [8989, '9898']], ['running', 'yes'], ['adoptable', 'absent'],
]);

it('refuses a layout cost that does not say why, what, how to fix it or where', function (string $field, mixed $said): void {
    expect(fn(): TheSurvey => WhatIsAlreadyHere::in(surveySaying(aSurveyWith(aSurveyOfTwoOfEverything(), ['linking', $field], $said))))
        ->toThrow(MigrationIsUnreadable::class, sprintf('The migration envelope has no readable `linking.%s`', $field));
})->with([
    ['because', ' '], ['cost', 'absent'], ['remedy', 7], ['filesystems', 'ext4'], ['filesystems', ['ext4', ' ']], ['filesystems', ['ext4', 7]],
]);
