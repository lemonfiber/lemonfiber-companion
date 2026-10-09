<?php

declare(strict_types=1);

use Lemonfiber\Sdk\ActionRequest;
use Lemonfiber\Sdk\Client;
use Modules\Kernel\Api\ABundleAsked;
use Modules\Kernel\Api\AgainstThePins;
use Modules\Kernel\Api\AGuardAskedFor;
use Modules\Kernel\Api\AskingThemIn;
use Modules\Kernel\Api\ConnectingADevice;
use Modules\Kernel\Api\ExtendingIt;
use Modules\Kernel\Api\Forms;
use Modules\Kernel\Api\HandingOver;
use Modules\Kernel\Api\HowManyLines;
use Modules\Kernel\Api\HowServicesTookIt;
use Modules\Kernel\Api\HowTheNotesStand;
use Modules\Kernel\Api\MovingInBy;
use Modules\Kernel\Api\Releases;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\Services;
use Modules\Kernel\Api\SettingsToReveal;
use Modules\Kernel\Api\TakingAnUpdate;
use Modules\Kernel\Api\TakingItOff;
use Modules\Kernel\Api\TakingThemOut;
use Modules\Kernel\Api\TheStackEdits;
use Modules\Kernel\Api\Upkeep;
use Modules\Kernel\Api\WhatFilenamesShow;
use Modules\Kernel\Api\WhatThePlayerAsks;
use Modules\Kernel\Api\WhatToChange;
use Modules\Kernel\Api\WhatToDoAboutPairing;
use Modules\Kernel\Api\WhatToDoAboutQuality;
use Modules\Kernel\Api\WhatToDoAboutWiring;
use Modules\Kernel\Api\WhatToDoWithACopy;
use Modules\Kernel\Api\WhatToDoWithADownload;
use Modules\Kernel\Api\WhatToDoWithARun;
use Modules\Kernel\Api\WhatToDoWithIt;
use Modules\Kernel\Api\WhatToWalk;
use Modules\Kernel\Api\WhatWasDecided;
use Tests\Support\Tree;

// The doors this application is allowed to open on a stack.
//
// Two requirements with one shape. The app must not offer first-run
// setup, and must not offer to set or change a credential's
// value. Both are about writes, and both are stated in the spec as things a
// screen must not do — which is a rule about prose, and prose is what the
// household module's README argues cannot be checked without a rule that fires
// on its own documentation.
//
// What can be checked is the door. The SDK's client publishes a handful of
// methods and one of them, `act()`, takes an action — it is how anything on a
// stack is changed, and it is the only way this app could ever configure a
// machine or write a credential. A screen that offered setup would have to call
// it. So the rule is a list of the doors this app opens, each with the reason it
// is open.
//
// What goes through `act()` is held by the type: it takes an `ActionRequest`,
// and the SDK generates one class per action the contract lists, so an action
// is named by the class that asks for it and never by a string at a call site.
// Two things are left to check here. An action class of the app's own would be
// a name this app chose, so none is allowed. And the actions the app does build
// are checked against the list of reasons in both directions, because the
// forward check catches one that lost its reason and only the reverse catches
// the verb somebody adds to the list by hand.
//
// The list is compared against the client rather than trusted: a method renamed
// in the SDK fails here rather than leaving this rule describing a door that no
// longer exists, which is the failure mode a hand-kept list has.

