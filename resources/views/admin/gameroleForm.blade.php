@extends('layouts.content')
@section('card')
<div class="card-header">{{ $title }}</div><div class="card-body">
<form method='POST' enctype='multipart/form-data' >
@csrf
<div class="form-group">
<label for='name'>{{ \App\Models\GameRole::labels()['name'] }}</label>
<input class='form-control @error("name") is-invalid @enderror' id='name' name='name' value='{{ $model->name }}' >
</div>
<div class="form-group">
<label for='max_amount_in_game'>{{ \App\Models\GameRole::labels()['max_amount_in_game'] }}</label>
<input type="number" class='form-control @error("max_amount_in_game") is-invalid @enderror' id='max_amount_in_game' name='max_amount_in_game' value='{{ $model->max_amount_in_game ?? '1' }}' >
</div>
<div class="form-group">
    <label for='night_action'>Ночное действие</label>
    <input  class='form-control @error("night_action") is-invalid @enderror' id='night_action' name='night_action' value='{{ $model->night_action }}' >
</div>
<div class="form-group">
<label for='description'>{{ \App\Models\GameRole::labels()['description'] }}</label>
<textarea class='form-control @error("description") is-invalid @enderror' id='description' name='description' >{{ $model->description }}</textarea>    
</div>
<div class="form-group">
<label for='comment'>{{ \App\Models\GameRole::labels()['comment'] }}</label>
<textarea class='form-control @error("comment") is-invalid @enderror' id='comment' name='comment' >{{ $model->comment }}</textarea>    
</div>
<div class="form-group">
<label for='first_message'>{{ \App\Models\GameRole::labels()['first_message'] }}</label>
<textarea class='form-control @error("first_message") is-invalid @enderror' id='first_message' name='first_message'  >{{ $model->first_message }}</textarea>    
</div>
<div class="form-group">
<label for='kill_message'>{{ \App\Models\GameRole::labels()['kill_message'] }}</label>
<textarea class='form-control @error("kill_message") is-invalid @enderror' id='kill_message' name='kill_message'  >{{ $model->kill_message }}</textarea>    
</div>
<div class="form-group">
<div class="form-check">
<input class='form-check-input' type='checkbox' value='1' name='is_select_partner' id='is_select_partner' @if($model->is_select_partner == 1) checked @endif>
<label for='is_select_partner'>{{ \App\Models\GameRole::labels()['is_select_partner'] }}</label>
</div>
</div>
<div class="form-group">
<label for='night_message_priv'>{{ \App\Models\GameRole::labels()['night_message_priv'] }}</label>
<textarea class='form-control @error("night_message_priv") is-invalid @enderror' id='night_message_priv' name='night_message_priv'  >{{ $model->night_message_priv }}</textarea>    
</div>
<div class="form-group">
<label for='night_message_publ'>{{ \App\Models\GameRole::labels()['night_message_publ'] }}</label>
<textarea class='form-control @error("night_message_publ") is-invalid @enderror' id='night_message_publ' name='night_message_publ'  >{{ $model->night_message_publ }}</textarea>    
</div>
<div class="form-group">
<label for='message_to_partner'>{{ \App\Models\GameRole::labels()['message_to_partner'] }}</label>
<textarea class='form-control @error("message_to_partner") is-invalid @enderror' id='message_to_partner' name='message_to_partner'  >{{ $model->message_to_partner }}</textarea>    
</div>
<div class="form-group">
<label for='role_type_id'>{{ \App\Models\GameRole::labels()['role_type_id'] }}</label>
<select class='form-control form-select' id='role_type_id' name='role_type_id' >
{!! \App\Modules\Functions::comboOptions($role_type_ids,'id','name',$model->role_type_id) !!}
</select>
</div>
<button type="submit" class="btn btn-primary">Сохранить</button>
</form>
@endsection