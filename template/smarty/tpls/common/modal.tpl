<script>
function showModal(title,body) {
	$("#{$with_id} .modal-header button").remove();
	var params={
		"allowClose":true,
		"buttons": [
			{
				"class":"btn btn-danger",
				"action":"close",
				"label":"Cerrar"
			}
		]
	};
	if(arguments[2]!=null) {
		for(var i in arguments[2]) {
			params[i]=arguments[2][i];
		}
	}
	$("#{$with_id} .modal-title").html(title);
	$("#{$with_id} .modal-body").html(body);
	
	var html_buttons=new Array();
	for(var b in params.buttons) {
		var button=params.buttons[b];
		var html_button='<button class="'+button.class+'"';
		if(typeof button.action == "string") {
			if(button.action=="close") {
				html_button=html_button+' data-dismiss="modal"';
			}
		}else if(typeof button.action == "function") {
			html_button=html_button+' onclick="'+button.action.name+'(this)"';
		}
		html_button=html_button+">"+button.label+"</button>";
		html_buttons.push(html_button);
	}
	$("#{$with_id} .modal-footer").html(html_buttons.join(""));
	
	$("#{$with_id}").modal()
}
</script>
<!-- The Modal -->
<div class="modal fade" id="{$with_id}" data-backdrop="static" data-keyboard="false">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">

      <!-- Modal Header -->
      <div class="modal-header">
        <h4 class="modal-title">Modal Heading</h4>
        <button type="button" class="close" data-dismiss="modal">&times;</button>
      </div>

      <!-- Modal body -->
      <div class="modal-body">
        Modal body..
      </div>

      <!-- Modal footer -->
      <div class="modal-footer">
        <button type="button" class="btn btn-danger" data-dismiss="modal">Close</button>
      </div>

    </div>
  </div>
</div>
<!-- end modal -->