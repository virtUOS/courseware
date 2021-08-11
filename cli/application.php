<?php

require_once __DIR__.'/bootstrap.php';

use Symfony\Component\Console\Application;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Mooc\Command\Command;
use Mooc\Command\MigrateCoursewareCommand;

/**
 * Builds the service container.
 *
 * @return ContainerInterface The container
 */
function buildContainer()
{
    $container = new ContainerBuilder();

    return $container;
}

/**
 * Bootstraps the Application.
 *
 * @return Application The bootstrapped Application
 */
function bootstrapApplication()
{
    $application = new Application();
    registerCommands($application, buildContainer());

    return $application;
}

/**
 * Registers the Application Commands.
 *
 * @param Application        $application The Application to register Commands to
 * @param ContainerInterface $container   The service container to inject into
 *                                        the Commands
 */
function registerCommands(Application $application, ContainerInterface $container)
{
    $commands = array(
        new MigrateCoursewareCommand(),
    );

    foreach ($commands as $command) {
        if ($command instanceof Command) {
            $command->setContainer($container);
        }

        $application->add($command);
    }
}
$application = bootstrapApplication();
$application->run();
set_time_limit(900);
