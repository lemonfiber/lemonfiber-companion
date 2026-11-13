<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/** One way the operator offered the household to be told something they asked for has arrived, and whether this member is told on it. */
final readonly class AWayToBeTold
{
    private function __construct(private string $id, private string $name, private bool $receiving) {}

    /** A way offered, refused where it has no id to choose it by or no name to read. */
    public static function offered(string $id, string $name, bool $receiving): self
    {
        if (trim($id) === '') {
            throw AWayToBeToldSaysNothing::unchoosable();
        }

        $said = trim($name);

        if ($said === '') {
            throw AWayToBeToldSaysNothing::unnamed();
        }

        return new self($id, $said, $receiving);
    }

    /** What choosing it sends back. */
    public function id(): string
    {
        return $this->id;
    }

    /** The name the household reads it by. */
    public function name(): string
    {
        return $this->name;
    }

    /** Whether this member is told on it now. */
    public function receiving(): bool
    {
        return $this->receiving;
    }
}
