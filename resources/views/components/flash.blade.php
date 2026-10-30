@if (session('status'))
    <div class="flash" role="status">
        <span>{{ __(session('status')) }}</span>
        <button type="button" data-dismiss aria-label="Cerrar aviso">&times;</button>
    </div>
@endif