/** Every method this application calls on an SDK client, and why that one. */
const DOORS_THE_APP_OPENS = [
    // One read per frame: a screen reads once and renders what came back. Every
    // read this app does — the doctor run, the household, what has stopped —
    // goes through this one.
    'read' => 'reads an envelope from a named endpoint, changing nothing',

    // The bounded read. Its own door rather than `read()` because the
    // answer is one document a line rather than one envelope.
    'logs' => 'reads the tail of one service, bounded and named',

    // The event stream, which is where the core publishes the health summary
    // and nowhere else, and where it says each step of a running walk as it
    // happens. It reads and changes nothing, and a screen holds it only while
    // somebody can see it.
    'eventSource' => 'listens to the stack\'s event stream for the health summary and the steps of a running walk, changing nothing',

    // The one read whose answer is a file rather than an envelope: a support
    // bundle the stack already wrote, fetched by the name its path ends in so
    // the operator can hand it over through the device's own sharing. It
    // changes nothing on the machine, and nothing is sent back through it.
    'bundle' => 'reads one support bundle the stack wrote, by its name, changing nothing',

    // The one thing this app can ask a stack to change,
    // and it takes a `Repair` the stack itself offered rather than an endpoint
    // and a body. A caller cannot spell an arbitrary change through it.
    'repair' => 'carries out a repair the stack offered, against the reading it was offered on',

    // The other half of an undelivered action: work that answered with a name is asked after by
    // that name. It reads and changes nothing — a job's standing is what the
    // stack already decided — and it is how a repair's outcome arrives at all.
    // It was opened when the repairs screen was built and this rule could not see it: the
    // call is chained off `client(...)` rather than landing in a variable, and
    // the rule matched a receiver.
    'whatBecameOf' => 'asks what became of work the stack named, changing nothing',

    // The one way to end work the stack named. Opened for the guard on the
    // data location alone, which has no ending of its own: the screen holding
    // it lets it go when it is left, rather than leaving it to lapse.
    'letGoOf' => 'ends a guard this app started, when the screen holding it is left',

    // The verbs below, and nothing else can reach it: it takes an action the
    // SDK generates from the contract, and the rule below holds the ones this
    // app builds to the list.
    'act' => 'asks for one of the verbs the list below explains, as the class the SDK generates for it',

    // The one door that does nothing to anybody's machine: it answers every
    // request from a mock, so none reaches a stack. Opened in exactly one place
    // and for the opposite of the usual reason — `Modules\Dx\Api\ClientsThatReachNothing`
    // uses it so that a build somebody is looking at answers every screen
    // without a stack and without a socket.
    //
    // Listed rather than exempted, because the rule is about what this app *can*
    // do and standing a mock in for a stack is a real capability. What bounds it
    // is the SDK: the client still refuses a request for anywhere but its own
    // stack, and still holds that stack to the pin it was introduced under.
    'withMockClient' => 'answers every request from the contract instead of the network, so a stand-in reaches no stack',
];

