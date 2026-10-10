<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Screens;

use Closure;
use Modules\Kernel\Api\Scanning;
use Modules\Kernel\Api\WhatTheCameraSaw;
use Modules\Kernel\Api\WhyNothingWasScanned;
use Native\Mobile\Edge\NativeComponent;

/**
 * A screen that reads a code with the camera, and says why where nothing came back.
 *
 * Every screen that takes a code or an address as text takes it from the
 * camera too, so what the camera was for, and what to say where it handed
 * nothing back, is written once.
 *
 * @phpstan-require-extends NativeComponent
 */
trait ReadsACode
{
    /** Why the camera handed nothing back, once it was asked and did not. */
    public ?WhyNothingWasScanned $nothingCameBack = null;

    /** Whether the camera was asked and handed nothing back. */
    public function nothingWasScanned(): bool
    {
        return $this->nothingCameBack instanceof WhyNothingWasScanned;
    }

    /** Why the camera handed nothing back, as a key, or empty where it was not asked or read something. */
    public function whyNothingCameBack(): string
    {
        return $this->nothingCameBack?->saidOnTheScreen() ?? '';
    }

    /** What to do about the camera having handed nothing back, as a key, or empty. */
    public function remedyForTheCamera(): string
    {
        return $this->nothingCameBack?->remedy() ?? '';
    }

    /**
     * Open the camera, and hand what it read to `$took`.
     *
     * @param Closure(string): void $took
     */
    protected function readACode(Scanning $camera, Closure $took): void
    {
        $this->nothingCameBack = null;

        $camera->aCode(function (WhatTheCameraSaw $saw) use ($took): void {
            $saw->either(
                read: function (string $payload) use ($took): WhatTheCameraSaw {
                    $took($payload);

                    return WhatTheCameraSaw::read($payload);
                },
                nothing: function (WhyNothingWasScanned $why): WhatTheCameraSaw {
                    $this->nothingCameBack = $why;

                    return WhatTheCameraSaw::nothing($why);
                },
            );
        });
    }
}
