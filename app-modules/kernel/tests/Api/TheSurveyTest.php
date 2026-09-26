<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function array_keys;
use function expect;
use function implode;
use function it;
use function iterator_to_array;

use Modules\Kernel\Api\AMode;
use Modules\Kernel\Api\APortHeld;
use Modules\Kernel\Api\APortMoved;
use Modules\Kernel\Api\AProjectStanding;
use Modules\Kernel\Api\AServiceStanding;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\TheModes;
use Modules\Kernel\Api\ThePortsHeld;
use Modules\Kernel\Api\ThePortsItPublishes;
use Modules\Kernel\Api\ThePortsMoved;
use Modules\Kernel\Api\TheSurvey;
use Modules\Kernel\Api\TheSurveySaysNothing;
use Modules\Kernel\Api\WhatAdoptingOneWouldDo;
use Modules\Kernel\Api\WhatAdoptingWouldDo;
use Modules\Kernel\Api\WhatIsUnsupported;
use Modules\Kernel\Api\WhatLinkingCosts;
use Modules\Kernel\Api\WhatStandsHere;
use Modules\Kernel\Api\WhatWasFoundAlreadyHere;

use function sprintf;

/** One line carried out of an arm. */
final readonly class WhichArmTheSurveyTook
{
    public function __construct(public string $said) {}
}

/** What a layout costs, folded to one line. */
function whatTheLayoutCosts(WhatLinkingCosts $linking): string
{
    return $linking->either(
        costs: static fn(string $because, string $cost, string $remedy, array $filesystems): WhichArmTheSurveyTook => new WhichArmTheSurveyTook(sprintf('%s|%s|%s|%s|%s', $because, $cost, $remedy, implode(',', $filesystems), implode(',', array_keys($filesystems)))),
        links: static fn(): WhichArmTheSurveyTook => new WhichArmTheSurveyTook('links'),
    )->said;
}

/** A survey that found nothing, looked or not. */
function aSurveyThatFoundNothing(bool $looked): TheSurvey
{
    return TheSurvey::reported(
        looked: $looked,
        standing: WhatStandsHere::of(),
        conflicts: ThePortsHeld::of(),
        unsupported: WhatIsUnsupported::none(),
        modes: TheModes::of(),
        beside: ThePortsMoved::of(),
        linking: WhatLinkingCosts::nothing(),
        carrying: WhatAdoptingWouldDo::of(),
        notCarried: WhatIsUnsupported::none(),
    );
}

it('keeps everything it was reported with, and whether it looked', function (): void {
    $standing = WhatStandsHere::of();
    $conflicts = ThePortsHeld::of();
    $unsupported = WhatIsUnsupported::none();
    $modes = TheModes::of();
    $beside = ThePortsMoved::of();
    $linking = WhatLinkingCosts::nothing();
    $carrying = WhatAdoptingWouldDo::of();
    $notCarried = WhatIsUnsupported::none();
    $survey = TheSurvey::reported(looked: true, standing: $standing, conflicts: $conflicts, unsupported: $unsupported, modes: $modes, beside: $beside, linking: $linking, carrying: $carrying, notCarried: $notCarried);

    expect([$survey->looked(), $survey->standing(), $survey->conflicts(), $survey->unsupported(), $survey->modes(), $survey->beside(), $survey->linking(), $survey->carrying(), $survey->notCarried()])
        ->toBe([true, $standing, $conflicts, $unsupported, $modes, $beside, $linking, $carrying, $notCarried])
        ->and(aSurveyThatFoundNothing(looked: false)->looked())->toBeFalse();
});

it('keeps a project by its name, with its services in the stack\'s order', function (): void {
    $project = AProjectStanding::named('media', ...[
        'first' => AServiceStanding::found('sonarr', ThePortsItPublishes::of(8989), running: true, adoptable: true),
        'second' => AServiceStanding::found('tautulli', ThePortsItPublishes::of(), running: false, adoptable: false),
    ]);
    $named = [];

    foreach ($project as $service) {
        $named[] = $service->service();
    }

    expect($project->project())->toBe('media')
        ->and($named)->toBe(['sonarr', 'tautulli'])
        ->and(array_keys(iterator_to_array($project, preserve_keys: true)))->toBe([0, 1])
        ->and($project)->toHaveCount(2)
        ->and(fn(): AProjectStanding => AProjectStanding::named(' '))->toThrow(TheSurveySaysNothing::class, 'arrived with its `project` blank');
});

