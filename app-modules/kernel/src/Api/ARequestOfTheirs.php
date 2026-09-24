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
    ) {}

    /**
     * A service whose reach the stack records.
     *
     * @param string $destination where its requests go, or empty where it reaches nothing
     */
    public static function recorded(ServiceId $service, string $destination, string $purpose): self
    {
        if (trim($purpose) === '') {
            throw RequestSaysNothing::about('purpose');
        }

        return new self($service, ['destination' => $destination, 'purpose' => $purpose]);
    }

    /** A service the stack has no record of, listed anyway so the list is not short. */
    public static function unrecorded(ServiceId $service): self
    {
        return new self($service, null);
    }

    /** The service, by the name the stack declares it under. */
    public function service(): ServiceId
    {
        return $this->service;
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
