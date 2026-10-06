<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use function array_map;

use IteratorAggregate;
use Lemonfiber\Sdk\Contract\Api;
use Lemonfiber\Sdk\Repair;
use Modules\Kernel\Api\Ability;
use Modules\Kernel\Api\ABundleAsked;
use Modules\Kernel\Api\AGuardAskedFor;
use Modules\Kernel\Api\AnAction;
use Modules\Kernel\Api\AskingThemIn;
use Modules\Kernel\Api\ConnectingADevice;
use Modules\Kernel\Api\HandingOver;
use Modules\Kernel\Api\MovingInBy;
use Modules\Kernel\Api\TakingAnUpdate;
use Modules\Kernel\Api\TakingItOff;
use Modules\Kernel\Api\TakingThemOut;
use Modules\Kernel\Api\WhatToChange;
use Modules\Kernel\Api\WhatToDoAboutPairing;
use Modules\Kernel\Api\WhatToDoAboutQuality;
use Modules\Kernel\Api\WhatToDoAboutWiring;
use Modules\Kernel\Api\WhatToDoWithACopy;
use Modules\Kernel\Api\WhatToDoWithADownload;
use Modules\Kernel\Api\WhatToDoWithARun;
use Modules\Kernel\Api\WhatToDoWithIt;
use Modules\Kernel\Api\WhatToWalk;
use Modules\Kernel\Api\WhatWasDecided;
use Traversable;

/**
 * Every request this app sends a stack, by the path the stack serves it at.
 *
 * The registry each request is declared in, keyed the way a stack declares
 * what it serves: a stack's declaration is a map of these same paths, so this
 * is the list a gated request is asked about. A request an adapter sends that
 * is missing here, and a line here no adapter sends, are each refused by a
 * rule, which is what keeps this list the app rather than a description of it.
 *
 * **What is not here.** The declaration itself, the event stream, and what
 * became of work: none of them is a request the stack offers, and none is
 * asked about before it is sent.
 *
 * @implements IteratorAggregate<int, Ability>
 */
final readonly class EveryRequestThisAppSends implements IteratorAggregate
{
    /** @param list<Ability> $paths */
    private function __construct(private array $paths) {}

    /** Every path, the readings first and then the actions. */
    public static function listed(): self
    {
        return new self(array_map(
            Ability::of(...),
            [...self::readings(), ...self::actions()],
        ));
    }

    /** @return Traversable<int, Ability> */
    public function getIterator(): Traversable
    {
        yield from $this->paths;
    }

    /**
     * Every reading a screen opens on.
     *
     * @return list<string>
     */
    private static function readings(): array
    {
        return [
            Api::ALERTS_ENDPOINT, Api::BACKUPS_ENDPOINT, Api::BANDWIDTH_ENDPOINT, Api::CATALOGUE_ENDPOINT,
            Api::CHECKS_ENDPOINT, Api::CLIENTS_ENDPOINT, Api::CONFIG_ENDPOINT, Api::CREDENTIALS_ENDPOINT,
            Api::EXPLAIN_ENDPOINT, Api::FORMS_ENDPOINT, Api::FRONT_DOOR_ENDPOINT, Api::HELD_ENDPOINT,
            Api::HISTORY_ENDPOINT, Api::HOSTING_ENDPOINT, Api::LOGS_ENDPOINT, Api::MIGRATION_ENDPOINT,
            Api::NEWS_ENDPOINT, Api::OUTBOUND_ENDPOINT, Api::PROVENANCE_ENDPOINT, Api::QUALITY_ENDPOINT,
            Api::REQUESTS_ENDPOINT, Api::SPACE_ENDPOINT, Api::STATUS_ENDPOINT, Api::STORED_ENDPOINT,
            Api::STUCK_ENDPOINT, Api::TRACE_ENDPOINT, Api::UNINSTALL_ENDPOINT, Api::UPDATE_ENDPOINT,
            Api::VERSION_ENDPOINT, Api::WIRING_ENDPOINT,
        ];
    }

    /**
     * Every action's path: the repair the SDK spells, each single action the
     * kernel names, and every case of each closed set of them.
     *
     * @return list<string>
     */
    private static function actions(): array
    {
        $cases = [
            ...AskingThemIn::cases(), ...ConnectingADevice::cases(), ...HandingOver::cases(), ...MovingInBy::cases(),
            ...TakingItOff::cases(), ...TakingThemOut::cases(), ...WhatToChange::cases(), ...WhatToDoAboutPairing::cases(),
            ...WhatToDoAboutQuality::cases(), ...WhatToDoAboutWiring::cases(), ...WhatToDoWithACopy::cases(),
            ...WhatToDoWithADownload::cases(), ...WhatToDoWithARun::cases(), ...WhatToDoWithIt::cases(),
            ...WhatWasDecided::cases(),
        ];

        return [
            Repair::offer()->endpoint(),
            Api::action(ABundleAsked::named()),
            Api::action(AGuardAskedFor::named()),
            Api::action(TakingAnUpdate::named()),
            Api::action(WhatToWalk::named()),
            ...array_map(static fn(AnAction $action): string => Api::action($action->asked()), $cases),
        ];
    }
}
