@extends('layouts.content')
@section('card')
<div class="card-header">{{ $title }}</div><div class="card-body">
<form method='POST' enctype='multipart/form-data' >
@csrf
<div class="form-group">
<label for='id'>{{ \App\Models\Voiting::labels()['id'] }}</label>
<input class='form-control @error("id") is-invalid @enderror' id='id' name='id' value='{{ $model->id }}' >
</div>
<div class="form-group">
<label for='game_id'>{{ \App\Models\Voiting::labels()['game_id'] }}</label>
<select class='form-control form-select' id='game_id' name='game_id' >
{!! \App\Modules\Functions::comboOptions($game_ids,'id','name',$model->game_id) !!}
</select>
</div>
<div class="form-group">
<div class="form-check">
<input class='form-check-input' type='checkbox' value='1' name='is_active' id='is_active' @if($model->is_active == 1) checked @endif>
<label for='is_active'>{{ \App\Models\Voiting::labels()['is_active'] }}</label>
</div>
</div>
<div class="form-group">
<label for='long_in_seconds'>{{ \App\Models\Voiting::labels()['long_in_seconds'] }}</label>
<input class='form-control @error("long_in_seconds") is-invalid @enderror' id='long_in_seconds' name='long_in_seconds' value='{{ $model->long_in_seconds }}' >
</div>
<button type="submit" class="btn btn-primary">Сохранить</button>
</form>
@endsection