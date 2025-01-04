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

/**
 * Awaiting this will take the specified delay of time.
 * @param {number} ms time in milliseconds
 */
const delay = ms => new Promise(res => setTimeout(res, ms));

/**
 * Format the number with its unit prefix
 * @param {Number} number The number to format
 * @param {Boolearn} word_prefix Whether to use words instead of unit prefixes
 * @param {Boolean} step The step size to use instead of 1000, for example for squared or cubed units
 * @returns A string containing the number and its unit prefix separated by a space 
 */
function formatNumber(number, word_prefix, step = 1E3) {
	if (number >= Math.pow(step, 3)) {
		return (number / Math.pow(step, 3)).toPrecision(3).replace('.', objectL10n.decimalSeparator) + (word_prefix ? ' '+objectL10n.billion+' ' : ' G');
	}
	if (number >= Math.pow(step, 2)) {
		return (number / Math.pow(step, 2)).toPrecision(3).replace('.', objectL10n.decimalSeparator) + (word_prefix ? ' '+objectL10n.million+' ' : ' M');
	}
	if (number >= Math.pow(step, 1)) {
		return (number / Math.pow(step, 1)).toPrecision(3).replace('.', objectL10n.decimalSeparator) + (word_prefix ? ' '+objectL10n.thousand+' ' : ' k');
	}
	if (number == 0) {
		return '0 ';
	}
	if (number < Math.pow(step, -2)) {
		return (number / Math.pow(step, -2)).toPrecision(3).replace('.', objectL10n.decimalSeparator) + ' n';
	}
	if (number < Math.pow(step, -1)) {
		return (number / Math.pow(step, -1)).toPrecision(3).replace('.', objectL10n.decimalSeparator) + ' µ';
	}
	if (number < Math.pow(step, 0)) {
		return (number / Math.pow(step, 0)).toPrecision(3).replace('.', objectL10n.decimalSeparator) + ' m';
	}
	return number.toPrecision(3).replace('.', objectL10n.decimalSeparator) + ' ';
}