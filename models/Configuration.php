<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace humhub\modules\euro_office\models;

use humhub\components\SettingsManager;
use humhub\libs\SafeBaseUrl;
use humhub\modules\euro_office\services\DocumentServerClient;
use Throwable;
use Yii;
use yii\base\Model;

/**
 * Module settings: how to reach the document server and how to authenticate against it
 */
class Configuration extends Model
{
    public const MIN_SECRET_LENGTH = 32;

    /**
     * @var string URL of the document server as reachable by the browser
     */
    public string $serverUrl = '';

    /**
     * @var string Optional URL of the document server as reachable by HumHub
     */
    public string $internalServerUrl = '';

    /**
     * @var string Optional URL of HumHub as reachable by the document server
     */
    public string $humhubUrl = '';

    public string $jwtSecret = '';

    public string $jwtHeader = 'Authorization';

    public ?SettingsManager $settingsManager = null;

    /**
     * @var bool whether the values were taken over from the settings of the ONLYOFFICE module
     */
    public bool $importedFromOnlyOffice = false;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['serverUrl', 'jwtSecret', 'jwtHeader'], 'required'],
            // Allow host names without a dot, e.g. Docker service names like "http://documentserver"
            [
                ['serverUrl', 'internalServerUrl', 'humhubUrl'],
                'url',
                'pattern' => '/^{schemes}:\/\/([A-Z0-9][A-Z0-9_-]*(\.[A-Z0-9][A-Z0-9_-]*)*)(?::\d{1,5})?(?:$|[?\/#])/i',
            ],
            ['jwtSecret', 'string', 'min' => self::MIN_SECRET_LENGTH],
            ['jwtHeader', 'match', 'pattern' => '/^[A-Za-z0-9-]+$/'],
            ['serverUrl', 'validateConnection', 'skipOnError' => true],
        ];
    }

    public function validateConnection(string $attribute): void
    {
        if ($this->hasErrors()) {
            return;
        }

        try {
            (new DocumentServerClient($this))->checkHealth();
        } catch (Throwable $e) {
            $this->addError($attribute, Yii::t(
                'EuroOfficeModule.base',
                'Could not connect to the document server: {message}',
                ['message' => $e->getMessage()],
            ));
        }
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'serverUrl' => Yii::t('EuroOfficeModule.base', 'Document server URL'),
            'internalServerUrl' => Yii::t('EuroOfficeModule.base', 'Internal document server URL'),
            'humhubUrl' => Yii::t('EuroOfficeModule.base', 'HumHub URL for the document server'),
            'jwtSecret' => Yii::t('EuroOfficeModule.base', 'JWT secret'),
            'jwtHeader' => Yii::t('EuroOfficeModule.base', 'JWT header'),
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeHints()
    {
        return [
            'serverUrl' => Yii::t(
                'EuroOfficeModule.base',
                'URL of the Euro-Office or ONLYOFFICE Docs server, as reachable by the users\' browsers. Example: {url}',
                ['url' => 'https://docs.example.com'],
            ),
            'internalServerUrl' => Yii::t(
                'EuroOfficeModule.base',
                'Optional. URL at which HumHub can reach the document server, if it differs from the URL above.',
            ),
            'humhubUrl' => Yii::t(
                'EuroOfficeModule.base',
                'Optional. URL at which the document server can reach HumHub. Default: {url}',
                ['url' => SafeBaseUrl::base(true)],
            ),
            'jwtSecret' => Yii::t(
                'EuroOfficeModule.base',
                'The secret configured on the document server (e.g. environment variable JWT_SECRET). At least {length} characters.',
                ['length' => self::MIN_SECRET_LENGTH],
            ),
            'jwtHeader' => Yii::t(
                'EuroOfficeModule.base',
                'The HTTP header used by the document server for JWT tokens (e.g. environment variable JWT_HEADER). Default: {header}',
                ['header' => 'Authorization'],
            ),
        ];
    }

    public function isConfigured(): bool
    {
        return $this->serverUrl !== '' && strlen($this->jwtSecret) >= self::MIN_SECRET_LENGTH;
    }

    public function getPublicServerUrl(): string
    {
        return rtrim($this->serverUrl, '/');
    }

    public function getInternalServerUrl(): string
    {
        return rtrim($this->internalServerUrl ?: $this->serverUrl, '/');
    }

    public function getHumhubUrl(): string
    {
        return rtrim($this->humhubUrl ?: SafeBaseUrl::base(true), '/');
    }

    public function loadBySettings(): void
    {
        $this->importedFromOnlyOffice = false;
        $this->serverUrl = (string)$this->settingsManager->get('serverUrl');
        $this->internalServerUrl = (string)$this->settingsManager->get('internalServerUrl');
        $this->humhubUrl = (string)$this->settingsManager->get('humhubUrl');
        $this->jwtSecret = (string)$this->settingsManager->get('jwtSecret');
        $this->jwtHeader = (string)$this->settingsManager->get('jwtHeader', 'Authorization');

        if ($this->serverUrl === '') {
            $this->loadFromOnlyOfficeSettings();
        }
    }

    /**
     * Takes over the connection settings of the ONLYOFFICE module, so the module works
     * immediately for installations switching from the ONLYOFFICE module
     */
    private function loadFromOnlyOfficeSettings(): void
    {
        $settings = new SettingsManager(['moduleId' => 'onlyoffice']);

        if (empty($settings->get('serverUrl')) || !empty($settings->get('demoServer'))) {
            return;
        }

        $this->serverUrl = (string)$settings->get('serverUrl');
        $this->internalServerUrl = (string)$settings->get('internalServerUrl');
        $this->humhubUrl = (string)$settings->get('storageUrl');
        $this->jwtSecret = (string)$settings->get('jwtSecret');
        $this->jwtHeader = (string)$settings->get('jwtHeader') ?: 'Authorization';
        $this->importedFromOnlyOffice = true;
    }

    public function save(): bool
    {
        if (!$this->validate()) {
            return false;
        }

        foreach (['serverUrl', 'internalServerUrl', 'humhubUrl', 'jwtSecret', 'jwtHeader'] as $attribute) {
            $this->settingsManager->set($attribute, $this->$attribute);
        }

        return true;
    }
}
