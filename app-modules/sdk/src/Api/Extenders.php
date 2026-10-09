<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use Lemonfiber\Sdk\Contract\Api;
use Lemonfiber\Sdk\Envelope\Envelope;
use Lemonfiber\Sdk\Exception\ApiVersionMismatch;
use Lemonfiber\Sdk\Exception\CertificateWasRefused;
use Lemonfiber\Sdk\Exception\NoSuchJob;
use Lemonfiber\Sdk\Exception\RequestFailed;
use Lemonfiber\Sdk\Exception\UnexpectedKind;
use Lemonfiber\Sdk\Exception\Unreachable;
use Lemonfiber\Sdk\Exception\UnreadableResponse;
use Lemonfiber\Sdk\Generated\PluginInstallAction;
use Lemonfiber\Sdk\Generated\PluginRemoveAction;
use Lemonfiber\Sdk\Generated\PluginUpdateAction;
use Modules\Kernel\Api\APlugin;
use Modules\Kernel\Api\APluginInstallAgreed;
use Modules\Kernel\Api\APluginRemovalAgreed;
use Modules\Kernel\Api\APluginSource;
use Modules\Kernel\Api\APluginUpdateAgreed;
use Modules\Kernel\Api\Entropy;
use Modules\Kernel\Api\ExtendingTheStack;
use Modules\Kernel\Api\HowExtendingItIsGoing;
use Modules\Kernel\Api\IdempotencyKey;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\JobHasNoName;
use Modules\Kernel\Api\PluginLines;
use Modules\Kernel\Api\PluginSaysNothing;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\UndoSaysNothing;
use Modules\Kernel\Api\WhatWasFoundOfThePlugins;
use Modules\Sdk\Internal\GatedClient;
use Modules\Sdk\Internal\WhatARefusalMeant;

/**
 * The one place this application asks a stack about its plugins, and to install, update or remove one.
 *
 * **The listing is a read.** `/api/plugins` answers at once with what the
 * record holds and how each source stands.
 *
 * **Each rehearsal and its act are the same action, told apart by the
 * offer.** `plugin-install`, `plugin-update` and `plugin-remove` asked with no
 * offer are each the reading: the stack says what it would do, under a name,
 * and writes nothing. Asked again with that name, and for an install or an
 * update the values approved, it acts. An update names the source the plugin
 * was installed from. Each answers a
 * handle {@see self::whatBecameOf()} follows, and each carries a key of its
 * own, for {@see Supervisors::rehearsed()}'s reason.
 *
 * **No inputs are sent.** A recipe may ask the operator for a value, which
 * may be a secret, and this app takes none: a plugin whose recipes ask for
 * one is installed at the web console or the terminal, and the stack's
 * refusal says which it asks for.
 *
 * **A refusal keeps the stack's sentence**, for {@see Reversers}' reason: a
 * source holding no plugin, a manifest this build refuses, a value left
 * unapproved or an offer that moved is the operator's answer, not a stack
 * that could not be reached.
 */
