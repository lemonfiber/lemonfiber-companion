<?php

declare(strict_types=1);

namespace Modules\Connection\Api;

use Modules\Kernel\Api\Configured;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Launch;
use Modules\Kernel\Api\Networking;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Stacks;
use Modules\Kernel\Api\WhyTheStacksAreHeldBack;

/**
 * What the app found when it opened, decided once.
 *
 * A launch that cannot reach the stack is told apart from the two that are not
 * failures at all: no stack paired yet, and a stack paired and ready.
 * {@see Launch} is that shape.
 *
 * **The lock is not asked here.** The screen that asks this is built only
 * once the lock has opened, so nothing here runs before the device's own
 * authentication — and nothing reaches a network before it either.
 *
 * Whether the record could be read at all is asked before anything else. A
 * record held back is stacks still on the phone that this launch cannot list,
 * and it is neither a first run nor a stack to reach.
 *
 * Pairing is asked next, because a device with no stack has nothing to reach
 * and no reason to ask the network anything. A first run goes to a
 * screen offering pairing, and reporting it as a failure to reach would be the
 * app describing its own first run as a fault.
 *
 * The network is asked second and last, and only once a stack is known. It is
 * the one question about reaching a machine that can be answered without
 * sending anything, which is exactly why it belongs at a launch: a phone in
 * flight mode and a machine that is switched off produce the same silence at
 * the socket, and they are told apart. Asking it before the pairing
 * check would put the question to a device that has nothing to reach.
 *
 * **What this does not do is reach the stack.** A frame is not where a socket
 * is opened, and nothing but a declared cadence or the operator makes a screen
 * reach a machine, and opening the app is the moment both are easiest to break
 * — four paired machines, on a home network, one of them asleep. So *ready*
 * here means *paired, unlocked and ready to be asked*, and the asking belongs
 * to the screen the operator chose. The `blocked` arm is therefore only ever
 * reached by what a launch can learn without sending anything, and having no
 * network is the one thing that is.
 *
 * A query rather than a command, so it answers rather than refuses: *what did
 * the app find* has no failure case, only four answers, and two of them are
 * ordinary.
 */
final readonly class Opening
{
    public function __construct(
        private Stacks $stacks,
        private Networking $network,
    ) {}

    /** Ask, in the order the requirements put the questions. */
    public function found(): Launch
    {
        return $this->stacks->configured()->either(
            listed: fn(Configured $configured): Launch => $this->foundAmong($configured),
            heldBack: static fn(WhyTheStacksAreHeldBack $why): Launch => Launch::heldBack($why),
        );
    }

    /** What a launch found among the stacks it could list. */
    private function foundAmong(Configured $configured): Launch
    {
        // Walked rather than counted and then walked. `isEmpty()` followed by a
        // read of the first entry is the same question asked twice, and the
        // second answer needs a branch for a case the first ruled out — which
        // is a line no test can reach and no mutation can be caught on.
        //
        // Which stack: the first the device holds, which is the order they were
        // paired in. Not a choice made on the operator's behalf — the rule is
        // emphatic that two stacks are not interchangeable — but the answer to
        // *which one is this launch about*, which the screen needs before it can
        // say anything. An operator with four machines meets the list.
        foreach ($configured as $stack) {
            // Asked here rather than above the loop, so a first run never puts
            // the question at all. An unpaired device has nowhere to send
            // anything, and telling somebody their wifi is off when what they
            // have not done yet is pair a machine is an answer to a question
            // they did not ask.
            //
            // Only the refusal decides anything. A connected device is not a
            // reachable stack — the machine can still be asleep, on another
            // network, or behind a permission this app has not been granted —
            // so an affirmative here is permission to try, and the trying
            // belongs to the screen.
            return $this->network->isConnected()
                ? Launch::ready($stack->id())
                : Launch::blockedBy(Obstacle::of(KindOfObstacle::DeviceHasNoNetwork));
        }

        // No stack paired, which is a first run rather than a fault.
        // Reached by falling out of the loop, so it is the ordinary answer for
        // an empty record rather than a guard against one.
        return Launch::unpaired();
    }
}
