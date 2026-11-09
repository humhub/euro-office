<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

use humhub\components\View;
use humhub\helpers\Html;
use humhub\modules\euro_office\assets\Assets;
use humhub\modules\euro_office\services\DocumentFormats;
use humhub\modules\file\models\File;
use humhub\widgets\modal\Modal;

/* @var View $this */
/* @var File $file */
/* @var bool $edit */
/* @var string $apiUrl */
/* @var array $editorConfig */

Assets::register($this);
$this->registerJsConfig('euro_office', [
    'text' => [
        'error.loadApi' => Yii::t('EuroOfficeModule.base', 'Could not connect to the document server.'),
        'info.saving' => Yii::t('EuroOfficeModule.base', 'Your changes are being saved.'),
    ],
]);

$title = Html::encode($file->file_name);
if ($edit) {
    $title .= Html::tag('small', Yii::t('EuroOfficeModule.base', 'Changes are saved automatically'));
}
?>
<?php Modal::beginDialog([
    'id' => 'euro-office-modal',
    'title' => $title,
    'size' => Modal::SIZE_FULL_SCREEN,
    'headerOptions' => [
        'class' => 'euro-office-modal-header',
        'style' => '--euro-office-color: ' . DocumentFormats::getDocumentType($file)->getColor(),
    ],
]) ?>
<?= Html::beginTag('div', [
    'data-ui-widget' => 'euro_office.Editor',
    'data-ui-init' => '',
    'data-api-url' => $apiUrl,
    'data-config' => $editorConfig,
    'data-edit' => $edit ? 1 : 0,
]) ?>
    <div class="euro-office-placeholder" id="<?= Html::encode('euro-office-editor-' . $file->guid) ?>"></div>
<?= Html::endTag('div') ?>
<?php Modal::endDialog() ?>
