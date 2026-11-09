<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\euro_office\controllers;

use humhub\components\access\ControllerAccess;
use humhub\components\Controller;
use humhub\modules\euro_office\Module;
use humhub\modules\euro_office\permissions\CanUseEuroOffice;
use humhub\modules\euro_office\services\EditorConfigBuilder;
use humhub\modules\file\models\File;
use Yii;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\ServerErrorHttpException;

/**
 * Opens a file in the document editor
 *
 * @property Module $module
 */
class EditorController extends Controller
{
    /**
     * @inheritdoc
     */
    protected function getAccessRules()
    {
        return [
            [ControllerAccess::RULE_LOGGED_IN_ONLY],
            [ControllerAccess::RULE_PERMISSION => [CanUseEuroOffice::class]],
        ];
    }

    public function actionIndex(string $guid, bool $view = false)
    {
        $configuration = $this->module->getConfiguration();
        if (!$configuration->isConfigured()) {
            throw new ServerErrorHttpException(Yii::t('EuroOfficeModule.base', 'The document server is not configured.'));
        }

        $file = File::findOne(['guid' => $guid]);
        if ($file === null) {
            throw new NotFoundHttpException(Yii::t('EuroOfficeModule.base', 'Could not find requested file!'));
        }

        if (!$this->module->canView($file)) {
            throw new ForbiddenHttpException(Yii::t('EuroOfficeModule.base', 'You are not allowed to view this file!'));
        }

        $edit = !$view && $this->module->canEdit($file);
        $builder = new EditorConfigBuilder($configuration);

        return $this->renderAjax('index', [
            'file' => $file,
            'edit' => $edit,
            'apiUrl' => $builder->getApiUrl(),
            'editorConfig' => $builder->build($file, Yii::$app->user->getIdentity(), $edit),
        ]);
    }
}
