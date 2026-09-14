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
 * Two arms rather than a nullable string, for `C2`'s reason. The blank refusal
 * lives here rather than at the call site, as {@see Check} does it: a service
 * named as whitespace is a name nobody can read and the engine producing one
 * has a fault — and it keeps its own sentence rather than deferring to
 * {@see ServiceId::called()}, because *a finding named no service* and *a
 * caller asked for the logs of nothing* are different faults.
 *
 * **The arm hands over a {@see ServiceId} rather than the string.** A finding's
 * service and the service a log window is read for are the same name for the
 * same thing, and a screen that sent an operator from one to the other used to
 * do it by passing a bare string — which is exactly the mistake `D2` names.
 */
final readonly class AboutWhat
{
    private function __construct(private ?ServiceId $service) {}

    /** The machine itself, rather than anything running on it. */
    public static function theMachine(): self
    {
        return new self(null);
    }

    /** One of the services, named as the stack names it. */
    public static function theService(string $service): self
    {
        if (trim($service) === '') {
            throw ServiceIsUnnamed::onAFinding();
        }

        return new self(ServiceId::called($service));
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
}
