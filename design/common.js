function message(eid, cl, m) {
	var e = document.getElementById(eid);
	e.className = cl;
	e.innerHTML = m;
	if(m == "") e.style.display = "none"; else e.style.display = "block";
}

function hideIn5(eid) {
	setTimeout(function() {
		var e = document.getElementById(eid);
		e.style.display = "none";
	}, 5000);
}

function AJAX_POST(url, data, success, error) {
	$.ajax({
		type: "POST",
		url: url,
		dataType: "html",
		data: data,
		success: success,
		error: error
	});
}

function get_json(a) {
	if(String.toLowerCase(typeof(a)) == "string") {
		var ok = true;
		var response = null;
		//console.log(a);
		try { response = $.parseJSON(a); }
		catch(e) { ok = false; /*console.log(e);*/ }
		if(ok) return response;
		else return false;
	}

	return a;
}