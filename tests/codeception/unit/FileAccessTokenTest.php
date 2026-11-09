<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace euro_office\unit;

use euro_office\EuroOfficeTestCase;
use Firebase\JWT\JWT;
use humhub\modules\euro_office\services\FileAccessToken;

class FileAccessTokenTest extends EuroOfficeTestCase
{
    public function testRoundTrip(): void
    {
        $this->becomeUser('User1');
        $file = $this->createFile();

        $this->assertSame($file->id, FileAccessToken::findFile(FileAccessToken::create($file))?->id);
    }

    public function testInvalidTokens(): void
    {
        $this->becomeUser('User1');
        $file = $this->createFile();

        $this->assertNull(FileAccessToken::findFile(null));
        $this->assertNull(FileAccessToken::findFile(''));
        $this->assertNull(FileAccessToken::findFile('invalid'));

        // Signed with a foreign key, e.g. the document server secret
        $forged = JWT::encode(['fid' => $file->id], self::SECRET, 'HS256');
        $this->assertNull(FileAccessToken::findFile($forged));
    }
}
