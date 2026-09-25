<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\HowItWouldBeUpdated;
use Modules\Kernel\Api\ItselfSaysNothing;

use function sprintf;

/** One line carried out of an arm. */
final readonly class WhichArmTheUpdateTook
{
    public function __construct(public string $said) {}
}

/** Which arm an answer takes, and what it carried there. */
function howItWouldBeUpdated(HowItWouldBeUpdated $by): string
{
    return $by->either(
        byRunning: static fn(string $command): WhichArmTheUpdateTook => new WhichArmTheUpdateTook(sprintf('run:%s', $command)),
        instead: static fn(string $why): WhichArmTheUpdateTook => new WhichArmTheUpdateTook(sprintf('instead:%s', $why)),
        notSaid: static fn(): WhichArmTheUpdateTook => new WhichArmTheUpdateTook('neither'),
    )->said;
}

it('keeps a command, a reason there is none, and neither apart', function (): void {
    expect(howItWouldBeUpdated(HowItWouldBeUpdated::byRunning('brew upgrade lemonfiber')))->toBe('run:brew upgrade lemonfiber')
        ->and(howItWouldBeUpdated(HowItWouldBeUpdated::insteadBecause('A distribution owns it')))->toBe('instead:A distribution owns it')
        ->and(howItWouldBeUpdated(HowItWouldBeUpdated::notSaid()))->toBe('neither');
});

it('refuses a blank command or a blank reason, naming which', function (): void {
    expect(fn(): HowItWouldBeUpdated => HowItWouldBeUpdated::byRunning(' '))->toThrow(ItselfSaysNothing::class, '`command`')
        ->and(fn(): HowItWouldBeUpdated => HowItWouldBeUpdated::insteadBecause('  '))->toThrow(ItselfSaysNothing::class, '`instead`');
});
