{{-- A route is written as an expression and a method as a name, because that is
     what each attribute takes: `@navigate` compiles its contents as PHP, and
     `@tap` names a method for the navigation stack to call. --}}
@if ($goes !== '')
    <native:button label="{{ $label }}" :disabled="$disabled" @navigate="$goes" />
@else
    <native:button label="{{ $label }}" :disabled="$disabled" @tap="{{ $tap }}" />
@endif
