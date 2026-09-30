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
     * @param HowTheReadingWent $went      whether asking came back, and what stood in the way where it did not
     * @param bool              $isWorking whether the stack is still working it out
     * @param string            $refused   the stack's refusal in its own words, or empty
     * @param ?AHandoffAsShown  $handoff   where it stands, once the stack said
     */
    public function __construct(
        public HowTheReadingWent $went,
        public bool $isWorking,
        public string $refused,
        public ?AHandoffAsShown $handoff,
    ) {}
}
