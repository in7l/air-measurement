/**
 * Class for displaying tooltips.
 *
 * @param {String} containerId A string representing the HTML id of a parent container element for the tooltip. This can optionally contain the '#' character in the beginning.
 * @param {String} tooltipId A string representing the HTML id for the tooltip. This can optionally contain the '#' character in the beginning.
 * @param {[Object]} style The CSS style for this object, as accepted by D3js.
 * @param {[String]} content The HTML content for the tooltip.
 * @param {[Boolean]} show Indicates whether the tooltip should be initially visible or not. Defaults to false.
 */
function Tooltip(containerId, tooltipId, style, content, show) {
	// First assign the container to the object.
	this.assignContainer(containerId);
	// Assign the tooltipId to the object.
	this.assignTooltipId(tooltipId);

	// If no style was specified, default to an empty object.
	this.style = style || {};

	// If no content was specified, default to an empty string.
	this.content = content || {};

	// If it was not specified whether the tooltip should be shown or hidden, assume it should be hidden.
	if (show === undefined) {
		show = false;
	}

	this.hidden = !show;

	// List of other properties being set mostly at the init phase.
	// They are just listed here to make it clearer that they exist.
	this.element = null;
}


/**
 * Assigns a container element for the tooltip.
 *
 * @param {String} containerId A string representing the HTML id of a parent container element for the tooltip. This can optionally contain the '#' character in the beginning.
 */
