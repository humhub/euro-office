<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\euro_office;

use humhub\modules\euro_office\permissions\CanUseEuroOffice;
use humhub\modules\file\handler\FileHandlerCollection;
use Yii;
use yii\base\Event;

class Events
{
    public static function onFileHandlerCollection(Event $event): void
    {
        /** @var FileHandlerCollection $collection */
        $collection = $event->sender;

        /** @var Module $module */
        $module = Yii::$app->getModule('euro-office');

        if (Yii::$app->user->isGuest
            || !Yii::$app->user->can(CanUseEuroOffice::class)
            || !$module->getConfiguration()->isConfigured()) {
            return;
        }

        if ($collection->type === FileHandlerCollection::TYPE_CREATE) {
            $collection->register(new filehandler\CreateFileHandler());
            return;
        }

        if ($collection->file === null) {
            return;
        }

        // Users with write access get the editor only, which also allows them to view the file
        if ($collection->type === FileHandlerCollection::TYPE_EDIT && $module->canEdit($collection->file)) {
            $collection->register(new filehandler\EditFileHandler());
        } elseif ($collection->type === FileHandlerCollection::TYPE_VIEW
            && $module->canView($collection->file)
            && !$module->canEdit($collection->file)) {
            $collection->register(new filehandler\ViewFileHandler());
        }
    }
}
