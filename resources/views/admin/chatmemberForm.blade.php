@extends('layouts.content')
@section('card')
<div class="card-header">{{ $title }}</div><div class="card-body">
<form method='POST' enctype='multipart/form-data' >
@csrf
<div class="form-group">
<label for='id'>{{ \App\Models\ChatMember::labels()['id'] }}</label>
<input class='form-control @error("id") is-invalid @enderror' id='id' name='id' value='{{ $model->id }}' >
</div>
<div class="form-group">
<label for='member_id'>{{ \App\Models\ChatMember::labels()['member_id'] }}</label>
<select class='form-control form-select' id='member_id' name='member_id' >
{!! \App\Modules\Functions::comboOptions($member_ids,'id','name',$model->member_id) !!}
</select>
</div>
<div class="form-group">
<label for='group_id'>{{ \App\Models\ChatMember::labels()['group_id'] }}</label>
<select class='form-control form-select' id='group_id' name='group_id' >
{!! \App\Modules\Functions::comboOptions($group_ids,'id','name',$model->group_id) !!}
</select>
</div>
<div class="form-group">
<label for='username'>{{ \App\Models\ChatMember::labels()['username'] }}</label>
<input class='form-control @error("username") is-invalid @enderror' id='username' name='username' value='{{ $model->username }}' >
</div>
<div class="form-group">
<label for='first_name'>{{ \App\Models\ChatMember::labels()['first_name'] }}</label>
<input class='form-control @error("first_name") is-invalid @enderror' id='first_name' name='first_name' value='{{ $model->first_name }}' >
</div>
<div class="form-group">
<label for='last_name'>{{ \App\Models\ChatMember::labels()['last_name'] }}</label>
<input class='form-control @error("last_name") is-invalid @enderror' id='last_name' name='last_name' value='{{ $model->last_name }}' >
</div>
<div class="form-group">
<label for='role'>{{ \App\Models\ChatMember::labels()['role'] }}</label>
<input class='form-control @error("role") is-invalid @enderror' id='role' name='role' value='{{ $model->role }}' >
</div>
<div class="form-group">
<div class="form-check">
<input class='form-check-input' type='checkbox' value='1' name='is_bot' id='is_bot' @if($model->is_bot == 1) checked @endif>
<label for='is_bot'>{{ \App\Models\ChatMember::labels()['is_bot'] }}</label>
</div>
</div>
<div class="form-group">
<div class="form-check">
<input class='form-check-input' type='checkbox' value='1' name='is_premium' id='is_premium' @if($model->is_premium == 1) checked @endif>
<label for='is_premium'>{{ \App\Models\ChatMember::labels()['is_premium'] }}</label>
</div>
</div>
<button type="submit" class="btn btn-primary">Сохранить</button>
</form>
@endsection