it('keeps a service\'s ports, whether it runs and whether it could be taken over', function (): void {
    $ports = ThePortsItPublishes::of(...['web' => 8989, 'api' => 9898]);
    $service = AServiceStanding::found('sonarr', $ports, running: true, adoptable: false);

    expect([$service->service(), $service->ports(), $service->isRunning(), $service->isAdoptable()])->toBe(['sonarr', $ports, true, false])
        ->and(iterator_to_array($ports, preserve_keys: true))->toBe([0 => 8989, 1 => 9898])
        ->and($ports)->toHaveCount(2)
        ->and(AServiceStanding::found('tautulli', ThePortsItPublishes::of(), running: false, adoptable: true)->isRunning())->toBeFalse()
        ->and(AServiceStanding::found('tautulli', ThePortsItPublishes::of(), running: false, adoptable: true)->isAdoptable())->toBeTrue()
        ->and(fn(): AServiceStanding => AServiceStanding::found(' ', $ports, running: true, adoptable: true))->toThrow(TheSurveySaysNothing::class, '`service`');
});

it('keeps every project in the stack\'s order', function (): void {
    $standing = WhatStandsHere::of(...['a' => AProjectStanding::named('media'), 'b' => AProjectStanding::named('books')]);
    $named = [];

    foreach ($standing as $project) {
        $named[] = $project->project();
    }

    expect($named)->toBe(['media', 'books'])
        ->and(array_keys(iterator_to_array($standing, preserve_keys: true)))->toBe([0, 1])
        ->and($standing)->toHaveCount(2);
});

it('names what holds a port, and refuses a conflict that will not say who wants it or what holds it', function (): void {
    $held = APortHeld::of(8989, 'sonarr', 'media');
    $conflicts = ThePortsHeld::of(...['a' => $held, 'b' => APortHeld::of(7878, 'radarr', 'media')]);

    expect([$held->port(), $held->wantedBy(), $held->heldBy()])->toBe([8989, 'sonarr', 'media'])
        ->and(array_keys(iterator_to_array($conflicts, preserve_keys: true)))->toBe([0, 1])
        ->and($conflicts)->toHaveCount(2)
        ->and(fn(): APortHeld => APortHeld::of(8989, ' ', 'media'))->toThrow(TheSurveySaysNothing::class, '`wanted_by`')
        ->and(fn(): APortHeld => APortHeld::of(8989, 'sonarr', ''))->toThrow(TheSurveySaysNothing::class, '`held_by`');
});

it('keeps where a moved service would be reached, and refuses one that is not named', function (): void {
    $moved = APortMoved::of('sonarr', 8989, 8990);
    $beside = ThePortsMoved::of(...['a' => $moved, 'b' => APortMoved::of('radarr', 7878, 7879)]);

    expect([$moved->service(), $moved->from(), $moved->to()])->toBe(['sonarr', 8989, 8990])
        ->and(array_keys(iterator_to_array($beside, preserve_keys: true)))->toBe([0, 1])
        ->and($beside)->toHaveCount(2)
        ->and(fn(): APortMoved => APortMoved::of(' ', 8989, 8990))->toThrow(TheSurveySaysNothing::class, '`service`');
});

it('keeps each mode in the stack\'s order, with what it comes to and whether it disturbs', function (): void {
    $adopt = AMode::offered('adopt', 'Keeps what is here', disturbs: false, preselected: true);
    $modes = TheModes::of(...['a' => $adopt, 'b' => AMode::offered('import', 'Copies it across', disturbs: false, preselected: false)]);

    expect([$adopt->mode(), $adopt->what(), $adopt->disturbs(), $adopt->isPreselected()])->toBe(['adopt', 'Keeps what is here', false, true])
        ->and(AMode::offered('import', 'Copies it across', disturbs: false, preselected: false)->isPreselected())->toBeFalse()
        ->and(AMode::offered('replace', 'Stops the old one', disturbs: true, preselected: false)->disturbs())->toBeTrue()
        ->and(array_keys(iterator_to_array($modes, preserve_keys: true)))->toBe([0, 1])
        ->and($modes)->toHaveCount(2)
        ->and(fn(): AMode => AMode::offered(' ', 'Keeps what is here', disturbs: false, preselected: true))->toThrow(TheSurveySaysNothing::class, '`mode`')
        ->and(fn(): AMode => AMode::offered('adopt', '', disturbs: false, preselected: true))->toThrow(TheSurveySaysNothing::class, '`what`');
});

