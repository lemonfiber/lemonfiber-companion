<?php

declare(strict_types=1);

namespace Bootstrap\Composition;

use function config;

use Illuminate\Support\ServiceProvider;

use function logger;

use Modules\Codes\Api\QrCodes;
use Modules\Kernel\Api\Adjusting;
use Modules\Kernel\Api\Admitting;
use Modules\Kernel\Api\Advising;
use Modules\Kernel\Api\Arranging;
use Modules\Kernel\Api\Asking;
use Modules\Kernel\Api\AskingForHelp;
use Modules\Kernel\Api\Cataloguing;
use Modules\Kernel\Api\ChoosingAFiller;
use Modules\Kernel\Api\ChoosingQuality;
use Modules\Kernel\Api\Clock;
use Modules\Kernel\Api\Copying;
use Modules\Kernel\Api\Encoding;
use Modules\Kernel\Api\Explaining;
use Modules\Kernel\Api\ExtendingTheStack;
use Modules\Kernel\Api\Granting;
use Modules\Kernel\Api\Guarding;
use Modules\Kernel\Api\HandingOverADevice;
use Modules\Kernel\Api\Hearing;
use Modules\Kernel\Api\HearingTheStart;
use Modules\Kernel\Api\HearingTheWalk;
use Modules\Kernel\Api\History;
use Modules\Kernel\Api\Hosting;
use Modules\Kernel\Api\Inviting;
use Modules\Kernel\Api\KeepingCurrent;
use Modules\Kernel\Api\KnowingWhatAStackOffers;
use Modules\Kernel\Api\Linking;
use Modules\Kernel\Api\MakingPairingCodes;
use Modules\Kernel\Api\Measuring;
use Modules\Kernel\Api\Mending;
use Modules\Kernel\Api\MovingIn;
use Modules\Kernel\Api\Outgoing;
use Modules\Kernel\Api\Owing;
use Modules\Kernel\Api\Provenance;
use Modules\Kernel\Api\PuttingARunBack;
use Modules\Kernel\Api\PuttingBack;
use Modules\Kernel\Api\Rationing;
use Modules\Kernel\Api\Reaching;
use Modules\Kernel\Api\ReadingNews;
use Modules\Kernel\Api\ReadingVersions;
use Modules\Kernel\Api\Rehearsing;
use Modules\Kernel\Api\RemovingSomebody;
use Modules\Kernel\Api\ResettingTheConfiguration;
use Modules\Kernel\Api\Safekeeping;
use Modules\Kernel\Api\Saying;
use Modules\Kernel\Api\SelfChecking;
use Modules\Kernel\Api\Stalling;
use Modules\Kernel\Api\StoppingSeeding;
use Modules\Kernel\Api\Storing;
use Modules\Kernel\Api\Supervising;
use Modules\Kernel\Api\TakingCopies;
use Modules\Kernel\Api\TakingLemonfiberOff;
use Modules\Kernel\Api\Telling;
use Modules\Kernel\Api\Tracing;
use Modules\Kernel\Api\UpgradingTheLibrary;
use Modules\Kernel\Api\WalkingThrough;
use Modules\Kernel\Api\Wanting;
use Modules\Kernel\Api\Watching;
use Modules\Kernel\Api\Welcoming;
use Modules\Kernel\Api\WiringTheServices;
use Modules\Sdk\Api\Adjustments;
use Modules\Sdk\Api\Admissions;
use Modules\Sdk\Api\Advisers;
use Modules\Sdk\Api\Archivists;
use Modules\Sdk\Api\Arrangements;
use Modules\Sdk\Api\Assessors;
use Modules\Sdk\Api\Bundlers;
use Modules\Sdk\Api\Cataloguers;
use Modules\Sdk\Api\Chroniclers;
use Modules\Sdk\Api\Clients;
use Modules\Sdk\Api\ClientsThatAskTheDevice;
use Modules\Sdk\Api\ClientsThatAskWhatIsOffered;
use Modules\Sdk\Api\Connectors;
use Modules\Sdk\Api\Copiers;
use Modules\Sdk\Api\Copyists;
use Modules\Sdk\Api\Dismantlers;
use Modules\Sdk\Api\Doorkeepers;
use Modules\Sdk\Api\Doors;
use Modules\Sdk\Api\Explainers;
use Modules\Sdk\Api\Extenders;
use Modules\Sdk\Api\Fillers;
use Modules\Sdk\Api\Followers;
use Modules\Sdk\Api\Graders;
use Modules\Sdk\Api\Grantors;
use Modules\Sdk\Api\Guards;
use Modules\Sdk\Api\Guides;
use Modules\Sdk\Api\Heralds;
use Modules\Sdk\Api\Inspectors;
use Modules\Sdk\Api\Keepers;
use Modules\Sdk\Api\Keyholders;
use Modules\Sdk\Api\Linkers;
use Modules\Sdk\Api\Listeners;
use Modules\Sdk\Api\Lookouts;
use Modules\Sdk\Api\Menders;
use Modules\Sdk\Api\Narrators;
use Modules\Sdk\Api\Newsreaders;
use Modules\Sdk\Api\Pairers;
use Modules\Sdk\Api\PinnedDoors;
use Modules\Sdk\Api\Quartermasters;
use Modules\Sdk\Api\Questions;
use Modules\Sdk\Api\Recorders;
use Modules\Sdk\Api\Rehearsers;
use Modules\Sdk\Api\Releasers;
use Modules\Sdk\Api\Removers;
use Modules\Sdk\Api\Requests;
use Modules\Sdk\Api\Resetters;
use Modules\Sdk\Api\Restorers;
use Modules\Sdk\Api\Reversers;
use Modules\Sdk\Api\Scouts;
use Modules\Sdk\Api\Scrollbacks;
use Modules\Sdk\Api\Shelves;
use Modules\Sdk\Api\Stalls;
use Modules\Sdk\Api\StartLines;
use Modules\Sdk\Api\Storekeepers;
use Modules\Sdk\Api\Supervisors;
use Modules\Sdk\Api\Surveyors;
use Modules\Sdk\Api\TheirOwn;
use Modules\Sdk\Api\Upgraders;
use Modules\Sdk\Api\Upkeepers;
use Modules\Sdk\Api\Ushers;
use Modules\Sdk\Api\Wirers;
use Modules\Sdk\Internal\WhatEachStackOffers;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * The composition root's half that binds the way to every stack: the one way
 * this application opens a connection to one, and every port a stack is asked
 * through, each answered in `modules/sdk`.
 *
 * A provider of its own beside {@see CompositionRoot}, which binds the
 * platform and what the phone keeps, so each holds one concern and neither
 * runs past the length a reader can hold.
 */
