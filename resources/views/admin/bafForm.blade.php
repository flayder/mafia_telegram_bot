@extends('layouts.content')
@section('card')
<div class="card-header">{{ $title }}</div><div class="card-body">
<form method='POST' enctype='multipart/form-data' >
@csrf
<div class="form-group">
<label for='name'>{{ \App\Models\Baf::labels()['name'] }}</label>
<input class='form-control @error("name") is-invalid @enderror' id='name' name='name' value='{{ $model->name }}' >
</div>
<div class="form-group">
    <label for='baf_class'>{{ \App\Models\Baf::labels()['baf_class'] }}</label>
    <input class='form-control @error("baf_class") is-invalid @enderror' id='baf_class' name='baf_class' value='{{ $model->baf_class }}' >
</div>
<div class="form-group">
    <label for='assign_role_ids'>{{ \App\Models\Baf::labels()['assign_role_ids'] }}</label>
    <input class='form-control @error("assign_role_ids") is-invalid @enderror' id='assign_role_ids' name='assign_role_ids' value='{{ $model->assign_role_ids }}' >
</div>
<div class="form-group">
<label for='description'>{{ \App\Models\Baf::labels()['description'] }}</label>
<input class='form-control @error("description") is-invalid @enderror' id='description' name='description' value='{{ $model->description }}' >
</div>
<div class="form-group">
<label for='price'>{{ \App\Models\Baf::labels()['price'] }}</label>
<input class='form-control @error("price") is-invalid @enderror' id='price' name='price' value='{{ $model->price }}' >
</div>

<div class="form-group">
    <label for='cur_code'>{{ \App\Models\Baf::labels()['cur_code'] }}</label>
    <select class='form-control form-select' id='cur_code' name='cur_code' >
    @foreach ($currencies as $cur_code=>$v)
        <option value="{{ $cur_code }}" @if($cur_code==$model->cur_code) selected @endif >{{ $cur_code }}</option>
    @endforeach
    </select>
</div>

<button type="submit" class="btn btn-primary">Сохранить</button>
</form>
@endsection