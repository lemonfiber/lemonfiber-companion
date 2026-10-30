<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/** The addresses an invitation hands over now, each exactly as the stack sent it and as squares to scan. */
final readonly class TheAddressesToHandOver
{
    /**
     * @param list<list<bool>> $code        the invitation's address as squares, dark where true, row by row; empty where none could be drawn
     * @param string           $declines    the address that turns the invitation down, or empty
     * @param list<list<bool>> $declineCode that address as squares, the same way, or empty
     * @param string           $joins       the join link the app opens the invitation at, or empty
     * @param list<list<bool>> $joinCode    that link as squares, the same way, or empty; the first code shown where there is one
     */
    public function __construct(
        public array $code,
        public string $declines,
        public array $declineCode,
        public string $joins,
        public array $joinCode,
    ) {}
}
