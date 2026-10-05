<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Handing something to the operator so that *they* can send it.
 *
 * There are two clauses and the second is the one that needs a design: a
 * diagnostic report is assembled for the operator to send, **and must not be
 * transmitted by the app**. {@see Diagnostics} keeps the first half by holding
 * nothing that could transmit; this keeps the second by going the other way —
 * it hands the report to the platform's share sheet and stops. Where it goes
 * next is a choice a person makes in an app this one does not know about.
 * A support bundle is handed over the same way, and by nothing else.
 *
 * **The difference between this and a crash reporter is who pressed the
 * button**, which is why sending it is refused and why the two must
 * not be one line apart. A port that could send on its own behalf would be that
 * line: this one takes no address, no endpoint and no client, so *send it
 * somewhere* has no spelling here.
 *
 * **Each method takes one kernel value and nothing else.** Not a string and a
 * name, which is the same pair and would let a caller hand over text this app
 * did not assemble, or bytes no stack served — the parameter list is where
 * `Diagnostics`' refusal to carry a secret, and a bundle's being the stack's
 * own file, are inherited rather than re-checked.
 *
 * **Text is handed over as text; a file is written once, and swept.** A report
 * and an invitation travel as text and nothing is written. A bundle is an
 * archive, and the device writes it into one app-private directory used for
 * nothing else, grants the chosen app read access to that one file, and empties
 * the directory before every new handover and on the next launch: at most one
 * bundle is ever on the device.
 */
interface Sharing
{
    /**
     * Put it in front of the operator, or say why it could not be.
     *
     * Answers {@see Handed} rather than raising: a platform with no sheet to
     * offer is an ordinary state of the world, and a method answering with
     * nothing could report it only by throwing, which makes it the case nothing
     * checks.
     *
     * **Handing over is not sending, and this cannot tell whether it was
     * sent.** The share sheet belongs to the platform: an operator may pick an
     * app, or change their mind, and neither answer comes back. So `Handed`
     * says whether the sheet was reached, which is the whole of what this port
     * can honestly claim.
     */
    public function hand(Assembled $assembled): Handed;

    /**
     * Put an invitation in front of the operator to pass on, or say why it could not be.
     *
     * The same sheet and the same answer as a report. It takes an
     * {@see AnInvitationToPassOn}, whose text is the stack's address and its
     * caution under a covering sentence, so nothing but an address the stack
     * gave can be handed over this way, and nobody is sent anything by this
     * app: who receives it is the operator's choice in an app this one does
     * not know about.
     */
    public function passOn(AnInvitationToPassOn $invitation): Handed;

    /**
     * Put a support bundle's file in front of the operator to hand over, or say why it could not be.
     *
     * The same sheet and the same answer. It takes an {@see ABundleFile}, which
     * only a bundle the stack wrote and served can be, so nothing but that file
     * is handed over this way. A device with nowhere to write the file is
     * *nothing to hand over*, and a full disk is the commonest cause.
     */
    public function handOver(ABundleFile $bundle): Handed;
}
