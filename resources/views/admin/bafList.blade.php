@extends('layouts.content')
@section('card')
<div class='card-header'>Баффы</div><div class='card-body'>
<div class='container-fluid'  >
   <form class=row method='get' >
       <div class="col-4"><a class="btn btn-success" href="{{ route('baf.create') }}" >Добавить</a></div>
       <div class="col-2"></div>
        <div class="col-3"><input name="qu" type="search" id="isearch" class="form-control" value="{{ $search ?? null }}" /></div>
        <div class="col-3"><button type="submit" class="btn btn-primary" btnsearch="1" >
           <i class="fas fa-search"></i></button>
       </div>
   </form>
</div>
<table class="table"></div>
<tr> {!! $modelclass::headers() !!} <td></td></tr>
@foreach($models as $model)
<tr>
 @foreach($modelclass::viewFields() as $field)
 <td>{{ $model->$field }}</td>
 @endforeach
<td> <a href="{{ route('baf.update',['id'=>$model->id]) }}"><i class="fas fa-pen"></i></a>
        
        <a href="javascript:void()" onclick="openRemoveDialog('{{ route('baf.delete') }}',{{ $model->id }},'{{ $modelclass::$entityLabel }}')" ><i class="far fa-trash-alt"></i></a>        
        </td>
</tr>
@endforeach
</table>
{{ $models->links() }}
</div>
@endsection