function buscar_cuenta(btn) {
	/*
	$(btn).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>&nbsp;&nbsp;&nbsp;&nbsp;');
	$(btn).prop("disabled",true);
	$(btn).append("Buscando...");
	*/
	$("#error").hide();
	$("#error #error_text").html();
	$("#ajax_container").html("Consultando...");

	$.ajax({
		"url":"ajax.php",
		"method":"POST",
		"data":{
			"a":"dispatcher_buscar",
			"q":$("#terms").val(),
			"by":$("[name='buscar_por']:checked").val(),
			"user_name":usuario
		},
		"success":function(d) {
			try {
				d=$.parseJSON(d);
				if(!d.success) {
					throw(d.error);
				}
				$("#ajax_container").html(d.data);
			}catch(err) {
				$("#error #error_text").html(err);
				$("#error").fadeIn();
			}
		}
	});
}