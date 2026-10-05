<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * One service filling one capability, as the stack worked it out.
 *
 * The answer to choosing a filler, whether it was only read or also made:
 * the capability, the service that would answer it, the one that answers it
 * now, every service that asks for it, what the change would leave unfilled,
 * the reason given, and the name the reading goes by. The stack works all of
 * it out before anything is written, so an operator agrees with every
 * consequence in front of them.
 *
 * **The agreement names this reading and no other.** The stack builds it from
 * the choice, what fills it now, what asks for it and what it would leave
 * unfilled, so a yes carrying it is a yes to exactly those; a reading that
 * has moved since is refused by the stack rather than carried out. Blank is
 * refused here, because a reading nobody can name is one nobody can agree to.
 */
final readonly class AFill
{
    private function __construct(
        private Capability $capability,
        private ServiceId $now,
        private Services $was,
        private Services $askedBy,
        private WhatNothingFills $leaves,
        private string $why,
        private string $agreement,
        private bool $made,
    ) {}

    /**
     * A choice the stack worked out and wrote nothing for.
     *
     * `$was` is none where nothing answers the capability now, and `$why` is
     * blank where nobody gave a reason.
     */
    public static function read(Capability $capability, ServiceId $now, Services $was, Services $askedBy, WhatNothingFills $leaves, string $why, string $agreement): self
    {
        return new self($capability, $now, $was, $askedBy, $leaves, $why, self::named($agreement), made: false);
    }

    /** A choice the stack worked out and made, with the reason it recorded. */
    public static function made(Capability $capability, ServiceId $now, Services $was, Services $askedBy, WhatNothingFills $leaves, string $why, string $agreement): self
    {
        return new self($capability, $now, $was, $askedBy, $leaves, $why, self::named($agreement), made: true);
    }

    /** The capability being filled. */
    public function capability(): Capability
    {
        return $this->capability;
    }

    /** The service that answers it once the choice is made. */
    public function now(): ServiceId
    {
        return $this->now;
    }

    /** What answers it before the choice, which is none where nothing does. */
    public function was(): Services
    {
        return $this->was;
    }

    /** Every service that asks for the capability, in the stack's order. */
    public function askedBy(): Services
    {
        return $this->askedBy;
    }

    /** What the choice would leave unfilled, each with the service that would lose it. */
    public function leaves(): WhatNothingFills
    {
        return $this->leaves;
    }

    /** The reason given for the choice, or blank where none was. */
    public function why(): string
    {
        return $this->why;
    }

    /** What this reading is called, so a yes can name it. */
    public function agreement(): string
    {
        return $this->agreement;
    }

    /** Whether the stack made the choice, rather than only working it out. */
    public function wasMade(): bool
    {
        return $this->made;
    }

    /** The name a reading goes by, refused where it is blank. */
    private static function named(string $agreement): string
    {
        if (trim($agreement) === '') {
            throw FillSaysNothing::about('agreement');
        }

        return $agreement;
    }
}
