<?php

declare(strict_types=1);

namespace Modules\Device\Api;

use Lemonfiber\Native\Screen;
use Modules\Kernel\Api\Capture;

/**
 * The window, reached through lemonfiber's own native expansion.
 *
 * Thin on purpose. Everything interesting about capture protection is decided in
 * Kotlin and Swift — in `CaptureRule`, which imports nothing from either
 * framework and has the same six tests on both platforms — and this is the line
 * that carries the decision across.
 *
 * **Why a plugin of our own.** The marketplace sells one that blocks capture,
 * and it is not among the plugins this project holds a licence for. The
 * capability is small and the requirements are not optional, so it is written
 * rather than bought or deferred. The port above it is what keeps that a
 * reversible decision: buying the plugin later replaces this file and nothing
 * else.
 */
final readonly class PlatformScreen implements Capture
{
    public function __construct(private Screen $window) {}

    public function conceal(): bool
    {
        return $this->window->conceal();
    }

    public function reveal(): bool
    {
        return $this->window->reveal();
    }

    public function isProtected(): bool
    {
        return $this->window->isProtected();
    }
}
