<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use Illuminate\View\View;
use Modules\Operator\Internal\AwaitsAnOutcome;
use Native\Mobile\Edge\NativeComponent;

use function view;

/**
 * Whatever screen is on view when the device's lock stands again.
 *
 * It sent something and awaits its outcome, or it does not; that is the one
 * thing the lock asks of it.
 */
final class AScreenUnderTheLock extends NativeComponent implements AwaitsAnOutcome
{
    private function __construct(private readonly bool $awaits) {}

    /** A screen following something it sent, which has not finished. */
    public static function awaitingAnOutcome(): self
    {
        return new self(awaits: true);
    }

    /** A screen with nothing in flight. */
    public static function atRest(): self
    {
        return new self(awaits: false);
    }

    public function awaitsAnOutcome(): bool
    {
        return $this->awaits;
    }

    public function render(): View
    {
        return view('operator::locked');
    }
}
