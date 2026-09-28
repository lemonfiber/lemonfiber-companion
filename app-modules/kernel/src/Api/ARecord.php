<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * One of an operator's own records an import carried across, or would.
 *
 * A quality profile, an indexer, a root folder: whatever the service held,
 * named as it named it, under the kind the stack reads it as.
 */
final readonly class ARecord
{
    private function __construct(
        private string $service,
        private string $kind,
        private string $name,
    ) {}

    /** What the stack said of one record; all three words are required. */
    public static function of(string $service, string $kind, string $name): self
    {
        foreach (['service' => $service, 'kind' => $kind, 'name' => $name] as $field => $said) {
            if (trim($said) === '') {
                throw TheMoveSaysNothing::about($field);
            }
        }

        return new self($service, $kind, $name);
    }

    /** The service it belongs to. */
    public function service(): string
    {
        return $this->service;
    }

    /** What kind of record it is, in the plural a person reads. */
    public function kind(): string
    {
        return $this->kind;
    }

    /** What it is called. */
    public function name(): string
    {
        return $this->name;
    }
}
