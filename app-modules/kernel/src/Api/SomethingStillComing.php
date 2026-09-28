<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * One download still coming down, which stopping would interrupt, with how far along it is.
 */
final readonly class SomethingStillComing
{
    /** The furthest a download can be along, which is finished. */
    private const int ALL_OF_IT = 100;

    private function __construct(private string $name, private int $progress) {}

    /** That download, so far along; a blank name, or a share outside none to all of it, is refused. */
    public static function named(string $name, int $progress): self
    {
        if (trim($name) === '') {
            throw UninstallSaysNothing::about('coming.name');
        }

        if ($progress < 0 || $progress > self::ALL_OF_IT) {
            throw UninstallSaysNothing::outside('coming.progress', $progress);
        }

        return new self($name, $progress);
    }

    /** What it is, as the client names it. */
    public function name(): string
    {
        return $this->name;
    }

    /** How far along, from none to a hundred. */
    public function progress(): int
    {
        return $this->progress;
    }
}
