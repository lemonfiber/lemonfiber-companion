<?php

declare(strict_types=1);

namespace Modules\Household\Internal\ViewModels;

/**
 * What the core answered of asking for a found title, as a member is told it.
 *
 * `said` is the core's sentence, shown as it came. `became` is the catalogue
 * key for what the ask became, or would become where it was a rehearsal.
 */
final readonly class TheAskAnswered
{
    public function __construct(public string $said, public string $became) {}
}
