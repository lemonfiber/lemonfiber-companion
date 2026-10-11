<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * What kind of thing a request is for, where nothing has named it yet.
 *
 * A film and a series are the kinds the core says in words this app knows;
 * any other, or none, is something asked for.
 */
enum KindOfRequest: string
{
    case Film = 'film';
    case Series = 'series';
    case Something = 'something';
}
