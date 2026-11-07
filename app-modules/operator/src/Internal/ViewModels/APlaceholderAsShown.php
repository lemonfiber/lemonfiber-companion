<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/** One placeholder a starting file names a secret by: the secret's id and, as a catalogue key, the form its value is written in. */
final readonly class APlaceholderAsShown
{
    public function __construct(public string $secret, public string $formSaid) {}
}
