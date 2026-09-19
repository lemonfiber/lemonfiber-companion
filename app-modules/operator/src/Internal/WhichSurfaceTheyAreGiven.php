<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

/**
 * Which of this product's two applications a person is handed, carried out of an
 * `either()` arm.
 *
 * {@see \Modules\Kernel\Api\Whose::either()} answers with an object, so that a caller
 * cannot read a subject out without saying what happens for the operator. A screen
 * still wants to know which of two roads to offer, and this is the smallest honest
 * way across: it names the surface and carries no subject, and an enum case is an
 * object, so the arms may build one.
 *
 * **Backed, because a screen holds one between frames.** It is the shape
 * {@see \Modules\Connection\Api\HowTheSignInWent} already has and for the same
 * reason: what the stack said about whose session it opened is learned once, at the
 * tap, and a screen redrawn afterwards must answer the same way it did the first
 * time.
 *
 * **The surface is decided here and the path is written elsewhere**, which is the
 * whole reason this is two steps. The decision is about a person — the operator
 * reads the machine, a member reads what the machine owes them — and the path is
 * about a route, which {@see WhereAStackIs} owns for every screen a stack has. Naming
 * a path in these arms would put a second spelling of one beside the type that exists
 * to be the only spelling, and it would put it where the rule that walks this app's
 * navigation cannot see it: that walk reads an accessor's own body for the routes it
 * calls, so a road built behind a value object is a road it reports as leading
 * nowhere.
 *
 * `Internal` because it is a detail of how this surface reads an identity, and `E2`'s
 * promise is that anything here can be renamed without reading another module.
 */
enum WhichSurfaceTheyAreGiven: string
{
    /**
     * The machine's own report, which is what the operator came for.
     *
     * They are given the household's application as well, not instead: nothing here
     * takes a member's reading away from them, and the road to it is the one
     * {@see WhereAStackIs::yours()} already offers from this surface.
     */
    case TheReport = 'the_report';

    /**
     * What this machine says they are owed, which is a member's whole application.
     *
     * Not the report. That is the operator's reading of the machine — a verdict, its
     * services and what it would put right — and a member is never shown diagnostics.
     * Handing them there would be this app deciding to show what the core would
     * refuse, one screen before the core was asked anything.
     */
    case WhatTheyAreOwed = 'what_they_are_owed';
}
