<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use Closure;
use Lemonfiber\Sdk\Contract\Api;
use Lemonfiber\Sdk\Envelope\Envelope;
use Lemonfiber\Sdk\Exception\ApiVersionMismatch;
use Lemonfiber\Sdk\Exception\CertificateWasRefused;
use Lemonfiber\Sdk\Exception\RequestFailed;
use Lemonfiber\Sdk\Exception\UnexpectedKind;
use Lemonfiber\Sdk\Exception\Unreachable;
use Lemonfiber\Sdk\Exception\UnreadableResponse;
use Lemonfiber\Sdk\Generated\TuneAction;
use Modules\Kernel\Api\ALiveStream;
use Modules\Kernel\Api\ChannelId;
use Modules\Kernel\Api\ChannelIsUnnamed;
use Modules\Kernel\Api\Entropy;
use Modules\Kernel\Api\IdempotencyKey;
use Modules\Kernel\Api\InstantIsBeforeTheEpoch;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\SentenceSaysNothing;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\Tuning;
use Modules\Kernel\Api\WhatIsOnTheChannels;
use Modules\Kernel\Api\WhatRenewingCameTo;
use Modules\Kernel\Api\WhatStoppingCameTo;
use Modules\Kernel\Api\WhatTuningCameTo;
use Modules\Kernel\Api\Whose;
use Modules\Sdk\Internal\GatedClient;
use Modules\Sdk\Internal\WhatARefusalMeant;
use Override;

/**
 * Live TV as the stack answers one member: the channels they may watch, and a channel opened, renewed and closed for them.
 *
 * Written as {@see Shelves} is: the connection comes from {@see Clients}, and
 * the operator, who has no account to watch on, is answered as not theirs to
 * ask rather than sent to the core.
 */
final readonly class Broadcasters implements Tuning
{
    public function __construct(private Clients $clients, private Entropy $entropy) {}

    #[Override]
    public function theChannelsOf(Stack $stack, Session $session, Whose $whose): WhatIsOnTheChannels
    {
        return $whose->either(
            operator: static fn(): WhatIsOnTheChannels => WhatIsOnTheChannels::refused(Obstacle::of(KindOfObstacle::NotForThisAccount)),
            member: fn(string $member): WhatIsOnTheChannels => $this->listed($stack, $session, $member),
        );
    }

    #[Override]
    public function tune(Stack $stack, Session $session, Whose $whose, ChannelId $channel): WhatTuningCameTo
    {
        return $this->asMember(
            $stack,
            $whose,
            static fn(Obstacle $why): WhatTuningCameTo => WhatTuningCameTo::refused($why),
            fn(): WhatTuningCameTo => WhatTheTunerOpened::in($this->act($stack, $session, new TuneAction(id: $channel->named()))),
        );
    }

    #[Override]
    public function renew(Stack $stack, Session $session, Whose $whose, ALiveStream $stream): WhatRenewingCameTo
    {
        return $this->asMember(
            $stack,
            $whose,
            static fn(Obstacle $why): WhatRenewingCameTo => WhatRenewingCameTo::refused($why),
            fn(): WhatRenewingCameTo => WhatRenewingCameTo::renewedUntil(WhatTheTunerOpened::renewed($this->act($stack, $session, new TuneAction(stream: $stream->stream())))),
        );
    }

    #[Override]
    public function stop(Stack $stack, Session $session, Whose $whose, ALiveStream $stream): WhatStoppingCameTo
    {
        return $this->asMember(
            $stack,
            $whose,
            static fn(Obstacle $why): WhatStoppingCameTo => WhatStoppingCameTo::refused($why),
            fn(): WhatStoppingCameTo => WhatTheTunerOpened::stopped($this->act($stack, $session, new TuneAction(stream: $stream->stream(), stop: true))),
        );
    }

    /** The channels that member's account may watch, or why they could not be had. */
    private function listed(Stack $stack, Session $session, string $member): WhatIsOnTheChannels
    {
        $client = GatedClient::of($this->clients, $stack, $session);

        try {
            return WhatIsOnTheChannels::told(TheChannelsListed::in($client->read(Api::CHANNELS_ENDPOINT, ['member' => $member])));
        } catch (CertificateWasRefused|RequestFailed $why) {
            return WhatIsOnTheChannels::refused(WhatARefusalMeant::obstacle($why));
        } catch (ApiVersionMismatch|Unreachable|TheStackDoesNotOfferIt|UnreadableResponse|UnexpectedKind|ChannelsAreUnreadable|ChannelIsUnnamed|InstantIsBeforeTheEpoch $why) {
            return WhatIsOnTheChannels::refused($this->clients->whatStoodInTheWay($stack, $why));
        }
    }

    /**
     * Asked as a member, and answered with what stood in the way where the operator asks or the asking fails.
     *
     * @template TAnswer of object
     *
     * @param Closure(Obstacle): TAnswer $refused
     * @param Closure(): TAnswer         $asked
     *
     * @return TAnswer
     */
    private function asMember(Stack $stack, Whose $whose, Closure $refused, Closure $asked): object
    {
        return $whose->either(
            operator: static fn(): object => $refused(Obstacle::of(KindOfObstacle::NotForThisAccount)),
            member: fn(): object => $this->asked($stack, $refused, $asked),
        );
    }

    /**
     * @template TAnswer of object
     *
     * @param Closure(Obstacle): TAnswer $refused
     * @param Closure(): TAnswer         $asked
     *
     * @return TAnswer
     */
    private function asked(Stack $stack, Closure $refused, Closure $asked): object
    {
        try {
            return $asked();
        } catch (CertificateWasRefused|RequestFailed $why) {
            return $refused(WhatARefusalMeant::obstacle($why));
        } catch (ApiVersionMismatch|Unreachable|TheStackDoesNotOfferIt|UnreadableResponse|UnexpectedKind|TunedIsUnreadable|SentenceSaysNothing $why) {
            return $refused($this->clients->whatStoodInTheWay($stack, $why));
        }
    }

    /**
     * The `tune` action, sent under the session with a key of its own.
     *
     * @return Envelope<mixed>
     */
    private function act(Stack $stack, Session $session, TuneAction $action): Envelope
    {
        return GatedClient::of($this->clients, $stack, $session)->act($action, IdempotencyKey::from($this->entropy->nonce())->sent());
    }
}
