<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use Modules\Kernel\Api\TheAppsSettings;
use Modules\Kernel\Api\WhetherTheSettingsOpened;

/**
 * The phone's settings page for this app, opening or not the same way every time, and counting.
 *
 * Not `readonly`: every asking is counted as it happens.
 */
final class AppsSettingsThatOpen implements TheAppsSettings
{
    private int $asked = 0;

    public function __construct(private readonly bool $opens = true) {}

    /** A phone that would not open the page. */
    public static function wouldNot(): self
    {
        return new self(opens: false);
    }

    public function open(): WhetherTheSettingsOpened
    {
        $this->asked++;

        return $this->opens ? WhetherTheSettingsOpened::opened() : WhetherTheSettingsOpened::wouldNot();
    }

    /** How many times the page was asked for. */
    public function timesOpened(): int
    {
        return $this->asked;
    }
}
