<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\euro_office\controllers;

use humhub\modules\admin\components\Controller;
use humhub\modules\admin\permissions\ManageModules;
use humhub\modules\euro_office\Module;
use Yii;

/**
 * @property Module $module
 */
class ConfigController extends Controller
{
    /**
     * @inheritdoc
     */
    protected function getAccessRules()
    {
        return [['permissions' => ManageModules::class]];
    }

    public function actionIndex()
    {
        $model = $this->module->getConfiguration();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            $this->view->saved();
        }

        return $this->render('index', ['model' => $model]);
    }
}
