<?php declare(strict_types=1);

namespace Controllers\Auth;

use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\HttpFoundation\JsonResponse;

use Commands\ClearExpiredTokensCommand;
use Controllers\AbstractBaseController;

/**
 * Clearing expired tokens
 * =======================
 *
 * @package Controllers\Auth
 */
class ExpiredTokensController extends AbstractBaseController
{
    /**
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function clearExpiredTokensAction(): JsonResponse
    {
        $command = new ClearExpiredTokensCommand();
        $command->setApp($this->getContainer());
        $command->executeCommand(new StringInput(''), new NullOutput());

        return new JsonResponse(['success' => true, 'processed' => $command->getProcessedAmount()]);
    }
}
