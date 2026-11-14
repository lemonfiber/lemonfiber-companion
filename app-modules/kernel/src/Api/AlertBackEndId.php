<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/** What the core names one back-end that delivers alerts by. */
final readonly class AlertBackEndId implements WhatAScreenIsAbout
{
    private function __construct(private string $named) {}

    /** One back-end, named as the core names it. */
    public static function called(string $backEnd): self
    {
        if (trim($backEnd) === '') {
            throw WhereAlertsGoSaysNothing::about('a back-end\'s id');
        }

        return new self($backEnd);
    }

    /** The id, exactly as the core spelled it. */
    public function named(): string
    {
        return $this->named;
    }

    /** Whether this names the same back-end as another. */
    public function isTheSameAs(self $other): bool
    {
        return $this->named === $other->named;
    }

    public function namedInAPath(): string
    {
        return $this->named;
    }
}
