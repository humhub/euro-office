<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\euro_office\services;

use humhub\modules\file\models\File;

/**
 * The document key identifies an editing session on the document server.
 *
 * All users editing the same file content must get the same key (co-editing), and the key
 * must change as soon as the content changes, otherwise the document server would deliver
 * a cached, outdated version. Deriving it from the file content hash fulfills both
 * requirements without storing any state.
 */
final class DocumentKey
{
    public static function forFile(File $file): string
    {
        return substr(hash('sha256', $file->guid . ':' . $file->getHash()), 0, 40);
    }
}
