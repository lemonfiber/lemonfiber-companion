<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/** One change an install makes: the full path, and what it puts there as a catalogue key. */
final readonly class APluginChangeAsShown
{
    public function __construct(public string $path, public string $putsSaid) {}
}
