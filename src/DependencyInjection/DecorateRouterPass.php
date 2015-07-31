<?php
namespace Iltar\HttpBundle\DependencyInjection;

use Iltar\HttpBundle\Router\LazyParameterResolver;
use Iltar\HttpBundle\Router\ParameterResolvingRouter;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @author Iltar van der Berg <kjarli@gmail.com>
 */
final class DecorateRouterPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $resolvers = [];

        foreach (array_keys($container->findTaggedServiceIds('router.parameter_resolver')) as $serviceId) {
            $newId = 'iltar.http.parameter_resolving.' . $serviceId;
            $container
                ->register($newId)
                ->setClass(LazyParameterResolver::class)
                ->addArgument(new Reference('service_container'))
                ->addArgument($newId . '.inner')
                ->setPublic(false)
                ->setDecoratedService($serviceId);

            $resolvers[] = new Reference($newId);
        }

        if (empty($resolvers)) {
            return;
        }

        $container
            ->register('iltar.http.parameter_resolving_router')
            ->setClass(ParameterResolvingRouter::class)
            ->addArgument(new Reference('iltar.http.parameter_resolving_router.inner'))
            ->addArgument(new Reference('iltar.http.parameter_resolver_collection'))
            ->setPublic(false)
            ->setDecoratedService('router');

        $container->findDefinition('iltar.http.parameter_resolver_collection')->setArguments([$resolvers]);
    }
}
