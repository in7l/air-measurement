// Used by the JSON to diagram mapping form flow.

jQuery(document).ready(function() {
    // Hide the JSON instructions by default.
    $('#json-instructions').hide();

    // Add an onclick listener used for showing and hiding the json instructions.
    $('#json-instructions-trigger').click(function() {
        $('#json-instructions').toggle();
    });
});