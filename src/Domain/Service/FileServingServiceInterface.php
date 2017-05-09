<?php declare(strict_types=1);

namespace Domain\Service;

use Symfony\Component\HttpFoundation\Response;

use Model\Entity\File;

/**
 * Outputs the file to the web browser
 * -----------------------------------
 *   - Verifies browser cache
 *   - Builds headers
 *   - Streaming to browser
 *
 * @package Service
 */
interface FileServingServiceInterface
{
    public function buildResponse(File $file): Response;
}
