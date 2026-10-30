<?php

declare(strict_types=1);

namespace Modules\Sdk\Internal;

use function array_key_exists;
use function is_array;
use function is_string;

use Modules\Kernel\Api\DownloadsMoving;
use Modules\Sdk\Api\Fields\DashboardField;
use Modules\Sdk\Api\SummaryIsUnreadable;
use Modules\Sdk\Api\WireField;

use function trim;

/**
 * Reads the dashboard's `transfers` panel into the downloads moving, each by its name.
 *
 * A panel whose source could not be reached names none, which is what the
 * stack could say about them. Anything the contract would not send refuses the
 * summary, for {@see StoppedRows}' reason.
 */
final readonly class TheDownloadsMoving
{
    /** @param array<mixed> $data the dashboard's payload */
    public static function in(array $data): DownloadsMoving
    {
        if (! array_key_exists(DashboardField::Transfers->value, $data) || ! is_array($data[DashboardField::Transfers->value])) {
            throw SummaryIsUnreadable::missing(DashboardField::Transfers);
        }

        $panel = $data[DashboardField::Transfers->value];
        $came = array_key_exists(DashboardField::Panel->value, $panel) && is_string($panel[DashboardField::Panel->value])
            ? HowAPanelCameBack::tryFrom($panel[DashboardField::Panel->value])
            : null;

        return match ($came) {
            HowAPanelCameBack::Ready => DownloadsMoving::these(...self::names($panel)),
            HowAPanelCameBack::Unavailable => DownloadsMoving::these(),
            null => throw SummaryIsUnreadable::missing(DashboardField::Panel),
        };
    }

    /**
     * Each moving download's name, in the panel's order.
     *
     * @param  array<mixed> $panel
     * @return list<string>
     */
    private static function names(array $panel): array
    {
        if (! array_key_exists(WireField::Data->value, $panel) || ! is_array($panel[WireField::Data->value])) {
            throw SummaryIsUnreadable::missing(DashboardField::Transfers);
        }

        $names = [];

        foreach (InTheOrderSent::rows($panel[WireField::Data->value]) as $position => $row) {
            if (! is_array($row) || ! array_key_exists(WireField::Name->value, $row) || ! is_string($row[WireField::Name->value]) || trim($row[WireField::Name->value]) === '') {
                throw SummaryIsUnreadable::inItem($position, DashboardField::Transfers);
            }

            $names[] = $row[WireField::Name->value];
        }

        return $names;
    }
}
