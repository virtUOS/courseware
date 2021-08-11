<?php

namespace Mooc\Command;

use Symfony\Component\Console\Command\Command as BaseCommand;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for Commands.
 *
 */
abstract class Command extends BaseCommand
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * Sets the service container for the Command.
     *
     * @param ContainerInterface $container The service container
     */
    public function setContainer(ContainerInterface $container)
    {
        $this->container = $container;
    }

}
