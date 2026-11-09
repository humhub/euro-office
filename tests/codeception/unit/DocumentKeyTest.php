<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace euro_office\unit;

use euro_office\EuroOfficeTestCase;
use humhub\modules\euro_office\services\DocumentKey;

class DocumentKeyTest extends EuroOfficeTestCase
{
    public function testKeyIsStableAndChangesWithContent(): void
    {
        $this->becomeUser('User1');
        $file = $this->createFile();

        $key = DocumentKey::forFile($file);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{40}$/', $key);
        $this->assertSame($key, DocumentKey::forFile($file));

        $file->setStoredFileContent('changed content');
        $this->assertNotSame($key, DocumentKey::forFile($file));
    }

    public function testKeyDiffersForFilesWithSameContent(): void
    {
        $this->becomeUser('User1');

        $this->assertNotSame(
            DocumentKey::forFile($this->createFile()),
            DocumentKey::forFile($this->createFile()),
        );
    }
}
