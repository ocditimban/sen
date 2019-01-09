<?php

namespace ph\sen\DependencyInjection\Compiler;

use Exception;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Exception\RuntimeException;

class UserPass implements CompilerPassInterface
{
    /**
     * @param ContainerBuilder $container
     * @throws Exception
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('sen.user_manager')) {
            return;
        }
    }
}