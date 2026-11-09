<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

use humhub\components\View;
use humhub\modules\euro_office\assets\Assets;
use humhub\modules\euro_office\models\CreateDocument;
use humhub\widgets\modal\Modal;
use humhub\widgets\modal\ModalButton;

/* @var View $this */
/* @var CreateDocument $model */

Assets::register($this);
?>
<?php $form = Modal::beginFormDialog([
    'title' => Yii::t('EuroOfficeModule.base', '<strong>Create</strong> Office document'),
    'footer' => ModalButton::cancel() . ModalButton::save(Yii::t('EuroOfficeModule.base', 'Create'))->submit()->action('euro_office.createSubmit'),
]) ?>
    <?= $form->field($model, 'fileName')->textInput(['autofocus' => '']) ?>
    <?= $form->field($model, 'fileType')->radioList($model->getFileTypes()) ?>
<?php Modal::endFormDialog() ?>
