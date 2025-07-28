{{-- Schema.org JSON-LD --}}
@if(isset($schemas) && is_array($schemas))
    @foreach($schemas as $schema)
        <x-schema :type="$schema['type']" :data="$schema['data'] ?? []" />
    @endforeach
@elseif(isset($schema))
    {!! $schema !!}
@endif