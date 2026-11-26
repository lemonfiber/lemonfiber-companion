<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * The backup or restore running on a stack, as the stack's one reading of it says.
 */
final readonly class ACopyRunning
{
    /**
     * @param int $written bytes written so far
     * @param int $whole   bytes it will write
     * @param string $part the area being written now, named
     */
    public function __construct(
        public WhatIsCopied $what,
        public ScopeOfACopy $scope,
        public Instant $started,
        public int $written,
        public int $whole,
        public string $part,
        public HowTheCopyMoves $moves,
    ) {}
}
