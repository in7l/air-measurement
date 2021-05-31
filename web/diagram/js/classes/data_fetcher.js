/**
 * Fetches data for a measurement diagram.
 *
 * @param {String} url The URL for fetching measurement data.
 *   If the URL is under the same domain, this can be only a relative path
 *   that does not start with a '/'.
 * @param {Integer} limit The default limit determining how many measurement entries should be returned.
 */
function DataFetcher(url, limit) {
	// URL where the measurement results can be fetched.
	this.url = url;
	// Locally-stored measurement data.
	this.data = [];

	// Default maximum number of measurement entries to be fetched.
	this.limit = limit;
	// Earliest date of measurement entries to be fetched.
	this.startDate = null;
	// Latest date of measurement entries to be fetched.
	this.endDate = null;
	// Default selected sample rates.
	this.sampleRates = [0];

	// A list of listener functions (or methods) to be called on successful data fetching.
	this.successListenerSet = [];
	// A list of listener functions (or methods) to be called on data fetching failures.
	this.failureListenerSet = [];
}

/**
 * Initializes the data fetcher component.
 *
 * @param  {Integer} updateIntervalMilliseconds The amount of time in milliseconds between measurement data refreshing (updating).
 */
DataFetcher.prototype.init = function(updateIntervalMilliseconds) {

	// Fetch the initial data.
	this.fetchData();
	// Schedule subsequent data updating.
	this.scheduleDataFetching(updateIntervalMilliseconds);
};

/**
 * Schedules the measurement data to be retrieved every once in a while.
 *
 * @param  {Integer} updateIntervalMilliseconds The amount of time in milliseconds between measurement data refreshing (updating).
 */
DataFetcher.prototype.scheduleDataFetching = function(updateIntervalMilliseconds) {
	var self = this;

	// Fetch the data every N milliseconds.
	setInterval(function() {
		self.fetchData();
	}, updateIntervalMilliseconds);
};

/**
 * Fetches data from the assigned URL. On success or failure, informs any registered listeners.
 */
DataFetcher.prototype.fetchData = function() {
	var self = this;

	// Get a list of GET parameters to be passed to the back-end service.
	var getParams = this.getUrlParams();

	// Make an AJAX get request to fetch the data.
	$.ajax({
		url: self.url,
		type: 'get',
		data: getParams,
		dataType: 'json',
		success: function(resultObject) {
			// The result object should contain a property named 'data'.
			if (!resultObject || !('data' in resultObject) || !(resultObject.data)) {
				data = [];
			}

			self.data = resultObject.data;

			// Inform listeners interested in successful data fetching.
			self.notifyDataUpdateListeners(true);
		},
		error: function(err) {
			// Inform listeners interested in failed data fetching.
			self.notifyDataUpdateListeners(false, err);
		}
	});
};

/**
 * Fetches URL GET parameters to be specified to the back-end service that returns the measurement data.
 * @return {Object} An object with properties corresponding
 *   to the GET parameters.
 */
DataFetcher.prototype.getUrlParams = function() {
	var getParams = {};

	// Get date range parameters.
	var dateParams = this.getDateFilters();
	if (dateParams) {
		// Copy the date range components to the common list of GET parameters.
		for (var fieldName in dateParams) {
			getParams[fieldName] = dateParams[fieldName];
		}
	}
	else {
		// Apply the default limit if there are no date range parameters.
		getParams.limit = this.limit;
	}

	// Get the sample rate parameters.
	var sampleRates = this.getSampleRateFilters();
	if (sampleRates && sampleRates.length > 0) {
		var sampleRatesGetParamValue = sampleRates.join();
		getParams['consolidation-type'] = sampleRatesGetParamValue;
	}

	return getParams;
};

/**
 * Sets the default limit when fetching measurement data entries.
 * @param {Integer} limit The maximum number of measurement data entries to be retrieved.
 */
DataFetcher.prototype.setLimit = function(limit) {
	this.limit = limit;
};

/**
 * Sets the date filters to be used when fetching measurement data entries.
 *
 * @param {String|null} startDate Earliest allowed measurement data entry date in UTC time.
 * @param {String|null} endDate Latest allowed measurement data entry date in UTC time.
 */
DataFetcher.prototype.setDateFilters = function(startDate, endDate) {
	this.startDate = startDate || null;
	this.endDate = endDate || null;
};

/**
 * Fetches the stored date range filters in the expected format by the backend measurement data fetcher service.
 *
 * @return {Object|null} An object of date parameters if a date range has been selected, or null otherwise.
 */
DataFetcher.prototype.getDateFilters = function() {
	if (!this.startDate && !this.endDate) {
		// There is no selected date range.
		return null;
	}

	var dateFilters = {};
	if (this.startDate) {
		dateFilters.start = this.startDate;
	}
	if (this.endDate) {
		dateFilters.end = this.endDate;
	}

	return dateFilters;
};

/**
 * Sets the sample rate filters to be used when fetching measurement data entries.
 *
 * @param {[integer]} sampleRates The selected sample rates.
 */
DataFetcher.prototype.setSampleRateFilters = function(sampleRates) {
	this.sampleRates = sampleRates || null;
};

/**
 * Fetches the stored sample rate filters in the expected format by the backend measurement data fetcher service.
 *
 * @return {[integer]|null} An array of selected sample rates or null.
 */
DataFetcher.prototype.getSampleRateFilters = function() {
	if (!this.sampleRates) {
		// There are no selected sample rates.
		return null;
	}

	return this.sampleRates;
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
DataFetcher.prototype.addDataUpdateListener = function(success, listener, context) {
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
DataFetcher.prototype.removeDataUpdateListener = function(success, listener, context) {
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
DataFetcher.prototype.notifyDataUpdateListeners = function(success, err) {
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
 * @return {Function} The default event listener for reacting to changes in date selection filters.
 */
DataFetcher.prototype.getDefaultDateSelectionChangeListener = function() {
	var self = this;

	/**
	 * Reacts to changes in date selection filters.
	 *
	 * @param {String|null} startDate Earliest allowed measurement data entry date in UTC time.
	 * @param {String|null} endDate Latest allowed measurement data entry date in UTC time.
	 */
	var defaultDateSelectionChangeListener = function(startDate, endDate) {
		// Set the updated start and end date range options.
		self.setDateFilters(startDate, endDate);
		// Force data updating.
		self.fetchData();
	};

	return defaultDateSelectionChangeListener;
};

/**
 * @return {Function} The default event listener for reacting to changes in sample rate.
 */
DataFetcher.prototype.getDefaultSampleRateChangeListener = function() {
	var self = this;

	/**
	 * Reacts to changes in date selection filters.
	 *
	 * @param {[integer]} sampleRates The selected sample rates.
	 */
	var defaultSampleRateChangeListener = function(sampleRates) {
		// Set the updated sample rates.
		self.setSampleRateFilters(sampleRates);
		// Force data updating.
		self.fetchData();
	};

	return defaultSampleRateChangeListener;
};

/**
 * @return {Array} A list of measurement data entries that were last retrieved from the backend service.
 */
DataFetcher.prototype.getData = function() {
	return this.data;
};
