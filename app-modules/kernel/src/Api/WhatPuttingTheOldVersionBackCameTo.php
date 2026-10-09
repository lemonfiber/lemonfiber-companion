<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * Where an update did not hold: the version put back, whether its files are placed again, and whether it runs again.
 *
 * Placed and running apart, because they fail in different places: a file
 * that would not land is the disk, and a container that would not start is
 * the engine. Nothing to put back is its own answer: a rehearsal, and an
 * update that held.
 */
final readonly class WhatPuttingTheOldVersionBackCameTo
{
    private function __construct(private string $version, private bool $placed, private bool $running) {}

    /** Nothing had to be put back. */
    public static function notNeeded(): self
    {
        return new self('', placed: false, running: false);
    }

    /** The version put back, and how far it got; a blank version is refused. */
    public static function of(string $version, bool $placed, bool $running): self
    {
        if (trim($version) === '') {
            throw PluginSaysNothing::about('version');
        }

        return new self($version, $placed, $running);
    }

    /** Whether anything had to be put back. */
    public function wasNeeded(): bool
    {
        return $this->version !== '';
    }

    /** The version put back, or empty where nothing was. */
    public function version(): string
    {
        return $this->version;
    }

    /** Whether everything its record says it placed is on the machine again. */
    public function isPlaced(): bool
    {
        return $this->placed;
    }

    /** Whether its containers are running again. */
    public function isRunning(): bool
    {
        return $this->running;
    }
}
