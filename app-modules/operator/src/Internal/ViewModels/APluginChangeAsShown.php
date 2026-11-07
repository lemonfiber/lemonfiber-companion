<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/** One change an install makes: the full path, what it puts there as a catalogue key, and each placeholder its starting file names a secret by. */
final readonly class APluginChangeAsShown
{
    /** @param list<APlaceholderAsShown> $placeholders */
    public function __construct(public string $path, public string $putsSaid, public array $placeholders = []) {}
}
