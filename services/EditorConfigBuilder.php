<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\euro_office\services;

use humhub\helpers\DeviceDetectorHelper;
use humhub\libs\SafeBaseUrl;
use humhub\modules\euro_office\models\Configuration;
use humhub\modules\file\models\File;
use humhub\modules\user\models\User;
use InvalidArgumentException;
use Yii;

/**
 * Builds the signed configuration for `new DocsAPI.DocEditor()`
 *
 * @see https://api.onlyoffice.com/docs/docs-api/usage-api/advanced-parameters/
 */
final readonly class EditorConfigBuilder
{
    public function __construct(private Configuration $config)
    {
    }

    public function build(File $file, User $user, bool $edit): array
    {
        $documentType = DocumentFormats::getDocumentType($file);
        if ($documentType === null) {
            throw new InvalidArgumentException('Unsupported file type: ' . $file->file_name);
        }

        $accessToken = FileAccessToken::create($file);

        $config = [
            // Phones get the touch optimized editor, tablets work well with the desktop editor
            'type' => DeviceDetectorHelper::isMobile() && !DeviceDetectorHelper::isTablet() ? 'mobile' : 'desktop',
            'documentType' => $documentType->value,
            'document' => [
                'fileType' => DocumentFormats::getExtension($file),
                'key' => DocumentKey::forFile($file),
                'title' => $file->file_name,
                'url' => $this->toHumhubUrl(['/euro-office/callback/download', 'token' => $accessToken]),
                'permissions' => [
                    'edit' => $edit,
                    'download' => true,
                    'print' => true,
                ],
            ],
            'editorConfig' => [
                'mode' => $edit ? 'edit' : 'view',
                'lang' => $user->language ?: Yii::$app->language,
                'user' => [
                    'id' => $user->guid,
                    'name' => $user->displayName,
                ],
                'customization' => [
                    'forcesave' => false,
                    // Theme with a colored header, matching the HumHub modal header (see DocumentType::getColor())
                    'uiTheme' => 'theme-light',
                    // Shows a close button in the editor header, which triggers the onRequestClose event
                    'close' => [
                        'visible' => true,
                        'text' => Yii::t('EuroOfficeModule.base', 'Close'),
                    ],
                ],
            ],
        ];

        if ($edit) {
            $config['editorConfig']['callbackUrl'] = $this->toHumhubUrl(['/euro-office/callback/track', 'token' => $accessToken]);
        }

        $config['token'] = (new JwtService($this->config))->encode($config);

        return $config;
    }

    /**
     * URL of the Docs API script, loaded by the browser
     */
    public function getApiUrl(): string
    {
        return $this->config->getPublicServerUrl() . '/web-apps/apps/api/documents/api.js';
    }

    /**
     * Absolute URL for requests from the document server to HumHub
     */
    private function toHumhubUrl(array $route): string
    {
        $url = SafeBaseUrl::to($route, true);

        $baseUrl = SafeBaseUrl::base(true);
        $humhubUrl = $this->config->getHumhubUrl();
        if ($humhubUrl !== $baseUrl && str_starts_with($url, $baseUrl)) {
            $url = $humhubUrl . substr($url, strlen($baseUrl));
        }

        return $url;
    }
}
