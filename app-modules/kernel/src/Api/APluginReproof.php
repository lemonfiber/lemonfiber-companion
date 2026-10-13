<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * What proving an installed plugin again would ask, or came to: each proof, the answers kept against it, and whether a pass cleared them.
 *
 * A reading is a proof not yet asked of the plugin's adapters. A proof that was asked and did not clear leaves every kept answer standing.
 */
final readonly class APluginReproof
{
    private function __construct(
        private string $plugin,
        private TheProofs $proofs,
        private TheAnswersOutOfContract $kept,
        private bool $asked,
        private bool $cleared,
    ) {}

    /** The account, as the stack gave it; a blank plugin is refused. */
    public static function reported(string $plugin, TheProofs $proofs, TheAnswersOutOfContract $kept, bool $asked, bool $cleared): self
    {
        if (trim($plugin) === '') {
            throw PluginSaysNothing::about('plugin');
        }

        return new self($plugin, $proofs, $kept, $asked, $cleared);
    }

    /** The plugin it is about, by its id. */
    public function plugin(): string
    {
        return $this->plugin;
    }

    /** Each proof asked, or that would be asked, with what it came to. */
    public function proofs(): TheProofs
    {
        return $this->proofs;
    }

    /** Every answer kept against the plugin, each naming the capability a pass would clear. */
    public function kept(): TheAnswersOutOfContract
    {
        return $this->kept;
    }

    /** Whether this is a reading: the adapters not yet asked. */
    public function isAReading(): bool
    {
        return ! $this->asked;
    }

    /** Whether the proof was asked and cleared every answer kept against the plugin. */
    public function wasCleared(): bool
    {
        return $this->asked && $this->cleared;
    }
}