/** Every verb this application can ask a stack for, and why that one. */
const VERBS_THE_APP_ASKS_FOR = [
    // The one that writes a setting, and the one this list exists to be read
    // carefully about. Unconfirmed it changes nothing and answers with the
    // review; confirmed it writes. Both go through this name, and which of
    // the two happened is decided by an argument rather than by a second
    // verb — so a reader asking *what can this app write* gets one answer
    // here rather than having to find the flag.
    //
    // It cannot reach a credential's value. The app offers a change only on a
    // setting the stack showed it a value for, and a withheld one is not a
    // value — so the control an operator would type into never appears beside
    // one. That is a property of the type the listing is read into rather
    // than of this list, which is why it is worth saying here: this line
    // would otherwise read as the app being able to set anything.
    'config-set' => 'puts a value in one setting, having first been told what that would come to',

    // Two requests under one name. Unconfirmed it compares the operator's
    // files and connections with lemonfiber's and writes nothing; confirmed it
    // writes lemonfiber's back. The yes is only ever sent after a preview that
    // would revert something, which is the one thing `AResetAgreed` can be
    // built from. The app names no value: what the stack writes is
    // lemonfiber's own configuration over the operator's edits, and the
    // preview shows a withheld line masked, as every diff of a stack file is.
    'reset' => 'puts every edited file and connection back to lemonfiber\'s own, having first previewed what that reverts',

    // A start disturbs nothing, which is why it is the one verb
    // that asks for no confirmation.
    'up' => 'starts a form, or a service inside one',

    // A fetch takes nothing away and brings nothing up: a form's images are
    // brought onto the machine ahead of a start. It is said before it runs
    // that it may take long and use a lot of the line, and it names a form,
    // never one service.
    'pull' => 'fetches a form\'s images ahead of starting it',

    // The verb a confirmation exists for: it takes something away from
    // everybody in the house until somebody says otherwise.
    'down' => 'stops a form, or a service inside one',

    // A stop that means to come back, and still a gap the household
    // is in — so it is asked about as the stop is.
    'restart' => 'stops and starts it again',

    // The household's player asking for a grant to play on the member's own
    // account, for this device. It changes nothing on the machine; the core
    // opens a session on the media server and answers its token once.
    'grant' => 'asks for a grant for this device to play the member\'s titles',

    // The player telling the core where the member is, so the core keeps their
    // place. It changes nothing on the machine but that member's place.
    'watched' => 'tells the core where the member is in what they are watching',

    // The household's two, which are not verbs about a machine at all. They settle
    // one thing somebody in the house already asked for, and they are here
    // because this list is about what may go through `act()` rather
    // than about services — a second list would be a second answer to *what
    // can this app ask a stack to do*, and the two would drift.
    // This line is the requirement: a pending request is approvable from lemonfiber
    // without opening Seerr. The verb going to the stack's own endpoint is what
    // makes that true — there is no road from this app to the tool the request
    // came from, and this is the one that replaces it.
    'household-approve' => 'gives one waiting request the thing it asked for',

    // This one carries a sentence because a refusal owes the person who
    // asked a reason, and {@see \Modules\Kernel\Api\Decided} cannot be built
    // without one.
    'household-decline' => 'turns one waiting request down, with the reason it was turned down for',

    // The one verb that moves the stack onto different software. It cannot be
    // asked for bare: the only thing that spells it is an agreement naming the
    // release and the services it would change, and a release is one the stack
    // listed as waiting — so what reaches the wire is an update somebody was
    // shown and said yes to.
    'update' => 'takes a release the stack listed as waiting, against the services the operator was told it would change',

    'invite' => 'says what an invitation would grant and when it lapses, and makes the account only when that same request is agreed to',

    'reissue' => 'takes a member\'s password off so they choose the next one, naming the person and never a password',

    'household-handoff' => 'hands one member\'s device the server\'s address and reads whether a device of theirs has signed in since; it writes down only when the code was first given, carries no credential, and nothing is asked until the operator taps',
    'companion-pair' => 'makes a fresh pairing code another phone adds this stack with; it carries no credential and admits nobody, and nothing is asked until the operator taps',

    // Two requests under one name. Without the yes it says what taking
    // somebody out would cost and takes nobody out; with it, it takes out the
    // person that reading named, and nobody else.
    'remove' => 'says what taking one member out of the household would cost, and takes them out only on a yes given beneath that reading',
    // Unconfirmed it records the choice, or holds one this machine would
    // transcode in software; confirmed it records a held one. The yes is only
    // ever sent with a choice the stack held, which is the one thing
    // `AHeldChoice` can be built from.
    'quality-set' => 'chooses a quality preset, and confirms one the stack held because this machine would transcode it',

    // Unconfirmed it only says what it would come to; confirmed it asks each
    // service to search again. The yes is only ever sent after a description,
    // which is the one thing `AnUpgradeDescribed` can be built from.
    'quality-upgrade' => 'upgrades what is already in the library, having first said what that comes to kind by kind',

    // A copy of the stack's configuration, of the whole stack or one service.
    // It overwrites nothing; what it can remove is older copies, which the
    // stack reports with the copy.
    'backup' => 'takes a copy of the whole stack or of one service, and says which older copies it removed',

    // Two requests under one name. Without the yes it reads a copy's own
    // account of itself and changes nothing; with it, it puts back exactly
    // the listing the operator was shown, and nothing else, because the yes
    // quotes that listing by name.
    'restore' => 'says what putting one copy back would do, and puts it back only against that listing',

    // Two requests under one name, each answered as work. Naming only the
    // download it says what letting it go would cost and lets nothing go;
    // naming the offer as well, it asks the download client to let that one
    // download go. There is no blanket yes: the offer's own name is the only
    // one the stack takes.
    'stop-seeding' => 'says what stopping seeding one download would cost, and stops it only against that offer',
    // One run the record shows, named by its stamp. It takes no yes and offers
    // no rehearsal over the wire, so the only thing that spells it is an
    // agreement built from the record's own rows for that run — and a run the
    // record says cannot go back builds none.
    'undo' => 'puts back one run the record shows, named by its stamp, after the record\'s rows for it were agreed to',
    // One removal at a time, and only against the reading of it the operator
    // was shown: the yes quotes that reading by name, so a reading that has
    // moved on is refused by the stack rather than carried out.
    'uninstall' => 'takes one of the four removals off the machine, against the reading of it the operator was shown and agreed to',

    // A long-running command handed to the machine's service manager, or taken
    // back from it. Each is asked for on its own and named by the command, so
    // nothing comes to run on a machine as a side effect of another act.
    'hosting-install' => 'hands one command the stack hosts to the machine\'s service manager, which keeps it running',
    'hosting-remove' => 'takes one command back from the machine\'s service manager, so it runs only while a terminal holds it',

    // A plugin, rehearsed and then installed. Asked with no offer it is the
    // reading and writes nothing; asked with the reading's name and every
    // value a recipe carries elsewhere approved as itself, it installs what
    // was shown. The stack runs the plugin's proofs and its own checks, and
    // puts the install back where either does not hold.
    'plugin-install' => 'rehearses installing one plugin, and installs the one rehearsed on a second yes with each value it sends approved',

    // The same two parts for a plugin already installed. An update fetches
    // the source the record names again, takes the installed version's
    // changes off, and puts it back on where the new one does not hold; a
    // removal takes the plugin's changes off and writes the record last.
    'plugin-update' => 'rehearses updating one installed plugin from the source its record names, and updates it on a second yes with each value it sends approved',
    'plugin-remove' => 'rehearses removing one installed plugin, naming every service it stops and every capability it leaves unfilled, and removes it on a second yes',

    // The one verb that fetches something. It names at most a title, and
    // with none the stack chooses something likely to work; the stack
    // refuses to grab outside the tunnel and never re-fetches what is
    // already here, so what it can do is what an operator asked to watch.
    'walkthrough' => 'fetches one thing while the operator watches, narrated end to end, on a stack already set up',

    // A support bundle, described and then written. Described, it writes
    // nothing; written, it is a file on the stack's own machine, holding
    // what the description listed. A setting is shown as it is only where
    // the operator named it and agreed to it on its own.
    'support' => 'describes a support bundle, and writes the one described on a second yes',

    // Moving in beside what is already on the machine, one verb per mode.
    // Each is asked twice: without the yes it says what it would come to and
    // does nothing; with it, it is carried out. The yes is only ever sent
    // after a pending answer to the same verb, which is the one thing
    // `AMoveAgreed` can be built from.
    'migrate-adopt' => 'takes over the setup already here, having first said what it would open and what it copies first',
    'migrate-import' => 'carries the old setup\'s own records across, having first said what would and would not come',
    'migrate-beside' => 'stands lemonfiber up beside the setup already here, having first said where each service would listen',
    'migrate-replace' => 'stops the setup already here and stands in its place, having first said what it would stop',

    // Wires the services to each other. It changes nothing already right and
    // keeps what the operator changed, and it overwrites nothing of theirs.
    'seed' => 'wires the services to each other, keeping what the operator changed and changing nothing already right',

    // Chooses which service answers a capability two or more claim. Without
    // an offer it works the choice out and writes nothing, and it is sent as
    // a rehearsal besides; the write names the reading the operator was
    // shown, and the stack refuses it where that reading has moved.
    'wiring-fill' => 'chooses which service answers a capability, having first said what answers it now, what would after, and what it would leave unfilled',

    // A guard on the data location for the forms the operator names. It
    // stops those forms if the location goes, and never starts them again;
    // it is held only while the screen that started it keeps asking.
    'watch' => 'guards the data location for the forms named, while the screen that started it asks',
];

