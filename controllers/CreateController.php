<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\euro_office\controllers;

use humhub\components\access\ControllerAccess;
use humhub\components\Controller;
use humhub\modules\euro_office\models\CreateDocument;
use humhub\modules\euro_office\Module;
use humhub\modules\euro_office\permissions\CanUseEuroOffice;
use humhub\modules\file\libs\FileHelper;
use Yii;
use yii\helpers\Url;
use yii\web\ServerErrorHttpException;

/**
 * Creates a new, empty document
 *
 * @property Module $module
 */
class CreateController extends Controller
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

    public function actionIndex()
    {
        if (!$this->module->getConfiguration()->isConfigured()) {
            throw new ServerErrorHttpException(Yii::t('EuroOfficeModule.base', 'The document server is not configured.'));
        }

        $model = new CreateDocument();

        if (!$model->load(Yii::$app->request->post())) {
            return $this->renderAjax('index', ['model' => $model]);
        }

        $file = $model->save();
        if ($file === null) {
            return $this->asJson([
                'success' => false,
                'output' => $this->renderAjax('index', ['model' => $model]),
            ]);
        }

        return $this->asJson([
            'success' => true,
            'file' => FileHelper::getFileInfos($file),
            'editorUrl' => Url::to(['/euro-office/editor', 'guid' => $file->guid]),
        ]);
    }
}
