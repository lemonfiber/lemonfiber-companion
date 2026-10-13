<?php

declare(strict_types=1);

namespace Modules\Sdk\Internal;

use Lemonfiber\Sdk\ActionRequest;
use Lemonfiber\Sdk\BundleFile;
use Lemonfiber\Sdk\Contract\Api;
use Lemonfiber\Sdk\Envelope\Envelope;
use Lemonfiber\Sdk\Exception\ApiVersionMismatch;
use Lemonfiber\Sdk\Exception\ConfigurationProblem;
use Lemonfiber\Sdk\Exception\NoSuchJob;
use Lemonfiber\Sdk\Exception\RequestFailed;
use Lemonfiber\Sdk\Exception\UnexpectedKind;
use Lemonfiber\Sdk\Exception\Unreachable;
use Lemonfiber\Sdk\Exception\UnreadableResponse;
use Lemonfiber\Sdk\JobStanding;
use Lemonfiber\Sdk\Keys;
use Lemonfiber\Sdk\Logs;
use Lemonfiber\Sdk\LogWindow;
use Lemonfiber\Sdk\Picture;
use Lemonfiber\Sdk\Repair;
use Modules\Kernel\Api\Ability;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Sdk\Api\Clients;
use Modules\Sdk\Api\TheStackDoesNotOfferIt;

/**
 * One stack, asked through the gate: every request names the path it is sent
 * to, and the stack is asked whether it serves that path first.
 *
 * What every adapter in this module reaches a stack through. Each read and
 * each action goes to {@see Clients::towards()} with its path, so a stack too
 * old for it, or an account it is not for, is answered before the wire with
 * {@see TheStackDoesNotOfferIt} rather than the stack refusing a request it
 * never knew. Deciding by trying the request is what the stack's declaration
 * exists to replace.
 *
 * **What passes without asking, and why each.** What became of work and
 * letting it go follow an action that was asked about already, and a bundle is
 * fetched by the name the support action answered with: none of them is a
 * request a stack declares.
 *
 * The SDK's client's own method names, because every adapter calls them and
 * the doors this app opens are counted by name.
 */
