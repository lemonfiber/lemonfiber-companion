<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Handing something to the operator so that *they* can send it.
 *
 * `N4-R13` has two clauses and the second is the one that needs a design: a
 * diagnostic report is assembled for the operator to send, **and must not be
 * transmitted by the app**. {@see Diagnostics} keeps the first half by holding
 * nothing that could transmit; this keeps the second by going the other way —
 * it hands the report to the platform's share sheet and stops. Where it goes
 * next is a choice a person makes in an app this one does not know about.
 *
 * **The difference between this and a crash reporter is who pressed the
 * button**, which is why `N4-R12` refuses the other one and why the two must
 * not be one line apart. A port that could send on its own behalf would be that
 * line: this one takes no address, no endpoint and no client, so *send it
 * somewhere* has no spelling here.
 *
 * **It takes an {@see Assembled} and nothing else.** Not a string and a name,
 * which is the same pair and would let a caller hand over text this app did not
 * assemble — the parameter list is where `Diagnostics`' refusal to carry a
 * secret is inherited rather than re-checked.
 */
interface Sharing
{
    /**
     * Put it in front of the operator, or say why it could not be.
     *
     * Answers {@see Handed} rather than raising, which `C1` requires: a device
     * with nowhere to write a temporary file is an ordinary state of the world
     * — a full disk is the commonest one — and a method answering with nothing
     * could report it only by throwing, which makes it the case nothing checks.
     *
     * **Handing over is not sending, and this cannot tell whether it was
     * sent.** The share sheet belongs to the platform: an operator may pick an
     * app, or change their mind, and neither answer comes back. So `Handed`
     * says whether the sheet was reached, which is the whole of what this port
     * can honestly claim.
     */
    public function hand(Assembled $assembled): Handed;
}
