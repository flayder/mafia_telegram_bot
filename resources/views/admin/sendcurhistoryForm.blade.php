@extends('layouts.content')
@section('card')
<div class="card-header">{{ $title }}</div><div class="card-body">
<form method='POST' enctype='multipart/form-data' >
@csrf
<div class="form-group">
<label for='id'>{{ \App\Models\SendCurHistory::labels()['id'] }}</label>
<input class='form-control @error("id") is-invalid @enderror' id='id' name='id' value='{{ $model->id }}' >
</div>
<div class="form-group">
<label for='currency'>{{ \App\Models\SendCurHistory::labels()['currency'] }}</label>
<input class='form-control @error("currency") is-invalid @enderror' id='currency' name='currency' value='{{ $model->currency }}' >
</div>
<div class="form-group">
<label for='amount'>{{ \App\Models\SendCurHistory::labels()['amount'] }}</label>
<input class='form-control @error("amount") is-invalid @enderror' id='amount' name='amount' value='{{ $model->amount }}' >
</div>
<div class="form-group">
<label for='sender'>{{ \App\Models\SendCurHistory::labels()['sender'] }}</label>
<input class='form-control @error("sender") is-invalid @enderror' id='sender' name='sender' value='{{ $model->sender }}' >
</div>
<div class="form-group">
<label for='recipient'>{{ \App\Models\SendCurHistory::labels()['recipient'] }}</label>
<input class='form-control @error("recipient") is-invalid @enderror' id='recipient' name='recipient' value='{{ $model->recipient }}' >
</div>
<div class="form-group">
<label for='group_id'>{{ \App\Models\SendCurHistory::labels()['group_id'] }}</label>
<select class='form-control form-select' id='group_id' name='group_id' >
{!! \App\Modules\Functions::comboOptions($group_ids,'id','name',$model->group_id) !!}
</select>
</div>
<button type="submit" class="btn btn-primary">Сохранить</button>
</form>
@endsection