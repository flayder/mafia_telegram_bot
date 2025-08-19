@extends('layouts.content')
@section('card')
<div class="card-header">{{ $title }}</div><div class="card-body">
<form method='POST' enctype='multipart/form-data' >
@csrf
<div class="form-group">
<label for='id'>{{ \App\Models\Withdrawal::labels()['id'] }}</label>
<input class='form-control @error("id") is-invalid @enderror' id='id' name='id' value='{{ $model->id }}' >
</div>
<div class="form-group">
<label for='user_id'>{{ \App\Models\Withdrawal::labels()['user_id'] }}</label>
<select class='form-control form-select' id='user_id' name='user_id' >
{!! \App\Modules\Functions::comboOptions($user_ids,'id','name',$model->user_id) !!}
</select>
</div>
<div class="form-group">
<label for='groups'>{{ \App\Models\Withdrawal::labels()['groups'] }}</label>
<input class='form-control @error("groups") is-invalid @enderror' id='groups' name='groups' value='{{ $model->groups }}' >
</div>
<div class="form-group">
<label for='amount'>{{ \App\Models\Withdrawal::labels()['amount'] }}</label>
<input class='form-control @error("amount") is-invalid @enderror' id='amount' name='amount' value='{{ $model->amount }}' >
</div>
<div class="form-group">
<label for='way'>{{ \App\Models\Withdrawal::labels()['way'] }}</label>
<input class='form-control @error("way") is-invalid @enderror' id='way' name='way' value='{{ $model->way }}' >
</div>
<div class="form-group">
<label for='status'>{{ \App\Models\Withdrawal::labels()['status'] }}</label>
<input class='form-control @error("status") is-invalid @enderror' id='status' name='status' value='{{ $model->status }}' >
</div>
<button type="submit" class="btn btn-primary">Сохранить</button>
</form>
@endsection