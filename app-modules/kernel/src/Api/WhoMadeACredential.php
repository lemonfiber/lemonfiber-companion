<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function sprintf;

/**
 * Who produced a credential, which decides who fixes a failing one.
 *
 * One the operator supplied comes from an account they hold somewhere else;
 * one a service minted is that service's; one lemonfiber minted is lemonfiber's.
 */
enum WhoMadeACredential: string
{
    /** The operator supplied it, from an account they hold elsewhere. */
    case Operator = 'operator';

    /** The service minted it for itself. */
    case Service = 'service';

    /** lemonfiber minted it, because the service offers nothing durable to read. */
    case Lemonfiber = 'lemonfiber';

    /** The catalogue key for this, as an operator reads it. */
    public function saidOnTheScreen(): string
    {
        return sprintf('stacks.credentials.origin.%s', $this->value);
    }
}
