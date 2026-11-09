<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\euro_office\permissions;

use humhub\libs\BasePermission;
use Yii;

class CanUseEuroOffice extends BasePermission
{
    /**
     * @inheritdoc
     */
    protected $moduleId = 'euro-office';

    /**
     * @inheritdoc
     */
    protected $defaultState = self::STATE_ALLOW;

    /**
     * @inheritdoc
     */
    public function getTitle()
    {
        return Yii::t('EuroOfficeModule.base', 'Can use Euro-Office');
    }

    /**
     * @inheritdoc
     */
    public function getDescription()
    {
        return Yii::t('EuroOfficeModule.base', 'Allows the user to create, view and edit office documents.');
    }
}
