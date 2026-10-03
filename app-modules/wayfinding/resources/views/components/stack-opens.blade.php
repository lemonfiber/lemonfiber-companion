{{-- The bar's title is the stack's name, drawn as a control: pressed, it opens
     the list of stacks. Top-level for the reason every bar is, which is what
     gets it hoisted into the platform's own bar. --}}
<native:top-bar title="{{ $title }}">
    <native:top-bar-title>
        <native:button variant="ghost" class="text-theme-text" label="{{ $title }}" icon-trailing="expand_more" ios-icon-trailing="chevron.down" a11y-label="{{ $title }}" @press="chooseAStack()" />
    </native:top-bar-title>
</native:top-bar>

<x-wayfinding::stacks-to-choose :stacks="$stacks" :choosing="$choosing" />
