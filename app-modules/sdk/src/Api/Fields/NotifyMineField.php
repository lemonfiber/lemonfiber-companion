<?php

declare(strict_types=1);

namespace Modules\Sdk\Api\Fields;

use Modules\Sdk\Api\NamesAWireField;
use Modules\Sdk\Api\SaysWhereItSits;

/** The `notify-mine` envelope's fields no other envelope shares. */
enum NotifyMineField: string implements NamesAWireField
{
    use SaysWhereItSits;

    /** Whether this phone is registered to be woken. */
    case Phone = 'phone';

    /** Whether the member is told on one way offered. */
    case Receiving = 'receiving';
}