/**
 * Every action the application builds, by the name the contract gives it.
 *
 * Read off what each source imports from the SDK's generated actions: an
 * action can be built only by a class that names it, so an import is where
 * every one shows. The name is the class's own `ACTION`, which is the
 * contract's word rather than this app's.
 *
 * @return list<string>
 */
function everyActionTheAppBuilds(): array
{
    $found = [];

    foreach (theApplicationsSources() as $path) {
        preg_match_all('/^use (Lemonfiber\\\\Sdk\\\\Generated\\\\\w+Action);$/m', (string) file_get_contents($path), $imported);

        foreach ($imported[1] as $class) {
            $name = class_exists($class) ? new ReflectionClass($class)->getConstant('ACTION') : false;

            if (is_subclass_of($class, ActionRequest::class) && is_string($name)) {
                $found[] = $name;
            }
        }
    }

    $found = array_values(array_unique($found));
    sort($found);

    return $found;
}

/**
 * Every class of this application's own that would be an action.
 *
 * An action class written here is a name this app chose rather than one the
 * contract lists, and `setup` is a name — which is the whole of what is
 * refused. An anonymous class extending it is the same thing written inline.
 *
 * @return list<string>
 */
function everyActionOfTheAppsOwn(): array
{
    $found = [];

    foreach (theApplicationsSources() as $path) {
        if (preg_match('/extends\s+\\\\?(Lemonfiber\\\\Sdk\\\\)?ActionRequest\b/', (string) file_get_contents($path)) === 1) {
            $found[] = $path;
        }
    }

    return $found;
}

