@if ($goes !== '')
<native:button native:key="{{ $named }}" class="w-full" label="{{ $label }}" a11y-label="{{ $named }}" variant="{{ $variant }}" size="lg" :disabled="$disabled" @navigate="$goes" />
@else
<native:button native:key="{{ $named }}" class="w-full" label="{{ $label }}" a11y-label="{{ $named }}" variant="{{ $variant }}" size="lg" :disabled="$disabled" @tap="{{ $tap }}" />
@endif
