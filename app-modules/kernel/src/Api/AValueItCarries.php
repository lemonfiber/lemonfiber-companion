<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * One value a plugin's recipe could carry to one destination, as the operator agrees to it.
 *
 * **Where it goes is the manifest's name for it, never an address.** A pair
 * that carries a value off the machine, or releases one away from the service
 * it was read from, asks for an approval of its own, written as the stack
 * spells it; any other pair to a service of this stack's asks for none.
 */
final readonly class AValueItCarries
{
    private function __construct(
        private string $value,
        private string $origin,
        private string $to,
        private string $approval,
        private string $release,
        private string $from,
    ) {}

    /**
     * The pair, as the reading lists it.
     *
     * `origin`, `approval`, `release` and `from` are empty where the stack
     * left them out; `value` and `to` blank are refused.
     */
    public static function listed(string $value, string $origin, string $to, string $approval, string $release, string $from): self
    {
        foreach (['value' => $value, 'to' => $to] as $field => $said) {
            if (trim($said) === '') {
                throw PluginSaysNothing::about($field);
            }
        }

        return new self($value, trim($origin), $to, trim($approval), trim($release), trim($from));
    }

    /** What the value is called within the recipe. */
    public function value(): string
    {
        return $this->value;
    }

    /** Whose value it is, as the manifest says, or empty where it does not. */
    public function origin(): string
    {
        return $this->origin;
    }

    /** Where it may be carried, by the manifest's name. */
    public function to(): string
    {
        return $this->to;
    }

    /** What approving it is written as, or empty where it asks for no approval. */
    public function approval(): string
    {
        return $this->approval;
    }

    /** Whether it asks for an approval of its own. */
    public function asksForApproval(): bool
    {
        return $this->approval !== '';
    }

    /** Why it is released away from the service it was read from, or empty where it is not. */
    public function release(): string
    {
        return $this->release;
    }

    /** The service it is read from, where it is released, or empty. */
    public function from(): string
    {
        return $this->from;
    }
}
