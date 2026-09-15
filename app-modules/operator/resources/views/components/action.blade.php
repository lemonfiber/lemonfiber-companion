{{-- A route is written as an expression and a method as a name, because that is
     what each attribute takes: `@navigate` compiles its contents as PHP, and
     `@tap` names a method for the navigation stack to call.

     The accent is asserted here and nowhere else, which is what `DES-R24` maps
     `lemon` to. It is one place because every control goes through this file —
     painting it at each site would be the decision made forty-eight times.
     `ink` on `lemon` measures 10.9:1 either way the reader has their phone set,
     so the pair carries its own legibility rather than borrowing the ground's
     (`DES-R15`). Everything else the platform owns. --}}
@if ($goes !== '')
    <native:button class="w-full bg-theme-accent text-theme-on-accent" label="{{ $label }}" :disabled="$disabled" @navigate="$goes" />
@else
    <native:button class="w-full bg-theme-accent text-theme-on-accent" label="{{ $label }}" :disabled="$disabled" @tap="{{ $tap }}" />
@endif
