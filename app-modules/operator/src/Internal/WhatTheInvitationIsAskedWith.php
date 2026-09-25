<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

use Closure;

use function ctype_digit;
use function explode;

use Modules\Kernel\Api\AnInvitationAskedFor;
use Modules\Kernel\Api\TheLibraries;
use Modules\Kernel\Api\WhatBecomesOfUnrated;

use function trim;

/**
 * What the operator typed, as an invitation to ask for, or why it is not one.
 *
 * The fields are text, and this is the one place they become a request: the
 * name trimmed, the libraries split at commas with blanks dropped, an age read
 * as a whole number of years or not at all, and unrated material said or left
 * to the stack. A name left blank, or an age that is not a number, is said on
 * the screen rather than sent — the stack would refuse either, and the
 * operator can put both right without asking it.
 *
 * `Internal` because it is how this surface reads its own fields.
 */
final readonly class WhatTheInvitationIsAskedWith
{
    private function __construct(private AnInvitationAskedFor|string $answer) {}

    /** The four fields as typed. */
    public static function from(string $name, string $libraries, string $age, string $unrated): self
    {
        $named = trim($name);
        $years = trim($age);

        if ($named === '') {
            return new self('stacks.invitation.needs_a_name');
        }

        if ($years !== '' && ! ctype_digit($years)) {
            return new self('stacks.invitation.age_is_a_number');
        }

        $open = TheLibraries::of(...self::named($libraries));
        $asked = $years === ''
            ? AnInvitationAskedFor::for($named, $open)
            : AnInvitationAskedFor::heldToAge((int) $years, $named, $open);
        $choice = WhatBecomesOfUnrated::tryFrom($unrated);

        return new self($choice instanceof WhatBecomesOfUnrated ? $asked->withUnrated($choice) : $asked);
    }

    /**
     * Say what happens in each case, and get back what you built.
     *
     * @template TAsked of object
     * @template TNot of object
     *
     * @param Closure(AnInvitationAskedFor): TAsked $asked
     * @param Closure(string): TNot                 $notAskable given the catalogue key saying why
     *
     * @return TAsked|TNot
     */
    public function either(Closure $asked, Closure $notAskable): object
    {
        return $this->answer instanceof AnInvitationAskedFor ? $asked($this->answer) : $notAskable($this->answer);
    }

    /**
     * The libraries named, each trimmed, with the blanks between commas dropped.
     *
     * @return list<string>
     */
    private static function named(string $libraries): array
    {
        $named = [];

        foreach (explode(',', $libraries) as $library) {
            $trimmed = trim($library);

            if ($trimmed !== '') {
                $named[] = $trimmed;
            }
        }

        return $named;
    }
}
