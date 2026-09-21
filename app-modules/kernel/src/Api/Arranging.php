<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * What a stack is set to, asked of the stack rather than remembered here.
 *
 * **This app holds no list of settings, and that is the requirement rather
 * than an implementation note.** The operator is offered reconfiguration *in
 * full*, and the only listing that is ever in full is the one the stack sent:
 * an app enumerating the settings it knows about offers a subset the day the
 * stack gains one, and offers it silently. There is no version check that
 * would catch it and no error anywhere — the new setting is simply not on the
 * screen, and nobody finds out until somebody goes looking for it.
 *
 * So the port answers with whatever came back, unread and unfiltered, and the
 * screen draws that. The app's only contribution is the drawing.
 *
 * **Per stack, because settings belong to a machine.** A port taking no stack
 * would answer about whichever one was reached first, which on a device paired
 * with two houses is a coin toss. The session travels with it rather than
 * being held by whatever implements this, for the reason every other port
 * here takes one: a session is resumed per reading and may have been let go
 * of since the last, and an implementation holding one would answer with a
 * credential the app had already decided to stop using.
 *
 * **Reading only, for now.** Changing one is a second question with a
 * confirmation and a cost attached, and it is not folded in here: a port whose
 * one method both read and wrote would make every caller that only wanted to
 * look hold the capability to change.
 */
interface Arranging
{
    /**
     * What this stack says it is set to.
     *
     * Answers {@see HowItIsSet} rather than raising, which `C1` requires: a
     * stack that is asleep and a stack that declines are both ordinary states
     * of the world, and a method answering with nothing could report them only
     * by throwing.
     */
    public function asItStands(Stack $stack, Session $session): HowItIsSet;
}
