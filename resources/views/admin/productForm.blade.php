@extends('layouts.content')
@section('card')
<div class="card-header">{{ $title }}</div><div class="card-body">
<form method='POST' enctype='multipart/form-data' >
@csrf
<div class="form-group">
<label for='name'>{{ \App\Models\Product::labels()['name'] }}</label>
<input class='form-control @error("name") is-invalid @enderror' id='name' name='name' value='{{ $model->name }}' >
</div>
<div class="form-group">
<label for='description'>{{ \App\Models\Product::labels()['description'] }}</label>
<input class='form-control @error("description") is-invalid @enderror' id='description' name='description' value='{{ $model->description }}' >
</div>
<div class="form-group">
<label for='price'>{{ \App\Models\Product::labels()['price'] }}</label>
<input class='form-control @error("price") is-invalid @enderror' id='price' name='price' value='{{ $model->price }}' >
</div>
<div class="form-group">
    <label for='class'>{{ \App\Models\Product::labels()['class'] }}</label>
    <input class='form-control @error("class") is-invalid @enderror' id='class' name='class' value='{{ $model->class }}' >
</div>

<div class="form-group">
    <label for='cur_code'>{{ \App\Models\Product::labels()['cur_code'] }}</label>
    <select class='form-control form-select' id='cur_code' name='cur_code' >
    @foreach ($currencies as $cur_code=>$v)
        <option value="{{ $cur_code }}" @if($cur_code==$model->cur_code) selected @endif >{{ $cur_code }}</option>
    @endforeach
    </select>
</div>
<button type="submit" class="btn btn-primary">Сохранить</button>
</form>
@endsection