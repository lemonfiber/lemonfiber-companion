<?php

declare(strict_types=1);

use Modules\Design\Api\TheSmallestTarget;
use Modules\Household\Internal\Presenters\HowTheGuideReads;
use Modules\Household\Internal\ViewModels\WhatACellSays;
use Modules\Kernel\Api\AChannelsGuide;
use Modules\Kernel\Api\AnAiring;
use Modules\Kernel\Api\ChannelId;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\TheGuide;
use Modules\Kernel\Api\WhenItSays;
use Modules\Kernel\Api\Zone;
use Native\Mobile\Edge\TailwindParser;
use Tests\Support\Template;
use Tests\Support\WhatMarkupDraws;

// A target is bigger than the words on it.
//
// The platform paints one button style, so a screen with a second thing to
// offer draws it as text that is tapped. What that costs is a target the size
// of the words: a line of note text with eight points of padding is about
// thirty, against the 44 iOS asks for and the 48 Android does — and these are
// the controls somebody reaches for one-handed when something has gone wrong.
//
// A button is the platform's own and carries its own height; a `pressable` is
// a container this repository draws, so its target is whatever the class list
// says. That is the one this rule reads, through the edge's own parser.
//
// **It asks for a minimum rather than a height.** A row holding two lines is
// already past 48 and setting a fixed height would shrink it; `min-h-` says
// *at least this*, which is what a target is.
//
// **A width bound to a value is measured as drawn.** The guide draws each
// programme as wide as it runs, so its template cannot say how wide a cell
// is: a binding this rule knows is drawn below from every cell a crowded
// guide gives, and any other binding is refused.

/** The larger of the two platforms' minimum targets, in points: Android's 48 over iOS's 44. */
const A_FINGER_NEEDS = TheSmallestTarget::POINTS;

/** Each width a template binds to a value, which the test that draws the guide measures as drawn. */
const MEASURED_AS_DRAWN = ['$cell->width'];

/** Every element a finger operates, which is the one this repository draws. */
const OPERATED_BY_A_FINGER = 'pressable';

/**
 * Every tappable element a template draws, as the tag it is written as.
 *
 * Read off the composed source, so a screen that moved its rows into a
 * component is a screen this still reads — six rules in this suite have gone
 * quiet that way.
 *
 * @return list<string>
 */
function everyTargetIn(Template $template): array
{
    preg_match_all(
        sprintf('/<native:%s\b.*?>/s', OPERATED_BY_A_FINGER),
        $template->composedSource(),
        $found,
    );

    return $found[0];
}

/**
 * What the edge draws a target's class list as.
 *
 * @return array<mixed>
 */
function whatTheEdgeDraws(string $tag): array
{
    return preg_match('/\sclass="([^"]*)"/', $tag, $class) === 1 ? TailwindParser::parse($class[1]) : [];
}

/** A measure the edge drew, in points, or nothing where it drew none a finger could use. */
function inPoints(mixed $measure): float
{
    if (is_int($measure) || is_float($measure)) {
        return (float) $measure;
    }

    return 0.0;
}

/** Whether a finger can hit a target the edge draws so. */
function aFingerCanHit(string $tag): bool
{
    if (preg_match('/\s:width="([^"]*)"/', $tag, $bound) === 1 && ! in_array($bound[1], MEASURED_AS_DRAWN, strict: true)) {
        return false;
    }

    $drawn = whatTheEdgeDraws($tag);

    return inPoints($drawn['minHeight'] ?? null) >= A_FINGER_NEEDS
        && inPoints($drawn['width'] ?? A_FINGER_NEEDS) >= A_FINGER_NEEDS;
}

it('every tappable element is drawn at least as tall and as wide as a finger needs', function (): void {
    $small = [];
    $looked = 0;

    foreach (Template::all() as $template) {
        foreach (everyTargetIn($template) as $tag) {
            $looked++;

            if (! aFingerCanHit($tag)) {
                $small[] = sprintf('%s — %s', $template->path, trim(preg_replace('/\s+/', ' ', $tag) ?? ''));
            }
        }
    }

    expect($looked)->toBeGreaterThan(2, 'no tappable element was found, so this rule read nothing');

    expect($small)->toBe([], sprintf(
        "These are tapped and drawn smaller than a finger needs:\n  %s\n\n"
        . 'Give each a `min-h-` of at least 48 the edge can read — `min-h-12` is 48, which clears both '
        . 'platform minima — and no fixed width under it. Enlarge the target around the words rather '
        . "than making the words bigger.\n",
        implode("\n  ", $small),
    ));
});

it('reads a target too short, too narrow or declaring no floor as one a finger cannot hit', function (string $tag): void {
    expect(aFingerCanHit($tag))->toBeFalse();
})->with([
    'no floor' => ['<native:pressable class="w-full py-2">'],
    'a floor too low' => ['<native:pressable class="w-full min-h-10">'],
    'too narrow' => ['<native:pressable class="w-10 min-h-12">'],
    'no class at all' => ['<native:pressable a11y-label="Open">'],
    'a width bound to a value nothing measures' => ['<native:pressable class="min-h-12" :width="$row->width">'],
]);

