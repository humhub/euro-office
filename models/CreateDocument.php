<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\euro_office\models;

use humhub\modules\euro_office\services\DocumentTemplate;
use humhub\modules\file\libs\FileHelper;
use humhub\modules\file\models\File;
use Yii;
use yii\base\Model;

/**
 * Form to create a new document from an empty template
 */
class CreateDocument extends Model
{
    public string $fileName = '';

    public string $fileType = 'docx';

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['fileName', 'fileType'], 'required'],
            ['fileName', 'trim'],
            ['fileName', 'string', 'max' => 200],
            ['fileName', 'match', 'pattern' => '/^[^\/\\\\:*?"<>|]+$/', 'message' => Yii::t('EuroOfficeModule.base', 'The file name contains invalid characters.')],
            ['fileType', 'in', 'range' => array_keys($this->getFileTypes())],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'fileName' => Yii::t('EuroOfficeModule.base', 'File name'),
            'fileType' => Yii::t('EuroOfficeModule.base', 'Type'),
        ];
    }

    /**
     * @return array<string, string> extension => label
     */
    public function getFileTypes(): array
    {
        return [
            'docx' => Yii::t('EuroOfficeModule.base', 'Document (.docx)'),
            'xlsx' => Yii::t('EuroOfficeModule.base', 'Spreadsheet (.xlsx)'),
            'pptx' => Yii::t('EuroOfficeModule.base', 'Presentation (.pptx)'),
        ];
    }

    public function save(): ?File
    {
        if (!$this->validate()) {
            return null;
        }

        $file = new File();
        $file->file_name = $this->fileName . '.' . $this->fileType;
        $file->mime_type = FileHelper::getMimeTypeByExtension($file->file_name);
        $file->size = 0;

        if (!$file->save()) {
            $this->addErrors(['fileName' => $file->getErrorSummary(true)]);
            return null;
        }

        $file->setStoredFileContent(DocumentTemplate::getContent($this->fileType, $this->getLanguage()));

        return $file;
    }

    private function getLanguage(): string
    {
        return Yii::$app->user->getIdentity()?->language ?: Yii::$app->language;
    }
}
