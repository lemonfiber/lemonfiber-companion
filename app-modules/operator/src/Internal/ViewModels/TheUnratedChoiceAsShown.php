<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * What becomes of unrated material as chosen so far, and every way it can go.
 */
final readonly class TheUnratedChoiceAsShown
{
    /**
     * @param string                       $said    the catalogue key for what becomes of it as chosen now
     * @param list<AnUnratedChoiceAsShown> $offered every way it can go, the one chosen now marked
     */
    public function __construct(
        public string $said,
        public array $offered,
    ) {}
}
