<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function array_map;
use function array_values;

/**
 * An update the operator said yes to, against what it will change.
 *
 * {@see Confirmed}'s argument applied to an update, and the same argument
 * {@see AgreedTo} makes for a verb: the way `N2-R17` gets broken is never
 * deliberate — a screen draws the pending release, the button is right there,
 * and a tap handler calls the thing that applies it.
 *
 * So {@see KeepingCurrent::take()} takes one of these, and the only way to make
 * one names the release and the services together. Rendering an upkeep reading
 * produces no `TakingAnUpdate` and cannot be made to.
 *
 * **The services are carried rather than looked up later.** `N2-R17` wants the
 * confirmation to name what it would change, which is only worth anything if
 * what was named is what gets done. An update applied against a list re-read
 * after the yes would be an update to whatever the stack had by then, confirmed
 * against a screen that is no longer true.
 */
final readonly class TakingAnUpdate
{
    /** @param list<ServiceId> $changing */
    private function __construct(
        private Release $release,
        private array $changing,
    ) {}

    /**
     * What the operator was shown and agreed to.
     *
     * Variadic so that an empty call is a decision somebody wrote rather than
     * an array that happened to arrive empty. Reindexed all the same: a
     * variadic collected from named arguments carries their names as keys, so
     * *variadic* and *list* are not the same claim.
     */
    public static function agreed(Release $release, ServiceId ...$changing): self
    {
        return new self($release, array_values($changing));
    }

    public function release(): Release
    {
        return $this->release;
    }

    /**
     * The services this was agreed about, by name.
     *
     * Names rather than the values, because this is what crosses the wire and
     * the adapter has no business reaching into a value object to build a
     * payload.
     *
     * @return list<string>
     */
    public function changing(): array
    {
        return array_map(
            static fn(ServiceId $service): string => $service->named(),
            $this->changing,
        );
    }

    /**
     * Whether this update leaves the stack alone.
     *
     * A release that changes no service is a changelog entry rather than an
     * evening, and a screen can say so instead of asking somebody to confirm
     * nothing.
     */
    public function changesNothing(): bool
    {
        return $this->changing === [];
    }
}
