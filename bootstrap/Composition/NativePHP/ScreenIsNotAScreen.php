<?php

declare(strict_types=1);

namespace Bootstrap\Composition\NativePHP;

use RuntimeException;

use function sprintf;

/**
 * A route named something that is not a screen.
 *
 * `Container::make()` answers with whatever the name resolves to, so a typo in
 * a route registration — or a binding that has been pointed somewhere else —
 * produces an object the router will then call `setRouter()` on. What a reader
 * sees is a fatal about an undefined method, three frames inside the vendor
 * package, at the moment the app launches.
 *
 * Named here instead. `C3` asks for a module-owned exception rather than a bare
 * `RuntimeException`, and this is the one failure this composition can produce
 * that is not somebody else's.
 */
final class ScreenIsNotAScreen extends RuntimeException
{
    public static function named(string $class): self
    {
        return new self(sprintf(
            'The route for %s resolved to something that is not a screen, so there is nothing to mount.',
            $class,
        ));
    }

}
