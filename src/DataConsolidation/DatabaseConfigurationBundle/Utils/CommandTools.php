<?php
/**
 * Created by PhpStorm.
 * User: ordwvr
 * Date: 3/17/16
 * Time: 12:04 AM
 */

namespace DataConsolidation\DatabaseConfigurationBundle\Utils;

use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Bundle\FrameworkBundle\Command\CacheClearCommand;

/**
 * Class CommandTools
 *
 * Handles execution of commands that are normally executed in cli.
 *
 * @package DataConsolidation\DatabaseConfigurationBundle\Utils
 */
class CommandTools
{
    // Use a trait that allows setting a service container.
    use ContainerAwareTrait;


    public function clearCache()
    {
        if (empty($this->container)) {
            // The service container is unavailable. Not possible to clear cache.
            return FALSE;
        }

        // Create a clear cache command and pass the service container to it.
        $command = new CacheClearCommand();
        $command->setContainer($this->container);

        // Determine the current environment, e.g. "dev" or "prod".
        $environment = $this->container->getParameter('kernel.environment');
        $input = new ArgvInput(array(
            '--env=' . $environment,
        ));

        $output = new BufferedOutput();
        $command->run($input, $output);

        $content = $output->fetch();
        // The output message should contain: "was successfully cleared" on success.
        if (strpos($content, 'was successfully cleared') !== FALSE) {
            return TRUE;
        }
        else {
            // Failed to clear cache.
            return FALSE;
        }
    }

}