it('reads a target at a finger\'s size or past it as one a finger can hit', function (string $tag): void {
    expect(aFingerCanHit($tag))->toBeTrue();
})->with([
    'a full-width row' => ['<native:pressable class="w-full min-h-12 py-2">'],
    'a fixed width past it' => ['<native:pressable class="w-12 min-h-12">'],
    'a taller floor' => ['<native:pressable class="w-28 h-16 min-h-16">'],
]);

/** Ten past eight in the evening, UTC: the grid starts at eight. */
const A_CROWDED_EVENING = 1_893_442_200;

/** A programme from so many minutes after eight to so many. */
function onAt(string $title, int $starts, int $ends): AnAiring
{
    return AnAiring::of($title, Instant::atEpochSeconds(A_CROWDED_EVENING + (($starts - 10) * 60)), Instant::atEpochSeconds(A_CROWDED_EVENING + (($ends - 10) * 60)), '');
}

/** One channel of a crowded guide: an edge at the grid's start, then programmes of every length from a minute up with gaps between, its guide ending mid-programme. */
function aCrowdedChannel(int $shortest): AChannelsGuide
{
    $airings = [onAt('began before', -30, $shortest % 5)];
    $at = $shortest % 5;

    foreach (range(0, 15) as $one) {
        $length = (($one * $shortest) % 13) + 1;
        $gap = ($one + $shortest) % 4 === 0 ? ($one % 3) + 1 : 0;
        $airings[] = onAt(sprintf('%d-%d', $shortest, $one), $at + $gap, $at + $gap + $length);
        $at += $gap + $length;
    }

    return AChannelsGuide::of(ChannelId::called(sprintf('c%d', $shortest)), sprintf('Channel %d', $shortest), '', WhenItSays::at(Instant::atEpochSeconds(A_CROWDED_EVENING + (($at - 10 - ($shortest % 3)) * 60))), ...$airings);
}

/**
 * Every cell of a guide as crowded as one gets.
 *
 * @return list<WhatACellSays>
 */
function everyCellOfACrowdedGuide(): array
{
    $cells = [];
    $guide = TheGuide::of(...array_map(aCrowdedChannel(...), range(1, 12)));

    foreach (new HowTheGuideReads(Zone::utc(), Instant::atEpochSeconds(A_CROWDED_EVENING))->these($guide)->rows as $row) {
        $cells = [...$cells, ...$row->cells];
    }

    return $cells;
}

/**
 * Every pressable in a drawn tree, as its layout.
 *
 * @param array<array-key, mixed> $node
 *
 * @return list<array<array-key, mixed>>
 */
function everyPressableDrawnIn(array $node): array
{
    $found = ($node['type'] ?? null) === OPERATED_BY_A_FINGER && is_array($node['layout'] ?? null) ? [$node['layout']] : [];

    foreach (is_array($node['children'] ?? null) ? $node['children'] : [] as $child) {
        if (is_array($child)) {
            $found = [...$found, ...everyPressableDrawnIn($child)];
        }
    }

    return $found;
}

/** @param array<array-key, mixed> $layout */
function drawnBigEnough(array $layout): bool
{
    $width = $layout['width'] ?? 'fill';

    return inPoints($layout['min_height'] ?? null) >= A_FINGER_NEEDS
        && ($width === 'fill' || inPoints($width) >= A_FINGER_NEEDS);
}

it('draws every cell of the guide a finger presses at least as wide and as tall as a finger needs, however crowded the guide', function (): void {
    $small = [];
    $pressed = 0;
    $edges = 0;
    $together = 0;

    foreach (everyCellOfACrowdedGuide() as $cell) {
        if ($cell->isAGap()) {
            continue;
        }

        $drawn = everyPressableDrawnIn(WhatMarkupDraws::drawn('<x-household::guide-cell :cell="$cell" channel="NPO 1" />', ['cell' => $cell]));
        $edges += $cell->isAnEdge && $drawn === [] ? 1 : 0;
        $together += $cell->isSeveral() ? 1 : 0;

        foreach ($cell->together as $programme) {
            $drawn = [...$drawn, ...everyPressableDrawnIn(WhatMarkupDraws::drawn('<x-household::listed-programme :programme="$programme" channel="NPO 1" />', ['programme' => $programme]))];
        }

        foreach ($drawn as $layout) {
            $pressed++;

            if (! drawnBigEnough($layout)) {
                $small[] = sprintf('%s (%s) drawn %s', $cell->title === '' ? $cell->times : $cell->title, $cell->times, json_encode($layout));
            }
        }
    }

    expect($pressed)->toBeGreaterThan(100, 'the crowded guide drew too few targets to measure')
        ->and($edges)->toBeGreaterThan(2, 'the crowded guide drew no edge to leave unpressed')
        ->and($together)->toBeGreaterThan(5, 'the crowded guide drew no programmes together')
        ->and($small)->toBe([], sprintf("These guide targets are drawn smaller than a finger needs:\n  %s\n", implode("\n  ", $small)));
});

it('reads a drawn target too narrow or too short as one a finger cannot hit, and one past both as one it can', function (array $layout, bool $hit): void {
    expect(drawnBigEnough($layout))->toBe($hit);
})->with([
    'too narrow' => [['width' => 40, 'min_height' => 48], false],
    'too short' => [['width' => 120, 'min_height' => 40], false],
    'no floor' => [['width' => 120], false],
    'across its parent' => [['width' => 'fill', 'min_height' => 64], true],
    'a finger exactly' => [['width' => 48, 'min_height' => 48], true],
]);
