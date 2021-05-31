function Util() {
}

/**
 * This function is taken from "Javascript: The definitive guide" published by O'REILLY
 *
 * inherit() returns a newly created object that inherits properties from the
 * prototype object p. It uses the ECMAScript 5 function Object.create() if
 * it is defined, and otherwise falls back to an older technique.
 */
Util.inherit = function(p) {
	if (p == null) throw TypeError(); // p must be a non-null object
	if (Object.create) // If Object.create() is defined...
		return Object.create(p); // then just use it.

	var t = typeof p; // Otherwise do some more type checking
	if (t !== "object" && t !== "function") throw TypeError();
	function f() {} // Define a dummy constructor function.
	f.prototype = p; // Set its prototype property to p.
	return new f(); // Use f() to create an "heir" of p.
};


/**
 * Array comparison function using jQuery methods.
 *
 * Taken from http://stackoverflow.com/a/7726509
 *
 * @param  {Array} a An array to be compared with b.
 * @param  {Array} b An array to be compared with a.
 * @return {Boolean} true if the two arrays are considered equal, or false otherwise.
 */
Util.areArraysEqual = function(a, b) {
	// If either a or b is falsy, fallback to an empty array, just in case something else was passed as a parameter.
	if (!a) {
		a = [];
	}
	if (!b) {
		b = [];
	}

	var result = $(a).not(b).length === 0 && $(b).not(a).length === 0;

	return result;
};

/**
 * Converts a RGB color to a HEX string.
 *
 * @param  {String} rgb A rgb string in the format 'rgb(R, G, B)'
 * @param  {[Boolean]} excludeHashCharacter If set to true, the '#' character
 * *	will be excluded from the result.
 * @return {String} The color as a HEX string.
 */
Util.rgbColorToHex = function(rgb, excludeHashCharacter) {
	var componentToHex = function(c) {
		var hex = c.toString(16);
		return hex.length == 1 ? "0" + hex : hex;
	};

	var rgbPattern = /rgb\((\d+),\s*(\d+),\s*(\d+)\)/;

	var matches = rgb.match(rgbPattern);
	if (!matches) {
		return '';
	}

	r = componentToHex(parseInt(matches[1]));
	g = componentToHex(parseInt(matches[2]));
	b = componentToHex(parseInt(matches[3]));

	var hexColor = [r, g, b].join('');

	if (!excludeHashCharacter) {
		hexColor = '#' + hexColor;
	}

	return hexColor;
};

/**
 * Sanitizes a string so it can be used as a DOM id.
 *
 * @see http://stackoverflow.com/a/9635698
 *
 * @param {String} name The string to be sanitized.
 * @return {String} The sanitized string
 */
Util.sanitizeNameForId = function(name) {
	name = name.replace(/^[^a-z]+|[^\w:.-]+/gi, "");
	return name;
};
