<?php

declare(strict_types=1);

namespace Modules\Connection\Api;

/**
 * How a member signs in to a house: with a name and password, or by a code a service outside the house approves.
 */
enum TheWayIn: string
{
    case Password = 'password';

    case Code = 'code';
}
