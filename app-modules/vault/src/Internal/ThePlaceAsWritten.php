<?php

declare(strict_types=1);

namespace Modules\Vault\Internal;

use function array_key_exists;
use function is_array;
use function is_string;
use function json_decode;
use function json_encode;

use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\WhichTab;

/**
 * Where the operator was, as one record in the platform's store: the stack last
 * on view, and the word for the tab last used on each stack.
 *
 * A record this build did not write, or a part of one it cannot read, reads as
 * nowhere.
 */
final readonly class ThePlaceAsWritten
{
    private const int SHAPE = 1;

    private const string LAST = 'last';

    private const string TABS = 'tabs';

    /** @param array<mixed> $tabs */
    private function __construct(public string $last, public array $tabs) {}

    public static function nowhere(): self
    {
        return new self('', []);
    }

    public static function read(string $written): self
    {
        $record = json_decode($written, associative: true);

        if (! KeptInAShape::isIn($record, self::SHAPE)) {
            return self::nowhere();
        }

        $last = array_key_exists(self::LAST, $record) ? $record[self::LAST] : '';
        $tabs = array_key_exists(self::TABS, $record) ? $record[self::TABS] : [];

        return new self(is_string($last) ? $last : '', is_array($tabs) ? $tabs : []);
    }

    /** The record as it is written down, or false where it cannot be. */
    public function written(): string|false
    {
        return json_encode(KeptInAShape::written(self::SHAPE, [self::LAST => $this->last, self::TABS => $this->tabs]));
    }

    /** This place, with the operator on this tab of this stack. */
    public function on(StackId $stack, WhichTab $tab): self
    {
        return new self($stack->stored(), [...$this->tabs, $stack->stored() => $tab->value]);
    }

    /** This place, without anything of this stack. */
    public function without(StackId $stack): self
    {
        $tabs = $this->tabs;
        unset($tabs[$stack->stored()]);

        return new self($this->last === $stack->stored() ? '' : $this->last, $tabs);
    }

    /** Whether this names the stack, as the one last on view or by a tab used on it. */
    public function names(StackId $stack): bool
    {
        return $this->last === $stack->stored() || array_key_exists($stack->stored(), $this->tabs);
    }

    /** The tab last used on this stack, Health where none it can read is held. */
    public function tabOf(StackId $stack): WhichTab
    {
        if (! array_key_exists($stack->stored(), $this->tabs) || ! is_string($this->tabs[$stack->stored()])) {
            return WhichTab::Health;
        }

        return WhichTab::tryFrom($this->tabs[$stack->stored()]) ?? WhichTab::Health;
    }
}
