@extends('layouts.content')
@section('card')
<div class="card-header">{{ $title }}</div><div class="card-body">
<form method='POST' enctype='multipart/form-data' >
@csrf
<div class="form-group">
<label for='role_id'>{{ \App\Models\GameRolesOrder::labels()['role_id'] }}</label>
<select class='form-control form-select' id='role_id' name='role_id' >
{!! \App\Modules\Functions::comboOptions($role_ids,'id','name',$model->role_id) !!}
</select>
</div>
<div class="form-group">
<label for='position'>{{ \App\Models\GameRolesOrder::labels()['position'] }}</label>
<input type='number' class='form-control @error("position") is-invalid @enderror' id='position' name='position' value='{{ $model->position ?? '0' }}' >
</div>
<div class="form-group">
<label for='gamers_min'>{{ \App\Models\GameRolesOrder::labels()['gamers_min'] }}</label>
<input type='number' class='form-control @error("gamers_min") is-invalid @enderror' id='gamers_min' name='gamers_min' value='{{ $model->gamers_min ?? '0' }}' >
</div>
<div class="form-group">
<label for='gamers_max'>{{ \App\Models\GameRolesOrder::labels()['gamers_max'] }}</label>
<input type='number' class='form-control @error("gamers_max") is-invalid @enderror' id='gamers_max' name='gamers_max' value='{{ $model->gamers_max ?? '5000' }}' >
</div>
<button type="submit" class="btn btn-primary">Сохранить</button>
</form>
@endsection