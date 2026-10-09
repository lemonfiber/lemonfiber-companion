<?php

declare(strict_types=1);

namespace Tests\Support;

use Closure;
use Modules\Kernel\Api\ABundleAsked;
use Modules\Kernel\Api\ACopy;
use Modules\Kernel\Api\ACopyAsked;
use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\ADownloadHeld;
use Modules\Kernel\Api\ADownloadOnDisk;
use Modules\Kernel\Api\AFill;
use Modules\Kernel\Api\AFillAgreed;
use Modules\Kernel\Api\AgainstThePins;
use Modules\Kernel\Api\AgreedTo;
use Modules\Kernel\Api\AGuardAskedFor;
use Modules\Kernel\Api\AHeldChoice;
use Modules\Kernel\Api\AMove;
use Modules\Kernel\Api\AMoveAgreed;
use Modules\Kernel\Api\AnAddressToHand;
use Modules\Kernel\Api\AnInvitation;
use Modules\Kernel\Api\AnInvitationAgreed;
use Modules\Kernel\Api\AnInvitationAskedFor;
use Modules\Kernel\Api\AnInvitationToHand;
use Modules\Kernel\Api\AnUninstall;
use Modules\Kernel\Api\AnUninstallAgreed;
use Modules\Kernel\Api\AnUpgradeDescribed;
use Modules\Kernel\Api\APluginInstallAgreed;
use Modules\Kernel\Api\APluginRemovalAgreed;
use Modules\Kernel\Api\APluginSource;
use Modules\Kernel\Api\APluginUpdateAgreed;
use Modules\Kernel\Api\APresetToChoose;
use Modules\Kernel\Api\ARemoval;
use Modules\Kernel\Api\ARemovalAgreed;
use Modules\Kernel\Api\AResetAgreed;
use Modules\Kernel\Api\ARun;
use Modules\Kernel\Api\ARunAgreedTo;
use Modules\Kernel\Api\AWordInUse;
use Modules\Kernel\Api\AWrittenBundle;
use Modules\Kernel\Api\Capability;
use Modules\Kernel\Api\Change;
use Modules\Kernel\Api\Check;
use Modules\Kernel\Api\Confirmed;
use Modules\Kernel\Api\ConnectionsReverted;
use Modules\Kernel\Api\Decided;
use Modules\Kernel\Api\Effects;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\Form;
use Modules\Kernel\Api\Forms;
use Modules\Kernel\Api\HandingOver;
use Modules\Kernel\Api\HoldingId;
use Modules\Kernel\Api\HostingAgreed;
use Modules\Kernel\Api\HowFarIn;
use Modules\Kernel\Api\HowFarItGoesBack;
use Modules\Kernel\Api\HowFarTheRemovalReached;
use Modules\Kernel\Api\HowManyLines;
use Modules\Kernel\Api\HowMuchWasRead;
use Modules\Kernel\Api\HowServicesTookIt;
use Modules\Kernel\Api\HowTheNotesStand;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\MovingInBy;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Offer;
use Modules\Kernel\Api\PluginLines;
use Modules\Kernel\Api\Reading;
use Modules\Kernel\Api\Releases;
use Modules\Kernel\Api\Repair;
use Modules\Kernel\Api\Repairs;
use Modules\Kernel\Api\RequestId;
use Modules\Kernel\Api\ScopeOfACopy;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\Services;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\SettingsToReveal;
use Modules\Kernel\Api\SomebodyInTheHousehold;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Kernel\Api\Stance;
use Modules\Kernel\Api\TakingAnUpdate;
use Modules\Kernel\Api\TheAdoption;
use Modules\Kernel\Api\TheLibraries;
use Modules\Kernel\Api\ThePlace;
use Modules\Kernel\Api\ThePresetsInForce;
use Modules\Kernel\Api\TheQualityChosen;
use Modules\Kernel\Api\TheRecord;
use Modules\Kernel\Api\TheReset;
use Modules\Kernel\Api\TheStackEdits;
use Modules\Kernel\Api\TheUpgrade;
use Modules\Kernel\Api\ThisDevice;
use Modules\Kernel\Api\Undoing;
use Modules\Kernel\Api\Upkeep;
use Modules\Kernel\Api\WhatACopyHolds;
use Modules\Kernel\Api\WhatBecameOfTheChoice;
use Modules\Kernel\Api\WhatFilenamesShow;
use Modules\Kernel\Api\WhatGoesAndWhatStays;
use Modules\Kernel\Api\WhatIsNotLemonfibers;
use Modules\Kernel\Api\WhatIsStillComing;
use Modules\Kernel\Api\WhatItCannotTake;
use Modules\Kernel\Api\WhatItReaches;
use Modules\Kernel\Api\WhatLettingItGoCosts;
use Modules\Kernel\Api\WhatMusicIsSetTo;
use Modules\Kernel\Api\WhatNothingFills;
use Modules\Kernel\Api\WhatPuttingItBackWouldDo;
use Modules\Kernel\Api\WhatTakingItOffComesTo;
use Modules\Kernel\Api\WhatTheRemovalFound;
use Modules\Kernel\Api\WhatToDoWithIt;
use Modules\Kernel\Api\WhatToFollow;
use Modules\Kernel\Api\WhatToKnowFirst;
use Modules\Kernel\Api\WhatToSet;
use Modules\Kernel\Api\WhatToWalk;
use Modules\Kernel\Api\WhatWasNamed;
use Modules\Kernel\Api\WhatWroteACopy;
use Modules\Kernel\Api\WhenItWasMade;
use Modules\Kernel\Api\WhereTakingItOffGot;
use Modules\Kernel\Api\WhereTheDataGoes;
use Modules\Kernel\Api\WhereTheInvitationStands;
use Modules\Kernel\Api\WhetherTheyCanAsk;
use Modules\Kernel\Api\WhetherToWait;
use Modules\Kernel\Api\WhichRemoval;
use Modules\Kernel\Api\Whose;
use Modules\Kernel\Api\WhoWasSwitchedOff;
use Modules\Kernel\Api\WhoWasTakenBack;
use Modules\Sdk\Api\Adjustments;
use Modules\Sdk\Api\Advisers;
use Modules\Sdk\Api\Archivists;
use Modules\Sdk\Api\Arrangements;
use Modules\Sdk\Api\Bundlers;
use Modules\Sdk\Api\Cataloguers;
use Modules\Sdk\Api\Chroniclers;
use Modules\Sdk\Api\Connectors;
use Modules\Sdk\Api\Copiers;
use Modules\Sdk\Api\Copyists;
use Modules\Sdk\Api\Dismantlers;
use Modules\Sdk\Api\Doorkeepers;
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
use Modules\Sdk\Api\PinnedClients;
use Modules\Sdk\Api\PlaceKeepers;
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

