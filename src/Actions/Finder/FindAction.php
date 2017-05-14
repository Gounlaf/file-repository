<?php declare(strict_types=1);

namespace Actions\Finder;

use \DateTime;

use Actions\AbstractBaseAction;
use Actions\Exception\InvalidStateException;
use Manager\FileRegistry;
use Model\Entity\Tag;
use Model\Request\SearchQueryPayload;

/**
 * @package Actions\Registry
 */
class FindAction extends AbstractBaseAction
{
    /**
     * @var \Manager\FileRegistry
     */
    protected $registry;

    /**
     * @var \Model\Request\SearchQueryPayload
     */
    protected $payload;

    /**
     * @param \Manager\FileRegistry $registry
     */
    public function __construct(FileRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * @param \Model\Entity\File[] $files
     *
     * @return array
     */
    private function remapFilesToResults(array $files): array
    {
        $results = [];

        foreach ($files as $file) {
            $results[$file->getPublicId()] = [
                'name'         => $file->getFileName(),
                'content_hash' => $file->getContentHash(),
                'mime_type'    => $file->getMimeType(),
                'tags'         => array_map(function (Tag $tag) {
                    return $tag->getName();
                }, $file->getTags()->toArray()),
                'date_added'   => $file->getDateAdded()->format(DateTime::ISO8601),
                'url'          => $this->registry->getFileUrl($file),
            ];
        }

        return $results;
    }

    /**
     * @throws \Actions\Exception\InvalidStateException
     *
     * @return array
     */
    public function execute(): array
    {
        if (empty($this->payload)) {
            throw new InvalidStateException('Missing search parameters (payload)');
        }

        $files = $this->registry->findBySearchQuery($this->payload);

        $max = $files->count();

        return [
            'success'     => true,
            'results'     => $this->remapFilesToResults($files->getIterator()->getArrayCopy()),
            'max_results' => $max,
            'pages'       => $max > 0 ? ceil($max / $this->payload->getLimit()) : 0,
        ];
    }

    /**
     * @param SearchQueryPayload $payload
     *
     * @return \Actions\Finder\FindAction
     */
    public function setPayload(SearchQueryPayload $payload): FindAction
    {
        $this->payload = $payload;

        return $this;
    }
}
