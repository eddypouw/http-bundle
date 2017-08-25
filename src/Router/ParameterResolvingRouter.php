<?php

namespace Iltar\HttpBundle\Router;

use Iltar\HttpBundle\Exception\UnresolvedParameterException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\CacheWarmer\WarmableInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Matcher\RequestMatcherInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouterInterface;

/**
 * @author Iltar van der Berg <kjarli@gmail.com>
 */
final class ParameterResolvingRouter implements RouterInterface, RequestMatcherInterface, WarmableInterface
{
    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var ResolverCollectionInterface
     */
    private $resolverCollection;

    /**
     * @param RouterInterface             $router
     * @param ResolverCollectionInterface $resolverCollection
     */
    public function __construct(RouterInterface $router, ResolverCollectionInterface $resolverCollection)
    {
        $this->router = $router;
        $this->resolverCollection = $resolverCollection;
    }

    /**
     * {@inheritdoc}
     */
    public function setContext(RequestContext $context)
    {
        return $this->router->setContext($context);
    }

    /**
     * {@inheritdoc}
     */
    public function getContext()
    {
        return $this->router->getContext();
    }

    /**
     * {@inheritdoc}
     */
    public function getRouteCollection()
    {
        return $this->router->getRouteCollection();
    }

    /**
     * {@inheritdoc}
     */
    public function generate($name, $unresolvedParameters = [], $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH)
    {
        $parameters = $this->resolverCollection->resolve($unresolvedParameters);
        $unresolved = [];

        foreach ($parameters as $key => $parameter) {
            // should skip parameters that didn't need resolving
            if ($parameter === $unresolvedParameters[$key]) {
                continue;
            }

            // sanity check for parameters that could not be resolved at all
            if (!is_scalar($parameter)) {
                $unresolved[$key] = is_object($parameter) ? get_class($parameter): gettype($parameter);
                continue;
            }

            // sanity check for parameters that could not be resolved into a usable value
            if ('' === (string) $parameter) {
                $unresolved[$key] = gettype($parameter);
                continue;
            }
        }

        if (0 !== count($unresolved)) {
            throw new UnresolvedParameterException($name, $unresolved);
        }

        return $this->router->generate($name, $parameters, $referenceType);
    }

    /**
     * {@inheritdoc}
     */
    public function match($pathinfo)
    {
        return $this->router->match($pathinfo);
    }

    /**
     * {@inheritdoc}
     */
    public function matchRequest(Request $request)
    {
        if (!$this->router instanceof RequestMatcherInterface) {
            throw new \BadMethodCallException('Router has to implement the '.RequestMatcherInterface::class);
        }

        return $this->router->matchRequest($request);
    }

    /**
     * {@inheritdoc}
     */
    public function warmUp($cacheDir)
    {
        if ($this->router instanceof WarmableInterface) {
            $this->router->warmUp($cacheDir);
        }
    }
}
