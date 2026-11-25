<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/** The stack's two settings for a running copy, flattened for a template. */
final readonly class HowCopiesRunShows
{
    /**
     * @param HowTheReadingWent          $went     whether the settings came back, or what stood in the way
     * @param list<AStackSettingAsShown> $settings each of the two the stack lists, in its order
     */
    public function __construct(public HowTheReadingWent $went, public array $settings = []) {}
}