it('never offers a mode that disturbs what is running as already chosen, whatever the stack marked', function (): void {
    expect(AMode::offered('replace', 'Stops the old one', disturbs: true, preselected: true)->isPreselected())->toBeFalse();
});

it('keeps what adopting a service would come to, and refuses one that will not say which or what it means', function (): void {
    $one = WhatAdoptingOneWouldDo::said('sonarr', 'Its database is opened by a newer version', backupFirst: true, refused: false);
    $carrying = WhatAdoptingWouldDo::of(...['a' => $one, 'b' => WhatAdoptingOneWouldDo::said('radarr', 'Nothing changes', backupFirst: false, refused: true)]);

    expect([$one->service(), $one->because(), $one->wantsACopyFirst(), $one->isRefused()])->toBe(['sonarr', 'Its database is opened by a newer version', true, false])
        ->and(WhatAdoptingOneWouldDo::said('radarr', 'Nothing changes', backupFirst: false, refused: true)->wantsACopyFirst())->toBeFalse()
        ->and(WhatAdoptingOneWouldDo::said('radarr', 'Nothing changes', backupFirst: false, refused: true)->isRefused())->toBeTrue()
        ->and(array_keys(iterator_to_array($carrying, preserve_keys: true)))->toBe([0, 1])
        ->and($carrying)->toHaveCount(2)
        ->and(fn(): WhatAdoptingOneWouldDo => WhatAdoptingOneWouldDo::said(' ', 'Nothing changes', backupFirst: false, refused: false))->toThrow(TheSurveySaysNothing::class, '`service`')
        ->and(fn(): WhatAdoptingOneWouldDo => WhatAdoptingOneWouldDo::said('radarr', ' ', backupFirst: false, refused: false))->toThrow(TheSurveySaysNothing::class, '`because`');
});

it('says what a layout that cannot link costs, and that one which links costs nothing', function (): void {
    expect(whatTheLayoutCosts(WhatLinkingCosts::cannotLink('Two filesystems', 'Twice the room', 'Put both on one', ...['x' => 'ext4', 'y' => 'nfs'])))
        ->toBe('Two filesystems|Twice the room|Put both on one|ext4,nfs|0,1')
        ->and(whatTheLayoutCosts(WhatLinkingCosts::nothing()))->toBe('links');
});

it('refuses a layout cost that will not say why, what, or how to fix it, or names a blank filesystem', function (string $because, string $cost, string $remedy, string $filesystem, string $field): void {
    expect(fn(): WhatLinkingCosts => WhatLinkingCosts::cannotLink($because, $cost, $remedy, 'ext4', $filesystem))->toThrow(TheSurveySaysNothing::class, sprintf('`%s`', $field));
})->with([
    [' ', 'Twice the room', 'Put both on one', 'nfs', 'because'],
    ['Two filesystems', '', 'Put both on one', 'nfs', 'cost'],
    ['Two filesystems', 'Twice the room', ' ', 'nfs', 'remedy'],
    ['Two filesystems', 'Twice the room', 'Put both on one', ' ', 'filesystems'],
]);

it('a survey that could not be read is never a machine with nothing on it', function (): void {
    $fold = static fn(WhatWasFoundAlreadyHere $answer): string => $answer->either(
        found: static fn(TheSurvey $survey): WhichArmTheSurveyTook => new WhichArmTheSurveyTook(sprintf('found:%s', $survey->looked() ? 'looked' : 'could not look')),
        met: static fn(Obstacle $why): WhichArmTheSurveyTook => new WhichArmTheSurveyTook(sprintf('met:%s', $why->value)),
    )->said;

    expect($fold(WhatWasFoundAlreadyHere::found(aSurveyThatFoundNothing(looked: false))))->toBe('found:could not look')
        ->and($fold(WhatWasFoundAlreadyHere::met(Obstacle::StackDidNotAnswer)))->toBe(sprintf('met:%s', Obstacle::StackDidNotAnswer->value));
});
