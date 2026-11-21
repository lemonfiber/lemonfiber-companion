<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * How a stack serves its web surface, as its own screen draws it.
 */
final readonly class HowItIsServedAsShown
{
    /**
     * @param HowTheReadingWent             $went        whether the stack answered, and what stopped it where not
     * @param string                        $port        the port it answers on, as drawn
     * @param string                        $encrypted   the key for whether it is encrypted
     * @param string                        $network     the key for whether it is offered to the network
     * @param string                        $named       the name a request has to carry, empty where none
     * @param string                        $unrecorded  why where it is served could not be written down, in the stack's words, or empty
     * @param TheCertificateAsShown|null    $certificate the certificate it presents, or nothing where it is served plainly
     */
    public function __construct(
        public HowTheReadingWent $went,
        public string $port,
        public string $encrypted,
        public string $network,
        public string $named,
        public string $unrecorded,
        public ?TheCertificateAsShown $certificate,
    ) {}
}
