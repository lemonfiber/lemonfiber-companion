{{-- One service reading a setting or a secret: the variable it reads it into, and the default it declares, an empty one said as empty. --}}
@if (! $reader->declaresADefault)
    <x-design::note>{{ __('plugins.settings.read_by', ['service' => $reader->service, 'variable' => $reader->variable]) }}</x-design::note>
@elseif ($reader->default === '')
    <x-design::note>{{ __('plugins.settings.read_by_empty', ['service' => $reader->service, 'variable' => $reader->variable]) }}</x-design::note>
@else
    <x-design::note>{{ __('plugins.settings.read_by_default', ['service' => $reader->service, 'variable' => $reader->variable, 'default' => $reader->default]) }}</x-design::note>
@endif
