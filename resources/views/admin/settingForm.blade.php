@extends('layouts.content')
@section('card')
<div class="card-header">{{ $title }}</div><div class="card-body">
<form method='POST' enctype='multipart/form-data' >
@csrf
<div class="form-group">
<label for='set_key'>{{ \App\Models\Setting::labels()['set_key'] }}</label>
<input class='form-control @error("set_key") is-invalid @enderror' id='set_key' name='set_key' value='{{ $model->set_key }}' >
</div>
<div class="form-group">
    <label for='title'>{{ \App\Models\Setting::labels()['title'] }}</label>
    <input class='form-control @error("title") is-invalid @enderror' id='title' name='title' value='{{ $model->title }}' >
</div>
<div class="form-group">
    <label for='description'>{{ \App\Models\Setting::labels()['description'] }}</label>
    <input class='form-control @error("description") is-invalid @enderror' id='description' name='description' value='{{ $model->description }}' >
</div>
<div class="form-group">
<label for='set_value'>{{ \App\Models\Setting::labels()['set_value'] }}</label>
<input class='form-control @error("set_value") is-invalid @enderror' id='set_value' name='set_value' value='{{ $model->set_value }}' >
</div>
<div class="form-group">
    <label for='variants'>{{ \App\Models\Setting::labels()['variants'] }}</label>
    <input class='form-control @error("variants") is-invalid @enderror' id='variants' name='variants' value='{{ $model->variants }}' >
</div>
<div class="form-group">
    <label for='variants'>{{ \App\Models\Setting::labels()['tarif_id'] }}</label>
    <select class='form-control form-select' id='tarif_id' name='tarif_id' >
        <option value="0">Не задан</option>
        {!! \App\Modules\Functions::comboOptions($tarif_ids,'id','name',$model->tarif_id) !!}
    </select>
</div>
<button type="submit" class="btn btn-primary">Сохранить</button>
</form>
@endsection