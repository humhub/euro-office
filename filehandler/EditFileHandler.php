<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\euro_office\filehandler;

use humhub\modules\file\handler\BaseFileHandler;
use Yii;
use yii\helpers\Url;

class EditFileHandler extends BaseFileHandler
{
    /**
     * @inheritdoc
     */
    public $position = self::POSITION_TOP;

    /**
     * @inheritdoc
     */
    public function getLinkAttributes()
    {
        return [
            'label' => Yii::t('EuroOfficeModule.base', 'Edit'),
            'data-action-url' => Url::to(['/euro-office/editor', 'guid' => $this->file->guid]),
            'data-action-click' => 'ui.modal.load',
            'data-modal-id' => 'euro-office-modal',
            'data-modal-close' => '',
        ];
    }
}
