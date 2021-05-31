/**
 * A class that defines a diagram's legend.
 */
function Legend() {
	this.valueSet = this.createEmptyValueSet();
	// A list of listener functions (or methods) to be called on valueSet update.
	this.listenerSet = [];
}


/**
 * Creates an empty valueSet.
 *
 * @return {Object} A valueSet object which can hold the values for the legend. It defines a method getLength which is used to keep track of the number of elements in the set.
 */
Legend.prototype.createEmptyValueSet = function() {
	var valueSet = {
		_length: 0,
		// Define a non-standard getter function for the length property. This is done to avoid naming collisions of a possible value named "length".
		getLength: function() {
			return this._length;
		}
	};

	return valueSet;
};


/**
 * Adds a value to the legend's valueSet, or updates the color for an existing value.
 *
 * @param {String} name The value name to be added or updated in the legend.
 * @param {String} color A hexadecimal RGB color string, optionally starting with a '#' character. This is the color representation for this value name.
 * @param {[Boolean]} disableUpdateNotification If set to true, the listeners will not be notified about updates.
 */
Legend.prototype.addValue = function(name, color, disableUpdateNotification) {
	// If this is a valid color string that does not start with the '#' character, then prepend it.
	if (typeof color === "string" && color.length > 0 && color.charAt(0) !== "#") {
		color = '#' + color;
	}

	// If a value with this name already exists.
	if (name in this.valueSet) {
		// Check if the color differs.
		var oldColor = this.valueSet[name];
		if (oldColor != color) {
			// Update the color.
			this.valueSet[name] = color;

			if (!disableUpdateNotification) {
				// Notify any listeners that the valueSet changed.
				this.notifyUpdateListeners();
			}
		}
	}
	else {
		// This value does not exist yet in the valueSet. Add it now.
		this.valueSet[name] = color;
		this.valueSet._length++;

		if (!disableUpdateNotification) {
			// Notify any listeners that the valueSet changed.
			this.notifyUpdateListeners();
		}
	}
};


/**
 * Removes a value from the legend's valueSet.
 *
 * @param  {String} name The value name to be removed from the legend.
 * @param {[Boolean]} disableUpdateNotification If set to true, the listeners will not be notified about updates.
 * @return {Boolean} true If the value existed in the valueSet and it was successfully removed, or false otherwise.
 */
Legend.prototype.removeValue = function(name, disableUpdateNotification) {
	if (!name) {
		return false;
	}

	// If there is such a value defined in the valueSet, then remove it.
	if (name in this.valueSet) {
		delete this.valueSet[name];
		this.valueSet._length--;

		if (!disableUpdateNotification) {
			// Notify any listeners that the valueSet changed.
			this.notifyUpdateListeners();
		}

		return true;
	}
	else {
		return false;
	}
};


/**
 * Clears the legend's valueSet.
 *
 * @param {[Boolean]} disableUpdateNotification If set to true, the listeners will not be notified about updates.
 */
Legend.prototype.clearValueSet = function(disableUpdateNotification) {
	// Replace the value set with a new empty one.
	this.valueSet = this.createEmptyValueSet();

	if (!disableUpdateNotification) {
		// Notify any listeners that the valueSet changed.
		this.notifyUpdateListeners();
	}
};



/**
 * Assigns a valueSet to the legend.
 *
 * @param  {Object} valueSet An object whose elements should be a map in the format valueName: color.
 * @param {[Boolean]} disableUpdateNotification If set to true, the listeners will not be notified about updates.
 */
Legend.prototype.assignValueSet = function(valueSet, disableUpdateNotification) {
	if (typeof valueSet !== "object") {
		throw "Attempted to assign an invalid valueSet. Expected an object.";
	}

	// First, clear the existing valueSet as it may contain some elements. Do not notify about updates yet.
	this.clearValueSet(true);

	// Go through each element in the valueSet and add it using the existing Legend methods. This should filter out any duplicates.
	for (var name in valueSet) {
		var color = valueSet[name];
		//  Do not notify about updates yet.
		this.addValue(name, color, true);
	}

	if (!disableUpdateNotification) {
		// Notify any listeners that the valueSet changed.
		this.notifyUpdateListeners();
	}
};


