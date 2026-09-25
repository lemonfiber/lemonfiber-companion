<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * One invitation, as the stack answered it, flattened for the template.
 *
 * `rehearsed` is the first thing a template reads: a rehearsal is drawn as
 * what inviting them would come to, and never as an account that exists.
 * `mayBeSent` is true only on a rehearsal that found something to send, so
 * the yes is never offered for somebody who has already joined.
 */
final readonly class AnInvitationAsShown
{
    /**
     * @param bool                      $rehearsed    whether this only described the invitation
     * @param bool                      $mayBeSent    whether this is a rehearsal the operator may now agree to
     * @param string                    $standingSaid the catalogue key for what the stack found
     * @param string                    $askingSaid   the catalogue key for whether the request service knows them yet
     * @param AnInvitationToHandAsShown $toHand       the name, the address, its caution, the hours and whether to hand it over
     * @param WhatWasGrantedAsShown $granted      what it writes on the account
     * @param list<string>          $withdrawn    the invitations taken back on the way past, or that would be
     */
    public function __construct(
        public bool $rehearsed,
        public bool $mayBeSent,
        public string $standingSaid,
        public string $askingSaid,
        public AnInvitationToHandAsShown $toHand,
        public WhatWasGrantedAsShown $granted,
        public array $withdrawn,
    ) {}
}
