<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

use function trim;

/**
 * What one of the stack's services reaches, which is not lemonfiber's doing.
 *
 * **Two arms, because *reaches nothing* and *nobody knows* are opposite
 * claims.** The stack ships a record of what most services reach; one that
 * arrived after this build, or from somebody's own fork, has none. Carried as
 * an empty destination, it would be this app telling somebody nothing leaves
 * their machine on the strength of having no idea — so the unrecorded arm
 * carries no destination and no purpose at all, and a screen has nothing to
 * mistake for one.
 *
 * On the recorded arm an empty destination *is* the answer: this service
 * reaches nothing.
 */
final readonly class ARequestOfTheirs
{
    /**
     * @param array{destination: string, purpose: string}|null $record what the stack records it reaching, or
     *                                                                   nothing where it records nothing
     */
    private function __construct(
        private ServiceId $service,
        private ?array $record,
        private WhoPutItThere $origin,
    ) {}

    /**
     * A service whose reach the stack records.
     *
     * @param string $destination where its requests go, or empty where it reaches nothing
     */
    public static function recorded(ServiceId $service, string $destination, string $purpose, WhoPutItThere $origin): self
    {
        if (trim($purpose) === '') {
            throw RequestSaysNothing::about('purpose');
        }

        return new self($service, ['destination' => $destination, 'purpose' => $purpose], $origin);
    }

    /** A service the stack has no record of, listed anyway so the list is not short. */
    public static function unrecorded(ServiceId $service, WhoPutItThere $origin): self
    {
        return new self($service, null, $origin);
    }

    /** The service, by the name the stack declares it under. */
    public function service(): ServiceId
    {
        return $this->service;
    }

    /**
     * Who put this service here, and so whose request this is.
     *
     * On both arms, because a plugin's service is the likeliest to arrive with
     * no record of where it reaches — its manifest has nowhere to say — and a
     * row reading *nobody knows what this reaches* is the one where knowing
     * who brought it matters most.
     */
    public function origin(): WhoPutItThere
    {
        return $this->origin;
    }

    /**
     * Say what happens in each case, and get back what you built.
     *
     * @template TRecorded of object
     * @template TUnrecorded of object
     *
     * @param Closure(string $destination, string $purpose): TRecorded $recorded
     * @param Closure(): TUnrecorded                                    $unrecorded
     *
     * @return TRecorded|TUnrecorded
     */
    public function reaches(Closure $recorded, Closure $unrecorded): object
    {
        return $this->record === null
            ? $unrecorded()
            : $recorded($this->record['destination'], $this->record['purpose']);
    }
}
