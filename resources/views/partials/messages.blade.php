@if (session('ok'))
    <div class="qa-alert qa-alert-ok">
        {{ session('ok') }}
    </div>
@endif

@if (session('error'))
    <div class="qa-alert qa-alert-error">
        {{ session('error') }}
    </div>
@endif

@if ($errors->any())
    <div class="qa-alert qa-alert-error">
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
