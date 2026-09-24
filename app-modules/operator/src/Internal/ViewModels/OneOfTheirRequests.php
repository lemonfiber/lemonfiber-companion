<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * What one of the stack's services reaches, flattened for a template.
 *
 * **`$reachesSaid` is a catalogue key chosen by which arm the service took**,
 * so *reaches this*, *reaches nothing* and *there is no record* are three
 * sentences the catalogue owns rather than one field a template tests for
 * emptiness — an empty destination tested for emptiness is exactly how
 * *nobody knows* would come out as *nothing*.
 */
final readonly class OneOfTheirRequests
{
    /**
     * @param string $service     the service, by the name the stack declares it under
     * @param string $reachesSaid the catalogue key for what it reaches, or that nobody knows
     * @param string $destination where it goes, where the key takes one; empty otherwise
     * @param string $purpose     what it asks for; empty where there is no record
     */
    public function __construct(
        public string $service,
        public string $reachesSaid,
        public string $destination,
        public string $purpose,
    ) {}
}
