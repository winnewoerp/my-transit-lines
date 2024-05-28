/* flextiles.js */

var $ = jQuery;

var flextilesWidth = 250;
var flextilesSpace = 10;

$(document).ready(function(){
	changeTilePadding();
});

$( window ).on("resize", function(){
	changeTilePadding();
});

function changeTilePadding() {
	if($('.flextiles-list').length) {
		var el = '.flextiles-list';
		var elWidth = $(el).innerWidth();
		var tileListWidth = flextilesWidth;
		while(elWidth>=(tileListWidth+flextilesWidth+flextilesSpace)) {
			tileListWidth = tileListWidth+flextilesWidth+flextilesSpace;
		}
		var leftPadding = (elWidth-tileListWidth)/2;
		$(el).css('padding-left',leftPadding+'px');
	}
}