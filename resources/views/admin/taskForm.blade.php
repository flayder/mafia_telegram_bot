@extends('layouts.content')
@section('card')
<div class="card-header">{{ $title }}</div><div class="card-body">
<form method='POST' enctype='multipart/form-data' >
@csrf
<div class="form-group">
<label for='id'>{{ \App\Models\Task::labels()['id'] }}</label>
<input class='form-control @error("id") is-invalid @enderror' id='id' name='id' value='{{ $model->id }}' >
</div>
<div class="form-group">
<label for='name'>{{ \App\Models\Task::labels()['name'] }}</label>
<input class='form-control @error("name") is-invalid @enderror' id='name' name='name' value='{{ $model->name }}' >
</div>
<div class="form-group">
<div class="form-check">
<input class='form-check-input' type='checkbox' value='1' name='is_active' id='is_active' @if($model->is_active == 1) checked @endif>
<label for='is_active'>{{ \App\Models\Task::labels()['is_active'] }}</label>
</div>
</div>
<div class="form-group">
<label for='options'>{{ \App\Models\Task::labels()['options'] }}</label>
<input class='form-control @error("options") is-invalid @enderror' id='options' name='options' value='{{ $model->options }}' >
</div>
<button type="submit" class="btn btn-primary">Сохранить</button>
</form>
@endsection