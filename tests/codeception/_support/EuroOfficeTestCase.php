<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace euro_office;

use humhub\modules\euro_office\Module;
use humhub\modules\file\models\File;
use tests\codeception\_support\HumHubDbTestCase;
use Yii;

abstract class EuroOfficeTestCase extends HumHubDbTestCase
{
    protected const SECRET = 'test-secret-with-at-least-32-characters';

    protected function _before()
    {
        parent::_before();

        $module = $this->getEuroOfficeModule();
        $module->settings->set('serverUrl', 'https://docs.example.com');
        $module->settings->set('jwtSecret', self::SECRET);
        $module->settings->set('jwtHeader', 'Authorization');
    }

    protected function getEuroOfficeModule(): Module
    {
        return Yii::$app->getModule('euro-office');
    }

    protected function createFile(string $fileName = 'test.docx', string $content = 'content'): File
    {
        $file = new File();
        $file->file_name = $fileName;
        $file->mime_type = 'application/octet-stream';
        $file->size = 0;
        $this->assertTrue($file->save());
        $file->setStoredFileContent($content);

        return $file;
    }
}
