// jQuery document ready.
$(function() {
	var statusData = [];

	var updateIntervalMilliseconds = 30 * 1000;

	fetchActualData();

	function fetchActualData() {
		fetchData();

		// Fetch the data every N seconds.
		setInterval(fetchData, updateIntervalMilliseconds);

		function fetchData() {
			var getParams = {
				limit: 1
			};
			// Make an AJAX get request to fetch the data.
			$.ajax({
				url: 'common/data-fetcher/measurement.php',
				type: 'get',
				data: getParams,
				dataType: 'json',
				success: function(data) {
					try {
						if (!data) {
							data = [];
						}

						onDataUpdate(data);
					}
					catch (err) {
						console.log(err);
					}
				},
				error: function(err) {
					console.log(err);
				}
			});
		}
	}

	var lastFetchedDate = null;

	function onDataUpdate(data) {
		try {
			lastFetchedDate = new Date();
			statusData = data;
			setStatusData();
			fetchQualityConditions();
		}
		catch (err) {
			console.log(err);
		}
	}


	function setStatusData() {
		// If no element could be fetched, leave the status element as it is.
		if (!statusData || !statusData[0]) {
			return;
		}
		// Otherwise, populate the fields.
		
		// Change the title.
		$( "#measurement-description" ).html("Latest measurement: ")

		// Define an empty value text to be displayed if some of the measurements does not exist or is invalid.
		var emptyMeasurementValue = "N/A";

		var measurementFields = [
			{name: "n", field: "measure_n", htmlId: "measurement-n"},
			{name: "pA", field: "measure_pa", htmlId: "measurement-pa"},
			{name: "mg", field: "measure_mg", htmlId: "measurement-mg"}
		];

		for (var i = 0; i < measurementFields.length; i++) {
			var measurementField = measurementFields[i];
			var value = statusData[0][measurementField.field];
			// If the value is invalid, use the empty measurement value.
			if (isNaN(value)) {
				measurementField.value = emptyMeasurementValue;
			}
			else {
				measurementField.value = value;
			}

			// Update the html element's content.
			var content = measurementField.name + ':';
			content += ' <span class="value">' + measurementField.value + '</span>';

			$( "#" + measurementField.htmlId ).html(content)
		}

		// Now handle the measurement date.
		var dateString = emptyMeasurementValue;
		if (!isNaN(statusData[0].timestamp)) {
			var timestampInMilliseconds = statusData[0].timestamp * 1000;
			// Create a date object from the timestamp.
			var date = new Date(timestampInMilliseconds);
			dateString = formatDateToLocalTimeString(date);
		}
		// Update the html element's content.
		var dateContent = 'measured at:';
		dateContent += ' <span class="value">' + dateString + '</span>';
		$( "#measurement-date" ).html(dateContent);

		// Change the time when the measurement was last fetched.
		var lastFetchedDateString = formatDateToLocalTimeString(lastFetchedDate);
		var lastFetchedDateContent = "last fetched at:"
		lastFetchedDateContent += ' <span class="value">' + lastFetchedDateString + '</span>';
		$( "#last-fetched" ).html(lastFetchedDateContent);
	}

	function formatDateToLocalTimeString(date) {
		var dateString = $.datepicker.formatDate('yy-mm-dd', date)
		var h = ('0' + date.getHours()).slice(-2);
		var i = ('0' + date.getMinutes()).slice(-2);
		var s = ('0' + date.getSeconds()).slice(-2);
		dateString += ' ' + h + ":" + i + ":" + s;

		return dateString;
	}

	function fetchQualityConditions() {
		if (statusData && statusData[0] && statusData[0].device_id) {
			var sensorId = statusData[0].device_id;

			// Make an AJAX get request to fetch the conditions.
			$.ajax({
				url: 'conditions/view/' + sensorId,
				type: 'get',
				dataType: 'json',
				success: function(conditionsData) {
					try {
						if (!conditionsData) {
							conditionsData = [];
						}

						onConditionsUpdate(conditionsData);
					}
					catch (err) {
						console.log(err);
					}
				},
				error: function(err) {
					console.log(err);
				}
			});
		}
	}


	function onConditionsUpdate(conditionsData) {
		var groupedConditions = groupConditions(conditionsData);
		var quality = determineConditionsQuality(groupedConditions);
		displayAirQuality(quality);
	}

	function groupConditions(conditionsData) {
		if (conditionsData.length > 0) {
			conditionsData = conditionsData[0];
		}
		var groupedConditions = {};

		var pattern = /^(good|fair)(.*)(Min|Max)(Inclusive)?$/;
		for (var fieldName in conditionsData) {
			var matches = fieldName.match(pattern);

			// If this field is of relevance for the conditions.
			if (matches) {
				var value = conditionsData[fieldName];
				var quality = matches[1];
				if (!groupedConditions[quality]) {
					groupedConditions[quality] = {};
				}

				var field = getMappedConditionFieldName(matches[2]);
				if (!groupedConditions[quality][field]) {
					groupedConditions[quality][field] = {};
				}

				var minOrMax = matches[3];
				if (!groupedConditions[quality][field][minOrMax]) {
					groupedConditions[quality][field][minOrMax] = {};
				}

				var inclusive = matches[4];

				if (inclusive !== undefined) {
					groupedConditions[quality][field][minOrMax]['inclusive'] = value;
				}
				else {
					groupedConditions[quality][field][minOrMax]['value'] = value;
				}
			}
		}

		return groupedConditions;
	}


	function getMappedConditionFieldName(conditionFieldName) {
		var conditionsToMeasurementFieldNames = {
			'MeasureN': 'measure_n',
			'MeasurePa': 'measure_pa',
			'MeasureMg': 'measure_mg'
		}

		var result = conditionsToMeasurementFieldNames[conditionFieldName] || conditionFieldName;

		return result;
	}

	function determineConditionsQuality(groupedConditions) {
		if (statusData.length < 1) {
			return "N/A";
		}

		var data = statusData[0];

		// First check the good conditions.
		var goodConditions = groupedConditions.good;

		var matchesGoodConditions = true;
		// Marks whether there are any good conditions defined at all.
		var goodConditionsAreDefined = false;

		// Go through all the conditions and check if the data matches those that are specified.
		for (var fieldName in goodConditions) {
			var value = data[fieldName];

			var minConditions = goodConditions[fieldName]['Min'];
			var maxConditions = goodConditions[fieldName]['Max'];


			// If the data does not specify that field, assume it matches these conditions.
			if (value == null) {
				if (minConditions.value !== null || maxConditions.value !== null) {
					goodConditionsAreDefined = true;
				}
				continue;
			}

			// If some minimum value was specified.
			if (minConditions.value !== null) {
				goodConditionsAreDefined = true;

				if (minConditions.inclusive) {
					if (value < minConditions.value) {
						matchesGoodConditions = false;
						break;
					}
				}
				else {
					if (value <= minConditions.value) {
						matchesGoodConditions = false;
						break;
					}
				}
			}

			// If some maximum value was specified.
			if (maxConditions.value !== null) {
				goodConditionsAreDefined = true;

				if (maxConditions.inclusive) {
					if (value > maxConditions.value) {
						matchesGoodConditions = false;
						break;
					}
				}
				else {
					if (value >= maxConditions.value) {
						matchesGoodConditions = false;
						break;
					}
				}
			}
		}

		if (matchesGoodConditions || !goodConditionsAreDefined) {
			return "Good";
		}

		// Now check the fair conditions.
		var fairConditions = groupedConditions.fair;

		var matchesFairConditions = true;
		// Marks whether there are any fair conditions defined at all.
		var fairConditionsAreDefined = false;

		// Go through all the conditions and check if the data matches those that are specified.
		for (var fieldName in fairConditions) {
			var value = data[fieldName];

			var minConditions = fairConditions[fieldName]['Min'];
			var maxConditions = fairConditions[fieldName]['Max'];

			// If the data does not specify that field, assume it matches these conditions.
			if (value == null) {
				if (minConditions.value !== null || maxConditions.value !== null) {
					fairConditionsAreDefined = true;
				}
				continue;
			}


			// If some minimum value was specified.
			if (minConditions.value !== null) {
				fairConditionsAreDefined = true;

				if (minConditions.inclusive) {
					if (value < minConditions.value) {
						matchesFairConditions = false;
						break;
					}
				}
				else {
					if (value <= minConditions.value) {
						matchesFairConditions = false;
						break;
					}
				}
			}

			// If some maximum value was specified.
			if (maxConditions.value !== null) {
				fairConditionsAreDefined = true;

				if (maxConditions.inclusive) {
					if (value > maxConditions.value) {
						matchesFairConditions = false;
						break;
					}
				}
				else {
					if (value >= maxConditions.value) {
						matchesFairConditions = false;
						break;
					}
				}
			}
		}

		if (matchesFairConditions || !fairConditionsAreDefined) {
			return "Fair";
		}
		else {
			return "Bad";
		}
	}


	function displayAirQuality(quality) {
		var colorMapping = {
			"Good": "#94B84D",
			"Fair": "#FF8533",
			"Bad": "#D63333"
		};

		var color = colorMapping[quality] || null;

		var content = 'quality:';
		content += ' <span class="value">' + quality + '</span>';

		$( "#measurement-quality" ).html(content);

		$( ".measurement" ).css("background-color", color);
	}
});