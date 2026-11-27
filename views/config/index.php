<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

use humhub\components\View;
use humhub\modules\euro_office\models\Configuration;
use humhub\widgets\bootstrap\Button;
use humhub\widgets\form\ActiveForm;

/* @var View $this */
/* @var Configuration $model */
?>
<div class="panel panel-default">
    <div class="panel-heading"><?= Yii::t('EuroOfficeModule.base', '<strong>Euro-Office</strong> configuration') ?></div>

    <div class="panel-body">
        <p><?= Yii::t('EuroOfficeModule.base', 'Connect HumHub to a Euro-Office or ONLYOFFICE Docs server. The connection is checked when saving.') ?></p>

        <?php if ($model->importedFromOnlyOffice): ?>
            <div class="alert alert-info">
                <?= Yii::t('EuroOfficeModule.base', 'The settings were taken over from the ONLYOFFICE module. Please check and save them.') ?>
            </div>
        <?php endif; ?>

        <?php $form = ActiveForm::begin() ?>

        <?= $form->field($model, 'serverUrl') ?>
        <?= $form->field($model, 'jwtSecret')->passwordInput([
            'autocomplete' => 'new-password',
            // Never send the stored secret back to the browser
            'value' => $model->getJwtSecretInputValue(),
            'placeholder' => $model->hasStoredJwtSecret()
                ? Yii::t('EuroOfficeModule.base', 'Configured. Leave empty to keep the current secret.')
                : null,
        ]) ?>
        <?= $form->field($model, 'jwtHeader') ?>

        <h5><?= Yii::t('EuroOfficeModule.base', 'Advanced') ?></h5>
        <?= $form->field($model, 'internalServerUrl') ?>
        <?= $form->field($model, 'humhubUrl') ?>
        <?= $form->field($model, 'verifyPeerOff')->checkbox() ?>

        <?= Button::save()->submit() ?>

        <?php ActiveForm::end() ?>
    </div>
</div>
