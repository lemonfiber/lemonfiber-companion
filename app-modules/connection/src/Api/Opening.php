<?php

declare(strict_types=1);

namespace Modules\Connection\Api;

use Modules\Kernel\Api\DeviceAuth;
use Modules\Kernel\Api\Launch;
use Modules\Kernel\Api\Networking;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Stacks;

/**
 * What the app found when it opened, decided once.
 *
 * `N1-R37` wants a launch with no network, a launch that cannot reach the stack
 * and a launch where the app is locked told apart. `N1-R35` and `N1-R36` add the
 * two that are not failures at all: no stack paired yet, and a stack paired and
 * ready. {@see Launch} is the shape those five collapse into four of, and until
 * this existed nothing produced one — the first screen looked at the stack list
 * and drew a conclusion from its length, which answers `N1-R35` and none of the
 * others.
 *
 * **The order is the requirement, not a convenience.** Locked is asked first,
 * and asked before anything touches a network. `N4-R19` requires the device's
 * own authentication on a cold start, and an app that reached a stack and then
 * asked for a passcode has already sent the credential it was holding — the
 * check would be theatre over a request that already happened.
 *
 * Pairing is asked second, because a device with no stack has nothing to reach
 * and no reason to ask the network anything. `N1-R35` sends that launch to a
 * screen offering pairing, and reporting it as a failure to reach would be the
 * app describing its own first run as a fault.
 *
 * The network is asked third and last, and only once a stack is known. It is
 * the one question about reaching a machine that can be answered without
 * sending anything, which is exactly why it belongs at a launch: a phone in
 * flight mode and a machine that is switched off produce the same silence at
 * the socket, and `N1-R37` wants them told apart. Asking it before the pairing
 * check would put the question to a device that has nothing to reach.
 *
 * **What this does not do is reach the stack.** `F4` says a frame is not where
 * a socket is opened and `N1-R66` says nothing but a cadence or the operator
 * makes a screen reach a machine, and opening
 * the app is the moment both are easiest to break — four paired machines, on a
 * home network, one of them asleep. So *ready* here means *paired, unlocked and
 * ready to be asked*, and the asking belongs to the screen the operator chose.
 * The `blocked` arm is therefore only ever reached by what a launch can learn
 * without sending anything, and having no network is the one thing that is.
 *
 * A query rather than a command, so it answers rather than refuses (`M1`):
 * *what did the app find* has no failure case, only five answers, and four of
 * them are ordinary.
 */
final readonly class Opening
{
    public function __construct(
        private DeviceAuth $device,
        private Stacks $stacks,
        private Networking $network,
    ) {}

    /**
     * Ask, in the order the requirements put the questions.
     *
     * Takes the unlock as a whole rather than asking whether the device *can*
     * authenticate and then authenticating: {@see DeviceAuth::isAvailable()}
     * answering true is a question a caller can forget to ask, and a caller who
     * forgets has written an app that opens unlocked on a device that offers a
     * passcode.
     */
    public function found(): Launch
    {
        // `N4-R19` requires the device's own authentication on a cold start, and
        // read without a qualifier that is true of a freshly installed app
        // holding nothing — where the prompt stands in front of a screen that
        // says there are no stacks yet. `N4-R22` names that case: a lock over
        // an empty store protects nothing, and a prompt protecting nothing is
        // how an operator learns the prompt is noise.
        //
        // `holdsAny()` rather than the list, and it is the whole of why that
        // method exists. The order below is the requirement — a shut app has
        // not read the operator's machines — and asking whether the record is
        // empty is not reading them. `N4-R23` wants exactly this: the store
        // itself, not a flag, so the unlocked first run cannot outlive it.
        if ($this->stacks->holdsAny() && $this->heldShut()) {
            return Launch::locked();
        }

        // Walked rather than counted and then walked. `isEmpty()` followed by a
        // read of the first entry is the same question asked twice, and the
        // second answer needs a branch for a case the first ruled out — which
        // is a line no test can reach and no mutation can be caught on.
        //
        // Which stack: the first the device holds, which is the order they were
        // paired in. Not a choice made on the operator's behalf — `N1-R31` is
        // emphatic that two stacks are not interchangeable — but the answer to
        // *which one is this launch about*, which the screen needs before it can
        // say anything. An operator with four machines meets the list.
        foreach ($this->stacks->configured() as $stack) {
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
                : Launch::blockedBy(Obstacle::DeviceHasNoNetwork);
        }

        // No stack paired, which is a first run rather than a fault (`N1-R35`).
        // Reached by falling out of the loop, so it is the ordinary answer for
        // an empty record rather than a guard against one.
        return Launch::unpaired();
    }

    /**
     * Whether the device refused to let the operator in.
     *
     * A device that offers no authentication at all is not locked. `N4-R3` says
     * every permission is optional and the app offers a working alternative for
     * each declined one, and a handset with no passcode set is the same
     * situation one step further back — refusing to open would be this app
     * requiring something the platform does not have, on a device where the
     * operator has already decided.
     */
    private function heldShut(): bool
    {
        if (! $this->device->isAvailable()) {
            return false;
        }

        return $this->device->unlock()->either(
            held: static fn(): WhetherItOpened => WhetherItOpened::itDidNot(),
            open: static fn(): WhetherItOpened => WhetherItOpened::itDid(),
        )->held;
    }

}