final class TheWayToEveryStack extends ServiceProvider
{
    /** The setting that says this build is one a developer runs. */
    private const string A_DEBUG_BUILD = 'app.debug';

    /** The one way to a stack, and every port a stack is asked through, each answered in `modules/sdk`. */
    public function register(): void
    {
        // The one way this application opens a connection to a stack.
        //
        // Bound rather than a singleton because a client is built for one stack
        // and holds that stack's pin — a single instance would be a client for
        // whichever machine was reached first, which is exactly the attribution
        // that must not happen.
        //
        // `modules/sdk` is the only manifest that requires the SDK, and
        // `NothingReachesAStackUnpinnedTest` refuses any other file that names
        // its transport. This line is where those two facts meet the container.
        //
        // Over the phone's own answers about its network, so that a stack that
        // went silent is told apart from a phone that has no way to it; and in
        // front of both, the stack asked first what it serves, so nothing is
        // sent that the stack does not declare. A stand-in takes the whole of
        // this binding over, and serves every request a stack can declare.
        $this->app->bind(Clients::class, $this->askingFirst(...));

        // What each stack last declared, held in memory for the life of the
        // process and never kept. A singleton for `WhatEachStackLastNamed`'s
        // reason: it is what one screen asks and the next screen draws, and a
        // button and the request behind it are answered from the same answer.
        $this->app->singleton(WhatEachStackOffers::class);

        // The same answer, asked by a screen before it draws a button.
        $this->app->bind(KnowingWhatAStackOffers::class, Assessors::class);

        // A silent reach is noted for a developer in a debug build's log, and
        // in no other build: a release is handed a log that keeps nothing.
        $this->app->when(ClientsThatAskTheDevice::class)
            ->needs(LoggerInterface::class)
            ->give(static fn(): LoggerInterface => config(self::A_DEBUG_BUILD) === true ? logger() : new NullLogger());

        // The kernel's name for the same thing, so a capability can say *a way
        // to reach a stack* without naming the SDK. One binding rather than two
        // adapters: the narrowed interface extends the port, so what answers
        // here is whatever answers above — and a stand-in put over one of them
        // cannot be missed by a caller that asked for the other.
        $this->app->bind(Reaching::class, Clients::class);

        // The one place a credential is offered to a stack, bound beside the
        // client for the same reason: `modules/sdk` is the only manifest that
        // requires the SDK, so the two adapters that name its doors are the two
        // lines here that come from it.
        //
        // Bound rather than a singleton, and this one matters more than the
        // client's. A door is opened for one stack with one credential; an
        // instance held across two would be an object that has already been
        // handed a password, and nothing may hold a credential for re-sending.
        // It holds no state today — the binding is what keeps that true of
        // whatever it grows into.
        $this->app->bind(Doors::class, PinnedDoors::class);
        $this->app->bind(Admitting::class, Admissions::class);

        // Asking a stack how it is, which is what a session is opened for.
        //
        // Bound rather than a singleton, and taking the client factory rather
        // than the `Reaching` port it implements: the port answers `object` so
        // that the kernel never names the SDK's client, which is what the port
        // is for — and a caller needing to call a method on one would have to
        // narrow, which is a branch nothing can reach. Both classes live in
        // `modules/sdk`, so no boundary is crossed by using the real type.
        $this->app->bind(Asking::class, Questions::class);

        // Holding a stack's event stream open, for the health summary the core
        // publishes there and nowhere else.
        //
        // Bound rather than a singleton, and here it is the whole point: one
        // holds one connection for one screen, so each screen is handed its
        // own, and a screen letting go of its connection lets go of nobody
        // else's.
        $this->app->bind(Hearing::class, Listeners::class);

        // Holding the same stream for the steps a running walk says, which the
        // stack narrates there as they happen. Bound rather than a singleton,
        // for the reason above: the screen following a walk holds its own.
        $this->app->bind(HearingTheWalk::class, Narrators::class);

        // Holding the same stream for what a running start is waiting for,
        // which the stack says there as it waits. Bound rather than a
        // singleton, for the reason above: the screen that sent the start
        // holds its own.
        $this->app->bind(HearingTheStart::class, StartLines::class);

        // What the household has asked its stack for, which the requests screen
        // reads. Beside `Asking` and built the same way: both go through
        // `PinnedClients`, so there is one place a certificate is checked.
        $this->app->bind(Wanting::class, Requests::class);

        // What a member is owed, which is the same endpoint read as a reading
        // about one person rather than as a list of everything a house asked
        // for. Two bindings over one payload rather than one port answering
        // both, because the two questions differ in who the answer is about:
        // the operator's read flattens the house and loses the member, and a
        // member's read is nothing but the member.
        $this->app->bind(Owing::class, TheirOwn::class);
        $this->app->bind(Watching::class, Shelves::class);

        // A grant for this device to play a member's titles, asked of the core
        // under the member's own session.
        $this->app->bind(Granting::class, Grantors::class);

        // What a stack would put right, asked without changing anything.
        // `Repair::offer()` is the unconfirmed form and the SDK makes the two
        // refused consent arrangements unrepresentable, so nothing bound here
        // can turn the question into an instruction.
        //
        // The agreement half of it does change a stack, so it is handed the
        // randomness a key for one attempt is minted from.
        $this->app->bind(Mending::class, Menders::class);

        // What a stack is set to, read every time it is shown rather than
        // held. Offering reconfiguration in full is a property of the listing
        // that came back — an app that remembered one would be right until the
        // stack gained a setting and then quietly short, with nothing on the
        // screen saying when it was taken.
        $this->app->bind(Arranging::class, Arrangements::class);

        // Changing one. Apart from the reading above rather than folded into
        // it, so a caller that only wants to look is not also handed the
        // capability to write.
        $this->app->bind(Adjusting::class, Adjustments::class);

        // What has stopped coming in, the first of four.

        // What one service has been saying, bounded and named.
        // Beside the two above and built the same way, because the reason they
        // share a constructor is the pin: one place decides whether a
        // certificate is checked, and a port that built its own client would be
        // a second.
        $this->app->bind(Stalling::class, Stalls::class);

        // What the machine keeps running with nobody signed in. Beside the
        // three above because it shares their reason for existing at all: one
        // place decides whether a certificate is checked, and a port that built
        // its own client would be a second.
        $this->app->bind(Hosting::class, Keepers::class);

        // The record a stack keeps of what it changed. Beside the others for the
        // reason they share: one place decides whether a certificate is checked,
        // and a port that built its own client would be a second.
        $this->app->bind(History::class, Recorders::class);

        // Where a stack's services come from, the other half of what it keeps
        // about itself, and bound beside the record for the same reason.
        $this->app->bind(Provenance::class, Archivists::class);

        // What each service is for, read out of the same stack description as
        // where it comes from, and bound beside it for the same reason.
        $this->app->bind(Cataloguing::class, Cataloguers::class);

        // What the stack wires to what, bound beside what each service is for
        // for the same reason: one place decides whether a certificate is
        // checked.
        $this->app->bind(Linking::class, Linkers::class);

        // Choosing what fills a capability, bound beside what the stack wires
        // to what for the same reason.
        $this->app->bind(ChoosingAFiller::class, Fillers::class);

        // What leaves a stack, bound beside what it keeps about itself for the
        // same reason: one place decides whether a certificate is checked.
        $this->app->bind(Outgoing::class, Lookouts::class);

        // What the operator is told about, read beside the rest and bound for
        // the same reason: one place decides whether a certificate is checked.
        $this->app->bind(Telling::class, Heralds::class);

        // How the line is shared, read beside the rest and bound for the same
        // reason: one place decides whether a certificate is checked.
        $this->app->bind(Rationing::class, Quartermasters::class);

        // What the stack keeps and the copies it holds, read beside the rest
        // and bound for the same reason.
        $this->app->bind(Storing::class, Storekeepers::class);
        $this->app->bind(Copying::class, Copyists::class);

        // Taking a copy and putting one back, bound beside the listing of
        // copies for the same reason.
        $this->app->bind(TakingCopies::class, Copiers::class);
        $this->app->bind(MakingPairingCodes::class, Pairers::class);
        $this->app->bind(HandingOverADevice::class, Connectors::class);
        $this->app->bind(PuttingBack::class, Restorers::class);

        // Putting the configuration back, bound beside putting a copy back:
        // both are asked what they would do before a yes.
        $this->app->bind(ResettingTheConfiguration::class, Resetters::class);

        // A support bundle, described and written, asked beside the rest and
        // bound for the same reason.
        $this->app->bind(AskingForHelp::class, Bundlers::class);

        // Putting back one run the record shows, bound beside putting a copy
        // back for the same reason.
        $this->app->bind(PuttingARunBack::class, Reversers::class);

        // How full the machine is, read beside the rest and bound for the
        // same reason.
        $this->app->bind(Measuring::class, Surveyors::class);

        // Stopping seeding one download, beside how full the machine is and
        // bound for the same reason.
        $this->app->bind(StoppingSeeding::class, Releasers::class);

        // The running copy of lemonfiber, read beside the rest and bound for
        // the same reason.
        $this->app->bind(SelfChecking::class, Inspectors::class);
        $this->app->bind(ReadingVersions::class, Chroniclers::class);

        // Who gets in: the credentials, which app to watch on, and the front
        // door, each read beside the rest and bound for the same reason.
        $this->app->bind(Safekeeping::class, Keyholders::class);
        $this->app->bind(Advising::class, Advisers::class);
        $this->app->bind(Welcoming::class, Doorkeepers::class);
        $this->app->bind(ReadingNews::class, Newsreaders::class);

        // Asking somebody in goes through the same door as every other action,
        // and the address it answers with is drawn as a code by an encoder
        // that reaches nothing: it is handed the address and draws squares.
        $this->app->bind(Inviting::class, Ushers::class);
        $this->app->bind(Encoding::class, QrCodes::class);

        // Taking somebody out of the household goes through the same door,
        // said as what it would cost before it is agreed to.
        $this->app->bind(RemovingSomebody::class, Removers::class);

        // What is already on the machine, before anything is moved in, read
        // beside the rest and bound for the same reason.
        $this->app->bind(MovingIn::class, Scouts::class);

        // Wiring the services to each other, which a run reports connection by
        // connection.
        $this->app->bind(WiringTheServices::class, Wirers::class);

        // How good the media should be: choosing a preset, and upgrading what
        // is already here, which is its own act.
        $this->app->bind(ChoosingQuality::class, Graders::class);
        $this->app->bind(UpgradingTheLibrary::class, Upgraders::class);

        // Taking lemonfiber off the machine: a reading, and the removal agreed
        // against it, bound beside the rest for the same reason.
        $this->app->bind(TakingLemonfiberOff::class, Dismantlers::class);

        // The plugins that extend the stack: what is installed, a rehearsal of
        // an install, and the install agreed against it.
        $this->app->bind(ExtendingTheStack::class, Extenders::class);

        $this->app->bind(Explaining::class, Explainers::class);
        $this->app->bind(Rehearsing::class, Rehearsers::class);
        $this->app->bind(Tracing::class, Followers::class);
        $this->app->bind(WalkingThrough::class, Guides::class);

        // A guard on the data location, started, followed and let go through
        // the same door as every other action, and bound for the same reason.
        $this->app->bind(Guarding::class, Guards::class);

        $this->app->bind(Saying::class, Scrollbacks::class);

        // What a stack is running, and the three verbs offered about it.
        // The one binding that both reads and writes, which is the shape the
        // port argues for: an operator reads a listing, picks a row and says a
        // verb, and a second port for the verb would be a second place a client
        // could be reached for.
        //
        // Handed randomness for the same reason the repairs adapter is: a verb
        // changes a stack, and an action that changes one names the attempt it
        // is part of.
        $this->app->bind(Supervising::class, Supervisors::class);

        // Where a stack stands on being up to date, and taking one. Both halves
        // on one binding for the reason supervising is: an operator reads what
        // is waiting, agrees to it, and a second port for the agreeing would be
        // a second place a client could be reached for.
        $this->app->bind(KeepingCurrent::class, Upkeepers::class);
    }

    /**
     * The clients that ask the phone, with the stack asked first what it serves.
     *
     * A method rather than a closure, because `make()` raises a checked
     * exception and a closure's caller cannot see what it throws.
     */
    private function askingFirst(): Clients
    {
        return new ClientsThatAskWhatIsOffered(
            $this->app->make(ClientsThatAskTheDevice::class),
            $this->app->make(WhatEachStackOffers::class),
            $this->app->make(Clock::class),
        );
    }
}
