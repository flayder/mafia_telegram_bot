@extends('layouts.content')
@section('card')
<div class="card-header">{{ $title }}</div><div class="card-body">
<form method='POST' enctype='multipart/form-data' >
@csrf
<div class="form-group">
<label for='id'>{{ \App\Models\BotGroup::labels()['id'] }}</label>
<input class='form-control @error("id") is-invalid @enderror' id='id' name='id' value='{{ $model->id }}' >
</div>
<div class="form-group">
<label for='title'>{{ \App\Models\BotGroup::labels()['title'] }}</label>
<input class='form-control @error("title") is-invalid @enderror' id='title' name='title' value='{{ $model->title }}' >
</div>
<div class="form-group">
<label for='group_type'>{{ \App\Models\BotGroup::labels()['group_type'] }}</label>
<input class='form-control @error("group_type") is-invalid @enderror' id='group_type' name='group_type' value='{{ $model->group_type }}' >
</div>
<div class="form-group">
<label for='group_link'>{{ \App\Models\BotGroup::labels()['group_link'] }}</label>
<input class='form-control @error("group_link") is-invalid @enderror' id='group_link' name='group_link' value='{{ $model->group_link }}' >
</div>
<div class="form-group">
    <label for='reward'>{{ \App\Models\BotGroup::labels()['reward'] }}</label>
    <input type="number" step="0.01" class='form-control @error("reward") is-invalid @enderror' id='reward' name='reward' value='{{ $model->reward }}' >
</div>
<button type="submit" class="btn btn-primary">Сохранить</button>
</form>
@endsection