/**
 *	AdminTool: PageCloner - backend.js
 *
 *	@version:	1.3.0
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
		document.getElementById(id).style.display = "table-row";
	} else {
		document.getElementById(id).style.display = "none";
	}
}

var plus = new Image;
plus.src = IMAGE_URL+"/plus_16.png";

var minus = new Image;
minus.src = IMAGE_URL+"/minus_16.png";

function toggle_plus_minus(id) {
	var img_ref= document.getElementById('plus_minus_' + id);
    img_ref.src = (img_ref.src == plus.src) ? minus.src : plus.src;
}

function call_detailpage(aID)
{
    var ref = document.getElementById("pagecloner_caller");
    if(ref) {
        var temp_element = document.createElement("input");
        if(temp_element) {
            temp_element.setAttribute("type", "hidden" );
            temp_element.setAttribute("name", "pagetoclone" );
            temp_element.setAttribute("value", aID );
    
            ref.appendChild( temp_element );
            ref.submit();
        }
    }  
}
