<?php

declare(strict_types=1);

namespace Tests\Support;

use Modules\Dx\Internal\WhatTheContractDeclares;

use function strpos;
use function substr;

/**
 * The envelopes the SDK generates from the contract, each read as the payload it declares.
 *
 * Read for the unions and literals each declares, which the rules in
 * `EveryWireValueIsACaseTest` hold this app's cases to. Through
 * {@see WhatTheContractDeclares::shapeOf()}, because the SDK names a shape once
 * and imports it wherever it is carried, so a union the file names rather than
 * writes is only found once the names are spelled out.
 */
final readonly class TheGeneratedEnvelopes
{
    /** The generated envelope that carries a report, as text. */
    public static function theGeneratedDoctorEnvelope(): string
    {
        return self::theGeneratedEnvelope('DoctorEnvelope');
    }

    /** The generated envelope that carries what a repair came to, as text. */
    public static function theGeneratedRepairEnvelope(): string
    {
        return self::theGeneratedEnvelope('RepairEnvelope');
    }

    /** The generated envelope that carries what has stopped coming in, as text. */
    public static function theGeneratedStuckEnvelope(): string
    {
        return self::theGeneratedEnvelope('StuckEnvelope');
    }

    /** The generated envelope that carries what reaches what, as text. */
    public static function theGeneratedWiringEnvelope(): string
    {
        return self::theGeneratedEnvelope('WiringEnvelope');
    }

    /** The generated envelope that carries what a member may watch, as text. */
    public static function theGeneratedHeldEnvelope(): string
    {
        return self::theGeneratedEnvelope('HeldEnvelope');
    }

    /** The generated envelope that carries a service's scrollback, as text. */
    public static function theGeneratedLogEnvelope(): string
    {
        return self::theGeneratedEnvelope('LogEnvelope');
    }

    /**
     * The health summary inside the generated `dashboard` envelope, as text.
     *
     * Cut out of the envelope because the dashboard names `standing` twice, once
     * for the front door and once for the summary, and two unions under one name
     * are no union at all to `TheWireUnions::unionIn()`.
     */
    public static function theGeneratedHealthSummary(): string
    {
        $dashboard = self::theGeneratedEnvelope('DashboardEnvelope');
        $from = (int) strpos($dashboard, 'health: array{');

        return substr($dashboard, $from, (int) strpos($dashboard, 'household:', $from) - $from);
    }

    /** The generated envelope that carries what a stack is set to, as text. */
    public static function theGeneratedConfigEnvelope(): string
    {
        return self::theGeneratedEnvelope('ConfigEnvelope');
    }

    /** The generated envelope that carries what the whole stack is doing, as text. */
    public static function theGeneratedStatusEnvelope(): string
    {
        return self::theGeneratedEnvelope('StatusEnvelope');
    }

    /** The generated envelope that carries what the machine keeps running, as text. */
    public static function theGeneratedHostingEnvelope(): string
    {
        return self::theGeneratedEnvelope('HostingEnvelope');
    }

    /** The generated `history` envelope, as text. */
    public static function theGeneratedHistoryEnvelope(): string
    {
        return self::theGeneratedEnvelope('HistoryEnvelope');
    }

    /** The generated `bandwidth` envelope, as text. */
    public static function theGeneratedBandwidthEnvelope(): string
    {
        return self::theGeneratedEnvelope('BandwidthEnvelope');
    }

    /** The generated `space` envelope, as text. */
    public static function theGeneratedSpaceEnvelope(): string
    {
        return self::theGeneratedEnvelope('SpaceEnvelope');
    }

    /** The generated `trace` envelope, as text. */
    public static function theGeneratedTraceEnvelope(): string
    {
        return self::theGeneratedEnvelope('TraceEnvelope');
    }

    /** The generated `self-update` envelope, as text. */
    public static function theGeneratedSelfUpdateEnvelope(): string
    {
        return self::theGeneratedEnvelope('SelfUpdateEnvelope');
    }

    /** The generated `outbound` envelope, as text. */
    public static function theGeneratedOutboundEnvelope(): string
    {
        return self::theGeneratedEnvelope('OutboundEnvelope');
    }

    /**
     * One generated envelope's payload, as text, every shape it names spelled out.
     *
     * Named rather than read at each caller once there were several: an envelope
     * this tree does not have answers `''`, and an empty source makes every union
     * it is asked about come back empty. The rules in `EveryWireValueIsACaseTest`
     * assert they found something for exactly that reason, but they would name the
     * union rather than the envelope.
     */
    public static function theGeneratedEnvelope(string $called): string
    {
        return WhatTheContractDeclares::shapeOf($called);
    }

    /**
     * The generated envelope that carries the household, as text.
     *
     * A second file rather than a search across `src/Generated`, and deliberately.
     * `state` is declared in both this envelope and the doctor one, with different
     * unions — a problem's standing and a request's — so a reader that swept every
     * generated file would find two occurrences that disagree and answer nothing,
     * which is `unionIn`'s honest refusal applied to a question that is not really
     * ambiguous. The ambiguity is in the wire's choice of name, not in the contract,
     * and naming the envelope is how this side says which `state` it means.
     */
    public static function theGeneratedHouseholdEnvelope(): string
    {
        return self::theGeneratedEnvelope('HouseholdEnvelope');
    }
}
