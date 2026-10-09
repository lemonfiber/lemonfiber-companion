<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * What updating a plugin would come to, or came to, as one account.
 *
 * **One account, because it is one operation**: the installed version going
 * back and another coming on. The version the machine is on has two answers,
 * the new one where its install holds, or the one it replaced, which what
 * putting it back came to says the state of.
 *
 * **A reading is told by its reversal**: asked with no offer, the stack says
 * what putting the installed version back would come to, rehearsed, and
 * writes nothing.
 */
final readonly class AnUpdate
{
    private function __construct(
        private string $plugin,
        private TheVersionsItMovesBetween $versions,
        private PluginLines $interrupts,
        private APluginInstall $install,
        private ARunPutBack $wentBack,
        private string $stopped,
        private WhatPuttingTheOldVersionBackCameTo $restored,
    ) {}

    /**
     * The account, as the stack gave it; `stopped` is empty where nothing stopped the new version early.
     *
     * A blank plugin is refused.
     */
    public static function reported(
        string $plugin,
        TheVersionsItMovesBetween $versions,
        PluginLines $interrupts,
        APluginInstall $install,
        ARunPutBack $wentBack,
        string $stopped,
        WhatPuttingTheOldVersionBackCameTo $restored,
    ): self {
        if (trim($plugin) === '') {
            throw PluginSaysNothing::about('plugin');
        }

        return new self($plugin, $versions, $interrupts, $install, $wentBack, trim($stopped), $restored);
    }

    /** The plugin it is about, by its id. */
    public function plugin(): string
    {
        return $this->plugin;
    }

    /** The version it moves from, and the one it moves to. */
    public function versions(): TheVersionsItMovesBetween
    {
        return $this->versions;
    }

    /** Every service of the installed version that stops for it, named before any does. */
    public function interrupts(): PluginLines
    {
        return $this->interrupts;
    }

    /** The new version's own account, the same one an install gives. */
    public function install(): APluginInstall
    {
        return $this->install;
    }

    /** What putting the installed version's changes back came to, or would. */
    public function wentBack(): ARunPutBack
    {
        return $this->wentBack;
    }

    /** What stopped the new version before its proofs were asked, or empty. */
    public function stopped(): string
    {
        return $this->stopped;
    }

    /** What putting the version it replaced back came to, where it did not hold. */
    public function restored(): WhatPuttingTheOldVersionBackCameTo
    {
        return $this->restored;
    }

    /** Whether this is a reading: the reversal only rehearsed, and nothing recorded. */
    public function isAReading(): bool
    {
        return $this->wentBack->rehearsed() === WhetherItWasRehearsed::Rehearsed && ! $this->install->held();
    }

    /** Whether it holds: the new version's install held. */
    public function held(): bool
    {
        return $this->install->held();
    }
}
