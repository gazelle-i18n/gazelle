listener.set(window,'load',function(){
	$('#extra1').raw().innerHTML = '<a href="#" onclick="mToggleGrid();return false;"></a>';
	$('#extra2').raw().innerHTML = '<a href="#" onclick="mToggleSearch();return false;"></a>';
});

function mToggleGrid() {
	$('#searchbars').raw().style.display = 'none';
	if ($('#userinfo').raw().style.display == 'block') {
		$('#userinfo').raw().style.display = 'none';
		$('#menu').raw().style.display = 'none';
		$('#content').raw().style.display = 'block';
		$('#alerts').raw().style.display = 'block';
	} else { 
		$('#userinfo').raw().style.display = 'block';
		$('#menu').raw().style.display = 'block';
		$('#content').raw().style.display = 'none';
		$('#alerts').raw().style.display = 'none';
	}
}

function mToggleSearch() {
	$('#userinfo').raw().style.display = 'none';
	$('#menu').raw().style.display = 'none';
	
	if ($('#searchbars').raw().style.display == 'block') {
		$('#searchbars').raw().style.display = 'none';
		$('#content').raw().style.display = 'block';
		$('#alerts').raw().style.display = 'block';
	} else { 
		$('#searchbars').raw().style.display = 'block';
		$('#content').raw().style.display = 'none';
		$('#alerts').raw().style.display = 'none';
	}
}
