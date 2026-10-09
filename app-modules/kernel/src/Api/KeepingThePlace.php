<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Telling a stack where a member is in what they are watching.
 *
 * The core keeps a member's place, so a title is picked up where it was left
 * on any device, and the part-way row is the core's answer rather than a copy
 * kept here. Told as it happens and never held back: a place that could not be
 * told is dropped, and the next one says the same, later.
 */
interface KeepingThePlace
{
    /** Tell the stack where the signed-in member is, and answer whether it kept it. */
    public function keep(Stack $stack, Session $session, ThePlace $place): WhatThePlaceCameTo;
}
