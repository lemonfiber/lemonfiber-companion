<?php

declare(strict_types=1);

namespace Dx\Mutation;

/**
 * What one runner mutates: files at one floor against the whole suite, or the
 * paths the `holds:` groups judge, each against its group.
 *
 * `digests` holds each unit's proof digest, by the unit's path, where one could
 * be told; a unit without one is mutated and never recorded as proved.
 */
final readonly class AShard
{
    /**
     * @param list<string>          $files
     * @param list<AHeldPath>       $held
     * @param array<string, string> $digests
     */
    public function __construct(
        public int $id,
        public string $label,
        public int $floor,
        public array $files,
        public array $held,
        public array $digests,
    ) {}

    /** @param array<string, string> $digests */
    public function provedBy(array $digests): self
    {
        return new self($this->id, $this->label, $this->floor, $this->files, $this->held, $digests);
    }
}
