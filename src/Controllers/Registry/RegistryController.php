<?php

namespace Controllers\Registry;

use Actions\AbstractBaseAction;
use Actions\Registry\CheckExistAction;
use Actions\Registry\DeleteAction;

use Controllers\AbstractBaseController;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * @package Controllers\Registry
 */
class RegistryController extends AbstractBaseController
{
    /**
     * @return JsonResponse
     */
    public function checkExistsAction()
    {
        $act = $this->getAction(new CheckExistAction(
            $this->getContainer()->offsetGet('manager.file_registry'),
            (string)$this->getRequest()->get('fileName')
        ));

        return $this->getActionResponse($act, 'CheckExistAction');
    }

    /**
     * @return JsonResponse
     */
    public function deleteAction()
    {
        $act = $this->getAction(new DeleteAction(
            $this->getContainer()->offsetGet('manager.file_registry'),
            (string)$this->getRequest()->get('fileId')
        ));

        return $this->getActionResponse($act, 'DeleteAction');
    }

    /**
     * Handles a common exception
     * and returns a response in common format
     *
     * @param AbstractBaseAction $act
     * @param string $actionName
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    private function getActionResponse(AbstractBaseAction $act, string $actionName): JsonResponse
    {
        try {
            return new JsonResponse([
                'success' => true,
                'action'  => $actionName,
                'data'    => $act->execute(),
            ], 200);

        } catch (FileNotFoundException $e) {
            return new JsonResponse([
                'success' => false,
                'action'  => $actionName,
                'message' => 'Requested file does not exists',
            ], 404);
        }
    }
}
