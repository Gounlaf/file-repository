<?php declare(strict_types=1);

namespace Commands;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @package Commands
 */
class ClearExpiredTokensCommand extends BaseCommand
{
    /**
     * @var int $processed
     */
    private $processed = 0;

    public function configure()
    {
        $this->setName('tokens:expired:clear')
            ->setDescription('Clear all expired tokens')
            ->setHelp('Removes all tokens with expiration date in the past');
    }

    /**
     * @inheritdoc
     */
    public function executeCommand(InputInterface $input, OutputInterface $output)
    {
        /**
         * @var \Repository\Domain\TokenRepositoryInterface $repository
         * @var \Manager\Domain\TokenManagerInterface $manager
         */
        $repository = $this->getApp()['repository.token'];
        $manager    = $this->getApp()['manager.token'];

        $output->writeln('Cleaning up expired tokens...');

        foreach ($repository->findExpiredTokens() as $token) {
            $date = $token->getExpirationDate();
            $id   = $token->getId();

            $manager->removeToken($token);

            $output->writeln(sprintf(
                '[%s] <comment>Removing token %s</comment>',
                $date->format('Y-m-d H:i:s'),
                $id
            ));

            $this->processed++;
        }
    }

    /**
     * @return int
     */
    public function getProcessedAmount(): int
    {
        return $this->processed;
    }
}
