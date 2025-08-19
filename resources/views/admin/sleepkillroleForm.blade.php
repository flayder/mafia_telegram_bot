@extends('layouts.content')
@section('card')
<div class="card-header">{{ $title }}</div><div class="card-body">
<form method='POST' enctype='multipart/form-data' >
@csrf
<div class="form-group">
<label for='role_id'>{{ \App\Models\SleepKillRole::labels()['role_id'] }}</label>
<select class='form-control form-select' id='role_id' name='role_id' >
{!! \App\Modules\Functions::comboOptions($role_ids,'id','name',$model->role_id) !!}
</select>
</div>
<div class="form-group">
<label for='need_commands'>{{ \App\Models\SleepKillRole::labels()['need_commands'] }}</label>
<input class='form-control @error("need_commands") is-invalid @enderror' id='need_commands' name='need_commands' value='{{ $model->need_commands }}' >
</div>
<div class="form-group">
<label for='test_nights_count'>{{ \App\Models\SleepKillRole::labels()['test_nights_count'] }}</label>
<input type="number" class='form-control @error("test_nights_count") is-invalid @enderror' id='test_nights_count' name='test_nights_count' value='{{ $model->test_nights_count }}' >
</div>
<div class="form-group">
<div class="form-check">
<input class='form-check-input' type='checkbox' value='1' name='is_one' id='is_one' @if($model->is_one == 1) checked @endif>
<label for='is_one'>{{ \App\Models\SleepKillRole::labels()['is_one'] }}</label>
</div>
</div>
<button type="submit" class="btn btn-primary">Сохранить</button>
</form>
@endsection