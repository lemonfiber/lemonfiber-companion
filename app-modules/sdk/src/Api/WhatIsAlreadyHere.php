<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use function array_key_exists;
use function is_array;
use function is_int;
use function is_string;

use Lemonfiber\Sdk\Envelope\Envelope;
use Lemonfiber\Sdk\Generated\MigrationEnvelope;
use Modules\Kernel\Api\AMode;
use Modules\Kernel\Api\APortHeld;
use Modules\Kernel\Api\APortMoved;
use Modules\Kernel\Api\AProjectStanding;
use Modules\Kernel\Api\AServiceStanding;
use Modules\Kernel\Api\TheModes;
use Modules\Kernel\Api\ThePortsHeld;
use Modules\Kernel\Api\ThePortsItPublishes;
use Modules\Kernel\Api\ThePortsMoved;
use Modules\Kernel\Api\TheSurvey;
use Modules\Kernel\Api\Unsupported;
use Modules\Kernel\Api\WhatAdoptingOneWouldDo;
use Modules\Kernel\Api\WhatAdoptingWouldDo;
use Modules\Kernel\Api\WhatIsUnsupported;
use Modules\Kernel\Api\WhatLinkingCosts;
use Modules\Kernel\Api\WhatMayBeDone;
use Modules\Kernel\Api\WhatStandsHere;
use Modules\Sdk\Api\Fields\MigrationField;
use Modules\Sdk\Internal\Required;
use Modules\Sdk\Internal\Wire;

use function trim;

/**
 * Reads the `migration` envelope into what a stack found already on its machine.
 *
 * Written the way {@see WhereTheDoorIs} is: a static fold with no state,
 * refusing anything the kernel would refuse, with {@see MigrationIsUnreadable}.
 * Every list keeps the stack's order, the modes' above all: least destructive
 * first is the stack's decision.
 *
 * **`read` is read as it came.** A survey that could not look carries it
 * false, and nothing here turns an empty list into a claim that it did.
 */
