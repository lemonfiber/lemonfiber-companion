<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Asking a stack what it can mark as new: its releases, its requests and what is wrong.
 *
 * A read. It changes nothing, and takes a stack and a session rather than a
 * client, for the reason {@see Asking} gives. What anybody has seen is not the
 * stack's to know, so the stack answers with everything it orders and the phone
 * decides what is new.
 */
interface ReadingNews
{
    /**
     * Ask a stack what it lists, or come away with a reason.
     */
    public function newsOn(Stack $stack, Session $session): WhatWasFoundOfTheNews;
}
