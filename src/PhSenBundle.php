<?php
namespace ph\sen;

use ph\sen\DependencyInjection\Compiler\UserPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class PhSenBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new UserPass());
    }
}