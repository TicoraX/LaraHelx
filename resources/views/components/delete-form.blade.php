@props(['action', 'name', 'albums' => null, 'person' => true])
{{-- Con JS, app.js abre el <dialog> de la maestra con estos textos; sin JS, el DELETE se envía directo (03 §2).
     person: "eliminar a Soda Stereo" (artista) frente a "eliminar Signos" (álbum). --}}
@php($target = ($person ? 'a ' : '').$name)
<form method="POST" action="{{ $action }}" data-submit-once
    data-confirm-title="¿Eliminar {{ $target }}?"
    data-confirm="Vas a eliminar {{ $target }}{{ match (true) { ! $albums => '', $albums === 1 => ' y su álbum', default => " y sus {$albums} álbumes" } }}. No se puede deshacer.">
    @csrf
    @method('DELETE')
    <button class="link-action link-action--danger" type="submit" data-busy-text="Eliminando…">Eliminar<span class="visually-hidden"> {{ $target }}</span></button>
</form>
