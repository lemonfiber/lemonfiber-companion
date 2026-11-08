<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * Whether the tunnel came back with the stack, and the stack's words for the check that failed where one did.
 *
 * A tunnel nothing checked, for a stack running none or one that did not come
 * up, is never drawn as one that held.
 */
final readonly class TheTunnelAfterAStart
{
    private function __construct(private HowTheTunnelCameBack $came, private string $said) {}

    /** Its checks passed. */
    public static function held(): self
    {
        return new self(HowTheTunnelCameBack::Held, '');
    }

    /** A check of it failed, which the stack names in its own words. */
    public static function notEstablished(string $said): self
    {
        if (trim($said) === '') {
            throw ARestartSaysTooLittle::about('said');
        }

        return new self(HowTheTunnelCameBack::NotEstablished, $said);
    }

    /** Nothing checked it. */
    public static function notChecked(): self
    {
        return new self(HowTheTunnelCameBack::NotChecked, '');
    }

    /** How it came back. */
    public function came(): HowTheTunnelCameBack
    {
        return $this->came;
    }

    /** The stack's words for the check that failed, or empty where none did. */
    public function said(): string
    {
        return $this->said;
    }
}
