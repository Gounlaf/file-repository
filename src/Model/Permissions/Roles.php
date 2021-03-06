<?php declare(strict_types=1);

namespace Model\Permissions;

/**
 * List of roles which could be required for a temporary token
 * -----------------------------------------------------------
 *
 * @package Model\Permissions
 */
final class Roles
{
    const ROLE_UPLOAD_IMAGES = 'upload.images';
    const ROLE_UPLOAD_FILES  = 'upload.files';
    const ROLE_UPLOAD_DOCS   = 'upload.documents';
}