final readonly class Extenders implements ExtendingTheStack
{
    public function __construct(private Clients $clients, private Entropy $entropy) {}

    public function installedOn(Stack $stack, Session $session): WhatWasFoundOfThePlugins
    {
        $client = GatedClient::of($this->clients, $stack, $session);

        try {
            $envelope = $client->read(Api::PLUGINS_ENDPOINT);

            // Inside the same `try` as the request, for the argument
            // {@see Recorders::recordedOn()} makes.
            return WhatWasFoundOfThePlugins::found(PluginInstalls::in($envelope));
        } catch (CertificateWasRefused|RequestFailed $why) {
            return WhatWasFoundOfThePlugins::met(WhatARefusalMeant::obstacle($why));
        } catch (ApiVersionMismatch|Unreachable|TheStackDoesNotOfferIt|UnreadableResponse|UnexpectedKind|PluginsAreUnreadable|PluginSaysNothing|UndoIsUnreadable|UndoSaysNothing $why) {
            return WhatWasFoundOfThePlugins::met($this->clients->whatStoodInTheWay($stack, $why));
        }
    }

    public function rehearseInstalling(Stack $stack, Session $session, APluginSource $source): HowExtendingItIsGoing
    {
        return $this->asked($stack, $session, new PluginInstallAction(source: $source->said()));
    }

    public function install(Stack $stack, Session $session, APluginInstallAgreed $agreed): HowExtendingItIsGoing
    {
        return $this->asked($stack, $session, new PluginInstallAction(
            offer: $agreed->agreement(),
            source: $agreed->source()->said(),
            approved: $this->listed($agreed->approved()),
        ));
    }

    public function rehearseUpdating(Stack $stack, Session $session, APlugin $plugin): HowExtendingItIsGoing
    {
        return $this->asked($stack, $session, new PluginUpdateAction(plugin: $plugin->id(), source: $plugin->vouched()->source()));
    }

    public function update(Stack $stack, Session $session, APluginUpdateAgreed $agreed): HowExtendingItIsGoing
    {
        return $this->asked($stack, $session, new PluginUpdateAction(
            offer: $agreed->agreement(),
            plugin: $agreed->plugin(),
            source: $agreed->source()->said(),
            approved: $this->listed($agreed->approved()),
        ));
    }

    public function rehearseRemoving(Stack $stack, Session $session, APlugin $plugin): HowExtendingItIsGoing
    {
        return $this->asked($stack, $session, new PluginRemoveAction(plugin: $plugin->id()));
    }

    public function remove(Stack $stack, Session $session, APluginRemovalAgreed $agreed): HowExtendingItIsGoing
    {
        return $this->asked($stack, $session, new PluginRemoveAction(offer: $agreed->agreement(), plugin: $agreed->plugin()));
    }

    public function whatBecameOf(Stack $stack, Session $session, Job $job): HowExtendingItIsGoing
    {
        try {
            return $this->outcome($stack, $session, $job);
        } catch (CertificateWasRefused|RequestFailed $why) {
            return WhatARefusalMeant::inItsWords(
                $why,
                refused: HowExtendingItIsGoing::refused(...),
                met: HowExtendingItIsGoing::met(...),
            );
        } catch (ApiVersionMismatch|Unreachable|UnreadableResponse|UnexpectedKind|PluginsAreUnreadable|PluginSaysNothing|UndoIsUnreadable|UndoSaysNothing $why) {
            return HowExtendingItIsGoing::met($this->clients->whatStoodInTheWay($stack, $why));
        }
    }

    /**
     * Send the action under a key of its own, and answer with what to follow it by.
     *
     * A refusal of the asking itself is the stack's answer about the plugin,
     * kept in its words as one of the work would be.
     */
    private function asked(Stack $stack, Session $session, PluginInstallAction|PluginUpdateAction|PluginRemoveAction $action): HowExtendingItIsGoing
    {
        try {
            $envelope = GatedClient::of($this->clients, $stack, $session)->act(
                $action,
                IdempotencyKey::from($this->entropy->nonce())->sent(),
            );

            return HowExtendingItIsGoing::underway(Handles::in($envelope));
        } catch (CertificateWasRefused|RequestFailed $why) {
            return WhatARefusalMeant::inItsWords(
                $why,
                refused: HowExtendingItIsGoing::refused(...),
                met: HowExtendingItIsGoing::met(...),
            );
        } catch (ApiVersionMismatch|Unreachable|TheStackDoesNotOfferIt|UnreadableResponse|UnexpectedKind|HandleIsUnreadable|JobHasNoName $why) {
            return HowExtendingItIsGoing::met($this->clients->whatStoodInTheWay($stack, $why));
        }
    }

    /**
     * Every value approved, collected by hand into the list an action takes, as the reading spells each.
     *
     * @return list<string>
     */
    private function listed(PluginLines $approved): array
    {
        $listed = [];

        foreach ($approved as $approval) {
            $listed[] = $approval;
        }

        return $listed;
    }

    /**
     * What the stack says about the work, with only `NoSuchJob` caught, for
     * {@see Upkeepers::outcome()}'s reason.
     */
    private function outcome(Stack $stack, Session $session, Job $job): HowExtendingItIsGoing
    {
        try {
            return GatedClient::of($this->clients, $stack, $session)->whatBecameOf($job->shown())->answering(
                stillRunning: static fn(): HowExtendingItIsGoing => HowExtendingItIsGoing::underway($job),
                finished: static fn(Envelope $envelope): HowExtendingItIsGoing
                    => HowExtendingItIsGoing::done(PluginInstalls::in($envelope)),
                ended: static fn(): HowExtendingItIsGoing => HowExtendingItIsGoing::ended(),
            );
        } catch (NoSuchJob) {
            return HowExtendingItIsGoing::ended();
        }
    }
}
