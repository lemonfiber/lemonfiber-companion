<?php

declare(strict_types=1);

namespace Modules\Connection\Api;

use Modules\Connection\Internal\SettingsKept;
use Modules\Connection\Internal\TheSettingsHeld;
use Modules\Kernel\Api\ADistributor;
use Modules\Kernel\Api\Clock;
use Modules\Kernel\Api\ReachingADistributor;
use Modules\Kernel\Api\Sealed;
use Modules\Kernel\Api\TheDistributors;
use Modules\Kernel\Api\WhoWakesThisPhone;

/**
 * Which UnifiedPush distributor wakes this phone, as installed and as the operator chose among several.
 *
 * Chosen once, the first time waking is turned on with several installed, and
 * kept sealed with the phone's other settings. With one installed it is that
 * one, and nothing is asked.
 */
final readonly class ChoosingWhoWakesThisPhone
{
    private TheSettingsHeld $settings;

    public function __construct(Sealed $seal, SettingsKept $kept, Clock $clock, private ReachingADistributor $distributor)
    {
        $this->settings = new TheSettingsHeld($seal, $kept, $clock);
    }

    public function who(): WhoWakesThisPhone
    {
        return WhoWakesThisPhone::among($this->distributor->distributors(), $this->settings->current()->wakeThrough);
    }

    /** Keep the operator's choice, and answer who wakes this phone now. */
    public function choose(ADistributor $through): WhoWakesThisPhone
    {
        $this->settings->keep($this->settings->current()->wakingThrough($through->package()));

        return $this->who();
    }

    /** The distributor installed as the same package as `$asked`, or none where it is not installed. */
    public function installedAs(ADistributor $asked): TheDistributors
    {
        return $this->distributor->distributors()->either(
            installed: static fn(TheDistributors $installed): TheDistributors => $installed->onlyThe($asked),
            notHere: static fn(): TheDistributors => TheDistributors::of(),
        );
    }

    /** Whether `$distributor` is the one that wakes this phone now. */
    public function wakesThrough(ADistributor $distributor): bool
    {
        return $this->who()->either(
            through: static fn(ADistributor $through): TheDistributors => TheDistributors::of($through),
            toChoose: static fn(): TheDistributors => TheDistributors::of(),
            noneInstalled: static fn(): TheDistributors => TheDistributors::of(),
            notHere: static fn(): TheDistributors => TheDistributors::of(),
        )->onlyThe($distributor)->count() === 1;
    }
}
