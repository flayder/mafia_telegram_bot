@extends('layouts.content')
@section('card')
<div class="card-header">{{ $title }}</div><div class="card-body">
<form method='POST' enctype='multipart/form-data' >
@csrf
<div class="form-group">
<label for='role_id'>{{ \App\Models\BuyRole::labels()['role_id'] }}</label>
<select class='form-control form-select' id='role_id' name='role_id' >
{!! \App\Modules\Functions::comboOptions($role_ids,'id','name',$model->role_id) !!}
</select>
</div>
<div class="form-group">
<label for='price'>{{ \App\Models\BuyRole::labels()['price'] }}</label>
<input class='form-control @error("price") is-invalid @enderror' id='price' name='price' value='{{ $model->price }}' >
</div>
<div class="form-group">
    <label for='cur_code'>{{ \App\Models\BuyRole::labels()['cur_code'] }}</label>
    <select class='form-control form-select' id='cur_code' name='cur_code' >
    @foreach ($currencies as $cur_code=>$v)
        <option value="{{ $cur_code }}" @if($cur_code==$model->cur_code) selected @endif >{{ $cur_code }}</option>
    @endforeach
    </select>
</div>
<button type="submit" class="btn btn-primary">Сохранить</button>
</form>
@endsection