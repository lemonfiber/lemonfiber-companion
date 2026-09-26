<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function array_values;

use Closure;

use function trim;

/**
 * What an existing layout costs where it cannot hold a hardlink, or nothing where it can.
 *
 * **The remedy is words.** It is the operator's to take on their own disks,
 * and nothing the stack offers carries it out, so this holds the sentence and
 * no act.
 */
final readonly class WhatLinkingCosts
{
    /** @param array{string, string, string, list<string>}|null $costs why, what it costs, the remedy, and the filesystems */
    private function __construct(private ?array $costs) {}

    /** A layout that links, which the stack reports by saying nothing. */
    public static function nothing(): self
    {
        return new self(null);
    }

    /** A layout that cannot link: why, what that costs, what would fix it, and the filesystems it is about. */
    public static function cannotLink(string $because, string $cost, string $remedy, string ...$filesystems): self
    {
        foreach (['because' => $because, 'cost' => $cost, 'remedy' => $remedy] as $field => $said) {
            if (trim($said) === '') {
                throw TheSurveySaysNothing::about($field);
            }
        }

        foreach ($filesystems as $filesystem) {
            if (trim($filesystem) === '') {
                throw TheSurveySaysNothing::about('filesystems');
            }
        }

        return new self([$because, $cost, $remedy, array_values($filesystems)]);
    }

    /**
     * Say what a layout that cannot link costs, or that it links.
     *
     * @template TCosts of object
     * @template TLinks of object
     *
     * @param Closure(string, string, string, list<string>): TCosts $costs why, what it costs, the remedy, and the filesystems
     * @param Closure(): TLinks                                     $links
     *
     * @return TCosts|TLinks
     */
    public function either(Closure $costs, Closure $links): object
    {
        return $this->costs === null ? $links() : $costs(...$this->costs);
    }
}
