<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * The name, the address and how long it stands, flattened for the template.
 *
 * The address is the stack's text and the code is a picture of that text and
 * of nothing else; neither is built here.
 */
final readonly class AnInvitationToHandAsShown
{
    /**
     * @param string           $name    the name they sign in as
     * @param string           $url     the address, exactly as the stack sent it
     * @param string           $caution what the stack said about the address, or empty
     * @param int              $hours   how many hours it stands before it is withdrawn
     * @param list<list<bool>> $code      the address as squares, dark where true, row by row; empty where none could be drawn or none is to be handed over
     * @param bool             $handsOver whether there is an address to hand over now: false on a rehearsal, and for somebody who has already joined
     * @param bool             $lapses    whether it stands only for those hours: false for somebody who has already joined, whom nothing is withdrawn from
     */
    public function __construct(
        public string $name,
        public string $url,
        public string $caution,
        public int $hours,
        public array $code,
        public bool $handsOver,
        public bool $lapses,
    ) {}
}
