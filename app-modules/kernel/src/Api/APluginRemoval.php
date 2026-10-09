<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * What taking a plugin off the machine would come to, or came to.
 *
 * **Stated before it happens**: every service that stops, and every
 * capability nothing would fill afterwards. **Afterwards, removed only where
 * the record was written**: a run that put the files back and no further is
 * partial, told apart from a reading by its reversal not being a rehearsal.
 * What the reversal left on the machine is its own report's to say.
 */
final readonly class APluginRemoval
{
    private function __construct(
        private string $plugin,
        private PluginLines $interrupts,
        private TheCapabilitiesLeftUnfilled $leaves,
        private bool $removed,
        private ARunPutBack $wentBack,
    ) {}

    /** The account, as the stack gave it; a blank plugin is refused. */
    public static function reported(string $plugin, PluginLines $interrupts, TheCapabilitiesLeftUnfilled $leaves, bool $removed, ARunPutBack $wentBack): self
    {
        if (trim($plugin) === '') {
            throw PluginSaysNothing::about('plugin');
        }

        return new self($plugin, $interrupts, $leaves, $removed, $wentBack);
    }

    /** The plugin it is about, by its id. */
    public function plugin(): string
    {
        return $this->plugin;
    }

    /** Every service that stops when it goes. */
    public function interrupts(): PluginLines
    {
        return $this->interrupts;
    }

    /** Every capability nothing would fill afterwards. */
    public function leaves(): TheCapabilitiesLeftUnfilled
    {
        return $this->leaves;
    }

    /** What putting its changes back came to, or would. */
    public function wentBack(): ARunPutBack
    {
        return $this->wentBack;
    }

    /** Whether this is a reading: nothing removed, and the reversal only rehearsed. */
    public function isAReading(): bool
    {
        return ! $this->removed && $this->wentBack->rehearsed() === WhetherItWasRehearsed::Rehearsed;
    }

    /** Whether it is off the machine: the record was written without it. */
    public function wasRemoved(): bool
    {
        return $this->removed;
    }

    /** Whether it went only part of the way: its files went back and the record was not written. */
    public function isPartial(): bool
    {
        return ! $this->removed && $this->wentBack->rehearsed() === WhetherItWasRehearsed::CarriedOut;
    }
}
