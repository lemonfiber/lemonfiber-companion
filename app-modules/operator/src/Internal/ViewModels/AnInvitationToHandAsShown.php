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
     * @param string                       $unjoinable why the invitation has no join link, in the stack's words, or empty
     * @param bool                         $lapses     whether it stands only for those hours: false for somebody who has already joined, whom nothing is withdrawn from
     * @param TheAddressesToHandOver|null $handed     what is handed over now, where there is anything: nothing on a rehearsal, and nothing for somebody who has already joined
     */
    public function __construct(
        public string $name,
        public string $url,
        public string $caution,
        public int $hours,
        public string $unjoinable,
        public bool $lapses,
        public ?TheAddressesToHandOver $handed = null,
    ) {}
}
