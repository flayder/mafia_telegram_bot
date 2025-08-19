@extends('layouts.content')
@section('card')
<div class="card-header">{{ $title }}</div><div class="card-body">
<form method='POST' enctype='multipart/form-data' >
@csrf
<div class="form-group">
<label for='name'>{{ \App\Models\Offer::labels()['name'] }}</label>
<input class='form-control @error("name") is-invalid @enderror' id='name' name='name' value='{{ $model->name }}' >
</div>
<div class="form-group">
<label for='price'>{{ \App\Models\Offer::labels()['price'] }}</label>
<input class='form-control @error("price") is-invalid @enderror' id='price' name='price' value='{{ $model->price }}' >
</div>
<div class="form-group">
<label for='product'>{{ \App\Models\Offer::labels()['product'] }}</label>
<input class='form-control @error("product") is-invalid @enderror' id='product' name='product' value='{{ $model->product }}' >
</div>
<div class="form-group">
<label for='product_amount'>{{ \App\Models\Offer::labels()['product_amount'] }}</label>
<input class='form-control @error("product_amount") is-invalid @enderror' id='product_amount' name='product_amount' value='{{ $model->product_amount }}' >
</div>
<div class="form-group">
<label for='where_access'>{{ \App\Models\Offer::labels()['where_access'] }}</label>
<input class='form-control @error("where_access") is-invalid @enderror' id='where_access' name='where_access' value='{{ $model->where_access ?? 'always' }}' >
</div>
<div class="form-group">
<label for='parent_id'>{{ \App\Models\Offer::labels()['parent_id'] }}</label>
<select class='form-control form-select' id='parent_id' name='parent_id' >
    <option value="0" >Не выбрана</option>
{!! \App\Modules\Functions::comboOptions($parent_ids,'id','name',$model->parent_id) !!}
</select>
</div>
<button type="submit" class="btn btn-primary">Сохранить</button>
</form>
@endsection