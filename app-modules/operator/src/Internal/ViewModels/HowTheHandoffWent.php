<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * Where handing a device over has got to, as the fields a screen draws.
 *
 * One arm at a time: working, the stack's answer, its refusal, or what stood
 * in the way. None of them is nothing asked yet, which is the screen's opening.
 */
final readonly class HowTheHandoffWent
{
    /**
     * @param WhereTheWorkStands $stands      where the work stands, and whether the stack could be asked at all
     * @param string|null       $refused   the stack's refusal in its own words, or null where it did not refuse
     * @param ?AHandoffAsShown  $handoff   where it stands, once the stack said
     */
    public function __construct(
        public WhereTheWorkStands $stands,
        public ?string $refused,
        public ?AHandoffAsShown $handoff,
    ) {}
}
