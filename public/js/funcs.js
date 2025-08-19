function openRemoveDialog(url, m_id,modelname) {
    modelname = modelname || 'элемент';
    $("#modal_content").html("Вы действительно хотите удалить "+modelname+" #"+m_id+" ?");
    $('#removeForm').attr('action',url);
    $('#removeId').val(m_id);
    $('#removeDialog').modal('toggle');
}