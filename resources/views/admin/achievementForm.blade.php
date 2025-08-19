@extends('layouts.content')
@section('card')
<div class="card-header">{{ $title }}</div><div class="card-body">
<form method='POST' enctype='multipart/form-data' >
@csrf
<div class="form-group">
<label for='name'>{{ \App\Models\Achievement::labels()['name'] }}</label>
<input class='form-control @error("name") is-invalid @enderror' id='name' name='name' value='{{ $model->name }}' >
</div>
<div class="form-group">
<label for='role_id'>{{ \App\Models\Achievement::labels()['role_id'] }}</label>
<select class='form-control form-select' id='role_id' name='role_id' >
{!! \App\Modules\Functions::comboOptions($role_ids,'id','name',$model->role_id) !!}
</select>
</div>
<div class="form-group">
<label for='win_amount'>{{ \App\Models\Achievement::labels()['win_amount'] }}</label>
<input type="number" class='form-control @error("win_amount") is-invalid @enderror' id='win_amount' name='win_amount' value='{{ $model->win_amount ?? 10 }}' >
</div>
<button type="submit" class="btn btn-primary">Сохранить</button>
</form>
@endsection