final readonly class WhatIsAlreadyHere
{
    /**
     * What is already on the machine, and what may be done about it.
     *
     * @param Envelope<mixed> $envelope the `migration` envelope, as the client returned it
     */
    public static function in(Envelope $envelope): TheSurvey
    {
        $data = self::payload(Wire::checked($envelope));

        if (! is_array($data)) {
            throw MigrationIsUnreadable::missing(WireField::Data);
        }

        return TheSurvey::reported(
            looked: Required::flag($data, MigrationField::Read, MigrationIsUnreadable::missing(MigrationField::Read)),
            standing: WhatStandsHere::of(...self::projects($data)),
            conflicts: ThePortsHeld::of(...self::conflicts($data)),
            unsupported: WhatIsUnsupported::these(...self::limits($data, WireField::Unsupported)),
            beside: ThePortsMoved::of(...self::moved($data)),
            linking: self::linking($data),
            choices: WhatMayBeDone::offered(
                TheModes::of(...self::modes($data)),
                WhatAdoptingWouldDo::of(...self::carrying($data)),
                WhatIsUnsupported::these(...self::limits($data, MigrationField::NotCarried)),
            ),
        );
    }

    /**
     * The payload, as it actually arrived.
     *
     * `mixed` deliberately, for {@see Records::payload()}'s reason.
     *
     * @param Envelope<mixed> $envelope
     */
    private static function payload(Envelope $envelope): mixed
    {
        return MigrationEnvelope::in($envelope)->data;
    }

    /**
     * Every project already here, with its services.
     *
     * @param  array<mixed>           $data
     * @return list<AProjectStanding>
     */
    private static function projects(array $data): array
    {
        $found = [];
        $position = 0;

        foreach (Required::rows($data, WireField::Standing, MigrationIsUnreadable::missing(WireField::Standing)) as $row) {
            if (! is_array($row)) {
                throw MigrationIsUnreadable::entry(WireField::Standing, $position, MigrationField::Project);
            }

            $found[] = AProjectStanding::named(
                Required::text($row, MigrationField::Project, MigrationIsUnreadable::entry(WireField::Standing, $position, MigrationField::Project)),
                ...self::services($row, $position),
            );
            $position++;
        }

        return $found;
    }

    /**
     * Every service of one project, whether it runs and whether it could be taken over.
     *
     * @param  array<mixed>           $project
     * @return list<AServiceStanding>
     */
    private static function services(array $project, int $at): array
    {
        $found = [];
        $position = 0;

        foreach (Required::rows($project, WireField::Services, MigrationIsUnreadable::entry(WireField::Standing, $at, WireField::Services)) as $row) {
            if (! is_array($row)) {
                throw MigrationIsUnreadable::service($at, $position, WireField::Service);
            }

            $found[] = AServiceStanding::found(
                Required::text($row, WireField::Service, MigrationIsUnreadable::service($at, $position, WireField::Service)),
                ThePortsItPublishes::of(...self::ports($row, MigrationIsUnreadable::service($at, $position, MigrationField::Ports))),
                Required::flag($row, WireField::Running, MigrationIsUnreadable::service($at, $position, WireField::Running)),
                Required::flag($row, MigrationField::Adoptable, MigrationIsUnreadable::service($at, $position, MigrationField::Adoptable)),
            );
            $position++;
        }

        return $found;
    }

    /**
     * Every host port one service publishes.
     *
     * @param  array<mixed> $service
     * @return list<int>
     */
    private static function ports(array $service, MigrationIsUnreadable $refused): array
    {
        $ports = [];

        foreach (Required::rows($service, MigrationField::Ports, $refused) as $port) {
            if (! is_int($port)) {
                throw $refused;
            }

            $ports[] = $port;
        }

        return $ports;
    }

    /**
     * Every port lemonfiber wants that something already here holds, and what holds it.
     *
     * @param  array<mixed>    $data
     * @return list<APortHeld>
     */
    private static function conflicts(array $data): array
    {
        $found = [];
        $position = 0;

        foreach (Required::rows($data, MigrationField::Conflicts, MigrationIsUnreadable::missing(MigrationField::Conflicts)) as $row) {
            if (! is_array($row)) {
                throw MigrationIsUnreadable::entry(MigrationField::Conflicts, $position, MigrationField::Port);
            }

            $found[] = APortHeld::of(
                Required::number($row, MigrationField::Port, MigrationIsUnreadable::entry(MigrationField::Conflicts, $position, MigrationField::Port)),
                Required::text($row, MigrationField::WantedBy, MigrationIsUnreadable::entry(MigrationField::Conflicts, $position, MigrationField::WantedBy)),
                Required::text($row, MigrationField::HeldBy, MigrationIsUnreadable::entry(MigrationField::Conflicts, $position, MigrationField::HeldBy)),
            );
            $position++;
        }

        return $found;
    }

    /**
     * What was found and cannot be taken over, or what no mode carries, each with why.
     *
     * @param  array<mixed>      $data
     * @return list<Unsupported>
     */
    private static function limits(array $data, NamesAWireField $list): array
    {
        $found = [];
        $position = 0;

        foreach (Required::rows($data, $list, MigrationIsUnreadable::missing($list)) as $row) {
            if (! is_array($row)) {
                throw MigrationIsUnreadable::entry($list, $position, WireField::What);
            }

            $found[] = Unsupported::of(
                Required::text($row, WireField::What, MigrationIsUnreadable::entry($list, $position, WireField::What)),
                Required::text($row, WireField::Because, MigrationIsUnreadable::entry($list, $position, WireField::Because)),
            );
            $position++;
        }

        return $found;
    }

    /**
     * Every mode, in the stack's order, with what it would come to.
     *
     * @param  array<mixed> $data
     * @return list<AMode>
     */
    private static function modes(array $data): array
    {
        $found = [];
        $position = 0;

        foreach (Required::rows($data, MigrationField::Modes, MigrationIsUnreadable::missing(MigrationField::Modes)) as $row) {
            if (! is_array($row)) {
                throw MigrationIsUnreadable::entry(MigrationField::Modes, $position, MigrationField::Mode);
            }

            $found[] = AMode::offered(
                Required::text($row, MigrationField::Mode, MigrationIsUnreadable::entry(MigrationField::Modes, $position, MigrationField::Mode)),
                Required::text($row, WireField::What, MigrationIsUnreadable::entry(MigrationField::Modes, $position, WireField::What)),
                Required::flag($row, WireField::Disturbs, MigrationIsUnreadable::entry(MigrationField::Modes, $position, WireField::Disturbs)),
                Required::flag($row, MigrationField::Preselected, MigrationIsUnreadable::entry(MigrationField::Modes, $position, MigrationField::Preselected)),
            );
            $position++;
        }

        return $found;
    }

    /**
     * Where each service would listen to run beside what is here.
     *
     * @param  array<mixed>     $data
     * @return list<APortMoved>
     */
    private static function moved(array $data): array
    {
        $found = [];
        $position = 0;

        foreach (Required::rows($data, WireField::Beside, MigrationIsUnreadable::missing(WireField::Beside)) as $row) {
            if (! is_array($row)) {
                throw MigrationIsUnreadable::entry(WireField::Beside, $position, WireField::Service);
            }

            $found[] = APortMoved::of(
                Required::text($row, WireField::Service, MigrationIsUnreadable::entry(WireField::Beside, $position, WireField::Service)),
                Required::number($row, WireField::From, MigrationIsUnreadable::entry(WireField::Beside, $position, WireField::From)),
                Required::number($row, WireField::To, MigrationIsUnreadable::entry(WireField::Beside, $position, WireField::To)),
            );
            $position++;
        }

        return $found;
    }

    /**
     * What adopting each recognised service would come to.
     *
     * @param  array<mixed>                 $data
     * @return list<WhatAdoptingOneWouldDo>
     */
    private static function carrying(array $data): array
    {
        $found = [];
        $position = 0;

        foreach (Required::rows($data, MigrationField::Carrying, MigrationIsUnreadable::missing(MigrationField::Carrying)) as $row) {
            if (! is_array($row)) {
                throw MigrationIsUnreadable::entry(MigrationField::Carrying, $position, WireField::Service);
            }

            $found[] = WhatAdoptingOneWouldDo::said(
                Required::text($row, WireField::Service, MigrationIsUnreadable::entry(MigrationField::Carrying, $position, WireField::Service)),
                Required::text($row, WireField::Because, MigrationIsUnreadable::entry(MigrationField::Carrying, $position, WireField::Because)),
                Required::flag($row, MigrationField::BackupFirst, MigrationIsUnreadable::entry(MigrationField::Carrying, $position, MigrationField::BackupFirst)),
                Required::flag($row, WireField::Refused, MigrationIsUnreadable::entry(MigrationField::Carrying, $position, WireField::Refused)),
            );
            $position++;
        }

        return $found;
    }

    /**
     * What the layout costs where it cannot hold a hardlink, or nothing where the stack reports none.
     *
     * @param array<mixed> $data
     */
    private static function linking(array $data): WhatLinkingCosts
    {
        if (! array_key_exists(MigrationField::Linking->value, $data) || $data[MigrationField::Linking->value] === null) {
            return WhatLinkingCosts::nothing();
        }

        $linking = $data[MigrationField::Linking->value];

        if (! is_array($linking)) {
            throw MigrationIsUnreadable::missing(MigrationField::Linking);
        }

        return WhatLinkingCosts::cannotLink(
            Required::text($linking, WireField::Because, MigrationIsUnreadable::under(MigrationField::Linking, WireField::Because)),
            Required::text($linking, WireField::Cost, MigrationIsUnreadable::under(MigrationField::Linking, WireField::Cost)),
            Required::text($linking, WireField::Remedy, MigrationIsUnreadable::under(MigrationField::Linking, WireField::Remedy)),
            ...self::filesystems($linking),
        );
    }

    /**
     * The filesystems a layout that cannot link keeps its data on.
     *
     * @param  array<mixed> $linking
     * @return list<string>
     */
    private static function filesystems(array $linking): array
    {
        $refused = MigrationIsUnreadable::under(MigrationField::Linking, MigrationField::Filesystems);
        $named = [];

        foreach (Required::rows($linking, MigrationField::Filesystems, $refused) as $filesystem) {
            if (! is_string($filesystem) || trim($filesystem) === '') {
                throw $refused;
            }

            $named[] = $filesystem;
        }

        return $named;
    }
}
