<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function array_values;

use Closure;

/**
 * How far removing everything lemonfiber keeps went: not asked, listed and not yet agreed to, or done with what went and what was left.
 */
final readonly class WhereForgettingGot
{
    /**
     * @param list<string>    $gone
     * @param list<APathLeft> $left
     */
    private function __construct(private WhereTheForgettingStands $stands, private array $gone, private array $left) {}

    /** The listing was asked for, and nothing was removed. */
    public static function notAsked(): self
    {
        return new self(WhereTheForgettingStands::NotAsked, [], []);
    }

    /** Removing everything was asked about without the yes: what would go is listed, and nothing went. */
    public static function unconfirmed(): self
    {
        return new self(WhereTheForgettingStands::Unconfirmed, [], []);
    }

    /** It was removed: each place that went, and each the stack could not remove. */
    public static function done(ThePlacesGone $gone, APathLeft ...$left): self
    {
        $places = [];

        foreach ($gone as $place) {
            $places[] = $place;
        }

        return new self(WhereTheForgettingStands::Done, $places, array_values($left));
    }

    /** Whether this is the listing a yes may be given to. */
    public function awaitsTheYes(): bool
    {
        return $this->stands === WhereTheForgettingStands::Unconfirmed;
    }

    /**
     * Say what happens in each case, and get back what you built.
     *
     * @template T of object
     *
     * @param Closure(): T                                 $notAsked
     * @param Closure(): T                                 $unconfirmed
     * @param Closure(ThePlacesGone, APathLeft ...): T     $done
     *
     * @return T
     */
    public function either(Closure $notAsked, Closure $unconfirmed, Closure $done): object
    {
        return match ($this->stands) {
            WhereTheForgettingStands::NotAsked => $notAsked(),
            WhereTheForgettingStands::Unconfirmed => $unconfirmed(),
            WhereTheForgettingStands::Done => $done(ThePlacesGone::of(...$this->gone), ...$this->left),
        };
    }
}
