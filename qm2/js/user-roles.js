var delImg = 'img/deactive.gif';

$(document).ready(function() {
	createElements();
	$("#roles-search").autocomplete( {
		source : "admin/async/rolesearch",
		minLength : 2,
		select : function(event, ui) {
			addRole(ui.item.value, ui.item.id);
			$("#roles-search").val(""); //eingabefeld leeren
			return false; //füllen des eingabefeldes mit gewählten wert verhindern
		}
	});
});

// zusätzliche elemente erzeugen
function createElements() {
	var div = $('<tr><th>Zugeordnete Rollen</th><td><div style="width:100%;" id="roles"><ul id="role-list"></ul></td></tr>');
	var tr = $('<tr><th>Rollensuche</th><td><input type="text" name="roles-search" id="roles-search"/></td></tr>');
	var submit = $(".tfoot:parent");
	submit.remove();
	$('#user-form tbody').append(tr).append(div).append(submit);

	if ($('#roles').val() != ' ') {
		createRoles();
	}
}

// rolle hinzufügen
function addRole(role_name, role_id) {

	var roles = $('#roles').val();
	var search = ' ' + role_id + ' ';
	if (roles.search(search) == -1) {
		addLi(role_name, role_id);
		$('#roles').val(roles + role_id + " ");
	}
	// $('#roles-search').val('');
}

//neuen listenpunkt hinzufügen
function addLi(role_name, role_id) {
	var img = '<img id="rm_role_'
		+ role_id
		+ '" onclick="delRole('
		+ role_id
		+ ');" src="img/del.gif" alt="Rolle entfernen" title="Rolle entfernen" />';
	var li = $('<li id="role_id_' + role_id + '">' + role_name + ' ' + img
		+ '</li>');
	$('#role-list').append(li);
}

// rolle entfernen
function delRole(role_id) {
	var roles = $('#roles').val();
	var search = ' ' + role_id + ' ';
	var rolesNew = roles.replace(search, ' ');
	$('#roles').val(rolesNew);
	$('#role_id_' + role_id).remove();
}

function createRoles(role_ids) {
	if(role_ids == null) {
		role_ids = $('#roles').val();
	}
	$.post("admin/async/getUserRoles", { role_ids: role_ids },
            function(userRoles){
            	for(var i=0;i<userRoles.length;i++) {
            		addLi(userRoles[i].label, userRoles[i].id);
            	}
            }, "json");
}