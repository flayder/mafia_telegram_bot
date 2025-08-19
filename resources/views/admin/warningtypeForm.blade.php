@extends('layouts.content')
@section('card')
<div class="card-header">{{ $title }}</div><div class="card-body">
<form method='POST' enctype='multipart/form-data' >
@csrf
<div class="form-group">
<label for='name'>{{ \App\Models\WarningType::labels()['name'] }}</label>
<input class='form-control @error("name") is-invalid @enderror' id='name' name='name' value='{{ $model->name }}' >
</div>
<div class="form-group">
<label for='description'>{{ \App\Models\WarningType::labels()['description'] }}</label>
<input class='form-control @error("description") is-invalid @enderror' id='description' name='description' value='{{ $model->description }}' >
</div>
<div class="form-group">
<div class="form-check">
<input class='form-check-input' type='checkbox' value='1' name='is_mute' id='is_mute' @if($model->is_mute == 1) checked @endif>
<label for='is_mute'>{{ \App\Models\WarningType::labels()['is_mute'] }}</label>
</div>
</div>
<button type="submit" class="btn btn-primary">Сохранить</button>
</form>
@endsection