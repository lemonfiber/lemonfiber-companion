<?php

declare(strict_types=1);

use Modules\Operator\View\Components\Action;

// F5 — what a control answers to, which is not always what it draws.
//
// A frame drawing four rows of *Start it* gives a reader one name for four
// controls, and which one each acts on is carried by where it sits. The
// component takes a fuller name for those, and falls back to the drawn words
// for the ordinary case — a screen with one *Sign in* on it needs no second
// spelling of it.
//
// Asserted on the component rather than on a rendered frame because the
// fallback is a decision with two arms and only one of them is on any screen
// today: every site that passes `answersTo` passes a non-empty one, so a
// version of this that always took the drawn label would draw every screen
// exactly as it does now.

it('F5 — a control answers to its drawn words where it is given no name of its own', function (): void {
    expect(new Action(label: 'Start it')->named)->toBe('Start it');
});

it('F5 — and to the fuller name where it is', function (): void {
    expect(new Action(label: 'Start it', answersTo: 'Start Sonarr')->named)->toBe('Start Sonarr');
});
