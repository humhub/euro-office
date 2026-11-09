<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

use humhub\modules\euro_office\Events;
use humhub\modules\euro_office\Module;
use humhub\modules\file\handler\FileHandlerCollection;

return [
    'id' => 'euro-office',
    'class' => Module::class,
    'namespace' => 'humhub\modules\euro_office',
    'events' => [
        [FileHandlerCollection::class, FileHandlerCollection::EVENT_INIT, [Events::class, 'onFileHandlerCollection']],
    ],
];
