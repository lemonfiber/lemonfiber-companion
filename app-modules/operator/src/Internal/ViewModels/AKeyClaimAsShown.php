<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/** A key minted as a claim, as the screen hands it over: its name, the link, the link as a code, and when it lapses. */
final readonly class AKeyClaimAsShown
{
    /**
     * @param list<list<bool>> $squares the link as a code, rows of squares dark where true, or none where it could not be drawn
     * @param int              $lapsesAt when the link stops collecting anything, in seconds since the epoch, or 0 where the stack's moment could not be read
     */
    public function __construct(
        public string $name,
        public string $link,
        public array $squares,
        public int $lapsesAt,
    ) {}
}
