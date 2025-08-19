@extends('layouts.content')
@section('card')
<div class="card-header">{{ $title }}</div><div class="card-body">
<form method='POST' enctype='multipart/form-data' >
@csrf
<div class="form-group">
<label for='message'>{{ \App\Models\Newsletter::labels()['message'] }}</label>
<input class='form-control @error("message") is-invalid @enderror' id='message' name='message' value='{{ $model->message }}' >
</div>
<div class="form-group">
<label for='status'>{{ \App\Models\Newsletter::labels()['status'] }}</label>
<input class='form-control @error("status") is-invalid @enderror' id='status' name='status' value='{{ $model->status }}' >
</div>
<div class="form-group">
<label for='type_id'>{{ \App\Models\Newsletter::labels()['type_id'] }}</label>
<select class='form-control form-select' id='type_id' name='type_id' >
{!! \App\Modules\Functions::comboOptions($type_ids,'id','name',$model->type_id) !!}
</select>
</div>
<button type="submit" class="btn btn-primary">Сохранить</button>
</form>
@endsection