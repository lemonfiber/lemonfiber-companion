<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use function array_map;
use function implode;

use InvalidArgumentException;
use Modules\Kernel\Api\HowItIsHosted;
use Modules\Kernel\Api\WhatKeepsItRunning;

use function sprintf;

/**
 * The `hosting` envelope did not hold what the contract says it holds.
 *
 * The same refusal {@see StuckIsUnreadable} is, for what a machine keeps
 * running, and for its reason: every one of these is a bug somewhere other than
 * here, and the message names the field and what arrived because that is the
 * only thing that shortens the search. A developer reads it, so it is `sprintf`
 * and never translated (`L1`).
 *
 * **This listing is worth refusing rather than salvaging, and the direction of
 * error is the argument.** A row dropped for being unreadable is one fewer
 * command shown as not coming back — and an operator reading a short list
 * concludes the reboot went better than it did. That is the one wrong answer
 * this surface can give that nobody goes and checks, because everything on the
 * screen agrees with it.
 */
final class HostingIsUnreadable extends InvalidArgumentException
{
    public static function missing(NamesAWireField $field): self
    {
        return new self(sprintf(
            'The hosting envelope has no `%s`, or it is not what the contract says it is. This answer did not come from a lemonfiber of a version this app can read.',
            $field->value,
        ));
    }

    public static function command(int $position): self
    {
        return new self(sprintf(
            'Command %d in the hosting envelope is not a command. It is refused rather than dropped: a listing one row short reads as one fewer thing that did not come back, which is the direction of error nobody goes looking for.',
            $position,
        ));
    }

    public static function said(NamesAWireField $field, int $position): self
    {
        return new self(sprintf(
            'Command %d in the hosting envelope has no readable `%s`. A row missing it asks an operator to decide whether a blank should survive every reboot.',
            $position,
            $field->value,
        ));
    }

    public static function standing(string $said, int $position): self
    {
        // The accepted list comes from the enum rather than from a sentence
        // written here, so a case added to the contract cannot leave this
        // message describing the old vocabulary.
        return new self(sprintf(
            'Command %d in the hosting envelope says it stands at `%s`, and this app reads %s. Guessing which was meant is how a command that is not running gets shown as one that is.',
            $position,
            $said,
            implode(', ', array_map(
                static fn(HowItIsHosted $standing): string => sprintf('`%s`', $standing->value),
                HowItIsHosted::cases(),
            )),
        ));
    }

    public static function manager(string $said): self
    {
        return new self(sprintf(
            'The hosting envelope says this machine is kept by `%s`, and this app reads %s. Guessing which was meant is how a platform that configures nothing gets shown as one that does.',
            $said,
            implode(', ', array_map(
                static fn(WhatKeepsItRunning $keeper): string => sprintf('`%s`', $keeper->value),
                WhatKeepsItRunning::cases(),
            )),
        ));
    }

    /**
     * A machine with no manager arrived with nothing to do instead.
     *
     * Its own sentence rather than {@see self::missing()}'s, because this is
     * not a field that failed to arrive in general — it is the one arm that
     * requires it. *Not available here* with nothing after it is the empty box
     * that reads as *off*, and off is a thing somebody goes looking for a
     * switch for.
     */
    public static function withNothingToDoInstead(): self
    {
        return new self('The hosting envelope says this platform has no service manager and says nothing about what to do instead. That is the one arm where the sentence is the answer, and without it the screen draws the same empty box it draws for off.');
    }

    /**
     * An install or a removal was answered without a readable account of what it did.
     *
     * The field is named for {@see self::missing()}'s reason. An act answered
     * with no account of itself is refused rather than shown as done: *did it
     * happen* is the question an operator asked it.
     */
    public static function changed(NamesAWireField $field): self
    {
        return new self(sprintf(
            'The hosting envelope answering an install or a removal has no readable `%s` in what it changed. An act with no account of itself cannot be shown as having happened.',
            $field->value,
        ));
    }

    /** What a run changed names a command the listing beside it does not carry. */
    public static function unlisted(string $name): self
    {
        return new self(sprintf(
            'The hosting envelope says it acted on `%s`, and no command it lists carries that name. Where that command now stands is the answer an install owes, and there is none to read.',
            $name,
        ));
    }

    /** A removal said it started the command it took back. */
    public static function startedByRemoving(): self
    {
        return new self('The hosting envelope says a removal started the command it took back. Only installing starts anything, and a removal that claims to is not one to report either way.');
    }

    /** Where an installed command writes its words is there and is not a path. */
    public static function output(string $name): self
    {
        return new self(sprintf(
            'The hosting envelope says where `%s` writes its words with something that is not a path. Absent is the stack not saying, and this is neither that nor an answer.',
            $name,
        ));
    }
}
