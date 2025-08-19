@extends('layouts.content')
@section('card')
<div class="card-header">{{ $title }}</div><div class="card-body">
<form method='POST' enctype='multipart/form-data' >
@csrf
<div class="form-group">
<label for='action'>{{ \App\Models\RoleAction::labels()['action'] }}</label>
<input class='form-control @error("action") is-invalid @enderror' id='action' name='action' value='{{ $model->action }}' >
</div>
<div class="form-group">
<label for='role_id'>{{ \App\Models\RoleAction::labels()['role_id'] }}</label>
<select class='form-control form-select' id='role_id' name='role_id' >
{!! \App\Modules\Functions::comboOptions($role_ids,'id','name',$model->role_id) !!}
</select>
</div>
<button type="submit" class="btn btn-primary">Сохранить</button>
</form>
@endsection