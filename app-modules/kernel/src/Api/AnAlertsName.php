<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function hash;
use function sprintf;
use function trim;

/**
 * What names one alert: the same on its onset and on the resolution that ends it, and each stack's own.
 *
 * It is never shown. All it answers is the key a notification of it is filed
 * as, together with the stack that raised it, so one stack's alert can only
 * ever replace the same alert of the same stack.
 */
final readonly class AnAlertsName
{
    private function __construct(private string $name) {}

    /** The name the core gave the alert. */
    public static function of(string $name): self
    {
        $trimmed = trim($name);

        if ($trimmed === '') {
            throw CodeIsBlank::inAnAlert();
        }

        return new self($trimmed);
    }

    /** An alert with no name of its own, named by its kind, as the core's alerts were before they carried one. */
    public static function byItsKind(WhatTheCoreDecided $kind): self
    {
        return new self($kind->shown());
    }

    /** What a stack's one notification of having more to tell is filed under: a name no alert can have, since a blank one is refused. */
    public static function beyondTheCap(): self
    {
        return new self('');
    }

    /** The key it is filed as among every stack's notifications: digested with the stack, so the key holds neither in the clear. */
    public function filedFor(StackId $stack): string
    {
        return hash('sha256', sprintf('%s|%s', $stack->stored(), $this->name));
    }
}
