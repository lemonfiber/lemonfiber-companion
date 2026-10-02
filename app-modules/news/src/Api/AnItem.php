<?php

declare(strict_types=1);

namespace Modules\News\Api;

use Modules\Kernel\Api\Instant;

use function sprintf;
use function trim;

/**
 * One update, request or problem, by what tells it from the others of its kind.
 *
 * An update is named by its version and has no order of its own: it is newer by
 * its place in the stack's record, which {@see TheItems} holds. A request is
 * named and ordered by its number. A problem is named by its check and ordered
 * by when it began, so one that clears and comes back is a newer problem.
 */
final readonly class AnItem
{
    private function __construct(private KindOfNews $kind, private string $named, private int $order) {}

    /** A release, by its version. */
    public static function anUpdate(string $version): self
    {
        if (trim($version) === '') {
            throw NotAnItem::namedByNothing(KindOfNews::Update);
        }

        return new self(KindOfNews::Update, $version, 0);
    }

    /** A household request, by its number. */
    public static function aRequest(int $number): self
    {
        if ($number < 1) {
            throw NotAnItem::numbered($number);
        }

        return new self(KindOfNews::Request, sprintf('%d', $number), $number);
    }

    /** A check found wrong, by the check and when it began. */
    public static function aProblem(string $check, Instant $onset): self
    {
        if (trim($check) === '') {
            throw NotAnItem::namedByNothing(KindOfNews::Problem);
        }

        return new self(KindOfNews::Problem, $check, $onset->epochSeconds());
    }

    /** Which kind it is. */
    public function kind(): KindOfNews
    {
        return $this->kind;
    }

    /** What names it among the others of its kind: a version, a number or a check. */
    public function named(): string
    {
        return $this->named;
    }

    /** Where it falls among the others of its kind, higher being newer; nothing for an update. */
    public function order(): int
    {
        return $this->order;
    }

    /** Whether this is the same item as another. */
    public function is(self $other): bool
    {
        return $this->kind === $other->kind && $this->named === $other->named && $this->order === $other->order;
    }
}
