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
 * has a fault.
 */
final readonly class AboutWhat
{
    private function __construct(private ?string $service) {}

    /** The machine itself, rather than anything running on it. */
    public static function theMachine(): self
    {
        return new self(null);
    }

    /** One of the services, named as the stack names it. */
    public static function theService(string $service): self
    {
        $named = trim($service);

        if ($named === '') {
            throw ServiceIsUnnamed::onAFinding();
        }

        return new self($named);
    }

    /**
     * @template TMachine of object
     * @template TService of object
     *
     * @param  Closure(): TMachine  $theMachine
     * @param  Closure(string): TService  $theService
     * @return TMachine|TService
     */
    public function either(Closure $theMachine, Closure $theService): object
    {
        return $this->service === null
            ? $theMachine()
            : $theService($this->service);
    }
}
