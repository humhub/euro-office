<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\euro_office\assets;

use humhub\components\assets\AssetBundle;

class Assets extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public $sourcePath = '@euro-office/resources';

    /**
     * @inheritdoc
     */
    public $js = [
        'js/humhub.euro_office.js',
    ];

    /**
     * @inheritdoc
     */
    public $css = [
        'css/humhub.euro_office.css',
    ];

    /**
     * @inheritdoc
     */
    public $publishOptions = [
        'only' => ['css/*', 'js/*'],
    ];
}
