<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * Whether taking lemonfiber off removed anything on this run, and what became of it.
 *
 * Four states. **Surveyed** is a reading and nothing removed. **Rehearsed** is
 * the tier and the reading agreed to with nothing touched. **Complete** is
 * everything the reading named as going gone, and **partial** is some of it
 * left, each named with how to finish by hand. Partial is never complete, and
 * both say which credentials they destroyed.
 */
final readonly class WhereTakingItOffGot
{
    private function __construct(
        private bool $surveyed,
        private ?NamedOnTheManifest $gone = null,
        private ?NamedOnTheManifest $credentials = null,
        private ?WhatWasLeftBehind $left = null,
    ) {}

    /** Everything is listed with its size, and nothing has been removed. */
    public static function surveyed(): self
    {
        return new self(surveyed: true);
    }

    /** The tier and the reading were agreed to, and this run changed nothing. */
    public static function rehearsed(): self
    {
        return new self(surveyed: false);
    }

    /** Everything the reading named as going is gone. */
    public static function complete(NamedOnTheManifest $gone, NamedOnTheManifest $credentials): self
    {
        return new self(surveyed: false, gone: $gone, credentials: $credentials);
    }

    /** Some of it could not be removed, and this is what is left. */
    public static function partial(NamedOnTheManifest $gone, NamedOnTheManifest $credentials, WhatWasLeftBehind $left): self
    {
        return new self(surveyed: false, gone: $gone, credentials: $credentials, left: $left);
    }

    /** Whether this is a reading, which is the only answer a removal can be agreed against. */
    public function isAReading(): bool
    {
        return $this->surveyed;
    }

    /**
     * Say what happens in each case, and get back what you built.
     *
     * @template TSurveyed of object
     * @template TRehearsed of object
     * @template TComplete of object
     * @template TPartial of object
     *
     * @param Closure(): TSurveyed                                                  $surveyed
     * @param Closure(): TRehearsed                                                 $rehearsed
     * @param Closure(NamedOnTheManifest, NamedOnTheManifest): TComplete            $complete
     * @param Closure(NamedOnTheManifest, NamedOnTheManifest, WhatWasLeftBehind): TPartial $partial
     *
     * @return TSurveyed|TRehearsed|TComplete|TPartial
     */
    public function either(Closure $surveyed, Closure $rehearsed, Closure $complete, Closure $partial): object
    {
        return match (true) {
            $this->surveyed => $surveyed(),
            $this->gone instanceof NamedOnTheManifest && $this->credentials instanceof NamedOnTheManifest && $this->left instanceof WhatWasLeftBehind
                => $partial($this->gone, $this->credentials, $this->left),
            $this->gone instanceof NamedOnTheManifest && $this->credentials instanceof NamedOnTheManifest => $complete($this->gone, $this->credentials),
            default => $rehearsed(),
        };
    }
}
