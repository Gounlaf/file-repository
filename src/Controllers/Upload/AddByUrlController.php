<?php

namespace Controllers\Upload;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

use Actions\Upload\AddByUrlActionHandler;
use Controllers\AbstractBaseController;
use Model\Request\AddByUrlPayload;

/**
 * HTTP/HTTPS handler
 * ==================
 *
 * @package Controllers\Upload
 *
 * @method \Model\Request\AddByUrlPayload getPayload()
 */
class AddByUrlController extends AbstractBaseController implements UploadControllerInterface
{
    /**
     * @return string
     */
    protected function getPayloadClassName(): string
    {
        return AddByUrlPayload::class;
    }

    /**
     * @return JsonResponse|Response
     */
    public function uploadAction(): Response
    {
        $container = $this->getContainer();

        $action = new AddByUrlActionHandler(
            $container->offsetGet('manager.storage'),
            $container->offsetGet('manager.file_registry'),
            $container->offsetGet('manager.tag')
        );

        $payload = $this->getPayload();

        $action->setContainer($container)
            ->setController($this)
            ->setData(
                $payload->getFileUrl(),
                array_filter($payload->getTags())
            );

        return new JsonResponse($action->execute());
    }

    /**
     * @inheritdoc
     */
    public function supportsProtocol(string $protocolName): bool
    {
        return in_array($protocolName, ['http', 'https']);
    }
}
