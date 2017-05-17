<?php

namespace Controllers;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Serializer\SerializerInterface;

use Actions\AbstractBaseAction;
use Manager\Domain\TokenManagerInterface;
use Model\Entity\AdminToken;
use Repository\Domain\TokenRepositoryInterface;

/**
 * @package Controllers
 */
abstract class AbstractBaseController
{
    /**
     * @var \Silex\Application $container
     */
    protected $container;

    /**
     * @var \Symfony\Component\HttpFoundation\Request $request
     */
    protected $request;

    protected $payload;

    /**
     * @var \Model\Entity\Token|null
     */
    protected $token;

    /**
     * @param \Silex\Application $app
     * @param \Symfony\Component\HttpFoundation\Request $internalRequest
     */
    public function __construct(Application $app, Request $internalRequest = null)
    {
        $this->container = $app;
        $this->request   = !empty($internalRequest) ? $internalRequest : $app['request_stack']->getCurrentRequest();

        $this->assertValidateAccessRights($this->request, $app['manager.token'], $this->getRequiredRoleNames());
    }

    /**
     * @return \Model\Entity\Token|null
     */
    public function getToken()
    {
        return $this->token;
    }

    protected function getPayload()
    {
        if ($this->payload === null) {
            /** @var SerializerInterface $serializer */
            $serializer    = $this->getContainer()->offsetGet('serializer');
            $this->payload = $serializer->deserialize(
                $this->getRequest()->getContent(false),
                $this->getPayloadClassName(), 'json');
        }

        return $this->payload;
    }

    /**
     * @throws \Exception
     * @return string
     */
    protected function getPayloadClassName(): string
    {
        throw new \Exception('Not implemented');
    }

    /**
     * @return array
     */
    public function getRequiredRoleNames(): array
    {
        return [];
    }

    /**
     * @return \Twig_Environment
     */
    public function getRenderer(): \Twig_Environment
    {
        return $this->getContainer()->offsetGet('twig');
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param \Manager\Domain\TokenManagerInterface $tokenManager
     * @param array $requiredRoles
     */
    public function assertValidateAccessRights(
        Request $request,
        TokenManagerInterface $tokenManager,
        array $requiredRoles = []
    ) {
        $inputToken = $request->get('_token') ?? '';

        if ($tokenManager->isAdminToken($inputToken)) {
            $this->token = (new AdminToken())->setCustomId($inputToken);
            return;
        }

        if (!$tokenManager->isTokenValid($inputToken, $requiredRoles)) {
            throw new AccessDeniedException('Access denied, please verify the "_token" parameter');
        }

        /** @var TokenRepositoryInterface $repository */
        $repository  = $this->getContainer()->offsetGet('repository.token');
        $this->token = $repository->getTokenByUuid($inputToken);
    }

    /**
     * @return \Silex\Application
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Request
     */
    public function getRequest(): Request
    {
        return $this->request;
    }

    /**
     * @param \Actions\AbstractBaseAction $action
     *
     * @return \Actions\AbstractBaseAction
     */
    protected function getAction(AbstractBaseAction $action)
    {
        $action->setContainer($this->container);
        $action->setController($this);

        return $action;
    }
}
