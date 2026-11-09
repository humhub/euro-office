<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\euro_office\services;

/**
 * Document types as expected by the `documentType` parameter of the Docs API
 */
enum DocumentType: string
{
    case Word = 'word';
    case Cell = 'cell';
    case Slide = 'slide';
    case Pdf = 'pdf';

    /**
     * Header color of the editor in its default theme
     */
    public function getColor(): string
    {
        return match ($this) {
            self::Word => '#446995',
            self::Cell => '#3A8056',
            self::Slide => '#B75B44',
            self::Pdf => '#AA5252',
        };
    }
}