/**
 * Every file of this application, leaving its tests out.
 *
 * @return list<string>
 */
function theApplicationsSources(): array
{
    $sources = [
        ...Tree::filesUnder(Tree::at('app-modules'), '.php'),
        ...Tree::filesUnder(Tree::at('bootstrap'), '.php'),
    ];

    return array_values(array_filter(
        $sources,
        static fn(string $path): bool => ! str_contains($path, '/tests/'),
    ));
}

/**
 * An agreement to take an update, which is the only thing that spells `update`.
 *
 * Built rather than read off an enum, because there is no enum: one thing can
 * be done about an update, and the name lives on the agreement. Nothing about
 * the services changes what it is asked by, so the smallest update a reading
 * can offer is enough to ask it.
 */
function anUpdateSomebodyAgreedTo(): TakingAnUpdate
{
    return TakingAnUpdate::offeredBy(Upkeep::reported(
        AgainstThePins::UpdatesAvailable,
        Releases::none(),
        Services::these(ServiceId::called('jellyfin')),
        Services::none(),
        HowServicesTookIt::none(),
        HowTheNotesStand::Current,
        TheStackEdits::none(),
    ));
}

/**
 * Every method the application actually calls on a client.
 *
 * Driven by the client's own method names rather than by a receiver named
 * `$client`, and that is the whole of whether this rule works. Matching the
 * receiver reads one spelling of a call: `$door = $client; $door->act(...)`
 * walked past every assertion here, and so did the two calls `Menders` already
 * makes — `$this->clients->client(...)->whatBecameOf(...)` is chained and never
 * lands in a variable at all, so a door this app has opened since the repairs screen was
 * built was one this rule had never seen.
 *
 * It over-approximates: a method of the same name on something that is not a
 * client counts too, and `Mending::whatBecameOf()` is one. That is the safe
 * direction — this rule can then ask for an explanation it did not need, and
 * never miss one it did. Under-approximating is what let `setup` through.
 *
 * @return list<string>
 */
