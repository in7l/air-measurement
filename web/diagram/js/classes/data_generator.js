/**
 * A simple random data generator.
 */
function DataGenerator() {
	this.data = [];

	// A list of listener functions (or methods) to be called on successful data fetching.
	this.successListenerSet = [];
	// A list of listener functions (or methods) to be called on data fetching failures.
	// While failures are not really expected, this is kept in order to make the data generator replicate the data fetcher functionality more closely.
	this.failureListenerSet = [];
}

/**
 * Initializes the data fetcher component.
 *
 * @param  {Integer} updateIntervalMilliseconds The amount of time in milliseconds between measurement data generating (updating).
 */
DataGenerator.prototype.init = function(updateIntervalMilliseconds) {

	// Generate the initial random data.
	this.generateRandomData(true);
	// Schedule subsequent random data generation.
	this.scheduleDataGeneration(updateIntervalMilliseconds);
};

/**
 * Schedules additional measurement data to be generated every once in a while.
 *
 * @param  {Integer} updateIntervalMilliseconds The amount of time in milliseconds between measurement data generating (updating).
 */
DataGenerator.prototype.scheduleDataGeneration = function(updateIntervalMilliseconds) {
	var self = this;

	// Generate an additional measurement data entry every N milliseconds.
	setInterval(function() {
		self.generateRandomData();
	}, updateIntervalMilliseconds);
};

/**
 * Generates random data.
 *
 * @param {Multi} [varname] [description]
 * @return {Array} Random measurement data.
 */
DataGenerator.prototype.generateRandomData = function(multi) {

	var limit = 50;

	// Generate random data.
	if (multi) {
		// Clear any previously existing data.
		this.data = [];
	}
	else {
		// Generate only a single measurement data entry.
		limit = 1;
	}


	var ONE_HOUR = 60 * 60;
	var currentTime = Math.floor(new Date().getTime() / 1000);
	var pastTime = currentTime - ONE_HOUR / 3;

	for (var i = 0; i < limit; i++) {
		var timestamp = currentTime;
		if (multi) {
			// If there are multiple measurement data entries being generated,
			// then use a certain time range.
			timestamp = this.getRandomInt(pastTime, currentTime);
		}

		var measurement = {
			"timestamp": timestamp,
			"measure_n": this.getRandomArbitrary(0, 100),
			"measure_pa": this.getRandomArbitrary(0, 100),
			"measure_mg": this.getRandomArbitrary(0, 50),
			"measurement_id": i
		};
		this.data.push(measurement);

		if (!multi) {
			// Remove the first measurement entry.
			this.data.shift();
		}
	}

	if (multi) {
		// Sort the data by timestamp.
		this.data.sort(function(a, b) {
			return a.timestamp - b.timestamp;
		});
	}

	// Inform listeners interested in successful data generation.
	this.notifyDataUpdateListeners(true);
};

/**
 * Adds a listener callback that will be triggered whenever the the data is successfully fetched or whenever it fails to be fetched.
 *
 * @param {Boolean} success Set this to TRUE if the listener should be informed whenever the data is fetched successfully.
 *   Set this to FALSE if the listener should be informed on data fetching failures.
 * @param {Function} listener A listener callback function that will be notified whenever the data succeeds or fails to be updated.
 * @param {[Object]} context The object for which the listener should be called.
 * @return {Boolean} true If a valid listener, that has not been added previously, was added to the listenerSet, or false otherwise.
 */
DataGenerator.prototype.addDataUpdateListener = function(success, listener, context) {
	if (typeof listener !== "function") {
		return false;
	}

	var listenerSet = null;
	if (success) {
		listenerSet = this.successListenerSet;
	}
	else {
		listenerSet = this.failureListenerSet;
	}

	// Check if this listener for this context already exists.
	var exists = false;
	var listenerProperties = null;

	for (var i = 0; i < listenerSet.length; i++) {
		listenerProperties = listenerSet[i];
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
		listenerSet.push(listenerProperties);
		return true;
	}
	else {
		// This listener already exists. Do not add it again.
		return false;
	}
};


/**
 * Removes a listener callback that would have been triggered whenever the data is updated successfully or its updating fails.
 *
 * @param {Boolean} success Set this to TRUE if the listener was meant to be informed whenever the data is fetched successfully.
 *   Set this to FALSE if the listener was intended to be informed on data fetching failures.
 * @param {Function} listener A listener callback function that would have been notified whenever the data succeeds or fails to be updated.
 * @param {[Object]} context The object for which the listener would have been called.
 * @return {Boolean} true If a valid listener, that has been added previously, was removed from the listenerSet, or false otherwise.
 */
DataGenerator.prototype.removeDataUpdateListener = function(success, listener, context) {
	if (typeof listener !== "function") {
		return false;
	}

	var listenerSet = null;
	if (success) {
		listenerSet = this.successListenerSet;
	}
	else {
		listenerSet = this.failureListenerSet;
	}

	// Check if this listener for this context already exists.
	var listenerIndex = -1;
	for (var i = 0; i < listenerSet.length; i++) {
		var listenerProperties = listenerSet[i];
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
		listenerSet.splice(listenerIndex, 1);
	}
};


/**
 * Notifies registered listener callbacks that the data was updated successfully or it failed to be updated.
 *
 * @param {Boolean} success Set this to TRUE if the listeners waiting for successful data fetching notifications should be notified.
 *   Set this to FALSE to inform the listeners waiting for data fetching failure notifications.
 * @param {Mixed} err optional If an error occurred, it can be passed here to provide more context about what went wrong.
 */
DataGenerator.prototype.notifyDataUpdateListeners = function(success, err) {
	var listenerSet = null;
	if (success) {
		listenerSet = this.successListenerSet;
	}
	else {
		listenerSet = this.failureListenerSet;
	}

	for (var i = 0; i < listenerSet.length; i++) {
		var listenerProperties = listenerSet[i];
		var listener = listenerProperties.listener;
		var context = listenerProperties.context;

		// If there was no valid context specified for this litener, it is probably just a regular function.
		if (typeof context !== 'object') {
			if (!success && err) {
				// Pass the error to the listener function.
				listener(err);
			}
			else {
				listener();
			}
		}
		else {
			// Call the listener as part of the context.
			if (!success && err) {
				// Pass the error to the listener function.
				listener.call(context, err);
			}
			else {
				listener.call(context);
			}
		}
	}
};

/**
 * @return {Array} A list of measurement data entries that were last retrieved from the backend service.
 */
DataGenerator.prototype.getData = function() {
	return this.data;
};


/**
 * Returns a random number between min (inclusive) and max (exclusive)
 */
DataGenerator.prototype.getRandomArbitrary = function(min, max) {
	return Math.random() * (max - min) + min;
};

/**
* Returns a random integer between min (inclusive) and max (inclusive)
* Using Math.round() will give you a non-uniform distribution!
*/
DataGenerator.prototype.getRandomInt = function(min, max) {
	return Math.floor(Math.random() * (max - min + 1)) + min;
};
