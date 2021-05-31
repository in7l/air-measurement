/**
 * Represents filters for a measurement diagram.
 */
function Filters(containerId) {
	// Assign the container element for these filters.
	this.assignContainer(containerId);

	// List of other properties being set mostly at the init phase.
	// They are just listed here to make it clearer that they exist.
	this.lastConfirmedSelection = null;
	// A list of listener functions (or methods) to be called on selection update.
	this.listenerSet = [];
}


/**
 * Initializes the filters so they are able to react to events.
 */
Filters.prototype.init = function() {
	// Register event listeners for the filters menu.
	this.registerEventListeners();
};

/**
 * Checks if the filter selection has changed since the last confirmation.
 *
 * This function does not do anything useful. It is expected that subclasses overwrite this method.
 *
 * @return {Boolean} true If the selected filter options have been determined to have changed since the last confirmation, or false otherwise.
 */
Filters.prototype.hasSelectionUpdated = function() {
	return true;
};

/**
 * Sets the value for the last confirmed selection.
 *
 * This function does not do anything useful. It is expected that subclasses overwrite this method.
 */
Filters.prototype.setLastConfirmedSelection = function() {
	// Store the last confirmed options.
	this.lastConfirmedSelection = null;
};


/**
 * Assigns a container element for the filters.
 *
 * @param {String} containerId A string representing the HTML id of a parent container element for the filters. This can optionally contain the '#' character in the beginning.
 */
Filters.prototype.assignContainer = function(containerId) {
	// Make sure the containerId is valid.
	if (typeof containerId !== "string" || containerId.length < 1) {
		throw "Invalid containerId: " + containerId + ". Expected a non-empty string.";
	}

	// If the first character of the containerId is not '#', then prepend it.
	if (containerId.charAt(0) !== '#') {
		containerId = '#' + containerId;
	}

	// Store the container id in an object property.
	this.containerId = containerId;
};


/**
 * @return {Mixed} Array of machine names for the last confirmed selection, or null if no selection has been confirmed so far.
 */
Filters.prototype.getLastConfirmedSelection = function() {
	return this.lastConfirmedSelection;
};


/**
 * Registers event listeners for the filters menu.
 */
Filters.prototype.registerEventListeners = function() {
	this.preventMenuAutoHide();
	// Register a listener for the confirmation button.
	this.registerConfirmationEventListener();
};


/**
 * Prevents auto-hiding of the menu, whenever a filter option is selected or deselected.
 */
Filters.prototype.preventMenuAutoHide = function() {
	// Stop event propagation of dropdown menus with no-auto-collapse class.
	$( this.containerId + ' .no-auto-collapse' ).click(function( event ){
		event.stopPropagation();
	});
};


/**
 * Registers an event listener for the confirmation button.
 */
Filters.prototype.registerConfirmationEventListener = function() {
	var self = this;

	// Register onclick listeners for the elements with class confirm-selection.
	$( this.containerId + ' .confirm-selection' ).click(function( event ) {
		// Hide/collapse the parent dropdown menu.
		$( this ).closest( '.dropdown' ).removeClass( "open" );

		// Handle the filters selection confirmation.
		self.handleFiltersSelectionConfirmation();
	});
};

/**
 * Handles filters selection confirmation by registering the selection and checking if it has changed.
 */
Filters.prototype.handleFiltersSelectionConfirmation = function() {
	// Check if the filters option selection has changed.
	var optionsUpdated = this.hasSelectionUpdated();

	// Store the newly selected options.
	this.setLastConfirmedSelection();

	// If the selection options have changed, notify the listeners.
	if (optionsUpdated) {
		this.notifySelectionUpdateListeners();
	}
};

/**
 * Adds a listener callback that will be triggered whenever the the filters selection changes.
 *
 * @param {Function} listener A listener callback function that will be notified whenever the selected options change.
 * @param {[Object]} context The object for which the listener should be called.
 * @return {Boolean} true If a valid listener, that has not been added previously, was added to the listenerSet, or false otherwise.
 */
