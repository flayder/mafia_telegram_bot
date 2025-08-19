@extends('layouts.content')
@section('card')
<div class="card-header">{{ $title }}</div><div class="card-body">
<form method='POST' enctype='multipart/form-data' >
@csrf
<div class="form-group">
<label for='base_cur'>{{ \App\Models\CurrencyRate::labels()['base_cur'] }}</label>
<input class='form-control @error("base_cur") is-invalid @enderror' id='base_cur' name='base_cur' value='{{ $model->base_cur }}' >
</div>
<div class="form-group">
<label for='calc_cur'>{{ \App\Models\CurrencyRate::labels()['calc_cur'] }}</label>
<input class='form-control @error("calc_cur") is-invalid @enderror' id='calc_cur' name='calc_cur' value='{{ $model->calc_cur }}' >
</div>
<div class="form-group">
<label for='rate'>{{ \App\Models\CurrencyRate::labels()['rate'] }}</label>
<input class='form-control @error("rate") is-invalid @enderror' id='rate' name='rate' value='{{ $model->rate }}' >
</div>
<button type="submit" class="btn btn-primary">Сохранить</button>
</form>
@endsection