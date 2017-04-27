/* JS file for MTL Star Rating module

created 2015-04-03 by Johannes Bouchain */

var mtlUserCanRate = false;
var mtlRatingDetails = false;
var elemRated = new Array();
var canBeSubmitted = false;

if(typeof($) != 'undefined') var $ = jQuery;

$(document).ready(function(){
	if($('.mtl-rating-section').length) mtl_rating_section_handler();
	if($('.mtl-tile-rating').length) mtl_tile_rating_handler();
});

// handle the single proposal rating section
function mtl_rating_section_handler() {
	// hide/show elements initially
	$('.mtl-rating-details').hide();
	$('.mtl-rating-section-active').hide();
	
	$('.mtl-rating-section .show-text').show();
	$('.mtl-rating-section .hide-text').hide();
	
	// open close detail box on button click
	$('.mtl-button-rating-details').click(function(e){
		e.preventDefault();
		$(this).blur();
		$(this).closest('.mtl-rating-section').find('.mtl-rating-details').slideToggle();
		$('.mtl-button.add-rating').find('.show-text').show();
		$('.mtl-button.add-rating').find('.hide-text').hide();
		$('.mtl-rating-subsection.active').slideUp();
		$(this).find('.show-text').toggle();
		$(this).find('.hide-text').toggle();
		if(!mtlRatingDetails) {
			$('.mtl-rating-subsection.average-readonly').slideUp();
			$('.rating-message').slideUp();
			$('.mtl-rating-subsection.readonly').slideDown();
			mtlRatingDetails = true;
		}
		else {
			$('.mtl-rating-subsection.average-readonly').slideDown();
			$('.mtl-rating-subsection.readonly').slideUp();
			mtlRatingDetails = false;
		}
		mtlUserCanRate = false;
	});
	
	// enable/disable user rating on button click
	$('.mtl-button.add-rating').click(function(e){
		e.preventDefault();
		$(this).blur();
		showActiveRatingSection(this);
	});
	
	if(window.location.hash =='#rating-add') showActiveRatingSection('.mtl-button.add-rating');
	
	
	// define star rating options, based upon jQuery Raty plugin
	$('.mtl-rating-box').raty({
	  readOnly   : true,
	  cancelOff  : 'cancel-off.png',
	  cancelOn   : 'cancel-on.png',
	  path       : templateUrl + '/modules/mtl-star-rating/raty/mtl-images-large',
	  starHalf   : 'star-half.png',
	  starOff    : 'star-off.png',
	  starOn     : 'star-on.png',
	  noRatedMsg : notRatedMessage,
	  score: function() {
		return $(this).attr('data-score');
	  },
	  hints: [mtlStarHint[0], mtlStarHint[1], mtlStarHint[2], mtlStarHint[3], mtlStarHint[4]],
	  cancelHint: mtlCancelHint
	});
	$('.mtl-rating-box.active').raty('set', {'score':0,
		'click': function(score) {
			elemRated[$(this).attr('id')]=true;
			if(elemRated['mtl-rating-box1'] && elemRated['mtl-rating-box2'] && elemRated['mtl-rating-box3'] && !canBeSubmitted) {
				$('.mtl-rating-subsection.active').append(ratingForm);
					canBeSubmitted = true;
			}
			if(canBeSubmitted) {
				for(var i = 1;i<=3;i++) {
					if($(this).attr('id')==('mtl-rating-box'+i)) $('#rating_cat'+i).val(score); // directly get score from click event, as not saved to element yet
					else $('#rating_cat'+i).val($('#mtl-rating-box'+i).raty('score')); // get score from element
				}
			}
		}
	});
	
	// editor's rating
	$('.mtl-rating-section.editors .mtl-rating-subsection').append(ratingFormEditors);
	$('.mtl-rating-section.editors .mtl-rating-box').raty('set', {'half':true,
		'score': function() {
		    return $(this).attr('data-score');
		 },
		'click': function(score) {
			elemRated[$(this).attr('id')]=true;
			for(var i = 1;i<=3;i++) {
				if($(this).attr('id')==('mtl-rating-box'+i)) $('#rating_cat'+i).val(Math.ceil(score*2)/2); // directly get score from click event, as not saved to element yet
				else $('#rating_cat'+i).val($('#mtl-rating-box'+i).raty('score')); // get score from element
			}
		}
	});
	$('.mtl-rating-box.active').raty('readOnly', false);
	
	
}

// show active rating section
function showActiveRatingSection(elem) {
	if($('.mtl-rating-subsection.active').length) {
		if(!mtlUserCanRate) {
			$('.mtl-rating-subsection.active').slideDown();
			$('.mtl-rating-subsection.average-readonly').slideUp();
			$('.mtl-rating-details').slideUp();
			$('.mtl-button-rating-details').find('.show-text').show();
			$('.mtl-button-rating-details').find('.hide-text').hide();
			$('.mtl-rating-subsection.readonly').slideUp();
			$('.rating-message').slideUp();
			mtlUserCanRate = true;
		}
		else {
			$('.mtl-rating-subsection.active').slideUp();
			$('.mtl-rating-subsection.average-readonly').slideDown();
			mtlUserCanRate = false;
		}
		
		$(elem).find('.show-text').toggle();
		$(elem).find('.hide-text').toggle();
		mtlRatingDetails = false;
	}
}

//handle the tile list rating output
function mtl_tile_rating_handler() {
	$('.mtl-tile-rating').each(function(){
		$(this).raty({
		  readOnly   : true,
		  cancelOff  : 'cancel-off.png',
		  cancelOn   : 'cancel-on.png',
		  path       : templateUrl + '/modules/mtl-star-rating/raty/mtl-images-list',
		  starHalf   : 'star-half.png',
		  starOff    : 'star-off.png',
		  starOn     : 'star-on.png',
		  noRatedMsg : notRatedMessage,
		  score: function() {
			return $(this).attr('data-score');
		  },
		  hints: [mtlStarHint[0], mtlStarHint[1], mtlStarHint[2], mtlStarHint[3], mtlStarHint[4]],
		  cancelHint: mtlCancelHint
		});
	});
}