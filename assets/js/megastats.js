function fullHgt() {
	if (document.getElementById('scroll')) {
		var hgt = document.body.clientHeight - 27;
		document.getElementById('scroll').style.height = hgt + 'px';
	}
}