function everyDoorTheAppOpens(): array
{
    $said = implode("\n", array_map(
        static fn(string $path): string => (string) file_get_contents($path),
        theApplicationsSources(),
    ));

    $found = [];

    foreach (everyDoorTheClientHas() as $door) {
        if (preg_match(sprintf('/->%s\s*\(/', preg_quote($door, '/')), $said) === 1) {
            $found[] = $door;
        }
    }

    sort($found);

    return $found;
}

/**
 * Every method the SDK's client publishes.
 *
 * Read from the class rather than listed, so a door added to the SDK is one
 * this rule can see the day it arrives — which is the half a hand-kept list
 * cannot have.
 *
 * @return list<string>
 */
function everyDoorTheClientHas(): array
{
    $found = [];

    foreach (new ReflectionClass(Client::class)->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
        // Statics are not doors. `Client::at()` builds a client rather than
        // doing anything to a stack, and what may build one is
        // `NothingOpensAConnectionByHandTest`'s question — every connection
        // through `PinnedClients`, so the certificate pin lives in one file.
        // Left in, it also reads `$stack->at()` as a door, which is an address
        // accessor with the same name and nothing to do with a client.
        if ($method->isConstructor() || $method->isStatic()) {
            continue;
        }

        $found[] = $method->getName();
    }

    return $found;
}

it('the app opens only the doors it has a reason for', function (): void {
    $opened = everyDoorTheAppOpens();

    expect($opened)->not->toBe([], 'no call on a client was found anywhere, so this rule read nothing');

    $explained = array_map(strval(...), array_keys(DOORS_THE_APP_OPENS));
    $unexplained = array_values(array_diff($opened, $explained));

    expect($unexplained)->toBe([], sprintf(
        "These are called on a stack's client and this rule has no reason for them:\n  %s\n\n"
        . 'Every door is a thing this app can do to somebody\'s machine. `act()` in particular '
        . 'takes an endpoint and a body, which is how a stack is configured and how a credential '
        . "would be written — `N1-R4` and `N2-R12` refuse both by name.\n"
        . "Add the door here with the requirement it serves, or do not open it.\n",
        implode("\n  ", $unexplained),
    ));
});

it('every door this rule names is one the client still has', function (): void {
    // The other direction. A method renamed in the SDK would leave this rule
    // permitting a door that no longer exists and silently permitting nothing —
    // which is a rule that goes on passing while describing a contract nobody
    // speaks any more.
    $missing = [];

    foreach (array_keys(DOORS_THE_APP_OPENS) as $door) {
        if (! method_exists(Client::class, $door)) {
            $missing[] = $door;
        }
    }

    expect($missing)->toBe([], sprintf(
        "This rule names doors the SDK's client does not have:\n  %s\n",
        implode("\n  ", $missing),
    ));
});

it('the door that writes is handed only the actions the contract lists', function (): void {
    // Named rather than left to the list above, because this is the one that
    // matters and a reader of the list should not have to work out which.
    // Asked by name through reflection, so a client that stopped having `act()`
    // fails here rather than leaving this case passing about a door that is
    // gone — the same both-directions shape as the rule above.
    $doors = array_map(
        static fn(ReflectionMethod $method): string => $method->getName(),
        new ReflectionClass(Client::class)->getMethods(ReflectionMethod::IS_PUBLIC),
    );

    expect($doors)->toContain('act');

    expect(everyActionOfTheAppsOwn())->toBe([], sprintf(
        "These write an action of their own for the writing door:\n  %s\n\n"
        . 'An action this app writes is a name it chose, and the door takes any name a stack '
        . "offers — `setup` among them (`N1-R4`), and the ones that write a credential (`N2-R12`). "
        . "Build the class the SDK generates for the action instead.\n",
        implode("\n  ", everyActionOfTheAppsOwn()),
    ));

    $built = everyActionTheAppBuilds();
    $explained = array_map(strval(...), array_keys(VERBS_THE_APP_ASKS_FOR));
    sort($explained);

    expect($built)->not->toBe([], 'no action the app builds was found, so this rule read nothing')
        ->and($built)->toBe($explained, sprintf(
            "The actions this app builds and the verbs this rule explains are not the same set:\n"
            . "  built:     %s\n  explained: %s\n",
            implode(', ', $built),
            implode(', ', $explained),
        ));
});

