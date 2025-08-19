@extends('layouts.content')
@section('card')
<div class="card-header">{{ $title }}</div><div class="card-body">
<form method='POST' enctype='multipart/form-data' >
@csrf
<div class="form-group">
<label for='nick_name'>{{ \App\Models\BotUser::labels()['nick_name'] }}</label>
<input class='form-control @error("nick_name") is-invalid @enderror' id='nick_name' name='nick_name' value='{{ $model->nick_name }}' >
</div>
<div class="form-group">
<label for='first_name'>{{ \App\Models\BotUser::labels()['first_name'] }}</label>
<input class='form-control @error("first_name") is-invalid @enderror' id='first_name' name='first_name' value='{{ $model->first_name }}' >
</div>
<div class="form-group">
<label for='last_name'>{{ \App\Models\BotUser::labels()['last_name'] }}</label>
<input class='form-control @error("last_name") is-invalid @enderror' id='last_name' name='last_name' value='{{ $model->last_name }}' >
</div>
<div class="form-group">
<label for='balances'>{{ \App\Models\BotUser::labels()['balances'] }}</label>
<input class='form-control @error("balances") is-invalid @enderror' id='balances' name='balances' value='{{ $model->balances }}' >
</div>
<button type="submit" class="btn btn-primary">Сохранить</button>
</form>
@endsection