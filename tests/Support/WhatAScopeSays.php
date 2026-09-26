<?php

declare(strict_types=1);

namespace Tests\Support;

use function implode;

use Modules\Kernel\Api\AnExistingSetup;
use Modules\Kernel\Api\ScopeOfACopy;
use Modules\Kernel\Api\ServiceId;

use function sprintf;

/**
 * A copy's scope folded to one line, so two scopes can be compared as text.
 *
 * `whole`, `service:<name>` or `existing:<project>:<tree>,<tree>`. One
 * spelling for every suite that reads a scope, so a case in one file and a
 * case in another cannot mean different things by the same line.
 */
final readonly class WhatAScopeSays
{
    private function __construct(public string $said) {}

    public static function of(ScopeOfACopy $scope): string
    {
        return $scope->either(
            wholeStack: static fn(): self => new self('whole'),
            oneService: static fn(ServiceId $service): self => new self(sprintf('service:%s', $service->named())),
            existing: static function (AnExistingSetup $setup): self {
                $trees = [];

                foreach ($setup->trees() as $tree) {
                    $trees[] = $tree;
                }

                return new self(sprintf('existing:%s:%s', $setup->project(), implode(',', $trees)));
            },
        )->said;
    }
}
