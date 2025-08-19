@extends('layouts.content')
@section('card')
<div class="card-header">{{ $title }}</div><div class="card-body">
<form method='POST' enctype='multipart/form-data' >
@csrf
<div class="form-group">
<label for='word'>{{ \App\Models\WarningWord::labels()['word'] }}</label>
<input class='form-control @error("word") is-invalid @enderror' id='word' name='word' value='{{ $model->word }}' >
</div>
<div class="form-group">
<label for='group_id'>{{ \App\Models\WarningWord::labels()['group_id'] }}</label>
<select class='form-control form-select' id='group_id' name='group_id' >
{!! \App\Modules\Functions::comboOptions($group_ids,'id','name',$model->group_id) !!}
</select>
</div>
<button type="submit" class="btn btn-primary">Сохранить</button>
</form>
@endsection