/**
 * Adds a listener callback that will be triggered whenever the legend valueSet changes.
 *
 * @param {Function} listener A listener callback function that will be notified whenever the legend's valueSet changes.
 * @param {[Object]} context The object for which the listener should be called.
 * @return {Boolean} true If a valid listener, that has not been added previously, was added to the listenerSet, or false otherwise.
 */
Legend.prototype.addUpdateListener = function(listener, context) {
	if (typeof listener !== "function") {
		return false;
	}

	// Check if this listener for this context already exists.
	var exists = false;
	var listenerProperties = null;

	for (var i = 0; i < this.listenerSet.length; i++) {
		listenerProperties = this.listenerSet[i];
		if (listenerProperties.listener === listener && listenerProperties.context === context) {
			exists = true;
		}
	}

	// If this listener is not part of the listenerSet array yet, then add it.
	if (!exists) {
		listenerProperties = {
			listener: listener,
			context: context
		};
		this.listenerSet.push(listenerProperties);
		return true;
	}
	else {
		// This listener already exists. Do not add it again.
		return false;
	}
};


/**
 * Removes a listener callback that would have been triggered whenever the legend valueSet changes.
 *
 * @param {Function} listener A listener callback function that would have been notified whenever the legend's valueSet changes.
 * @param {[Object]} context The object for which the listener would have been called.
 * @return {Boolean} true If a valid listener, that has been added previously, was removed from the listenerSet, or false otherwise.
 */
Legend.prototype.removeUpdateListener = function(listener, context) {
	if (typeof listener !== "function") {
		return false;
	}

	// Check if this listener for this context already exists.
	var listenerIndex = -1;
	for (var i = 0; i < this.listenerSet.length; i++) {
		var listenerProperties = this.listenerSet[i];
		if (listenerProperties.listener === listener && listenerProperties.context === context) {
			listenerIndex = i;
		}
	}

	// If this listener is not part of the listenerSet array yet, then there is nothing to be done.
	if (listenerIndex < 0) {
		return false;
	}
	else {
		// This listener already exists. Remove it and re-index the listenerSet array.
		this.listenerSet.splice(listenerIndex, 1);
	}
};


/**
 * Notifies registered updateListener callbacks that the valueSet of the legend has updated.
 */
Legend.prototype.notifyUpdateListeners = function() {
	for (var i = 0; i < this.listenerSet.length; i++) {
		var listenerProperties = this.listenerSet[i];
		var listener = listenerProperties.listener;
		var context = listenerProperties.context;

		// If there was no valid context specified for this litener, it is probably just a regular function.
		if (typeof context !== 'object') {
			listener();
		}
		else {
			// Call the listener as part of the context.
			listener.call(context);
		}
	}
};


/**
 * @return {Object} The valueSet of the legend. This is an object whose elements should be a map in the format valueName: color.
 */
Legend.prototype.getValueSet = function() {
	return this.valueSet;
};


/**
 * @return {Object} An iterable value set that defines only the values themselves, without system fields like _length or getLength().
 * NOTE: The returned object is a copy of the valueSet, so adding elements to it will have practically no effect.
 */
Legend.prototype.getIterableValueSet = function() {
	var iterableValueSet = {};

	// Define a list of properties that should not be iterable.
	var nonIterableProperties = [
		'_length',
		'getLength'
	];

	for (var valueName in this.valueSet) {
		// If this property is determined to be non-iterable, then skip it.
		if (nonIterableProperties.indexOf(valueName) >= 0) {
			continue;
		}

		// Add the property to the iterable value set.
		iterableValueSet[valueName] = this.valueSet[valueName];
	}

	return iterableValueSet;
};
