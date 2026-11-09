<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\euro_office\services;

use humhub\modules\file\libs\FileHelper;
use humhub\modules\file\models\File;

/**
 * Knows which file formats can be opened and edited in the document server.
 *
 * Only Office Open XML formats are editable, because the document server saves them
 * back without conversion. All other supported formats are opened read-only.
 */
final class DocumentFormats
{
    /**
     * @var array<string, array{0: DocumentType, 1: bool}> extension => [document type, editable]
     */
    private const FORMATS = [
        'docx' => [DocumentType::Word, true],
        'doc' => [DocumentType::Word, false],
        'docm' => [DocumentType::Word, false],
        'dotx' => [DocumentType::Word, false],
        'odt' => [DocumentType::Word, false],
        'rtf' => [DocumentType::Word, false],
        'xlsx' => [DocumentType::Cell, true],
        'xls' => [DocumentType::Cell, false],
        'xlsm' => [DocumentType::Cell, false],
        'xltx' => [DocumentType::Cell, false],
        'ods' => [DocumentType::Cell, false],
        'pptx' => [DocumentType::Slide, true],
        'ppt' => [DocumentType::Slide, false],
        'pptm' => [DocumentType::Slide, false],
        'potx' => [DocumentType::Slide, false],
        'odp' => [DocumentType::Slide, false],
    ];

    public static function getExtension(File $file): string
    {
        return strtolower((string)FileHelper::getExtension($file->file_name));
    }

    public static function isSupported(File $file): bool
    {
        return isset(self::FORMATS[self::getExtension($file)]);
    }

    public static function isEditable(File $file): bool
    {
        return self::FORMATS[self::getExtension($file)][1] ?? false;
    }

    public static function getDocumentType(File $file): ?DocumentType
    {
        return self::FORMATS[self::getExtension($file)][0] ?? null;
    }
}
