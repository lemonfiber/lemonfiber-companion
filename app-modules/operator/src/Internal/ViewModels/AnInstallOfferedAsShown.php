<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/** A part a start offers to install: its plugin, and why its install could not be read, or empty where it could. */
final readonly class AnInstallOfferedAsShown
{
    public function __construct(public string $plugin, public string $why) {}
}
