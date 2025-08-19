@extends('layouts.content')
@section('card')
<div class="card-header">{{ $title }}</div><div class="card-body">
<form method='POST' enctype='multipart/form-data' >
@csrf
<div class="form-group">
<label for='id'>{{ \App\Models\YesnoVote::labels()['id'] }}</label>
<input class='form-control @error("id") is-invalid @enderror' id='id' name='id' value='{{ $model->id }}' >
</div>
<div class="form-group">
<label for='voiting_id'>{{ \App\Models\YesnoVote::labels()['voiting_id'] }}</label>
<select class='form-control form-select' id='voiting_id' name='voiting_id' >
{!! \App\Modules\Functions::comboOptions($voiting_ids,'id','name',$model->voiting_id) !!}
</select>
</div>
<div class="form-group">
<label for='gamer_id'>{{ \App\Models\YesnoVote::labels()['gamer_id'] }}</label>
<select class='form-control form-select' id='gamer_id' name='gamer_id' >
{!! \App\Modules\Functions::comboOptions($gamer_ids,'id','name',$model->gamer_id) !!}
</select>
</div>
<div class="form-group">
<label for='vote_user_id'>{{ \App\Models\YesnoVote::labels()['vote_user_id'] }}</label>
<select class='form-control form-select' id='vote_user_id' name='vote_user_id' >
{!! \App\Modules\Functions::comboOptions($vote_user_ids,'id','name',$model->vote_user_id) !!}
</select>
</div>
<div class="form-group">
<label for='answer'>{{ \App\Models\YesnoVote::labels()['answer'] }}</label>
<input class='form-control @error("answer") is-invalid @enderror' id='answer' name='answer' value='{{ $model->answer }}' >
</div>
<button type="submit" class="btn btn-primary">Сохранить</button>
</form>
@endsection