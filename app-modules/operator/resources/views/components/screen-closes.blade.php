{{-- Top-level for the reason the top bar is: hoisted out of the content tree,
     it becomes the platform's own navigation bar with its insets and gestures;
     left inside a container it is drawn as an ordinary row of buttons. --}}
<native:bottom-nav>
    <native:bottom-nav-item id="health" :active="$here === 'health'" label="{{ __('navigation.health') }}" url="{{ $goes->health() }}" icon="home" />
    <native:bottom-nav-item id="services" :active="$here === 'services'" label="{{ __('navigation.services') }}" url="{{ $goes->services() }}" icon="apps" />
    <native:bottom-nav-item id="updates" :active="$here === 'updates'" label="{{ __('navigation.updates') }}" url="{{ $goes->updates() }}" icon="download" />
    <native:bottom-nav-item id="repairs" :active="$here === 'repairs'" label="{{ __('navigation.repairs') }}" url="{{ $goes->repairs() }}" icon="build" />
</native:bottom-nav>
