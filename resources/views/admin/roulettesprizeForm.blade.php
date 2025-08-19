@extends('layouts.content')
@section('card')
<div class="card-header">{{ $title }}</div><div class="card-body">
<form method='POST' enctype='multipart/form-data' >
@csrf
<div class="form-group">
<label for='name'>{{ \App\Models\RoulettesPrize::labels()['name'] }}</label>
<input class='form-control @error("name") is-invalid @enderror' id='name' name='name' value='{{ $model->name }}' >
</div>
<div class="form-group">
<label for='percent'>{{ \App\Models\RoulettesPrize::labels()['percent'] }}</label>
<input type="number" class='form-control @error("percent") is-invalid @enderror' id='percent' name='percent' value='{{ $model->percent }}' >
</div>
<div class="form-group">
<label for='season'>{{ \App\Models\RoulettesPrize::labels()['season'] }}</label>
<input type="number" class='form-control @error("season") is-invalid @enderror' id='season' name='season' value='{{ $model->season ?? '0' }}' >
</div>
<div class="form-group">
    <label for='season'>{{ \App\Models\RoulettesPrize::labels()['prize_type'] }}</label>
    <input type="number" class='form-control @error("prize_type") is-invalid @enderror' id='prize_type' name='prize_type' value='{{ $model->prize_type ?? '0' }}' >
</div>
<div class="form-group">
<label for='add_function'>{{ \App\Models\RoulettesPrize::labels()['add_function'] }}</label>
<input class='form-control @error("add_function") is-invalid @enderror' id='add_function' name='add_function' value='{{ $model->add_function }}' >
</div>
<button type="submit" class="btn btn-primary">Сохранить</button>
</form>
@endsection