@if($errors->any())
    <div class="alert alert-danger small" role="alert">
        <strong>That couldn't be saved:</strong>
        <ul class="mb-0 mt-1">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    </div>
@endif
