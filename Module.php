<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\euro_office;

use humhub\modules\euro_office\models\Configuration;
use humhub\modules\euro_office\permissions\CanUseEuroOffice;
use humhub\modules\euro_office\services\DocumentFormats;
use humhub\modules\euro_office\services\DocumentType;
use humhub\modules\file\models\File;
use humhub\modules\user\models\User;
use Yii;
use yii\helpers\Url;

class Module extends \humhub\components\Module
{
    /**
     * @var int Timeout in seconds for server-to-server requests to the document server
     */
    public int $requestTimeout = 30;

    private ?Configuration $configuration = null;

    /**
     * @inheritdoc
     */
    public function getConfigUrl()
    {
        return Url::to(['/euro-office/config']);
    }

    /**
     * @inheritdoc
     */
    public function getPermissions($contentContainer = null)
    {
        if ($contentContainer === null) {
            return [new CanUseEuroOffice()];
        }

        return [];
    }

    public function getConfiguration(): Configuration
    {
        if ($this->configuration === null) {
            $this->configuration = new Configuration(['settingsManager' => $this->settings]);
            $this->configuration->loadBySettings();
        }

        return $this->configuration;
    }

    public function canView(File $file, User|int|null $user = null): bool
    {
        return DocumentFormats::isSupported($file)
            && !$this->isLeftToPdfViewer($file)
            && $file->canView($user)
            && $file->store->has();
    }

    /**
     * PDF files are opened by the PDF Viewer module, if it is enabled
     */
    private function isLeftToPdfViewer(File $file): bool
    {
        return DocumentFormats::getDocumentType($file) === DocumentType::Pdf
            && Yii::$app->hasModule('pdf-viewer');
    }

    /**
     * Editing a file requires the same permission as replacing it (e.g. uploading a new version)
     */
    public function canEdit(File $file, User|int|null $user = null): bool
    {
        return DocumentFormats::isEditable($file)
            && $this->canView($file, $user)
            && $file->canDelete($user);
    }
}
