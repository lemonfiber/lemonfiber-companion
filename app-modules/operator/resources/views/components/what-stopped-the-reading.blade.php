{{-- A screen's whole body where its reading did not come back, which is the
     `@else` of the screen's own `cameBack()`: what stood in the way, in the
     scrolling column every screen's body is. A reading that stops inside
     content the screen has already opened is `what-stood-in-the-way` on its
     own, because this inside that content is a scroll view inside a scroll
     view. --}}
<x-operator::content>
    <x-operator::what-stood-in-the-way :went="$went" :sign-in-goes-to="$signInGoesTo" />
</x-operator::content>
