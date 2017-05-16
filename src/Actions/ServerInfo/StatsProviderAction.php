<?php declare(strict_types=1);

namespace Actions\ServerInfo;

use \SplFileInfo;

use Actions\AbstractBaseAction;
use Manager\FileRegistry;
use Manager\StorageManager;

/**
 * @package Actions\ServerInfo
 */
class StatsProviderAction extends AbstractBaseAction
{
    /**
     * @var \Manager\FileRegistry
     */
    protected $fileRegistry;

    /**
     * @var \Manager\StorageManager
     */
    protected $storageManager;

    /**
     * StatsProviderAction constructor.
     *
     * @param \Manager\FileRegistry $fileRegistry
     * @param \Manager\StorageManager $storageManager
     */
    public function __construct(FileRegistry $fileRegistry, StorageManager $storageManager)
    {
        $this->fileRegistry = $fileRegistry;
        $this->storageManager = $storageManager;
    }


    /**
     * @return array
     */
    public function execute(): array
    {
        $diskSpace = [];

        foreach ($this->storageManager->getLocalFlysystem() as $k => $fs) {
            $path = new SplFileInfo($fs->getAdapter()->getPathPrefix());

            $diskSpace[$k] = [
                'free'  => disk_free_space($path->getRealPath()),
                'total' => disk_total_space($path->getRealPath()),
            ];
        }

        return [
            // Can't support remote storage
            'disk_space' => $diskSpace,
            'avg_load'   => sys_getloadavg(),
            'storage'    => [
                'elements_count' => $this->fileRegistry->count(),
            ],
        ];
    }
}
