<?php

declare(strict_types=1);

namespace Lemonfiber\Native;

use function array_key_exists;
use function is_array;
use function is_string;
use function sodium_base642bin;

use SodiumException;

/**
 * Being woken through a UnifiedPush distributor, through lemonfiber's bridge.
 *
 * Android alone: each stack is registered with the distributor as an instance
 * of its own, named by the stack's id, with a WebPush key pair the phone makes
 * for it in its keystore. A push wakes the app for that stack and is never
 * read. iOS answers that it cannot be woken this way.
 */
final readonly class Waking
{
    public function distributors(): DistributorsListed
    {
        $said = WhatTheBridgeAnswered::toNothing(Call::WakingDistributors);
        $listed = $said->listed(WhatAnAnswerHolds::Distributors);

        return HowWakingAnswered::in($said) === HowWakingAnswered::Listed && $listed !== null
            ? DistributorsListed::installed($this->installed($listed))
            : DistributorsListed::notHere();
    }

    /** Make the stack's key pair and ask `$distributor` for its endpoint, and wait for its answer. */
    public function register(string $stack, string $distributor): EndpointAnswered
    {
        return $this->answered(WhatTheBridgeAnswered::to(Call::WakingRegister, ['stack' => $stack, 'distributor' => $distributor]));
    }

    /** The endpoint the distributor gives the stack now. */
    public function endpoint(string $stack): EndpointAnswered
    {
        return $this->answered(WhatTheBridgeAnswered::to(Call::WakingEndpoint, ['stack' => $stack]));
    }

    /** Drop the stack's registration and key pair, and answer whether the phone let go. */
    public function letGo(string $stack): bool
    {
        return WhatTheBridgeAnswered::to(Call::WakingLetGo, ['stack' => $stack])->outcome() === HowWakingAnswered::LetGo->value;
    }

    /** Drop every registration and key pair made for waking, and answer whether the phone let go. */
    public function letGoOfEvery(): bool
    {
        return WhatTheBridgeAnswered::toNothing(Call::WakingLetGoOfEvery)->outcome() === HowWakingAnswered::LetGo->value;
    }

    private function answered(WhatTheBridgeAnswered $said): EndpointAnswered
    {
        $endpoint = $said->word(WhatAnAnswerHolds::Endpoint);
        $key = $this->bytes($said->word(WhatAnAnswerHolds::P256dh));

        if (HowWakingAnswered::in($said) !== HowWakingAnswered::Given || ! is_string($endpoint) || $key === '') {
            return EndpointAnswered::not($said->word(WhatAnAnswerHolds::Because) ?? '');
        }

        return EndpointAnswered::given($endpoint, $key);
    }

    /**
     * What each package listed is called, leaving out a row that names none.
     *
     * @param array<mixed> $listed
     *
     * @return array<string, string>
     */
    private function installed(array $listed): array
    {
        $installed = [];

        foreach ($listed as $row) {
            $package = $this->wordIn($row, WhatAnAnswerHolds::Package);

            if ($package !== '') {
                $installed[$package] = $this->wordIn($row, WhatAnAnswerHolds::Called);
            }
        }

        return $installed;
    }

    /** The word a listed row holds under this key, or empty where it holds none. */
    private function wordIn(mixed $row, WhatAnAnswerHolds $key): string
    {
        if (! is_array($row) || ! array_key_exists($key->value, $row)) {
            return '';
        }

        return is_string($row[$key->value]) ? $row[$key->value] : '';
    }

    private function bytes(?string $written): string
    {
        if ($written === null) {
            return '';
        }

        try {
            return sodium_base642bin($written, SODIUM_BASE64_VARIANT_ORIGINAL);
        } catch (SodiumException) {
            return '';
        }
    }
}
