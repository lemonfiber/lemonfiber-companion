<?php

declare(strict_types=1);

use function basename;
use function expect;
use function explode;
use function file_get_contents;
use function file_put_contents;
use function glob;

use Illuminate\Support\Facades\Blade;

use function is_string;
use function it;

use Native\Mobile\Edge\NativeTagPrecompiler;

use function sprintf;
use function str_replace;
use function str_starts_with;

use Symfony\Component\Process\Process;

use function sys_get_temp_dir;
use function tempnam;
use function trim;
use function unlink;

// A screen is Blade, and Blade becomes PHP before anything renders it. Every
// other suite here reads the *template* — that a screen names its controls, that
// each one is operated, that nothing addresses the operator in the wrong person.
// None of them compile it, so none of them can see a template that compiles to
// PHP that does not parse.
//
// That is not a theoretical gap. `@navigate="…"` hands its contents to the
// precompiler as an expression rather than as text, so a Blade echo inside one
// is copied verbatim into generated PHP:
//
//     ::nav('navigate', null, {{ $this->tappingGoesTo($stack) }})
//
// Thirty-two attributes across twelve templates were written that way, which is
// every screen the app has. On a device each one is a `ParseError` at the first
// frame; in the suite each one was green. The device is what found it, and a
// device is the most expensive place to find anything.
//
// The assertion is `php -l` rather than a search for a leftover `{{`, because
// the brace is one way to write PHP that does not parse and the linter is the
// question actually being asked.

/**
 * The compiled form of one template, as the device compiles it.
 *
 * The precompiler only transforms while a native render is in progress, so a
 * test that wants what the device produces has to say so — and put the flag
 * back, because it is static and everything after this renders too.
 */
function compiledAsTheDeviceCompilesIt(string $template): string
{
    $was = NativeTagPrecompiler::setActive(active: true);
    $source = file_get_contents($template);

    try {
        return Blade::compileString(is_string($source) ? $source : '');
    } finally {
        NativeTagPrecompiler::setActive(active: $was);
    }
}

/**
 * What `php -l` says about a compiled screen, or an empty string if it parses.
 *
 * At file scope rather than inside the assertion because {@see Process::run()}
 * declares a checked exception, and a closure that throws one is refused here.
 */
function whatTheLinterMakesOf(string $compiled): string
{
    $scratch = sprintf('%s.php', tempnam(sys_get_temp_dir(), 'compiled'));
    file_put_contents($scratch, $compiled);

    $linted = new Process(['php', '-l', $scratch]);
    $linted->run();

    $said = sprintf('%s%s', $linted->getErrorOutput(), $linted->getOutput());
    $parsed = $linted->isSuccessful();
    unlink($scratch);

    return $parsed ? '' : firstComplaintIn($said, $scratch);
}

/**
 * The linter's complaint, without the scratch path it happened to be written to.
 *
 * The first line is not always the complaint — `php -l` opens with a blank one
 * often enough that taking it by position names the file and nothing else, which
 * is the failure this suite exists to catch rather than commit.
 */
function firstComplaintIn(string $said, string $scratch): string
{
    foreach (explode("\n", $said) as $line) {
        $line = trim(str_replace($scratch, 'the compiled screen', $line));

        if ($line !== '' && ! str_starts_with($line, 'Errors parsing')) {
            return $line;
        }
    }

    return 'php -l refused it and said nothing';
}

/** @return list<string> */
function everyScreenTemplate(): array
{
    // `GLOB_BRACE` rather than one pattern, because a component lives a
    // directory deeper than a screen and a glob that stops at the screens
    // checks everything except the file every screen now goes through.
    $found = glob(
        sprintf('%s/../../app-modules/*/resources/views/{,*/}*.blade.php', __DIR__),
        GLOB_BRACE,
    );

    return $found === false ? [] : $found;
}

it('every screen compiles to PHP that parses', function (): void {
    $refused = [];

    foreach (everyScreenTemplate() as $template) {
        $said = whatTheLinterMakesOf(compiledAsTheDeviceCompilesIt($template));

        if ($said !== '') {
            $refused[] = sprintf('%s: %s', basename($template), $said);
        }
    }

    expect($refused)->toBe([]);
});

it('finds the screens it claims to read', function (): void {
    // A selector that matches nothing passes silently, which is the one way a
    // rule can claim more than it enforces. Twelve screens and the chrome they
    // sit in is what the repository holds; the count is what stops the glob
    // from quietly becoming a no-op, and what caught it reaching only the
    // screens while the component every screen goes through went unread.
    expect(everyScreenTemplate())->toHaveCount(20);
});

it('refuses a template whose compiled form does not parse', function (): void {
    // The counterfactual, planted rather than described: the exact shape the
    // device reported, compiled through the same path, has to be refused here.
    $scratch = sprintf('%s.blade.php', tempnam(sys_get_temp_dir(), 'screen'));
    file_put_contents($scratch, '<native:button label="x" @navigate="{{ $this->somewhere() }}" />');

    $compiled = compiledAsTheDeviceCompilesIt($scratch);
    unlink($scratch);

    expect($compiled)->toContain('{{')
        ->and(whatTheLinterMakesOf($compiled))->toContain('syntax error');
});
