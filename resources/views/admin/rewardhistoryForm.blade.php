@extends('layouts.content')
@section('card')
<div class="card-header">{{ $title }}</div><div class="card-body">
<form method='POST' enctype='multipart/form-data' >
@csrf
<div class="form-group">
<label for='id'>{{ \App\Models\RewardHistory::labels()['id'] }}</label>
<input class='form-control @error("id") is-invalid @enderror' id='id' name='id' value='{{ $model->id }}' >
</div>
<div class="form-group">
<label for='group_id'>{{ \App\Models\RewardHistory::labels()['group_id'] }}</label>
<select class='form-control form-select' id='group_id' name='group_id' >
{!! \App\Modules\Functions::comboOptions($group_ids,'id','name',$model->group_id) !!}
</select>
</div>
<div class="form-group">
<label for='game_id'>{{ \App\Models\RewardHistory::labels()['game_id'] }}</label>
<select class='form-control form-select' id='game_id' name='game_id' >
{!! \App\Modules\Functions::comboOptions($game_ids,'id','name',$model->game_id) !!}
</select>
</div>
<div class="form-group">
<label for='buy_sum'>{{ \App\Models\RewardHistory::labels()['buy_sum'] }}</label>
<input class='form-control @error("buy_sum") is-invalid @enderror' id='buy_sum' name='buy_sum' value='{{ $model->buy_sum }}' >
</div>
<div class="form-group">
<label for='reward_percent'>{{ \App\Models\RewardHistory::labels()['reward_percent'] }}</label>
<input class='form-control @error("reward_percent") is-invalid @enderror' id='reward_percent' name='reward_percent' value='{{ $model->reward_percent }}' >
</div>
<div class="form-group">
<label for='reward_sum'>{{ \App\Models\RewardHistory::labels()['reward_sum'] }}</label>
<input class='form-control @error("reward_sum") is-invalid @enderror' id='reward_sum' name='reward_sum' value='{{ $model->reward_sum }}' >
</div>
<button type="submit" class="btn btn-primary">Сохранить</button>
</form>
@endsection