final readonly class GatedClient
{
    private function __construct(
        private Clients $clients,
        private Stack $stack,
        private Session $session,
    ) {}

    /** This stack, asked with this session, through these clients. */
    public static function of(Clients $clients, Stack $stack, Session $session): self
    {
        return new self($clients, $stack, $session);
    }

    /**
     * @param array<string, scalar|list<scalar>|null> $query
     *
     * @return Envelope<mixed>
     *
     * @throws ApiVersionMismatch
     * @throws RequestFailed
     * @throws TheStackDoesNotOfferIt
     * @throws Unreachable
     * @throws UnreadableResponse
     */
    public function read(string $endpoint, array $query = []): Envelope
    {
        return $this->clients->towards($this->stack, $this->session, Ability::of($endpoint))->read($endpoint, $query);
    }

    /**
     * One of a read the stack declares once for all of them, such as one title on a shelf.
     *
     * @param array<string, scalar|list<scalar>|null> $query
     *
     * @return Envelope<mixed>
     *
     * @throws ApiVersionMismatch
     * @throws RequestFailed
     * @throws TheStackDoesNotOfferIt
     * @throws Unreachable
     * @throws UnreadableResponse
     */
    public function readOneOf(string $declared, string $endpoint, array $query = []): Envelope
    {
        return $this->clients->towards($this->stack, $this->session, Ability::of($declared))->read($endpoint, $query);
    }

    /**
     * @return Envelope<mixed>
     *
     * @throws ApiVersionMismatch
     * @throws ConfigurationProblem
     * @throws RequestFailed
     * @throws TheStackDoesNotOfferIt
     * @throws Unreachable
     * @throws UnreadableResponse
     */
    public function act(ActionRequest $action, ?string $idempotencyKey = null): Envelope
    {
        return $this->clients->towards($this->stack, $this->session, Ability::of($action->endpoint()))->act($action, $idempotencyKey);
    }

    /**
     * @return Envelope<mixed>
     *
     * @throws ApiVersionMismatch
     * @throws ConfigurationProblem
     * @throws RequestFailed
     * @throws TheStackDoesNotOfferIt
     * @throws Unreachable
     * @throws UnreadableResponse
     */
    public function repair(Repair $asked, ?string $idempotencyKey = null): Envelope
    {
        return $this->clients->towards($this->stack, $this->session, Ability::of($asked->request()->endpoint()))->repair($asked, $idempotencyKey);
    }

    /**
     * @throws ApiVersionMismatch
     * @throws RequestFailed
     * @throws TheStackDoesNotOfferIt
     * @throws UnexpectedKind
     * @throws Unreachable
     * @throws UnreadableResponse
     */
    public function logs(Logs $asked): LogWindow
    {
        return $this->clients->towards($this->stack, $this->session, Ability::of(Api::LOGS_ENDPOINT))->logs($asked);
    }

    /**
     * The poster of a title the house holds, through the gate the core declares it under.
     *
     * @param array<string, scalar> $query
     *
     * @throws ApiVersionMismatch
     * @throws ConfigurationProblem
     * @throws RequestFailed
     * @throws TheStackDoesNotOfferIt
     * @throws Unreachable
     * @throws UnreadableResponse
     */
    public function poster(string $id, array $query = []): Picture
    {
        return $this->clients->towards($this->stack, $this->session, Ability::of(Api::POSTER_ENDPOINT))->poster($id, $query);
    }

    /**
     * The integration keys the stack lists, asked of the client directly: the stack declares no
     * ability for the routes that let somebody in.
     *
     * @return Envelope<mixed>
     *
     * @throws ApiVersionMismatch
     * @throws RequestFailed
     * @throws Unreachable
     * @throws UnreadableResponse
     */
    public function keys(): Envelope
    {
        return $this->clients->client($this->stack, $this->session)->read(Api::KEYS_ENDPOINT);
    }

    /**
     * The phone keys the stack lists, asked of the client directly, for {@see self::keys()}'s reason.
     *
     * @return Envelope<mixed>
     *
     * @throws ApiVersionMismatch
     * @throws RequestFailed
     * @throws Unreachable
     * @throws UnreadableResponse
     */
    public function phoneKeys(): Envelope
    {
        return $this->clients->client($this->stack, $this->session)->read(Api::DEVICE_KEYS_ENDPOINT);
    }

    /**
     * What the client does with keys, asked of it directly, for {@see self::keys()}'s reason: revoking an
     * integration key or a phone key, each answered with its listing as it stands after.
     */
    public function keyring(): Keys
    {
        return $this->clients->client($this->stack, $this->session)->keys();
    }

    /**
     * The backdrop of a title the house holds, through the gate the core declares it under.
     *
     * @param array<string, scalar> $query
     *
     * @throws ApiVersionMismatch
     * @throws ConfigurationProblem
     * @throws RequestFailed
     * @throws TheStackDoesNotOfferIt
     * @throws Unreachable
     * @throws UnreadableResponse
     */
    public function backdrop(string $id, array $query = []): Picture
    {
        return $this->clients->towards($this->stack, $this->session, Ability::of(Api::BACKDROP_ENDPOINT))->backdrop($id, $query);
    }

    /**
     * The poster of a title a member's search offered, through the gate the core declares it under.
     *
     * @param array<string, scalar> $query
     *
     * @throws ApiVersionMismatch
     * @throws ConfigurationProblem
     * @throws RequestFailed
     * @throws TheStackDoesNotOfferIt
     * @throws Unreachable
     * @throws UnreadableResponse
     */
    public function askablePoster(string $id, array $query = []): Picture
    {
        return $this->clients->towards($this->stack, $this->session, Ability::of(Api::ASKABLE_POSTER_ENDPOINT))->askablePoster($id, $query);
    }

    /**
     * @throws ApiVersionMismatch
     * @throws NoSuchJob
     * @throws RequestFailed
     * @throws Unreachable
     * @throws UnreadableResponse
     */
    public function whatBecameOf(string $job): JobStanding
    {
        return $this->clients->client($this->stack, $this->session)->whatBecameOf($job);
    }

    /**
     * @throws ApiVersionMismatch
     * @throws NoSuchJob
     * @throws RequestFailed
     * @throws Unreachable
     * @throws UnreadableResponse
     */
    public function letGoOf(string $job): JobStanding
    {
        return $this->clients->client($this->stack, $this->session)->letGoOf($job);
    }

    /**
     * @throws RequestFailed
     * @throws Unreachable
     */
    public function bundle(string $name): BundleFile
    {
        return $this->clients->client($this->stack, $this->session)->bundle($name);
    }
}