use function str_repeat;

use Tests\Support\Fakes\SequencedEntropy;

/**
 * Every adapter call that reads an answer, and what each is asked with.
 *
 * The calls `EveryAdapterAnswersWhatItCannotReadTest` spoils the answers to,
 * against one stack and one session.
 */
final readonly class EveryAdapterCallThatReads
{
    /**
     * Every adapter call that reads an answer, by what it asks.
     *
     * @return array<string, Closure(): object>
     */
    public static function all(): array
    {
        $stack = self::aStackWhoseAnswersAreSpoiled();
        $session = self::theSessionSpoiledAnswersArriveOn();
        $clients = new PinnedClients();
        $entropy = SequencedEntropy::counting();

        return [
            'Adjustments::wouldBe' => static fn(): object
                => new Adjustments($clients, $entropy)->wouldBe($stack, $session, WhatToSet::to('LIBRARY_PATH', '/data/films')),
            'Adjustments::agreedTo' => static fn(): object
                => new Adjustments($clients, $entropy)->agreedTo($stack, $session, WhatToSet::to('LIBRARY_PATH', '/data/films')),
            'Advisers::advisedBy' => static fn(): object => new Advisers($clients)->advisedBy($stack, $session),
            'Archivists::declaredOn' => static fn(): object => new Archivists($clients)->declaredOn($stack, $session),
            'Arrangements::asItStands' => static fn(): object => new Arrangements($clients)->asItStands($stack, $session),
            'Cataloguers::describedOn' => static fn(): object => new Cataloguers($clients)->describedOn($stack, $session),
            'Linkers::linkedOn' => static fn(): object => new Linkers($clients)->linkedOn($stack, $session),
            'Fillers::whatItWouldComeTo' => static fn(): object
                => new Fillers($clients, $entropy)->whatItWouldComeTo($stack, $session, Capability::called('media-server'), ServiceId::called('plex')),
            'Fillers::choose' => static fn(): object
                => new Fillers($clients, $entropy)->choose($stack, $session, self::aFillToSpoilTheAnswerTo()),
            'Copiers::take' => static fn(): object
                => new Copiers($clients, $entropy)->take($stack, $session, ACopyAsked::ofTheWholeStack()),
            'Copiers::whatBecameOf' => static fn(): object
                => new Copiers($clients, $entropy)->whatBecameOf($stack, $session, Job::named('a-job')),
            'Bundlers::ask' => static fn(): object => new Bundlers($clients, $entropy)->ask(
                $stack,
                $session,
                ABundleAsked::described(HowManyLines::of(200), WhatFilenamesShow::Replaced, SettingsToReveal::none()),
            ),
            'Bundlers::whatBecameOf' => static fn(): object
                => new Bundlers($clients, $entropy)->whatBecameOf($stack, $session, Job::named('a-job')),
            'Bundlers::fetch' => static fn(): object
                => new Bundlers($clients, $entropy)->fetch($stack, $session, AWrittenBundle::at('/home/op/bundles/lemonfiber-support.tar.gz')),
            'Dismantlers::surveyed' => static fn(): object
                => new Dismantlers($clients, $entropy)->surveyed($stack, $session, WhichRemoval::Services),
            'Dismantlers::takeItOff' => static fn(): object
                => new Dismantlers($clients, $entropy)->takeItOff($stack, $session, self::anUninstallToSpoilTheAnswerTo()),
            'Dismantlers::whatBecameOf' => static fn(): object
                => new Dismantlers($clients, $entropy)->whatBecameOf($stack, $session, Job::named('a-job')),
            'Copyists::copiesOn' => static fn(): object => new Copyists($clients)->copiesOn($stack, $session),
            'Doorkeepers::frontDoorOf' => static fn(): object => new Doorkeepers($clients)->frontDoorOf($stack, $session),
            'Newsreaders::newsOn' => static fn(): object => new Newsreaders($clients)->newsOn($stack, $session),
            'Explainers::glossaryOn' => static fn(): object => new Explainers($clients)->glossaryOn($stack, $session),
            'Explainers::wordOn' => static fn(): object
                => new Explainers($clients)->wordOn($stack, $session, AWordInUse::named('seed')),
            'Extenders::installedOn' => static fn(): object => new Extenders($clients, $entropy)->installedOn($stack, $session),
            'Extenders::rehearseInstalling' => static fn(): object
                => new Extenders($clients, $entropy)->rehearseInstalling($stack, $session, APluginSource::typed('tdarr')),
            'Extenders::install' => static fn(): object
                => new Extenders($clients, $entropy)->install($stack, $session, APluginInstallAgreed::after(APluginAsItArrives::theReading(), APluginSource::typed('tdarr'), PluginLines::none())),
            'Extenders::rehearseUpdating' => static fn(): object
                => new Extenders($clients, $entropy)->rehearseUpdating($stack, $session, APluginAsItArrives::held()),
            'Extenders::update' => static fn(): object
                => new Extenders($clients, $entropy)->update($stack, $session, APluginUpdateAgreed::after(APluginAsItArrives::theUpdateReading(), APluginAsItArrives::held(), PluginLines::none())),
            'Extenders::rehearseRemoving' => static fn(): object
                => new Extenders($clients, $entropy)->rehearseRemoving($stack, $session, APluginAsItArrives::held()),
            'Extenders::remove' => static fn(): object
                => new Extenders($clients, $entropy)->remove($stack, $session, APluginRemovalAgreed::after(APluginAsItArrives::theRemovalReading(), APluginAsItArrives::held())),
            'Extenders::whatBecameOf' => static fn(): object
                => new Extenders($clients, $entropy)->whatBecameOf($stack, $session, Job::named('a-job')),
            'Followers::tracedOn' => static fn(): object
                => new Followers($clients)->tracedOn($stack, $session, WhatToFollow::called('sonarr')),
            'Graders::inForceOn' => static fn(): object => new Graders($clients, $entropy)->inForceOn($stack, $session),
            'Graders::choose' => static fn(): object
                => new Graders($clients, $entropy)->choose($stack, $session, APresetToChoose::named('lossless', 'music')),
            'Graders::confirm' => static fn(): object
                => new Graders($clients, $entropy)->confirm($stack, $session, self::aHeldChoiceToSpoilTheAnswerTo()),
            'Grantors::aGrantFor' => static fn(): object => new Grantors($clients, $entropy)->aGrantFor($stack, $session, ThisDevice::named('this-device')),
            'PlaceKeepers::keep' => static fn(): object => new PlaceKeepers($clients, $entropy)->keep($stack, $session, ThePlace::in(HoldingId::called('a1'), HowFarIn::at(61))),
            'Guards::guard' => static fn(): object
                => new Guards($clients, $entropy)->guard($stack, $session, AGuardAskedFor::of(Forms::these(Form::called('media')))),
            'Guards::whatBecameOf' => static fn(): object
                => new Guards($clients, $entropy)->whatBecameOf($stack, $session, Job::named('a-job')),
            'Guards::letGo' => static fn(): object
                => new Guards($clients, $entropy)->letGo($stack, $session, Job::named('a-job')),
            'Guides::walk' => static fn(): object
                => new Guides($clients, $entropy)->walk($stack, $session, WhatToWalk::called('Sintel')),
            'Guides::whatBecameOf' => static fn(): object
                => new Guides($clients, $entropy)->whatBecameOf($stack, $session, Job::named('a-job')),
            'Heralds::toldAbout' => static fn(): object => new Heralds($clients)->toldAbout($stack, $session),
            'Inspectors::checkedOn' => static fn(): object => new Inspectors($clients)->checkedOn($stack, $session),
            'Chroniclers::versionsOn' => static fn(): object => new Chroniclers($clients)->versionsOn($stack, $session),
            'Keepers::keptRunningOn' => static fn(): object => new Keepers($clients, $entropy)->keptRunningOn($stack, $session),
            'Keepers::handOver' => static fn(): object
                => new Keepers($clients, $entropy)->handOver($stack, $session, HostingAgreed::to(HandingOver::Install, 'Name')),
            'Listeners::howItIs' => static fn(): object => new Listeners($clients)->howItIs($stack, $session),
            'Narrators::whereItIs' => static fn(): object => new Narrators($clients)->whereItIs($stack, $session),
            'StartLines::whatItWaitsOn' => static fn(): object => new StartLines($clients)->whatItWaitsOn($stack, $session),
            'Pairers::make' => static fn(): object => new Pairers($clients, $entropy)->make($stack, $session),
            'Connectors::handOver' => static fn(): object => new Connectors($clients, $entropy)->handOver($stack, $session, SomebodyInTheHousehold::called('Sam')),
            'Connectors::whatBecameOf' => static fn(): object
                => new Connectors($clients, $entropy)->whatBecameOf($stack, $session, Job::named('a-job')),
            'Pairers::whatBecameOf' => static fn(): object
                => new Pairers($clients, $entropy)->whatBecameOf($stack, $session, Job::named('a-job')),
            'Keyholders::heldOn' => static fn(): object => new Keyholders($clients)->heldOn($stack, $session),
            'Lookouts::leaving' => static fn(): object => new Lookouts($clients)->leaving($stack, $session),
            'Menders::wouldPutRight' => static fn(): object => new Menders($clients, $entropy)->wouldPutRight($stack, $session),
            'Menders::agreeTo' => static fn(): object
                => new Menders($clients, $entropy)->agreeTo($stack, $session, self::aConfirmationToSpoilTheAnswerTo()),
            'Menders::whatWasDoneAbout' => static fn(): object
                => new Menders($clients, $entropy)->whatWasDoneAbout($stack, $session, Job::named('a-job')),
            'Menders::whatBecameOf' => static fn(): object
                => new Menders($clients, $entropy)->whatBecameOf($stack, $session, Job::named('a-job')),
            'Quartermasters::rationedOn' => static fn(): object => new Quartermasters($clients)->rationedOn($stack, $session),
            'Questions::about' => static fn(): object => new Questions($clients)->about($stack, $session),
            'Recorders::recordedOn' => static fn(): object => new Recorders($clients)->recordedOn($stack, $session),
            'Rehearsers::whatStarting' => static fn(): object
                => new Rehearsers($clients)->whatStarting($stack, $session, Form::called('media')),
            'Releasers::whatItWouldCost' => static fn(): object
                => new Releasers($clients, $entropy)->whatItWouldCost($stack, $session, ADownloadHeld::named('Show.Season1')),
            'Releasers::whatTheOfferCameTo' => static fn(): object
                => new Releasers($clients, $entropy)->whatTheOfferCameTo($stack, $session, Job::named('a-job')),
            'Releasers::stop' => static fn(): object
                => new Releasers($clients, $entropy)->stop($stack, $session, self::anOfferToLetGoToSpoilTheAnswerTo()),
            'Releasers::whatBecameOf' => static fn(): object
                => new Releasers($clients, $entropy)->whatBecameOf($stack, $session, Job::named('a-job')),
            'Removers::wouldRemove' => static fn(): object
                => new Removers($clients, $entropy)->wouldRemove($stack, $session, SomebodyInTheHousehold::called('anna')),
            'Removers::remove' => static fn(): object
                => new Removers($clients, $entropy)->remove($stack, $session, ARemovalAgreed::after(ARemoval::described(
                    SomebodyInTheHousehold::called('anna'),
                    0,
                    asksThroughTheRequestService: false,
                    revoked: HowFarTheRemovalReached::Nothing,
                    findings: WhatTheRemovalFound::of(),
                ))),
            'Removers::whatBecameOf' => static fn(): object
                => new Removers($clients, $entropy)->whatBecameOf($stack, $session, Job::named('a-job')),
            'Restorers::rehearse' => static fn(): object
                => new Restorers($clients, $entropy)->rehearse($stack, $session, ACopy::named('lemonfiber-20260924-0300-full')),
            'Restorers::putBack' => static fn(): object
                => new Restorers($clients, $entropy)->putBack($stack, $session, self::aListingToSpoilTheAnswerTo()),
            'Restorers::whatBecameOf' => static fn(): object
                => new Restorers($clients, $entropy)->whatBecameOf($stack, $session, Job::named('a-job')),
            'Reversers::putBack' => static fn(): object
                => new Reversers($clients, $entropy)->putBack($stack, $session, self::aRunToSpoilTheAnswerTo()),
            'Reversers::whatBecameOf' => static fn(): object
                => new Reversers($clients, $entropy)->whatBecameOf($stack, $session, Job::named('a-job')),
            'Resetters::wouldRevert' => static fn(): object => new Resetters($clients, $entropy)->wouldRevert($stack, $session),
            'Resetters::revert' => static fn(): object
                => new Resetters($clients, $entropy)->revert($stack, $session, AResetAgreed::to(TheReset::previewed(TheStackEdits::these(), ConnectionsReverted::these('sonarr → qbittorrent')))),
            'Resetters::whatBecameOf' => static fn(): object
                => new Resetters($clients, $entropy)->whatBecameOf($stack, $session, Job::named('a-job')),
            'Requests::askedOf' => static fn(): object => new Requests($clients, $entropy)->askedOf($stack, $session),
            'Requests::decided' => static fn(): object
                => new Requests($clients, $entropy)->decided($stack, $session, Decided::toApprove(RequestId::numbered(1))),
            'Scouts::surveyedOn' => static fn(): object => new Scouts($clients, $entropy)->surveyedOn($stack, $session),
            'Scouts::wouldMoveIn' => static fn(): object => new Scouts($clients, $entropy)->wouldMoveIn($stack, $session, MovingInBy::Adopting),
            'Scouts::moveIn' => static fn(): object => new Scouts($clients, $entropy)->moveIn($stack, $session, self::aMoveToSpoilTheAnswerTo()),
            'Scouts::whatBecameOf' => static fn(): object => new Scouts($clients, $entropy)->whatBecameOf($stack, $session, Job::named('a-job')),
            'Scrollbacks::saidBy' => static fn(): object
                => new Scrollbacks($clients)->saidBy($stack, $session, ServiceId::called('sonarr'), HowManyLines::of(3)),
            'Shelves::theShelfOf' => static fn(): object
                => new Shelves($clients)->theShelfOf($stack, $session, Whose::member('robin')),
            'Shelves::theDefaultShelf' => static fn(): object => new Shelves($clients)->theDefaultShelf($stack, $session),
            'Shelves::theTitle' => static fn(): object
                => new Shelves($clients)->theTitle($stack, $session, Whose::member('robin'), HoldingId::called('a1')),
            'Stalls::stoppedOn' => static fn(): object => new Stalls($clients)->stoppedOn($stack, $session),
            'Storekeepers::storedOn' => static fn(): object => new Storekeepers($clients)->storedOn($stack, $session),
            'Supervisors::formsOn' => static fn(): object => new Supervisors($clients, $entropy)->formsOn($stack, $session),
            'Supervisors::rehearsed' => static fn(): object => new Supervisors($clients, $entropy)->rehearsed(
                $stack,
                $session,
                AgreedTo::theService(WhatToDoWithIt::Restart, ServiceId::called('sonarr')),
            ),
            'Supervisors::running' => static fn(): object => new Supervisors($clients, $entropy)->running($stack, $session),
            'Supervisors::told' => static fn(): object => new Supervisors($clients, $entropy)->told(
                $stack,
                $session,
                AgreedTo::theService(WhatToDoWithIt::Stop, ServiceId::called('sonarr')),
            ),
            'Supervisors::whatBecameOf' => static fn(): object
                => new Supervisors($clients, $entropy)->whatBecameOf($stack, $session, Job::named('a-job')),
            'Surveyors::measuredOn' => static fn(): object => new Surveyors($clients)->measuredOn($stack, $session),
            'TheirOwn::toHandOver' => static fn(): object => new TheirOwn($clients)->toHandOver($stack, $session),
            'TheirOwn::theirRequests' => static fn(): object => new TheirOwn($clients)->theirRequests($stack, $session)->asked(),
            'TheirOwn::whatTheDefaultsAreTold' => static fn(): object => new TheirOwn($clients)->whatTheDefaultsAreTold($stack, $session),
            'Upgraders::whatItWouldComeTo' => static fn(): object
                => new Upgraders($clients, $entropy)->whatItWouldComeTo($stack, $session),
            'Upgraders::upgrade' => static fn(): object
                => new Upgraders($clients, $entropy)->upgrade($stack, $session, AnUpgradeDescribed::by(TheUpgrade::described())),
            'Upkeepers::standing' => static fn(): object => new Upkeepers($clients, $entropy)->standing($stack, $session),
            'Upkeepers::take' => static fn(): object
                => new Upkeepers($clients, $entropy)->take($stack, $session, self::anUpdateToSpoilTheAnswerTo()),
            'Upkeepers::whatBecameOf' => static fn(): object
                => new Upkeepers($clients, $entropy)->whatBecameOf($stack, $session, Job::named('a-job')),
            'Wirers::wire' => static fn(): object => new Wirers($clients, $entropy)->wire($stack, $session),
            'Wirers::whatBecameOf' => static fn(): object => new Wirers($clients, $entropy)->whatBecameOf($stack, $session, Job::named('a-job')),
            'Ushers::whoIsIn' => static fn(): object => new Ushers($clients, $entropy)->whoIsIn($stack, $session),
            'Ushers::wouldInvite' => static fn(): object
                => new Ushers($clients, $entropy)->wouldInvite($stack, $session, AnInvitationAskedFor::for('anna', TheLibraries::of())),
            'Ushers::invite' => static fn(): object
                => new Ushers($clients, $entropy)->invite($stack, $session, self::anInvitationToSpoilTheAnswerTo()),
            'Ushers::takeThePasswordOff' => static fn(): object
                => new Ushers($clients, $entropy)->takeThePasswordOff($stack, $session, SomebodyInTheHousehold::called('anna')),
            'Ushers::whatBecameOf' => static fn(): object
                => new Ushers($clients, $entropy)->whatBecameOf($stack, $session, Job::named('a-job')),
        ];
    }
    /** The stack every adapter here is pointed at. */
    private static function aStackWhoseAnswersAreSpoiled(): Stack
    {
        return Stack::of(
            StackId::of(Nonce::of(str_repeat('e', Nonce::SHORTEST))),
            StackName::of('The attic'),
            Address::of('https://192.168.1.43:8443'),
            Fingerprint::of(str_repeat('f', Fingerprint::CHARACTERS)),
        );
    }

    private static function theSessionSpoiledAnswersArriveOn(): Session
    {
        return Session::of('a-session-not-a-secret');
    }

    /** A confirmation that names a repair in the offer it came from. */
    private static function aConfirmationToSpoilTheAnswerTo(): Confirmed
    {
        $repair = Repair::offered(
            check: Check::of('storage.one-filesystem'),
            does: 'Move the library onto the larger disk',
            effects: Effects::of('Downloads pause while it moves'),
            undoing: Undoing::Possible,
        );
        $offer = Offer::of('an-agreement', Repairs::of($repair));

        return Confirmed::against($repair, $offer, Reading::live($offer));
    }

    /** An update a reading offered, moving one service. */
    private static function anUpdateToSpoilTheAnswerTo(): TakingAnUpdate
    {
        return TakingAnUpdate::offeredBy(Upkeep::reported(
            AgainstThePins::UpdatesAvailable,
            Releases::none(),
            Services::these(ServiceId::called('sonarr')),
            Services::none(),
            HowServicesTookIt::none(),
            HowTheNotesStand::Current,
            TheStackEdits::none(),
        ));
    }

    /** An invitation agreed to against its rehearsal. */
    private static function anInvitationToSpoilTheAnswerTo(): AnInvitationAgreed
    {
        $asked = AnInvitationAskedFor::for('anna', TheLibraries::of('Films'));

        return AnInvitationAgreed::after($asked, AnInvitation::rehearsed(
            AnInvitationToHand::to('anna', AnAddressToHand::at('http://loft.local:8096', ''), 72),
            WhereTheInvitationStands::Made,
            WhetherTheyCanAsk::NotTried,
            WhoWasTakenBack::of(),
            WhoWasSwitchedOff::of(),
        ));
    }

    /** A way of moving in the stack staged, for agreeing to against a spoiled answer. */
    private static function aMoveToSpoilTheAnswerTo(): AMoveAgreed
    {
        return AMoveAgreed::after(AMove::at(Stance::Pending, TheAdoption::of('media', WhatWasNamed::of('back_up'), '')));
    }

    /** A choice the stack held, for confirming against a spoiled answer. */
    private static function aHeldChoiceToSpoilTheAnswerTo(): AHeldChoice
    {
        $asked = APresetToChoose::named('maximum', 'movies');

        return AHeldChoice::of($asked, TheQualityChosen::reported(
            ThePresetsInForce::of(),
            WhatMusicIsSetTo::unset(),
            WhatBecameOfTheChoice::Held,
            customised: false,
        ));
    }

    /** A listing of a copy, to put back against. */
    private static function aListingToSpoilTheAnswerTo(): WhatPuttingItBackWouldDo
    {
        return WhatPuttingItBackWouldDo::listed(
            ACopy::named('lemonfiber-20260924-0300-full'),
            'an-agreement',
            ScopeOfACopy::theWholeStack(),
            WhatWroteACopy::of('0.9.0', '2026-09-24T03:00:00Z'),
            WhatACopyHolds::these(),
            older: false,
            data: WhereTheDataGoes::whereItWas(),
        );
    }

    /** An offer to stop seeding, to agree against. */
    private static function anOfferToLetGoToSpoilTheAnswerTo(): WhatLettingItGoCosts
    {
        return WhatLettingItGoCosts::offered(ADownloadOnDisk::neverImported('Show.Season1', 1), 'It goes', 'an-agreement');
    }

    /** A run the record shows, to put back against. */
    private static function aRunToSpoilTheAnswerTo(): ARunAgreedTo
    {
        $change = Change::made('Set LIBRARY_PATH', 'reconfigure', 'lemonfiber', WhenItWasMade::unreadable(), HowFarItGoesBack::Whole, 1);

        return ARunAgreedTo::by(TheRecord::reaching('the last 50 runs', $change)->theRun(ARun::stamped('0')));
    }

    /** A choice of filler the stack worked out, to agree against. */
    private static function aFillToSpoilTheAnswerTo(): AFillAgreed
    {
        return AFillAgreed::after(AFill::read(Capability::called('media-server'), ServiceId::called('plex'), Services::none(), Services::none(), WhatNothingFills::none(), '', 'cafe0001'), '');
    }

    /** A reading of the services, to agree against. */
    private static function anUninstallToSpoilTheAnswerTo(): AnUninstallAgreed
    {
        return AnUninstallAgreed::after(AnUninstall::of(
            WhatTakingItOffComesTo::read(
                WhichRemoval::Services,
                WhatGoesAndWhatStays::said(
                    'The containers and the images',
                    'Your configuration and your library',
                ),
                WhatItReaches::of(),
                0,
                WhatToKnowFirst::said(
                    WhatIsNotLemonfibers::of(),
                    WhatIsStillComing::of(),
                    WhatItCannotTake::of(),
                ),
                HowMuchWasRead::everything(),
                'services-0-lines',
            ),
            WhereTakingItOffGot::surveyed(),
        ), WhetherToWait::GoAheadNow, acknowledgedTheVolume: false);
    }
}
