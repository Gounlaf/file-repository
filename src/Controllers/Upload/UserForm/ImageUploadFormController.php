<?php

namespace Controllers\Upload\UserForm;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

use Actions\Upload\UserForm\Base64UploadAction;
use Controllers\AbstractBaseController;
use Controllers\Upload\UploadController;
use Exception\Upload\UploadException;
use Model\Entity\Token;
use Model\Permissions\Roles;
use Model\Request\ImageJsonPayload;

/**
 * User form handler: Image upload
 * ===============================
 *
 * @package Controllers\Upload
 *
 * @method \Model\Request\ImageJsonPayload getPayload()
 */
class ImageUploadFormController extends AbstractBaseController
{
    /**
     * @return string
     */
    public function showFormAction(): string
    {
        return $this->getRenderer()->render('@app/ImageUpload.html.twig', [
            'tokenId'     => $this->request->get('_token'),
            'backUrl'     => $this->request->get('back_url'),
            'aspectRatio' => $this->getAspectRatio((float)$this->request->get('aspectRatio'))
        ]);
    }

    /**
     * @return array
     */
    public function getRequiredRoleNames(): array
    {
        return [Roles::ROLE_UPLOAD_IMAGES];
    }

    /**
     * Normalize input aspect ratio
     *
     * @param float $ratio
     *
     * @return float
     */
    protected function getAspectRatio(float $ratio): float
    {
        if ($ratio < 0.5 || $ratio > 3.5) {
            return 16 / 9;
        }

        return $ratio;
    }

    /**
     * @return string
     */
    protected function getPayloadClassName(): string
    {
        return ImageJsonPayload::class;
    }

    /**
     * This upload action will emulate the request
     * and push it to the regular upload controller
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function uploadAction()
    {
        // this will mock our request
        $userUploadAction = new Base64UploadAction($this->getPayload());
        $result           = $userUploadAction->execute();

        // use a regular upload controller
        $request = new Request([
            '_token'    => $this->request->get('_token'),
            'file_name' => $result['fileName'],
            'tags'      => $this->token instanceof Token ? $this->token->getTags() : [],
        ]);

        try {
            $uploadController = new UploadController($this->getContainer(), $request);
            $uploadController->setStrictUploadMode(false);
            $uploadController->setAllowedMimeTypes([
                'jpg' => 'image/jpeg',
                'png' => 'image/png',
                'gif' => 'image/gif',
            ]);

            return $uploadController->uploadAction();
        } catch (UploadException $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Upload failed',
                'details' => $e->getMessage(),
            ], 400);
        }
    }
}
