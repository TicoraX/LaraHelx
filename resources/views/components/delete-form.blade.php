@props(['action', 'name', 'albums' => null])
{{-- Con JS, app.js abre el <dialog> de la maestra con estos textos; sin JS, el DELETE se envía directo (03 §2). --}}
<form method="POST" action="{{ $action }}" data-submit-once
    data-confirm-title="¿Eliminar a {{ $name }}?"
    data-confirm="Vas a eliminar a {{ $name }}{{ match (true) { ! $albums => '', $albums === 1 => ' y su álbum', default => " y sus {$albums} álbumes" } }}. No se puede deshacer.">
    @csrf
    @method('DELETE')
    <button class="link-action link-action--danger" type="submit" data-busy-text="Eliminando…">Eliminar<span class="visually-hidden"> a {{ $name }}</span></button>
</form>
