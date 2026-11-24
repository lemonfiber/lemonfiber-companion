<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

/** The two jobs a screen that offers before it acts follows: working out the offer, and carrying out the yes given to it. */
enum TheOfferAndItsYes
{
    use TellsTheJobsApart;

    case TheOffer;
    case TheYes;
}
