{{-- A route is written as an expression and a method as a name, because that is
     what each attribute takes: `@navigate` compiles its contents as PHP, and
     `@tap` names a method for the navigation stack to call.

     No colour here. A filled button takes `primary` from the widget theme and
     honours no per-instance colour — the renderer says so in as many words —
     so a class on a button is parsed, dropped, and looks like a design that
     did not take. `DES-R24`'s accent is asserted once, on the theme itself, in
     `TheTheme::paint()`. --}}
@if ($goes !== '')
    <native:button class="w-full" label="{{ $label }}" :disabled="$disabled" @navigate="$goes" />
@else
    <native:button class="w-full" label="{{ $label }}" :disabled="$disabled" @tap="{{ $tap }}" />
@endif
