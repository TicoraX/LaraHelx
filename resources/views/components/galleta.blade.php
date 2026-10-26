@props(['name'])
{{-- Avatar sin foto (04 §4): iniciales de las dos primeras palabras. aria-hidden porque el nombre ya está escrito al lado. --}}
<span {{ $attributes->class('galleta') }} aria-hidden="true">{{ collect(preg_split('/\s+/u', trim($name)))->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('') }}</span>
