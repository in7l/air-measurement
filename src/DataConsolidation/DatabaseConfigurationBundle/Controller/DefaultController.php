<?php

namespace DataConsolidation\DatabaseConfigurationBundle\Controller;

use DataConsolidation\DatabaseConfigurationBundle\Entity\DatabaseConnectionConfiguration;
use DataConsolidation\DatabaseConfigurationBundle\Form\Type\CreateConnectionFormType;
use DataConsolidation\DatabaseConfigurationBundle\Form\Type\ChangeConnectionPasswordFormType;
use DataConsolidation\DatabaseConfigurationBundle\Form\Type\EditConnectionFormType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class DefaultController extends Controller
{
    public function indexAction()
    {
        return $this->render('DataConsolidationDatabaseConfigurationBundle:Default:index.html.twig');
    }

    /**
     * Handles listing of all known database connection configurations defined in this bundle.
     */
    public function listAction()
    {
        $databaseConfigurator = $this->get('data_consolidation.database_configurator');
        $databaseConnectionConfigurations = $databaseConfigurator->getAllDatabaseConnectionConfigurations();
        return $this->render('DataConsolidationDatabaseConfigurationBundle:Default:list.html.twig', array(
            'database_configurations' => $databaseConnectionConfigurations,
        ));
    }

    /**
     * Handles creating new database connection configurations.
     */
    public function addAction(Request $request)
    {
        $databaseConnectionConfiguration = new DatabaseConnectionConfiguration();
        $form = $this->createForm(CreateConnectionFormType::class, $databaseConnectionConfiguration, array(
            'label' => false,
        ));

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Valid form submission.
            $databaseConfigurator = $this->get('data_consolidation.database_configurator');
            $databaseConfigurator->addDatabaseConnection($databaseConnectionConfiguration);
            // Obtain the connection alias that was associated with the new database connection.
            $connectionAlias = $databaseConnectionConfiguration->getConnectionAlias();

            // Add a flash message marking the successful addition of the new database connection config.
            $this->addFlash('notice', sprintf("Added a new database connection with alias '%s'. Remember to clear cache to register the changes.", $connectionAlias));

            return $this->redirectToRoute('data_consolidation.database_configuration.view', array(
                'connection_alias' => $connectionAlias,
            ));
        }

        return $this->render('DataConsolidationDatabaseConfigurationBundle:Default:add_configuration_form.html.twig', array(
            'form' => $form->createView(),
        ));
    }

    /**
     * Fetches database connection info.
     *
     * @param string $connection_alias The database connection configuration alias.
     */
    public function viewAction($connection_alias)
    {
        $databaseConfigurator = $this->get('data_consolidation.database_configurator');
        // Check if this is a valid connection alias.
        $connectionNames = $databaseConfigurator->getDatabaseConnectionNames();
        if (!in_array($connection_alias, $connectionNames)) {
            // Could not find a connection with this alias.
            throw $this->createNotFoundException(sprintf("The connection '%s' does not exist.", $connection_alias));
        }

        $databaseConnectionConfiguration = $databaseConfigurator->getDatabaseConnectionConfiguration($connection_alias);
        return $this->render('DataConsolidationDatabaseConfigurationBundle:Default:view.html.twig', array(
            'database_configuration' => $databaseConnectionConfiguration,
            'tab_items' => $this->getTabItems('view', $connection_alias),
        ));
    }

    /**
     * Edits database connection info.
     *
     * @param string $connection_alias The database connection configuration alias.
     */
    public function editAction($connection_alias, Request $request)
    {
        $databaseConfigurator = $this->get('data_consolidation.database_configurator');
        // Check if this is a valid connection alias.
        $connectionNames = $databaseConfigurator->getDatabaseConnectionNames();
        if (!in_array($connection_alias, $connectionNames)) {
            // Could not find a connection with this alias.
            throw $this->createNotFoundException(sprintf("The connection '%s' does not exist.", $connection_alias));
        }

        // Get the existing configuration.
        $databaseConnectionConfiguration = $databaseConfigurator->getDatabaseConnectionConfiguration($connection_alias);
        // Display the edit form.
        $form = $this->createForm(EditConnectionFormType::class, $databaseConnectionConfiguration, array(
            'label' => false,
        ));

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Valid form submission.
            $databaseConfigurator->editDatabaseConnection($databaseConnectionConfiguration);

            // Add a flash message marking the successful change of the database connection config.
            $this->addFlash('notice', sprintf("Saved changes for database connection with alias '%s'. Remember to clear cache to register the changes.", $connection_alias));

            return $this->redirectToRoute('data_consolidation.database_configuration.view', array(
              'connection_alias' => $connection_alias,
            ));
        }

        return $this->render('DataConsolidationDatabaseConfigurationBundle:Default:edit_configuration_form.html.twig', array(
            'form' => $form->createView(),
            'database_configuration' => array(
                // Only add certain relevant fields. The rest will be rendered directly in the form.
                'connectionAlias' => $connection_alias,
            ),
            'tab_items' => $this->getTabItems('edit', $connection_alias),
        ));
    }

    /**
     * Changes the password for an existing database connection configuration.
     *
     * @param string $connection_alias The database connection configuration alias.
     * @param Request $request
     */
    public function changePasswordAction($connection_alias, Request $request)
    {
        $databaseConfigurator = $this->get('data_consolidation.database_configurator');
        // Check if this is a valid connection alias.
        $connectionNames = $databaseConfigurator->getDatabaseConnectionNames();
        if (!in_array($connection_alias, $connectionNames)) {
            // Could not find a connection with this alias.
            throw $this->createNotFoundException(sprintf("The connection '%s' does not exist.", $connection_alias));
        }

        // Get the existing configuration.
        $databaseConnectionConfiguration = $databaseConfigurator->getDatabaseConnectionConfiguration($connection_alias);
        // Display the change password form.
        $form = $this->createForm(ChangeConnectionPasswordFormType::class, $databaseConnectionConfiguration, array(
            'label' => false,
        ));

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Valid form submission.
            $databaseConfigurator->editDatabaseConnection($databaseConnectionConfiguration);

            // Add a flash message marking the successful password change for the database connection config.
            $this->addFlash('notice', sprintf("Changed password for database connection with alias '%s'. Remember to clear cache to register the changes.", $connection_alias));

            return $this->redirectToRoute('data_consolidation.database_configuration.view', array(
                'connection_alias' => $connection_alias,
            ));
        }

        return $this->render('DataConsolidationDatabaseConfigurationBundle:Default:change_password_form.html.twig', array(
            'form' => $form->createView(),
            'database_configuration' => array(
                // Only add certain relevant fields. The rest will be rendered directly in the form.
                'connectionAlias' => $connection_alias,
            ),
            'tab_items' => $this->getTabItems('change_password', $connection_alias),
        ));
    }

    /**
     * Confirmation form for deleting a database connection configuration.
     *
     * @param string $connection_alias The database connection configuration alias.
     * @param Request $request
     */
    public function deleteAction($connection_alias)
    {
        $databaseConfigurator = $this->get('data_consolidation.database_configurator');
        // Check if this is a valid connection alias.
        $connectionNames = $databaseConfigurator->getDatabaseConnectionNames();
        if (!in_array($connection_alias, $connectionNames)) {
            // Could not find a connection with this alias.
            throw $this->createNotFoundException(sprintf("The connection '%s' does not exist.", $connection_alias));
        }

        // Use the contrib ConfirmBundle to display a confirmation for the deletion.
        $options = array(
            'message' => sprintf("Are you sure you want to delete the database connection configuration for '%s'?", $connection_alias),
            'warning' => 'The deletion cannot be undone!',
            'confirm_button_text' => 'Delete',
            'confirm_action' => array($this, 'delete'),
            'confirm_action_args' => array(
                'connectionAlias' => $connection_alias,
            ),
            'cancel_link_text' => 'Cancel',
            'cancel_url' => $this->generateUrl('data_consolidation.database_configuration.view', array(
                'connection_alias' => $connection_alias,
            )),
        );

        return $this->forward('ConfirmBundle:Confirm:confirm', array('options' => $options));
    }

    /**
     * Helper for the deleteAction confirmation.
     *
     * Handles the actual deletion of a database connection configuration, once the user has confirmed that is what they intend to do.
     *
     * @param array $args Arguments forwarded from the deletion confirmation. It is expected that this contains the following keys:
     *  'connectionAlias' => The connection alias to be deleted.
     */
    public function delete($args)
    {
        // Get the connection alias passed as an argument.
        $connectionAlias = isset($args['connectionAlias']) ? $args['connectionAlias'] : '';
        $databaseConfigurator = $this->get('data_consolidation.database_configurator');
        // Check if this is a valid connection alias.
        $connectionNames = $databaseConfigurator->getDatabaseConnectionNames();
        if (!in_array($connectionAlias, $connectionNames)) {
            // Could not find a connection with this alias.
            throw $this->createNotFoundException(sprintf("The connection '%s' does not exist.", $connectionAlias));
        }

        $databaseConfigurator->removeDatabaseConnection($connectionAlias);
        // Add a flash message marking the successful database connection configuration deletion.
        $this->addFlash('notice', sprintf("Deleted the configuration for database connection with alias '%s'. Remember to clear cache to register the changes.", $connectionAlias));

        return $this->redirectToRoute('data_consolidation.database_configuration.list');
    }

    /**
     * Lets users clear cache which is needed after each database configuration change.
     */
    public function clearCacheAction()
    {
        $commandTools = $this->get('data_consolidation.command_tools');
        $result = $commandTools->clearCache();

        if ($result) {
            // Add a flash message marking the successful cache clear.
            $this->addFlash('notice', "Successfully cleared cache.");
        }
        else {
            // Add a flash message marking the failed cache clear.
            $this->addFlash('warning', "Failed to clear cache.");
        }

        return $this->redirectToRoute('data_consolidation.database_configuration.index');
    }


    /**
     * Fetches tab items for the use in twig templates that mark different actions for a database connection configuration.
     *
     * @param string $currentAction The current action, e.g. 'view' or 'edit'.
     * @param string $connectionAlias The database configuration connection alias.
     *
     * @return array Tab items formatted in the way expected in the twig templates.
     */
    private function getTabItems($currentAction, $connectionAlias)
    {
        $tabItems = array(
            'view' => array(
                'url' => $this->generateUrl('data_consolidation.database_configuration.view', array(
                    'connection_alias' => $connectionAlias,
                )),
                'name' => 'View',
            ),
            'edit' => array(
                'url' => $this->generateUrl('data_consolidation.database_configuration.edit', array(
                    'connection_alias' => $connectionAlias,
                )),
                'name' => 'Edit'
            ),
            'change_password' => array(
                'url' => $this->generateUrl('data_consolidation.database_configuration.change_password', array(
                    'connection_alias' => $connectionAlias,
                )),
                'name' => 'Change password'
            ),
            'delete' => array(
                'url' => $this->generateUrl('data_consolidation.database_configuration.delete', array(
                    'connection_alias' => $connectionAlias,
                )),
                'name' => 'Delete'
            ),
        );

        // Mark the current action as active, if it is a valid one.
        $currentAction = strtolower($currentAction);
        if (!empty($tabItems[$currentAction])) {
            $tabItems[$currentAction]['active'] = TRUE;
            // Also change the URL to '#' since the user is on that page already.
            $tabItems[$currentAction]['url'] = '#';
        }

        return $tabItems;
    }
}
