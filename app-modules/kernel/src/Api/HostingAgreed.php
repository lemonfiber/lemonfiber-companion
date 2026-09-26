<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * Handing one command over, or taking it back, as the operator agreed to it.
 *
 * {@see AgreedTo}'s argument applied to a machine's service manager:
 * {@see Hosting::handOver()} takes one of these, and rendering a listing
 * produces none. What was agreed and what is sent are the same value, so a
 * screen that asked about one command cannot send another.
 */
final readonly class HostingAgreed
{
    private function __construct(
        private HandingOver $doing,
        private string $named,
    ) {}

    /**
     * The operator agreed to this, for the command lemonfiber calls `$named`.
     *
     * The name is lemonfiber's own word for the command, which is what the
     * stack is asked with. A blank one is refused: an install of nothing is
     * one the stack would refuse by name, after the operator had said yes to it.
     */
    public static function to(HandingOver $doing, string $named): self
    {
        $said = trim($named);

        if ($said === '') {
            throw HandoverSaysNothing::where('which command');
        }

        return new self($doing, $said);
    }

    /** Which of the two was agreed to. */
    public function doing(): HandingOver
    {
        return $this->doing;
    }

    /** lemonfiber's name for the command it is about. */
    public function named(): string
    {
        return $this->named;
    }
}
