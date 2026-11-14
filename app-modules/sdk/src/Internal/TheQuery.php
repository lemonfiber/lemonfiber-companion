<?php

declare(strict_types=1);

namespace Modules\Sdk\Internal;

use Modules\Kernel\Api\KindOfTitle;
use Modules\Kernel\Api\UnitOfTitle;
use Modules\Kernel\Api\WhichTitles;
use Modules\Sdk\Api\Fields\AskableField;
use Modules\Sdk\Api\WireField;

/**
 * Part of what a read is asked with, by the names the read takes.
 */
final readonly class TheQuery
{
    /** @param array<string, string> $said each name the read takes, with what it is asked */
    public function __construct(public array $said) {}

    /**
     * What a search or a found title's poster is asked among: nothing for films and series, and the kind and unit of music or books.
     *
     * @return array<string, string>
     */
    public static function among(WhichTitles $which): array
    {
        return $which->either(
            filmsAndSeries: static fn(): self => new self([]),
            of: static fn(KindOfTitle $kind, UnitOfTitle $unit): self => new self([WireField::Kind->value => $kind->value, AskableField::Unit->value => $unit->value]),
        )->said;
    }
}
