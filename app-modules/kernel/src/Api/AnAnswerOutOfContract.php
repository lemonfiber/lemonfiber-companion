<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * One answer an installed plugin's adapter gave outside its contract.
 *
 * Kept by the stack until proving the plugin again clears it, and the plugin
 * fills none of the capabilities it was asked as meanwhile.
 */
final readonly class AnAnswerOutOfContract
{
    private function __construct(
        private string $plugin,
        private string $capability,
        private string $operation,
        private string $why,
    ) {}

    /** The plugin's id, the capability and the operation it was asked, and what was outside the contract; a blank one is refused. */
    public static function of(string $plugin, string $capability, string $operation, string $why): self
    {
        foreach (['plugin' => $plugin, 'capability' => $capability, 'operation' => $operation, 'why' => $why] as $field => $said) {
            if (trim($said) === '') {
                throw PluginSaysNothing::about($field);
            }
        }

        return new self($plugin, $capability, $operation, $why);
    }

    /** Whether that plugin's adapter gave it. */
    public function isOf(APlugin $plugin): bool
    {
        return $this->plugin === $plugin->id();
    }

    /** The capability it was asked as. */
    public function capability(): string
    {
        return $this->capability;
    }

    /** The operation it was asked. */
    public function operation(): string
    {
        return $this->operation;
    }

    /** What was outside the contract, in the stack's words. */
    public function why(): string
    {
        return $this->why;
    }
}
