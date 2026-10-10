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
     * @param string           $declines  the address that turns the invitation down, exactly as the stack sent it, or empty
     * @param list<list<bool>> $declineCode that address as squares, the same way, or empty
     * @param string           $joins       the join link the app opens the invitation at, exactly as the stack sent it, or empty
     * @param list<list<bool>> $joinCode    that link as squares, the same way, or empty; the first code shown where there is one
     * @param string           $unjoinable  why the invitation has no join link, in the stack's words, or empty
     * @param bool             $handsOver whether there is an address to hand over now: false on a rehearsal, and for somebody who has already joined
     * @param bool             $lapses    whether it stands only for those hours: false for somebody who has already joined, whom nothing is withdrawn from
     */
    public function __construct(
        public string $name,
        public string $url,
        public string $caution,
        public int $hours,
        public array $code,
        public string $declines,
        public array $declineCode,
        public string $joins,
        public array $joinCode,
        public string $unjoinable,
        public bool $handsOver,
        public bool $lapses,
    ) {}
}
