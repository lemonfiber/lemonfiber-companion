<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

use function trim;

/**
 * What an invitation is asked with: a person, and what they may watch, in the stack's terms.
 *
 * A name, the libraries they may open, an age above which the media server
 * holds things back, and what becomes of material it has no rating for.
 * Nothing about how the media server stores any of it is asked for.
 *
 * **Only the name is required.** An age limit and a word about unrated
 * material are each either said or left to the stack, and each is read
 * through a fold so an adapter sends what was said and nothing in its place.
 * No library named is every library, which is the stack's reading of it too.
 */
final readonly class AnInvitationAskedFor
{
    private function __construct(
        private string $name,
        private TheLibraries $libraries,
        private ?int $age = null,
        private ?WhatBecomesOfUnrated $unrated = null,
    ) {}

    /** An invitation for this person to these libraries, with no age limit; a blank name is refused. */
    public static function for(string $name, TheLibraries $libraries): self
    {
        return new self(self::named($name), $libraries);
    }

    /** An invitation that holds back what is rated above this age; fewer years than none is refused. */
    public static function heldToAge(int $age, string $name, TheLibraries $libraries): self
    {
        if ($age < 0) {
            throw InvitationSaysNothing::below('age_limit', $age);
        }

        return new self(self::named($name), $libraries, $age);
    }

    /** The same invitation, saying what becomes of material with no rating. */
    public function withUnrated(WhatBecomesOfUnrated $unrated): self
    {
        return new self($this->name, $this->libraries, $this->age, $unrated);
    }

    /** The name they will sign in as. */
    public function name(): string
    {
        return $this->name;
    }

    /** The libraries named; none is every one. */
    public function libraries(): TheLibraries
    {
        return $this->libraries;
    }

    /**
     * The age limit, or that none was set.
     *
     * @template TLimited of object
     * @template TUnlimited of object
     *
     * @param Closure(int): TLimited $upTo
     * @param Closure(): TUnlimited  $none
     *
     * @return TLimited|TUnlimited
     */
    public function age(Closure $upTo, Closure $none): object
    {
        return $this->age === null ? $none() : $upTo($this->age);
    }

    /**
     * What becomes of unrated material, or that it was left to the stack.
     *
     * @template TChosen of object
     * @template TUnsaid of object
     *
     * @param Closure(WhatBecomesOfUnrated): TChosen $chosen
     * @param Closure(): TUnsaid                     $unsaid
     *
     * @return TChosen|TUnsaid
     */
    public function unrated(Closure $chosen, Closure $unsaid): object
    {
        return $this->unrated instanceof WhatBecomesOfUnrated ? $chosen($this->unrated) : $unsaid();
    }

    /** The name, refused where it is blank. */
    private static function named(string $name): string
    {
        if (trim($name) === '') {
            throw InvitationSaysNothing::about('name');
        }

        return $name;
    }
}
