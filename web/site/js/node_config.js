// Used by the node config form to handle adding and removing embedded node config field forms.

jQuery(document).ready(function() {

    initializeNodeConfigTargetEntityManagers();
    initializeNodeConfigFields();

    // Add bootstrap button classes to button elements of formflow (form wizard).
    $('.craue_formflow_buttons button').addClass('btn btn-default');

    /**
     * Fetches a string 'constant' to be visualized to the user.
     *
     * @param labelAlias The label alias.
     *
     * @returns string the label for the specified alias.
     *
     * @throws Error If the label alias is unknpwn.
     */
    function getLabel(labelAlias) {
        switch (labelAlias) {
            case 'no-field':
                return 'Add a field';
            case 'another-field':
                return 'Add another field';
            case 'delete-field':
                return 'Delete this field';
            case 'no-entity-manager':
                return 'Add an entity manager';
            case 'another-entity-manager':
                return 'Add another entity manager';
            case 'delete-entity-manager':
                return 'Delete this entity manager';
            default:
                throw "Invalid label alias: " + labelAlias;
        }
    }

    /**
     * Initializes the node config target entity managers fieldset, by adding "Add an entity manager" link, as well as "Delete entity manager" links.
     * This function also registers all necessary listeners.
     * This is the starting point of everything related to the node config target entity managers fieldset JS handling.
     */
    function initializeNodeConfigTargetEntityManagers() {
        // Get the fieldset that holds the collection of target entity managers
        var collectionHolder = $('div#config-target-entity-managers');

        // setup an "add another entity manager" link
        var addEntityManagerLink = $('<a href="#" class="add-entity-manager-link btn btn-default pull-right">' + getLabel('no-entity-manager') + '</a>');

        // Add a delete link to all of the existing entity manager elements.
        var entityManagers = collectionHolder.find('> .form-group');
        entityManagers.each(function() {
            addEntityManagerSelectionDeleteLink(collectionHolder, addEntityManagerLink, $(this));
        });

        // Check how many entity managers exist and change the add entity manager link's label based on that.
        var entityManagersCount = entityManagers.length;
        if (entityManagersCount > 0) {
            addEntityManagerLink.text(getLabel('another-entity-manager'));
        }
        // Set a custom data that marks the next allowed index for a possible form that will be added.
        collectionHolder.data('index', entityManagersCount);

        // add the "add another entity manager" anchor and li to the target entity managers fieldset
        collectionHolder.append(addEntityManagerLink);

        // Register an onclick listener for the add entity manager link.
        addEntityManagerLink.on('click', function(e) {
            // prevent the link from creating a "#" on the URL
            e.preventDefault();

            // add a new entity manager selection.
            addEntityManagerSelection(collectionHolder, addEntityManagerLink);
        });
    }

    /**
     * Adds a new embedded NodeConfigFieldForm.
     *
     * @param collectionHolder The main element for the node config target entity managers fieldset.
     * @param addEntityManagerLink The element to be displayed for adding another entity manager selection. It is needed here in order to update its text.
     */
    function addEntityManagerSelection(collectionHolder, addEntityManagerLink) {
        // Get the data-prototype explained earlier
        var prototype = collectionHolder.data('prototype');

        // get the new index
        var index = collectionHolder.data('index');

        // Replace '__name___' in the prototype's HTML to
        // instead be a number based on how many items we have
        var newEntityManagerSelection = $(prototype.replace(/__name__/g, index));

        // increase the index with one for the next item
        collectionHolder.data('index', index + 1);

        // Add also a deletion link to the new entity manager selection.
        addEntityManagerSelectionDeleteLink(collectionHolder, addEntityManagerLink, newEntityManagerSelection);

        // Display the form in the page before the "add another entity manager" link.
        addEntityManagerLink.before(newEntityManagerSelection);

        // Update the label of the add entity manager link.
        addEntityManagerLink.text(getLabel('another-entity-manager'));
    }

    /**
     * Adds a link for removing an embedded entity manager selection form element and registers a listener for it.
     *
     * @param collectionHolder The main element for the node config target entity managers fieldset.
     * @param addEntityManagerLink The element to be displayed for adding another entity manager selection. It is needed here in order to update its text.
     * @param entityManagerSelection The target entity manager selection field to which the delete link should be added.
     */
    function addEntityManagerSelectionDeleteLink(collectionHolder, addEntityManagerLink, entityManagerSelection) {
        var removeEntityManagerLink = $('<a href="#" class="delete-entity-manager-link btn btn-default pull-right">' + getLabel('delete-entity-manager') + '</a>');
        entityManagerSelection.append(removeEntityManagerLink);

        removeEntityManagerLink.on('click', function(e) {
            // prevent the link from creating a "#" on the URL
            e.preventDefault();

            // remove the node config entity manager selection
            entityManagerSelection.remove();

            // If all entity manager selections have been removed, change the label of the add entity manager link.
            if (collectionHolder.find('> .form-group').length < 1) {
                addEntityManagerLink.text(getLabel('no-entity-manager'));
            }
        });
    }

    /**
     * Initializes the node config fields fieldset, by adding "Add a field" link, as well as "Delete field" links.
     * This function also registers all necessary listeners.
     * This is the starting point of everything related to the node config fields fieldset JS handling.
     */
    function initializeNodeConfigFields() {
        // Get the fieldset that holds the collection of node config fields
        var collectionHolder = $('div#config-fields');

        // setup an "add another field" link
        var addFieldLink = $('<a href="#" class="add-field-link btn btn-default pull-right">' + getLabel('no-field') + '</a>');

        // Add a delete link to all of the existing field form elements.
        var fieldForms = collectionHolder.find('> .form-group');
        fieldForms.each(function() {
            addNodeConfigFieldFormDeleteLink(collectionHolder, addFieldLink, $(this));
        });

        // Check how many field forms exist and change the add field link's label based on that.
        var fieldFormsCount = fieldForms.length;
        if (fieldFormsCount > 0) {
            addFieldLink.text(getLabel('another-field'));
        }
        // Set a custom data that marks the next allowed index for a possible form that will be added.
        collectionHolder.data('index', fieldFormsCount);

        // Handle event listeners related to the options fieldset.
        registerOptionsListeners(collectionHolder);

        // add the "add another field" anchor and li to the node config fields fieldset
        collectionHolder.append(addFieldLink);

        // Register an onclick listener for the add field link.
        addFieldLink.on('click', function(e) {
            // prevent the link from creating a "#" on the URL
            e.preventDefault();

            // add a new node config field form.
            addNodeConfigFieldForm(collectionHolder, addFieldLink);
        });
    }

    /**
     * Adds a new embedded NodeConfigFieldForm.
     *
     * @param collectionHolder The main element for the node config fields fieldset.
     * @param addFieldLink The element to be displayed for adding another field form. It is needed here in order to update its text.
     */
    function addNodeConfigFieldForm(collectionHolder, addFieldLink) {
        // Get the data-prototype explained earlier
        var prototype = collectionHolder.data('prototype');

        // get the new index
        var index = collectionHolder.data('index');

        // Replace '__name___' in the prototype's HTML to
        // instead be a number based on how many items we have
        var newForm = $(prototype.replace(/__name__/g, index));

        // increase the index with one for the next item
        collectionHolder.data('index', index + 1);

        // Add also a deletion link to the new form.
        addNodeConfigFieldFormDeleteLink(collectionHolder, addFieldLink, newForm);

        // Display the form in the page before the "add another field" link.
        addFieldLink.before(newForm);

        // Handle event listeners related to the options fieldset.
        // This needs to be done after the new form has been attached to the collectionHolder.
        registerOptionsListeners(newForm);

        // Update the label of the add field link.
        addFieldLink.text(getLabel('another-field'));
    }

    /**
     * Adds a link for removing an embedded NodeConfigFieldForm and registers a listener for it.
     *
     * @param collectionHolder The main element for the node config fields fieldset.
     * @param addFieldLink The element to be displayed for adding another field form. It is needed here in order to update its text.
     * @param fieldForm The embedded field form to which the delete link should be added.
     */
    function addNodeConfigFieldFormDeleteLink(collectionHolder, addFieldLink, fieldForm) {
        // Do not add a remove link if this field is immutable.
        var mutable = (fieldForm.find('input:hidden.mutable').val() ? true : false);
        if (!mutable) {
            return;
        }

        var removeFieldLink = $('<a href="#" class="delete-field-link btn btn-default pull-right">' + getLabel('delete-field') + '</a>');
        fieldForm.append(removeFieldLink);

        removeFieldLink.on('click', function(e) {
            // prevent the link from creating a "#" on the URL
            e.preventDefault();

            // remove the node config field form
            fieldForm.remove();

            // If all field forms have been removed, change the label of the add field link.
            if (collectionHolder.find('> .form-group').length < 1) {
                addFieldLink.text(getLabel('no-field'));
            }
        });
    }

    /**
     * Handles registering listeners for the options fieldset, as well as making initial adjustments.
     *
     * @param collectionHolder The main element for the node config fields fieldset.
     */
    function registerOptionsListeners(collectionHolder) {
        // Collapse the direct children of the options fieldsets at first, except the legend itself.
        collectionHolder.find('.fieldset-options > :not(legend)').hide();
        // Register a listener for collapsing and expanding the options field.
        collectionHolder.find('.fieldset-options > legend').on('click', function() {
            // Expand or collapse the options fieldset elements, except the legend.
            $(this).siblings(':not(legend)').toggle();
            // Update the glyphicon that shows if the fieldset is expanded or collapsed.
            $(this).find('span').toggleClass('glyphicon-expand glyphicon-collapse-down');
        });

        // Search for select elements whose id ends with '_type'.
        $.each(collectionHolder.find('select[id$=_type]'), function(key, value) {
            // First, enable or disable the options fields based on the current type selection by calling the listener function directly.
            onTypeValueChangeListener($(value));
            // Also register a change listener for the type field.
            $(value).change(function () {
                // The type selection has changed. Call the relevant listener.
                onTypeValueChangeListener($(this));
            });
        });

        // Search for input elements whose id ends with '_primaryKey'.
        $.each(collectionHolder.find('input[id$=_primaryKey]'), function(key, value) {
            // Register a change listener for the primary key field.
            $(value).change(function () {
                // The type selection has changed. Call the relevant listener.
                onPrimaryKeyChangeListener($(this));
            });
        });
    }

    /**
     * Listener function to be called when a NodeConfigField type changes.
     *
     * @param typeElement A form element that marks the NodeConfigField type.
     */
    function onTypeValueChangeListener(typeElement) {
        // Get the field group for this type element.
        var fieldGroup = typeElement.closest('.field-group');

        // Enable and disable options input fields based on the new selection.
        enableAndDisableOptionsInputFields(fieldGroup);
    }

    /**
     * Listener function to be called when a NodeConfigOptions primary key attribute changes.
     *
     * @param primaryKeyElement A form element that marks if a NodeConfigField is a primary key.
     */
    function onPrimaryKeyChangeListener(primaryKeyElement) {
        // Get the field group for this type element.
        var fieldGroup = primaryKeyElement.closest('.field-group');

        // Enable and disable options input fields based on the new primary key value.
        enableAndDisableOptionsInputFields(fieldGroup);
    }

    /**
     * Enables or disables option input fields based on the selected type element value.
     *
     * @param fieldGroup A field group element that represents form elements for a single NodeConfigField.
     */
    function enableAndDisableOptionsInputFields(fieldGroup) {
        // Check if these field options are immutable.
        var mutable = (fieldGroup.find('input:hidden.mutable').val() ? true : false);
        if (!mutable) {
            // Do not enable or disable any options input fields.
            return;
        }

        var typeElement = fieldGroup.find('select[id$=_type]');
        var primaryFieldElement = fieldGroup.find('input[id$=_primaryKey]');

        var selectedValues = [
            typeElement.val()
        ];

        if (primaryFieldElement.prop('checked')) {
            // Primary key is checked.
            selectedValues.push('input-primaryKey');
        }

        // Go through all options input elements in the parent field-group, which have classes containing '-enabled'.
        var relevantInputs = fieldGroup.find('.fieldset-options :input[class*=-enabled]');
        $.each(relevantInputs, function(index, input) {
            // Obtain all classes which end with '-enabled'.
            var enabledClasses = $(input).attr('class').match(/\b\S+-enabled(?=\s+|$)/) || [];

            if (enabledClasses.length > 0) {
                // There is a proper '-enabled' class for this item.
                // If it matches any of the selectedValues, it should be enabled.
                // Otherwise it should be disabled.
                var disableField = true;

                for (var i = 0; i < selectedValues.length; i++) {
                    if (enabledClasses.indexOf(selectedValues[i] + '-enabled') >= 0) {
                        // This field should be enabled based on the currently selected values.
                        disableField = false;
                        break;
                    }
                }

                // Disable or enable the field.
                if (disableField) {
                    $(input).attr('disabled', 'disabled');
                }
                else {
                    $(input).removeAttr('disabled');
                }
            }
        });
    }

});