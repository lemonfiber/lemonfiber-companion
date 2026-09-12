<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;
use function json_encode;

use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\SessionIsBlank;

use function print_r;
use function str_contains;
use function var_export;

// Deliberately low-entropy and obviously not a credential. A realistic-looking
// token here — `sess_` and a run of hex — is what a secret scanner is built to
// find, and it found it: gitleaks read the first draft of this line as a
// `generic-api-key` and failed the branch. Nothing about this test needs the
// value to look real, and an allowlist entry would have taught the scanner to
// stay quiet about session tokens in this repository, which is the opposite of
// what it is for.
const A_TOKEN = 'a-session-not-a-secret';

it('carries what the stack sent, unedited', function (): void {
    // Not trimmed beyond the blank check: whitespace inside a token is the
    // stack's business, and a client that quietly edits a credential before
    // sending it fails in a way the server cannot explain.
    expect(Session::of(A_TOKEN)->forTheHeader())->toBe(A_TOKEN);
    expect(Session::of(' padded ')->forTheHeader())->toBe(' padded ');
});

it('refuses a session with nothing in it', function (): void {
    expect(fn(): Session => Session::of(''))->toThrow(SessionIsBlank::class);
    expect(fn(): Session => Session::of("  \t\n"))->toThrow(SessionIsBlank::class);
});

it('says nothing about the token when it refuses one', function (): void {
    // The one place a message here is deliberately less useful than it could
    // be: an exception carrying a token puts it in a stack trace, and a stack
    // trace is what ends up in a diagnostic report (N1-R15).
    expect(fn(): Session => Session::of(' '))
        ->toThrow(SessionIsBlank::class, 'A stack admitted this app and sent an empty session');
});

it('is the same session, and is not another', function (): void {
    expect(Session::of(A_TOKEN)->is(Session::of(A_TOKEN)))->toBeTrue();
    expect(Session::of(A_TOKEN)->is(Session::of('sess_0000000000')))->toBeFalse();
});

it('does not print itself into a debugger', function (): void {
    // How N1-R15 actually gets broken: not by a decision, but by a `var_dump`
    // in a crash handler.
    expect(Session::of(A_TOKEN)->__debugInfo())->toBe(['token' => '(a session, hidden)']);
});

it('does not serialise itself into a payload', function (): void {
    $encoded = json_encode(['session' => Session::of(A_TOKEN)]);

    expect($encoded)->toBe('{"session":"(a session, hidden)"}');
});

it('is still readable by var_export, which is the part this cannot close', function (): void {
    // Asserted rather than left implied. `print_r` and `var_export` read
    // private properties directly and no method intercepts them, so this type
    // narrows the surface and does not seal it. What seals it is a diagnostic
    // report that refuses to walk a `Session` at all (N4-R13), and there is no
    // report assembler yet — when there is, this test is where somebody will
    // find out what it still has to do.
    expect(str_contains(var_export(Session::of(A_TOKEN), return: true), A_TOKEN))->toBeTrue();
});
