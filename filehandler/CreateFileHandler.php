<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\euro_office\filehandler;

use humhub\modules\file\handler\BaseFileHandler;
use humhub\modules\ui\icon\widgets\Icon;
use Yii;
use yii\helpers\Url;

class CreateFileHandler extends BaseFileHandler
{
    /**
     * @inheritdoc
     */
    public function getLinkAttributes()
    {
        return [
            'label' => Icon::get('file-text-o') . Yii::t('EuroOfficeModule.base', 'Create Office document'),
            'data-action-url' => Url::to(['/euro-office/create']),
            'data-action-click' => 'ui.modal.load',
            'data-modal-id' => 'euro-office-modal',
            'data-modal-close' => '',
        ];
    }
}
