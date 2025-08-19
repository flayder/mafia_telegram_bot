@extends('layouts.content')
@section('card')
<div class="card-header">{{ $title }}</div><div class="card-body">
<form method='POST' enctype='multipart/form-data' >
@csrf
<div class="form-group">
<label for='name'>{{ \App\Models\Currency::labels()['name'] }}</label>
<input class='form-control @error("name") is-invalid @enderror' id='name' name='name' value='{{ $model->name }}' >
</div>
<div class="form-group">
<label for='code'>{{ \App\Models\Currency::labels()['code'] }}</label>
<input class='form-control @error("code") is-invalid @enderror' id='code' name='code' value='{{ $model->code }}' >
</div>
<div class="form-group">
<label for='chat_command'>{{ \App\Models\Currency::labels()['chat_command'] }}</label>
<input class='form-control @error("chat_command") is-invalid @enderror' id='chat_command' name='chat_command' value='{{ $model->chat_command }}' >
</div>
<div class="form-group">
<div class="form-check">
<input class='form-check-input' type='checkbox' value='1' name='is_season' id='is_season' @if($model->is_season == 1) checked @endif>
<label for='is_season'>{{ \App\Models\Currency::labels()['is_season'] }}</label>
</div>
</div>
<button type="submit" class="btn btn-primary">Сохранить</button>
</form>
@endsection