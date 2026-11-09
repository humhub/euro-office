<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace euro_office\unit;

use euro_office\EuroOfficeTestCase;
use humhub\modules\euro_office\services\CallbackHandler;
use humhub\modules\euro_office\services\DocumentServerClient;
use humhub\modules\file\models\File;
use humhub\modules\user\models\User;
use RuntimeException;

class CallbackHandlerTest extends EuroOfficeTestCase
{
    private ?string $downloadedUrl = null;

    public function testSaveOnMustSave(): void
    {
        $this->becomeUser('User1');
        $file = $this->createFile('Report.docx', 'old');
        $editor = User::findOne(['username' => 'User1']);
        $this->logout();

        $this->createHandler('new content')->handle($file, [
            'status' => CallbackHandler::STATUS_MUST_SAVE,
            'url' => 'https://docs.example.com/cache/files/output.docx',
            'filetype' => 'docx',
            'users' => [$editor->guid],
        ]);

        $file = File::findOne($file->id);
        $this->assertSame('new content', $file->store->getContent());
        $this->assertSame($editor->id, (int)$file->updated_by);
        $this->assertSame('https://docs.example.com/cache/files/output.docx', $this->downloadedUrl);
    }

    public function testIgnoreOtherStatus(): void
    {
        $this->becomeUser('User1');
        $file = $this->createFile('Report.docx', 'old');

        foreach ([CallbackHandler::STATUS_EDITING, CallbackHandler::STATUS_CLOSED_WITHOUT_CHANGES, CallbackHandler::STATUS_CORRUPTED] as $status) {
            $this->createHandler('new')->handle($file, ['status' => $status, 'url' => 'https://docs.example.com/x']);
        }

        $this->assertNull($this->downloadedUrl);
        $this->assertSame('old', $file->store->getContent());
    }

    public function testRejectUserWithoutWriteAccess(): void
    {
        $this->becomeUser('User1');
        $file = $this->createFile('Report.docx', 'old');

        $this->expectException(RuntimeException::class);
        $this->createHandler('new')->handle($file, [
            'status' => CallbackHandler::STATUS_MUST_SAVE,
            'url' => 'https://docs.example.com/x',
            'users' => [User::findOne(['username' => 'User2'])->guid],
        ]);
    }

    public function testRejectDifferentFileType(): void
    {
        $this->becomeUser('User1');
        $file = $this->createFile('Report.docx', 'old');

        $this->expectException(RuntimeException::class);
        $this->createHandler('new')->handle($file, [
            'status' => CallbackHandler::STATUS_MUST_SAVE,
            'url' => 'https://docs.example.com/x',
            'filetype' => 'odt',
            'users' => [User::findOne(['username' => 'User1'])->guid],
        ]);
    }

    private function createHandler(string $content): CallbackHandler
    {
        $client = new class ($this->getEuroOfficeModule()->getConfiguration(), $content, $this) extends DocumentServerClient {
            public function __construct($config, private readonly string $content, private readonly CallbackHandlerTest $test)
            {
                parent::__construct($config);
            }

            public function download(string $url): string
            {
                $this->test->setDownloadedUrl($url);
                return $this->content;
            }
        };

        return new CallbackHandler($this->getEuroOfficeModule(), $client);
    }

    public function setDownloadedUrl(string $url): void
    {
        $this->downloadedUrl = $url;
    }
}
