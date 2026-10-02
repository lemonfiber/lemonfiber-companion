<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/** One kind's section of What's new: its heading, and its new items newest first. */
final readonly class ANewsSectionAsShown
{
    /**
     * @param string               $said the catalogue key for the heading
     * @param list<ANewsRowAsShown> $rows each new item, newest first
     */
    public function __construct(public string $said, public array $rows) {}
}
