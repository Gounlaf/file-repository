<?php

namespace Controllers\Upload;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

use Actions\Upload\UploadByHttpActionHandler;
use Controllers\AbstractBaseController;
use Model\AllowedMimeTypes;
use Model\Entity\AdminToken;
use Model\Permissions\Roles;

/**
 * HTTP/HTTPS handler
 * ==================
 *
 * @package Controllers\Upload
 */
class UploadController extends AbstractBaseController implements UploadControllerInterface
{
    /**
     * @var bool
     */
    private $strictUploadMode = true;

    /**
     * @var array
     *
     * Don't get this property directly; use getter instead
     */
    private $allowedMimeTypes = null;

    /**
     * @return JsonResponse|Response
     */
    public function uploadAction(): Response
    {
        $container = $this->getContainer();
        $request   = $this->getRequest();

        $action = new UploadByHttpActionHandler(
            $container->offsetGet('manager.storage'),
            $container->offsetGet('manager.file_registry'),
            $container->offsetGet('manager.tag'),
            $container->offsetGet('storage.filesize')
        );

        // TODO: (re)Support file_name and file_overwrite options
        $action->setData(
            (string)$request->get('file_name'),
            $this->getTags(),
            (bool)$request->get('file_overwrite')
        );

        $action->setStrictUploadMode($this->isStrictUploadMode());
        $action->setAllowedMimes($this->getAllowedMimes());

        $result = $action->execute();

        if ($request->get('back_url') && $result['success'] ?? false) {
            return new RedirectResponse(
                $this->getRedirectUrl((string)$request->get('back_url'), $result)
            );
        }

        return new JsonResponse($result);
    }

    /**
     * @param string $backUrl
     * @param array $result
     *
     * @return string
     */
    private function getRedirectUrl(string $backUrl, array $result): string
    {
        return str_replace(
            ['%257Curl%257C', '%7Curl%7C', '|url|'],
            $result['url'] ?? '',
            $backUrl
        );
    }

    /**
     * @return array
     */
    private function getAllowedMimes()
    {
        if (null !== $this->allowedMimeTypes) {
            return $this->allowedMimeTypes;
        }

        return (new AllowedMimeTypes(
            $this->getContainer()->offsetGet('storage.allowed_types'),
            $this->getToken()->getAllowedMimeTypes()
        ))->all();
    }

    /**
     * @return string[]
     */
    private function getTags()
    {
        if ($this->getToken() instanceof AdminToken) {
            return array_filter((array)$this->getRequest()->get('tags'));
        }

        return $this->getToken()->getTags();
    }

    /**
     * @return string
     */
    public function showFormAction(): string
    {
        return $this->getRenderer()->render('@app/FileUpload.html.twig', [
            'tokenId'          => $this->getRequest()->get('_token'),
            'backUrl'          => (string)$this->getRequest()->get('back_url'),
            'allowedMimeTypes' => $this->getAllowedMimes()->toString(),
        ]);
    }

    /**
     * @inheritdoc
     */
    public function supportsProtocol(string $protocolName): bool
    {
        return in_array($protocolName, ['http', 'https']);
    }

    /**
     * @inheritdoc
     */
    public function getRequiredRoleNames(): array
    {
        return [
            Roles::ROLE_UPLOAD_IMAGES,
            Roles::ROLE_UPLOAD_FILES,
            Roles::ROLE_UPLOAD_DOCS,
        ];
    }

    /**
     * @param boolean $strictUploadMode
     *
     * @return UploadController
     */
    public function setStrictUploadMode(bool $strictUploadMode): UploadController
    {
        $this->strictUploadMode = $strictUploadMode;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isStrictUploadMode(): bool
    {
        return $this->strictUploadMode;
    }

    /**
     * @param array|null $allowedMimeTypes
     *
     * @return UploadController
     */
    public function setAllowedMimeTypes($allowedMimeTypes): UploadController
    {
        $this->allowedMimeTypes = $allowedMimeTypes;

        return $this;
    }
}
