<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\euro_office\services;

use RuntimeException;
use Yii;
use ZipArchive;

/**
 * Provides the content of new, empty documents.
 *
 * The document language of the templates is set to the language of the user,
 * so that e.g. the spell check of the editor uses the correct language.
 */
final class DocumentTemplate
{
    public const TYPES = ['docx', 'xlsx', 'pptx'];

    private const TEMPLATE_LANGUAGE = 'en-US';

    /**
     * Files containing language attributes, per document type
     */
    private const LANGUAGE_FILES = [
        'docx' => '~^word/styles\.xml$~',
        'pptx' => '~^ppt/(presentation|slideMasters/.*|slideLayouts/.*|slides/.*)\.xml$~',
    ];

    /**
     * HumHub languages whose region does not simply equal the language code
     */
    private const REGIONS = [
        'en' => 'en-US',
        'ca' => 'ca-ES',
        'cs' => 'cs-CZ',
        'da' => 'da-DK',
        'el' => 'el-GR',
        'et' => 'et-EE',
        'fa-IR' => 'fa-IR',
        'he' => 'he-IL',
        'ja' => 'ja-JP',
        'ko' => 'ko-KR',
        'nb-NO' => 'nb-NO',
        'nn-NO' => 'nn-NO',
        'sl' => 'sl-SI',
        'sq' => 'sq-AL',
        'sr' => 'sr-Latn-RS',
        'sv' => 'sv-SE',
        'uk' => 'uk-UA',
        'vi' => 'vi-VN',
    ];

    public static function getContent(string $type, string $language): string
    {
        if (!in_array($type, self::TYPES, true)) {
            throw new RuntimeException('Unknown document type: ' . $type);
        }

        $path = Yii::getAlias('@euro-office/resources/templates/new.' . $type);
        $locale = self::toLocale($language);

        if (!isset(self::LANGUAGE_FILES[$type]) || $locale === self::TEMPLATE_LANGUAGE) {
            return file_get_contents($path);
        }

        $tempFile = tempnam(sys_get_temp_dir(), 'euro-office');
        try {
            copy($path, $tempFile);
            self::setLanguage($tempFile, self::LANGUAGE_FILES[$type], $locale);
            return file_get_contents($tempFile);
        } finally {
            @unlink($tempFile);
        }
    }

    /**
     * Converts a HumHub language (e.g. "de", "pt-BR", "en-GB") to a locale (e.g. "de-DE")
     */
    public static function toLocale(string $language): string
    {
        $language = str_replace('_', '-', $language);

        if (isset(self::REGIONS[$language])) {
            return self::REGIONS[$language];
        }

        if (preg_match('/^[a-z]{2,3}-[A-Za-z]{2,4}$/', $language)) {
            return $language;
        }

        if (preg_match('/^[a-z]{2,3}$/', $language)) {
            return $language . '-' . strtoupper($language);
        }

        return self::TEMPLATE_LANGUAGE;
    }

    private static function setLanguage(string $file, string $pattern, string $locale): void
    {
        $zip = new ZipArchive();
        if ($zip->open($file) !== true) {
            throw new RuntimeException('Could not open document template');
        }

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (!preg_match($pattern, $name)) {
                continue;
            }

            $xml = $zip->getFromIndex($i);
            $replaced = str_replace(
                ['w:val="' . self::TEMPLATE_LANGUAGE . '"', 'lang="' . self::TEMPLATE_LANGUAGE . '"'],
                ['w:val="' . $locale . '"', 'lang="' . $locale . '"'],
                $xml,
            );

            if ($replaced !== $xml) {
                $zip->addFromString($name, $replaced);
            }
        }

        $zip->close();
    }
}
