<?php

declare(strict_types=1);

namespace Dx\Mutation;

/**
 * The paths a change reaches, with what was said deciding it.
 *
 * `paths` is null where every path has to be mutated.
 */
final readonly class AReach
{
    /**
     * @param list<string>|null $paths
     * @param list<string>      $said
     */
    public function __construct(public ?array $paths, public array $said) {}

    public static function everything(string $why): self
    {
        return new self(null, [$why]);
    }

    public function isEverything(): bool
    {
        return $this->paths === null;
    }
}
