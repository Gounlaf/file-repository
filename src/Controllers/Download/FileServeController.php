<?php declare(strict_types=1);

namespace Controllers\Download;

use Doctrine\ORM\EntityNotFoundException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Controllers\AbstractBaseController;
use Manager\Domain\TokenManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @package Controllers\Upload
 */
class FileServeController extends AbstractBaseController
{
    /**
     * Everyone could download images, as those are public
     *
     * @inheritdoc
     */
    public function assertValidateAccessRights(
        Request $request,
        TokenManagerInterface $tokenManager,
        array $requiredRoles = []
    ) {
        return;
    }

    /**
     * TODO: Replace $imageName with "filePublicId"
     *
     * @param string|null $imageName
     *
     * @return string
     */
    public function downloadAction($imageName = null)
    {
        // TODO: Manage case where there is "image_file_url" in request (don't found yet when it happens)
        $container = $this->getContainer();

        /** @var $fileServe \Domain\Service\FileServingServiceInterface */
        $fileServe = $container->offsetGet('service.file.serve');
        /** @var $registry \Repository\FileRepository */
        $registry = $container->offsetGet('repository.file');

        try {
            return $fileServe->buildResponse($registry->getFileByPublicId($imageName));
        } catch (EntityNotFoundException $e) {
            return JsonResponse::create([
                'success' => false,
                'code'    => 404,
                'message' => 'File not found in the registry',
            ], Response::HTTP_NOT_FOUND);
        }
    }
}
