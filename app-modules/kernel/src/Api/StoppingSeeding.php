<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Asking a stack what stopping seeding one download would cost, telling it to,
 * and asking what became of each.
 *
 * One conversation, so one port, for the reason {@see KeepingCurrent} gives.
 * Four methods for {@see Mending}'s reason: the stack answers the offer and the
 * yes each as a job, and a port that waited for either would be a screen that
 * freezes on a machine that may be asleep.
 *
 * **Stopping seeding takes the offer, which only the stack produces.**
 * {@see WhatLettingItGoCosts} is built from what the stack said letting the
 * download go would cost, and the yes quotes the offer's name, so a download
 * nobody was shown the cost of cannot reach this port. There is no blanket yes
 * here, because the stack takes none.
 */
interface StoppingSeeding
{
    /**
     * Ask what stopping seeding that download would cost, changing nothing.
     *
     * Answers {@see Underway}: the stack names the work of reading the offer,
     * and {@see whatTheOfferCameTo()} reads it.
     */
    public function whatItWouldCost(Stack $stack, Session $session, ADownloadHeld $download): Underway;

    /** What became of asking, by the handle it answered. */
    public function whatTheOfferCameTo(Stack $stack, Session $session, Job $job): HowTheOfferToLetGoIsGoing;

    /**
     * Stop seeding the download as offered, or come away with a reason.
     *
     * Answers {@see Underway}: the stack takes the work on and hands back
     * something to follow it by.
     */
    public function stop(Stack $stack, Session $session, WhatLettingItGoCosts $offer): Underway;

    /** What became of stopping, by the handle agreeing answered. */
    public function whatBecameOf(Stack $stack, Session $session, Job $job): HowLettingItGoIsGoing;
}
