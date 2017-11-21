/**
 *	AdminTool: PageCloner - backend.js
 *
 *	@version:	0.1.0
 *	@author:	cms-lab
 */

function toggle_viewers() {
	if(document.add.visibility.value == 'private') {
		document.getElementById('private_viewers').style.display = 'block';
		document.getElementById('registered_viewers').style.display = 'none';
	} else if(document.add.visibility.value == 'registered') {
		document.getElementById('private_viewers').style.display = 'none';
		document.getElementById('registered_viewers').style.display = 'block';
	} else {
		document.getElementById('private_viewers').style.display = 'none';
		document.getElementById('registered_viewers').style.display = 'none';
	}
}

function toggle_visibility(id){
	if(document.getElementById(id).style.display == "none") {
		document.getElementById(id).style.display = "block";
	} else {
		document.getElementById(id).style.display = "none";
	}
}

var plus = new Image;
plus.src = LEPTON_URL+"/modules/lib_lepton/backend_images/plus_16.png";

var minus = new Image;
minus.src = LEPTON_URL+"/modules/lib_lepton/backend_images/minus_16.png";

function toggle_plus_minus(id) {
	var img_src = document.images['plus_minus_' + id].src;
	if(img_src == plus.src) {
		document.images['plus_minus_' + id].src = minus.src;
	} else {
		document.images['plus_minus_' + id].src = plus.src;
	}
}
