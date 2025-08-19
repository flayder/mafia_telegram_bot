@extends('layouts.content')
@section('card')
<div class="card-header">{{ $title }}</div><div class="card-body">
<form method='POST' enctype='multipart/form-data' >
@csrf
<div class="form-group">
<label for='name'>{{ \App\Models\GroupTarif::labels()['name'] }}</label>
<input class='form-control @error("name") is-invalid @enderror' id='name' name='name' value='{{ $model->name }}' >
</div>
<div class="form-group">
<label for='max_gamer_count'>{{ \App\Models\GroupTarif::labels()['max_gamer_count'] }}</label>
<input type="number" class='form-control @error("max_gamer_count") is-invalid @enderror' id='max_gamer_count' name='max_gamer_count' value='{{ $model->max_gamer_count }}' >
</div>
<div class="form-group">
    <label for='price'>{{ \App\Models\GroupTarif::labels()['price'] }}</label>
    <input type="number" class='form-control @error("price") is-invalid @enderror' id='price' name='price' value='{{ $model->price }}' >
</div>
<div class="form-group">
    <label for='reward'>{{ \App\Models\GroupTarif::labels()['reward'] }}</label>
    <input type="number" step="0.01" class='form-control @error("price") is-invalid @enderror' id='reward' name='reward' value='{{ $model->reward }}' >
</div>
<div class="form-group">
<label for='role_ids'>{{ \App\Models\GroupTarif::labels()['role_ids'] }}</label>
<input class='form-control @error("name") is-invalid @enderror' id='role_ids' name='role_ids' value='{{ $model->role_ids }}' >

</div>
<button type="submit" class="btn btn-primary">Сохранить</button>
</form>
@endsection