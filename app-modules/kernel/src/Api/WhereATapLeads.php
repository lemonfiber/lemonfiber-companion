<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function ltrim;
use function sprintf;
use function str_starts_with;

/**
 * The screen of this app a notification opens when it is tapped.
 *
 * A route of this app's own and nothing else: the platform is handed it as a
 * link under the scheme the app is opened at, so a tap can only ever lead
 * back into the app.
 */
final readonly class WhereATapLeads
{
    private function __construct(private string $route) {}

    /** The screen at this route of the app's, which starts at its root. */
    public static function to(string $route): self
    {
        if (! str_starts_with($route, '/') || str_starts_with($route, '//')) {
            throw RouteIsNotTheApps::named();
        }

        return new self($route);
    }

    /** The link the platform opens the app at. */
    public function asALink(): string
    {
        return sprintf('%s://%s', AJoinLink::scheme(), ltrim($this->route, '/'));
    }
}