Tooltip.prototype.assignContainer = function(containerId) {
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
 * Assigns a tooltip id for the object.
 *
 * @param {String} tooltipId A string representing the HTML id for the tooltip. This can optionally contain the '#' character in the beginning.
 */
Tooltip.prototype.assignTooltipId = function(tooltipId) {
	// Make sure the tooltipId is valid.
	if (typeof tooltipId !== "string" || tooltipId.length < 1) {
		throw "Invalid tooltipId: " + tooltipId + ". Expected a non-empty string.";
	}

	// If the first character of the tooltipId is not '#', then prepend it.
	if (tooltipId.charAt(0) !== '#') {
		tooltipId = '#' + tooltipId;
	}

	// Store the tooltip id in an object property.
	this.tooltipId = tooltipId;

	// Define a getter for the tooltipId without the hash character.
	Object.defineProperty(this,
		"tooltipIdWithoutHash",
		{ get: function () { return this.tooltipId.substring(1); } }
	);
};


/**
 * Initializes the tooltip object.
 */
Tooltip.prototype.init = function() {
	this.initTooltipElement();
	this.applyStyle();
	this.initDisplaySettings();
	this.displayContent();
};


/**
 * Initializes the tooltip element by either finding an existing DOM element for it, or appending a new one at the proper place.
 */
Tooltip.prototype.initTooltipElement = function() {
	// Check if there is already an element for this tooltip.
	var selection = d3.select(this.containerId + " " + this.tooltipId);
	// If there is already such a tooltip added, do not append it again.
	if (!selection.empty()) {
		this.element = selection;
		return;
	}

	// At this point it is clear that the tooltip element does not exist. Append it now.
	this.element = d3.select(this.containerId)
		.append("div")
		.attr("id", this.tooltipIdWithoutHash);
};


/**
 * Initializes the tooltip display settings based on the hidden property.
 */
Tooltip.prototype.initDisplaySettings = function() {
	if (this.hidden) {
		this.hide();
	}
	else {
		this.show();
	}
};

/**
 * Checks if the tooltip is currently visible.
 * @return {Boolean} true if the tooltip is visible, or false otherwise.
 */
Tooltip.prototype.isVisible = function() {
	return (!this.hidden);
};


/**
 * Shows the tooltip in case it was hidden.
 */
Tooltip.prototype.show = function() {
	this.hidden = false;
	this.element.style("display", null);
};


/**
 * Hides the tooltip in case it was visible.
 */
Tooltip.prototype.hide = function() {
	this.hidden = true;
	this.element.style("display", "none");
};


/**
 * Triggers the display property of the tooltip between shown and hidden.
 */
Tooltip.prototype.triggerDisplay = function() {
	// Invert the hidden property's value.
	this.hidden = !this.hidden;
	// Apply the new setting.
	this.initDisplaySettings();
};


/**
 * Sets and applies a CSS style to the tooltip.
 *
 * @param {[Object]} style The CSS style for this object, as accepted by D3js.
 */
Tooltip.prototype.setStyle = function(style) {
	this.style = style;
	this.applyStyle();
};


/**
 * Sets and displays some content in the tooltip.
 *
 * @param {[String]} content The HTML content for the tooltip.
 */
Tooltip.prototype.setContent = function(content) {
	this.content = content;
	this.displayContent();
};


/**
 * Applies the assigned CSS style to the tooltip.
 */
Tooltip.prototype.applyStyle = function() {
	this.element.style(this.style);
	this.initDisplaySettings();
};


/**
 * Displays the assigned content to the tooltip.
 */
Tooltip.prototype.displayContent = function() {
	this.element.html(this.content);
};


/**
 * Sets the position of the tooltip.
 *
 * @param {*} my The my property of the jQuery's position method.
 * @param {*} of The of property of the jQuery's position method.
 */
Tooltip.prototype.setPosition = function(my, of) {
	$( this.containerId + " " + this.tooltipId ).position({
		// Define which position on the tooltip
		// to align with the target element.
		my: my,
		of: of,
		within: $( this.containerId )
	});
};

/**
 * Get the main (HTML) element for the tooltip.
 */
Tooltip.prototype.getElement = function() {
	return this.element;
};


/* MeasurementTooltip subclass */

/**
 * @see The Tooltip constructor for a list of accepted parameters.
 */
function MeasurementTooltip() {
	// Call the superclass constructor.
	Tooltip.apply(this, arguments);
}

MeasurementTooltip.prototype = Util.inherit(Tooltip.prototype); // Subclass inherits from superclass
MeasurementTooltip.prototype.constructor = MeasurementTooltip; // Override the inherited constructor prop.


/**
 * Sets the content of the tooltip based on the masurement date and value.
 * @param {[Date object]} date The date of the measurement.
 * @param {[String]} valueField The name of the value whose measurement is being displayed. Must be specified if any value is to be displayed.
 * @param {[Number]} value The value of the measurement.
 */
MeasurementTooltip.prototype.setMeasurementContent = function(date, valueField, value) {
	var content = "";

	// Display which value field this measurement refers to and also add the value.
	if (valueField) {
		// If no value is present, display "N/A".
		value = (value || value === 0) ? value : "N/A";
		content = valueField + ": " + value;
	}

	// If a valid date was passed, append it to the content.
	if (typeof date === "object") {
		if (content.length > 0) {
			// If there is already some content for this tooltip, add a newline.
			content += "<br>";
		}

		var dateFormat = d3.time.format("%d-%m-%Y %H:%M:%S");
		var tooltipDate = dateFormat(date);
		content += "Measured at:<br>" + tooltipDate;
	}

	this.setContent(content);
};


/* LegendTooltip subclass */

/**
 * @param {Object Legend} Legend A legend object for this tooltip.
 * @see The Tooltip constructor for a list of accepted parameters, following the legend parameter.
 */
function LegendTooltip(legend) {
	this.legend = legend;

	var superClassArgs = Array.prototype.slice.call(arguments);
	// Remove the first argument from the array.
	superClassArgs.shift();
	// Call the superclass constructor.
	Tooltip.apply(this, superClassArgs);
}

LegendTooltip.prototype = Util.inherit(Tooltip.prototype); // Subclass inherits from superclass
LegendTooltip.prototype.constructor = LegendTooltip; // Override the inherited constructor prop.

/**
 * Overrides the parent init method.
 */
LegendTooltip.prototype.init = function() {
	// Call the superclass's init method.
	Tooltip.prototype.init.call(this);
	// Register listeners for legend updates.
	this.registerLegendUpdateEventListeners();
};


/**
 * Registers listener functions to be called on legend valueSet update.
 */
LegendTooltip.prototype.registerLegendUpdateEventListeners = function() {
	this.legend.addUpdateListener(this.onLegendUpdate, this);
};


/**
 * A default method to be executed whenever the legend's valueSet gets updated.
 */
LegendTooltip.prototype.onLegendUpdate = function() {
	var legendValueSet = this.legend.getValueSet();

	// The line chart should take care of hiding or showing the tooltip, as it knows better if it has enough space to show the values or not.
	this.setContentFromLegend();
};


LegendTooltip.prototype.setContentFromLegend = function() {
	var legendValueSet = this.legend.getIterableValueSet();

	// Clear the current content of the tooltip element.
	this.element.html("");

	// Go through each object in the legend's valueSet and display it in the legend.
	for (var valueName in legendValueSet) {

		// Get the color for this value. Default to black color in case nothing was specified.
		var color = legendValueSet[valueName] || "#000000";

		// Append a span element for each value.
		this.element.append("span")
			.attr("class", "legend-value")
			.text(valueName)
			.style("color", color);
	}
};