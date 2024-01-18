/* util.js
(C) by Jan Garloff
 */

/**
 * Set the HTML title of the wordpress page
 * @param {*} newTitle 
 */
function setTitle(newTitle) {
	$('title').html(newTitle);
	$('h1.entry-title').html(newTitle);
}