@extends('layouts.admin')

@section('content')
<div class="modal" tabindex="-1" role="dialog" id="removeDialog" >
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modal_title" >Подтверждение удаления</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <p id="modal_content" >Вы действительно хотите удалить выбранный элемент? </p>
      </div>
      <div class="modal-footer">
      <form id=removeForm method="post">
        @csrf  
        <button type="submit" class="btn btn-danger">Удалить</button>  
        <input name="id" id="removeId"  type="hidden" >
      </form>
        <button type="button" onclick="$('#removeDialog').modal('toggle');" class="btn btn-secondary" data-dismiss="modal">Отмена</button>  
      </div>
    </div>
  </div>
</div>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">                
                 @yield('card')                                
            </div>
        </div>
    </div>
</div>
@endsection
