<?php

declare(strict_types=1);

namespace Modules\Household\Internal\Presenters;

/** Some words, or none, carried out of a fold that must answer with an object. */
final readonly class InWordsOrNone
{
    public function __construct(public ?string $said) {}
}
