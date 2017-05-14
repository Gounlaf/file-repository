<?php declare(strict_types=1);

namespace Controllers\Finder;

use Symfony\Component\HttpFoundation\JsonResponse;

use Actions\Finder\FindAction;
use Controllers\AbstractBaseController;
use Model\Request\SearchQueryPayload;

/**
 * @package Controllers\Finder
 *
 * @method \Model\Request\SearchQueryPayload getPayload()
 */
class FinderController extends AbstractBaseController
{
    /**
     * @return string
     */
    protected function getPayloadClassName(): string
    {
        return SearchQueryPayload::class;
    }

    /**
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function findAction()
    {
        $action = new FindAction($this->getContainer()->offsetGet('manager.file_registry'));
        $action->setPayload($this->getPayload());

        return new JsonResponse($action->execute());
    }
}
