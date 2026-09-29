<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function sprintf;

/**
 * Which of the four removals taking lemonfiber off a machine is.
 *
 * Four separate decisions rather than four steps of one, and the last is
 * never taken along with another: stopping, the services, the configuration,
 * and the library with the downloads. Each is read, and agreed to, on its own.
 */
enum WhichRemoval: string
{
    /** Nothing removed: the services are stopped and everything stays where it is. */
    case Stop = 'stop';

    /** The containers, the networks between them, and the images that were pulled. */
    case Services = 'services';

    /** Each service's own configuration, lemonfiber's own state, and the credentials in both. */
    case Configuration = 'configuration';

    /** The library and the downloads. */
    case Media = 'media';

    /**
     * Whether this removal takes what admits this app to the stack.
     *
     * The configuration, because lemonfiber's own state holds the password
     * the web interface signs in with, and without it the stack stops offering
     * the surface this app reaches.
     */
    public function takesWhatAdmitsThisApp(): bool
    {
        return $this === self::Configuration;
    }

    /** Whether this removal takes the library and the downloads, which nothing can fetch again. */
    public function takesTheLibrary(): bool
    {
        return $this === self::Media;
    }

    /** The catalogue key for this removal, as an operator reads it. */
    public function saidOnTheScreen(): string
    {
        return sprintf('uninstall.tier.%s', $this->value);
    }

    /** The catalogue key for the yes to this removal; the library's names how much data goes. */
    public function agreedToAs(): string
    {
        return sprintf('uninstall.agree.%s', $this->value);
    }
}
