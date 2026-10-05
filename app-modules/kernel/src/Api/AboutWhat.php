<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

use function trim;

/**
 * The service a finding is about, where it is about one.
 *
 * Absent for the checks that are about the machine rather than about something
 * running on it — the environment, the filesystem, the operator's own choices.
 * The engine says which, and this app dropped it.
 *
 * **The title does not already say this.** A finding reads `Vpn` /
 * `The tunnel` / `Failed`, and the service it is about is `gluetun` — carried
 * beside the title rather than inside it, because the same check runs against
 * whichever service fills that role and a title naming one would be wrong on
 * the next machine. An operator with nineteen services needs the name.
 *
 * Two arms rather than a nullable string. The blank refusal lives here rather
 * than at the call site, as {@see Check} does it: a service named as whitespace
 * is a name nobody can read and the engine producing one has a fault — and it
 * keeps its own sentence rather than deferring to {@see ServiceId::called()},
 * because *a finding named no service* and *a caller asked for the logs of
 * nothing* are different faults.
 *
 * **The arm hands over a {@see ServiceId} rather than the string.** A finding's
 * service and the service a log window is read for are the same name for the
 * same thing, so a screen sending an operator from one to the other hands over
 * the type rather than a bare string.
 *
 * **What the stack calls it travels beside the id.** The id is a key, not a
 * name: capitalising `qbittorrent` does not arrive at qBittorrent, and the
 * stack has already written the name down. So the name is the report's to give,
 * and a finding whose report gave none says so rather than being handed its id
 * to show in its place.
 */
final readonly class AboutWhat
{
    private function __construct(private ?ServiceId $service, private ?string $called) {}

    /** The machine itself, rather than anything running on it. */
    public static function theMachine(): self
    {
        return new self(null, null);
    }

    /** One of the services, named as the stack names it. */
    public static function theService(string $service): self
    {
        if (trim($service) === '') {
            throw ServiceIsUnnamed::onAFinding();
        }

        return new self(ServiceId::called($service), null);
    }

    /** One of the services, with what the stack calls it in front of an operator. */
    public static function theNamedService(string $service, string $called): self
    {
        $name = trim($called);

        if ($name === '') {
            throw ServiceIsUnnamed::calledNothing();
        }

        return new self(self::theService($service)->service, $name);
    }

    /**
     * @template TMachine of object
     * @template TService of object
     *
     * @param  Closure(): TMachine  $theMachine
     * @param  Closure(ServiceId): TService  $theService
     * @return TMachine|TService
     */
    public function either(Closure $theMachine, Closure $theService): object
    {
        return $this->service instanceof ServiceId
            ? $theService($this->service)
            : $theMachine();
    }

    /**
     * What the stack calls it, where the report said.
     *
     * The machine is called nothing, and so is a service whose report gave no
     * name — an engine from before the name was carried, or a service the stack
     * does not declare.
     *
     * @template TCalled of object
     * @template TUnsaid of object
     *
     * @param  Closure(string): TCalled  $called
     * @param  Closure(): TUnsaid  $unsaid
     * @return TCalled|TUnsaid
     */
    public function whatItIsCalled(Closure $called, Closure $unsaid): object
    {
        return $this->called === null ? $unsaid() : $called($this->called);
    }
}
