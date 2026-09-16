{{-- Top-level and titled by attribute, which is what gets it hoisted out of the
     content tree and given the platform's safe-area inset. A bar nested in a
     container is drawn inline instead: a back arrow in the middle of the screen
     and a title under the status bar. --}}
<native:top-bar title="{{ $title }}" />