Filters.prototype.addSelectionUpdateListener = function(listener, context) {
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
 * Removes a listener callback that would have been triggered whenever the selected options change.
 *
 * @param {Function} listener A listener callback function that would have been notified whenever the filter's selected option change.
 * @param {[Object]} context The object for which the listener would have been called.
 * @return {Boolean} true If a valid listener, that has been added previously, was removed from the listenerSet, or false otherwise.
 */
Filters.prototype.removeSelectionUpdateListener = function(listener, context) {
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
 * Notifies registered listener callbacks that the filter's selection was changed.
 */
Filters.prototype.notifySelectionUpdateListeners = function() {
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


/*
 * CheckboxFilters subclass
 * Represents filters with checkboxes for a measurement diagram.
 */

/**
 * @see The Tooltip constructor for a list of accepted parameters.
 * @param {Array} options An array of option objects, each containing the following fields:
 *   - machineName => A machine name for the option in the checkbox list.
 *   - name => A human-readable name for the option in the checkbox list.
 */
function CheckboxFilters(containerId, options) {
	// Call the superclass constructor.
	Filters.call(this, containerId);

	// Assign the filter selectable options.
	this.setOptions(options);
}

CheckboxFilters.prototype = Util.inherit(Filters.prototype); // Subclass inherits from superclass
CheckboxFilters.prototype.constructor = CheckboxFilters; // Override the inherited constructor prop.


/**
 * Initializes the filters so they are able to react to events.
 */
CheckboxFilters.prototype.init = function() {
	// Display the selectable filter options.
	this.renderOptions();
	// Initialize the last confirmed selection property.
	this.setLastConfirmedSelection();

	// Register event listeners for the filters menu.
	// This is done after rendering, since it needs some elements to be rendered first.
	this.registerEventListeners();
};


/**
 * Renders the selectable filter options as checkboxes.
 */
CheckboxFilters.prototype.renderOptions = function() {
	// Get a selection for the container where the options should be rendered.
	var filterOptionsContainer = d3.select(this.containerId + " ul.dropdown-menu");

	// Clear the contents of the container.
	filterOptionsContainer.html("");

	// Append an option for each element in the options array.
	for (var i = 0; i < this.options.length; i++) {
		var option = this.options[i];

		// Try to get the initial 'checked' status for this option. Default to true if unspecified.
		var checked = true;
		if (typeof option.checked === 'boolean') {
			checked = option.checked;
		}

		var label = filterOptionsContainer.append("li")
			.attr("role", "presentation")
			.attr("class", "checkbox menu-checkbox")
		.append("label")
			.attr("role", "menuitem")
			.attr("tabindex", "-1");

		label.append("input")
			.attr("type", "checkbox")
			.attr("value", "")
			.attr("name", option.machineName)
			.property("checked", checked);

		label.append("span")
			.html(option.name);
	}

	// Append a separator element in the filters menu.
	filterOptionsContainer.append("li")
		.attr("role", "presentation")
		.attr("class", "divider");
	// Append a confirmation button.
	filterOptionsContainer.append("li")
		.attr("role", "presentation")
		.attr("class", "dropdown-header")
	.append("a")
		.attr("role", "menuitem")
		.attr("tabindex", "-1")
		.attr("href", "#")
		.attr("class", "confirm-selection")
		.html("Confirm selection");
};


/**
 * Sets the value for the last confirmed selection.
 */
CheckboxFilters.prototype.setLastConfirmedSelection = function() {
	// Store the last confirmed options.
	this.lastConfirmedSelection = this.getSelectedOptions();
};


/**
 * Sets selectable filter options.
 *
 * @param {Array} options A list of filter option object, each containing the following fields:
 *   'machineName' - the HTML name for the option
 *   'name' - The human-readable name to be displayed to the user.
 *   'style' - optional. A CSS style object defining what the option should look like.
 *   'checked' - optional. Boolean value to determine if the option should be checked by default. Defaults to true if unspecified.
 * @param {[Boolean]} renderOptions If set to true, the updated options will be rendered immediately.
 */
CheckboxFilters.prototype.setOptions = function(options, renderOptions) {
	// Define a function for filtering out options which do not define 'machineName' and 'name' properties.
	var isValidOption = function(option) {
		if (option && 'machineName' in option && 'name' in option) {
			return true;
		}
		else {
			return false;
		}
	};

	// If no options were specified, fallback to an empty array.
	if (!options) {
		options = [];
	}
	else {
		// Some options were specified. Filter out the invalid options.
		options = options.filter(isValidOption);
	}

	// Register the options as a property of the CheckboxFilters object.
	this.options = options;

	// If it was requested that the updated options are rendered immediately, do so now.
	if (renderOptions) {
		this.renderOptions();
	}
};


/**
 * @param  {[Boolean]} machineNamesOnly If set to true, only the machine names for the options will be returned.
 * @return {Array} A list of option objects or a list of option machine names.
 */
CheckboxFilters.prototype.getOptions = function(machineNamesOnly) {
	// If it was requested that only the machine names for the options are fetched, then filter out the irrelevant fields.
	if (machineNamesOnly) {
		var optionMachineNames = this.options.map(function (option) {
			return option.machineName;
		});
		return optionMachineNames;
	}
	else {
		// Return the unmodified options array.
		return this.options;
	}
};


/**
 * Gets the currently selected options in this filter's menu.
 * @return {Array} A list of machine names for the selected options.
 */
CheckboxFilters.prototype.getSelectedOptions = function() {
	var selectedOptions = [];

	// Get the dropdown menu for these filters.
	var dropdownMenu = $( this.containerId + " .dropdown-menu");

	// Get the checkboxes selected within this dropdown menu and push them to the selectedOptions array.
	dropdownMenu.find( "input[type=checkbox]:checked" ).each(function( index ) {
		var selectedOption = $( this ).attr( "name" );
		selectedOptions.push(selectedOption);
	});

	return selectedOptions;
};


/**
 * @return {Boolean} true If the selected filter options have been determined to have changed since the last confirmation, or false otherwise.
 */
CheckboxFilters.prototype.hasSelectionUpdated = function() {
	// A flag that marks if the selected options have been determined to have been updated.
	var optionsUpdated = false;

	// Get the currently selected options.
	var selectedOptions = this.getSelectedOptions();
	// Get the last confirmed selection options.
	var lastConfirmedOptions = this.getLastConfirmedSelection();

	var i;
	var optionMachineName;


	// If no previous confirmation was done.
	if (lastConfirmedOptions === null) {
		// Get the machine names of the available options.
		var availableOptions = this.getOptions(true);

		// Check if there are any unselected options.
		for (i = 0; i < availableOptions.length; i++) {
			optionMachineName = availableOptions[i];

			// If that option is not among the selected ones, then the options have been updated since they are all selected by default.
			if (selectedOptions.indexOf(optionMachineName) < 0) {
				optionsUpdated = true;
				// No need to check the rest of the options, it has already been determined that the selection has changed.
				break;
			}
		}
	}
	else {
		// Check if the last confirmed selection differs from the current one.
		for (i = 0; i < selectedOptions.length; i++) {
			optionMachineName = selectedOptions[i];

			// If the last confirmed selection does not have this option, then the options were updated.
			if (lastConfirmedOptions.indexOf(optionMachineName) < 0) {
				optionsUpdated = true;
				break;
			}
		}

		// If the options have not yet been determined as updated, continue the comparison.
		if (!optionsUpdated) {
			for (i = 0; i < lastConfirmedOptions.length; i++) {
				optionMachineName = lastConfirmedOptions[i];

				// If the currently selected options do not have the option that was present in the last confirmed selection, then the options were updated.
				if (selectedOptions.indexOf(optionMachineName) < 0) {
					optionsUpdated = true;
					break;
				}
			}
		}
	}

	return optionsUpdated;
};


/*
 * TimeFilters subclass
 * Represents filters with checkboxes for a measurement diagram.
 */

/**
 * @see The Tooltip constructor for a list of accepted parameters.
 * @param {Array} options An array of option objects, each containing the following fields:
 *   - machineName => A machine name for the time picker.
 *   - name => A human-readable name for the time picker.
 */
function TimeFilters(containerId, options) {
	// Call the superclass constructor.
	Filters.call(this, containerId);

	// Assign the time picker options.
	this.setOptions(options);
}

TimeFilters.prototype = Util.inherit(Filters.prototype); // Subclass inherits from superclass
TimeFilters.prototype.constructor = TimeFilters; // Override the inherited constructor prop.

/**
 * Initializes the filters so they are able to react to events.
 */
TimeFilters.prototype.init = function() {
	// Display the time pickers.
	this.renderTimePickers();
	// Initialize the last confirmed selection property.
	this.setLastConfirmedSelection();

	// Register event listeners for the filters menu.
	// This is done after rendering, since it needs some elements to be rendered first.
	this.registerEventListeners();
	// Register the date pickers themselves.
	this.registerDatePickers();
};

/**
 * Renders the time pickers.
 */
TimeFilters.prototype.renderTimePickers = function() {
	// Get a selection for the container where the options should be rendered.
	var timePickersContainer = d3.select(this.containerId + " ul.dropdown-menu");

	// Clear the contents of the container.
	timePickersContainer.html("");

	// Append an option for each element in the options array.
	for (var i = 0; i < this.options.length; i++) {
		var option = this.options[i];

		// Try to get the initial 'checked' status for this option. Default to true if unspecified.
		var checked = true;
		if (typeof option.checked === 'boolean') {
			checked = option.checked;
		}

		var label = timePickersContainer.append("li")
			.attr("role", "presentation")
			.attr("class", "dropdown-header")
		.append("label")
			.attr("role", "menuitem")
			.attr("tabindex", "-1");

		label.append("span")
			.html(option.name);

		label.append("input")
			.attr("type", "text")
			.attr("id", option.machineName)
			.attr("name", option.machineName)
			.attr("class", "datetimepicker");
	}

	// Append a separator element in the filters menu.
	timePickersContainer.append("li")
		.attr("role", "presentation")
		.attr("class", "divider");
	// Append a confirmation button.
	timePickersContainer.append("li")
		.attr("role", "presentation")
		.attr("class", "dropdown-header")
	.append("a")
		.attr("role", "menuitem")
		.attr("tabindex", "-1")
		.attr("href", "#")
		.attr("class", "confirm-selection")
		.html("Confirm selection");
};

/**
 * Sets the value for the last confirmed selection.
 */
TimeFilters.prototype.setLastConfirmedSelection = function() {
	// Store the last confirmed options.
	this.lastConfirmedSelection = this.getSelectedValues();
};

/**
 * @see CheckboxFilter's setOptions() method.
 */
TimeFilters.prototype.setOptions = CheckboxFilters.prototype.setOptions;

/**
 * Registers date time pickers.
 */
TimeFilters.prototype.registerDatePickers = function() {
	// Create datetime pickers.
	$( this.containerId + ' .datetimepicker' ).datetimepicker({
		format:'Y-m-d H:i',
		onSelectTime: function(ct, $i){
			// Hide the date time picker once the time is selected.
			$i.datetimepicker('hide');
		}
	});
};

/**
 * Fetches the selected date time values from this filter.
 *
 * @return {Object} An object with property names corresponding to the machine names of the date time picker fields,
 *   and values corresponding to the current input value.
 */
TimeFilters.prototype.getSelectedValues = function() {
	var filterValues = {};

	// Find the date time pickers of the current filter.
	$( this.containerId ).find( "input[type=text].datetimepicker" ).each(function( index ) {
		// Add the current value of each datetime picker input field.
		var datetimepickerId = $( this ).attr( "id" );
		filterValues[datetimepickerId] = $( this ).val() || null;
	});

	return filterValues;
};

/**
 * Fetches the selected date time values, converted to UTC time strings.
 * @return {Object} An object with property names corresponding to the filter options' machine names
 *   and values UTC date time strings or null.
 */
TimeFilters.prototype.getSelectedValuesAsUtcStrings = function() {
	/**
	 * Converts a local-time string to UTC date string.
	 *
	 * @param {String} localDateTimeString A datetime string in one of the following formats:
	 *   '2015-03-18 00:45' or '2015-03-18 00:45:15'.
	 * @return {String|null} A date time string in UTC time format if the date was valid, or NULL otherwise.
	 */
	var convertDateToUtcString = function(localDateTimeString) {
		if (typeof localDateTimeString !== 'string') {
			return null;
		}

		// Regexp pattern for matching a date.
		// Matches e.g. '2015-03-18 00:45'
		// and '2015-03-18 00:45:15'.
		var timePattern = /^(\d{4}-\d{2}-\d{2})\s+(\d{2}:\d{2}(?::\d{2})?)$/;

		// The date should have a 'T' separator between the date and time.
		var formattedDateTimeString = localDateTimeString.replace(timePattern, '$1T$2');
		var timestamp = Date.parse(formattedDateTimeString);

		if (isNaN(timestamp)) {
			return null;
		}

		var date = new Date(timestamp);
		// Construct a string representation of the UTC datetime.
		// Add leading 0s where necessary.
		var y = date.getUTCFullYear();
		var m = ('0' + (date.getUTCMonth() + 1)).slice(-2);
		var d = ('0' + date.getUTCDate()).slice(-2);
		var h = ('0' + date.getUTCHours()).slice(-2);
		var i = ('0' + date.getUTCMinutes()).slice(-2);
		var s = ('0' + date.getUTCSeconds()).slice(-2);

		var dateUtcString = y + '-' + m + '-' + d +
			'T' + h + ':' + i + ':' + s;

		return dateUtcString;
	};

	var filterValues = this.getSelectedValues();

	var utcValues = {};
	for (var machineName in filterValues) {
		var dateString = filterValues[machineName];
		// Attempt to convert the date string to one in UTC time and store it to the result object.
		// Invalid dates will be converted to null.
		utcValues[machineName] = convertDateToUtcString(dateString);
	}

	return utcValues;
};

/**
 * Checks if the filter values are identical. It is expected that the filter objects' properties are ordered in the same way.
 *
 * This function is useful when determining if there has been an actual change to the filter after confirmation.
 *
 * @param {Object} oldFilters A filter object in a format returned by getSelectedValues() or getSelectedValuesAsUtcStrings().
 * @param {Object} newFilters A filter object in a format returned by getSelectedValues() or getSelectedValuesAsUtcStrings().
 * @return {Boolean} TRUE if the filter values are considered identical, or FALSE otherwise.
 */
TimeFilters.prototype.filterValuesAreIdentical = function(oldFilters, newFilters) {
	return (JSON.stringify(oldFilters) === JSON.stringify(newFilters));
};

/**
 * @return {Boolean} true If the date time picker filter options have been determined to have changed since the last confirmation, or false otherwise.
 */
TimeFilters.prototype.hasSelectionUpdated = function() {
	var lastConfirmedValues = this.getLastConfirmedSelection();
	var currentValues = this.getSelectedValues();

	if (this.filterValuesAreIdentical(lastConfirmedValues, currentValues)) {
		// The confirmed selection has not changed.
		return false;
	}
	else {
		// The confirmed selection has changed.
		return true;
	}
};