it('every action this app asks for has a reason, and every reason an action', function (): void {
    // Both directions, because they catch different mistakes. The forward check
    // finds a case that lost its reason; only the reverse finds the verb
    // somebody adds to this list by hand, which is the edit that would widen
    // what this app can ask for without widening the enum.
    // Every closed set, and the one agreement that is not a set, because a
    // screen asks whether a stack offers each by these names and the rule is
    // about that door rather than about services. One left out here is a verb this app can ask for
    // and this rule does not explain, which is the shape of a rule claiming
    // more than it enforces.
    $asked = [
        ...array_map(static fn(WhatToDoWithIt $doing): string => $doing->asked(), WhatToDoWithIt::cases()),
        ...array_map(static fn(WhatWasDecided $decided): string => $decided->asked(), WhatWasDecided::cases()),
        ...array_map(static fn(WhatToChange $change): string => $change->asked(), WhatToChange::cases()),
        ...array_map(static fn(AskingThemIn $asking): string => $asking->asked(), AskingThemIn::cases()),
        ...array_map(static fn(TakingThemOut $out): string => $out->asked(), TakingThemOut::cases()),
        ...array_map(static fn(WhatToDoAboutQuality $about): string => $about->asked(), WhatToDoAboutQuality::cases()),
        ...array_map(static fn(WhatToDoWithACopy $copy): string => $copy->asked(), WhatToDoWithACopy::cases()),
        ...array_map(static fn(WhatToDoAboutPairing $pairing): string => $pairing->asked(), WhatToDoAboutPairing::cases()),
        ...array_map(static fn(ConnectingADevice $connecting): string => $connecting->asked(), ConnectingADevice::cases()),
        ...array_map(static fn(MovingInBy $by): string => $by->asked(), MovingInBy::cases()),
        ...array_map(static fn(WhatToDoAboutWiring $about): string => $about->asked(), WhatToDoAboutWiring::cases()),
        ...array_map(static fn(WhatToDoWithADownload $download): string => $download->asked(), WhatToDoWithADownload::cases()),
        ...array_map(static fn(WhatToDoWithARun $run): string => $run->asked(), WhatToDoWithARun::cases()),
        ...array_map(static fn(TakingItOff $off): string => $off->asked(), TakingItOff::cases()),
        ...array_map(static fn(HandingOver $over): string => $over->asked(), HandingOver::cases()),
        ...array_map(static fn(ExtendingIt $extending): string => $extending->asked(), ExtendingIt::cases()),
        ...array_map(static fn(WhatThePlayerAsks $player): string => $player->asked(), WhatThePlayerAsks::cases()),
        anUpdateSomebodyAgreedTo()->asked(),
        WhatToWalk::called('')->asked(),
        ABundleAsked::described(HowManyLines::asMuchAsAPhoneShows(), WhatFilenamesShow::Replaced, SettingsToReveal::none())->asked(),
        AGuardAskedFor::of(Forms::none())->asked(),
    ];
    $explained = array_map(strval(...), array_keys(VERBS_THE_APP_ASKS_FOR));

    sort($asked);
    sort($explained);

    expect($asked)->toBe($explained, sprintf(
        "The verbs this app can ask for and the verbs this rule explains are not the same set:\n"
        . "  asked:     %s\n  explained: %s\n",
        implode(', ', $asked),
        implode(', ', $explained),
    ));
});
