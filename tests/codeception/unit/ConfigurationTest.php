<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace euro_office\unit;

use euro_office\EuroOfficeTestCase;
use humhub\components\SettingsManager;
use humhub\libs\CURLHelper;
use humhub\modules\euro_office\models\Configuration;
use humhub\modules\euro_office\services\DocumentServerClient;

class ConfigurationTest extends EuroOfficeTestCase
{
    public function testValidation(): void
    {
        $configuration = new Configuration(['settingsManager' => $this->getEuroOfficeModule()->settings]);
        $configuration->serverUrl = 'http://documentserver';
        $configuration->jwtSecret = 'too-short';

        $this->assertFalse($configuration->validate());
        $this->assertTrue($configuration->hasErrors('jwtSecret'));
        // Host names without a dot (e.g. Docker services) are valid
        $this->assertFalse($configuration->hasErrors('serverUrl'));
    }

    public function testUrls(): void
    {
        $configuration = new Configuration();
        $configuration->serverUrl = 'https://docs.example.com/';
        $this->assertSame('https://docs.example.com', $configuration->getInternalServerUrl());

        $configuration->internalServerUrl = 'http://documentserver';
        $client = new DocumentServerClient($configuration);
        $this->assertSame('http://documentserver/cache/out.docx', $client->toInternalUrl('https://docs.example.com/cache/out.docx'));
        $this->assertSame('https://other.example.com/out.docx', $client->toInternalUrl('https://other.example.com/out.docx'));
    }

    public function testImportFromOnlyOfficeModule(): void
    {
        $this->getEuroOfficeModule()->settings->delete('serverUrl');
        $onlyOffice = new SettingsManager(['moduleId' => 'onlyoffice']);
        $onlyOffice->set('serverUrl', 'https://onlyoffice.example.com');
        $onlyOffice->set('storageUrl', 'http://humhub.internal');
        $onlyOffice->set('jwtSecret', self::SECRET);
        $onlyOffice->set('verifyPeerOff', '1');

        $configuration = new Configuration(['settingsManager' => $this->getEuroOfficeModule()->settings]);
        $configuration->loadBySettings();

        $this->assertTrue($configuration->importedFromOnlyOffice);
        $this->assertTrue($configuration->isConfigured());
        $this->assertSame('https://onlyoffice.example.com', $configuration->serverUrl);
        $this->assertSame('http://humhub.internal', $configuration->getHumhubUrl());
        $this->assertSame('Authorization', $configuration->jwtHeader);
        $this->assertTrue($configuration->verifyPeerOff);

        // Own settings take precedence
        $this->getEuroOfficeModule()->settings->set('serverUrl', 'https://docs.example.com');
        $configuration->loadBySettings();
        $this->assertFalse($configuration->importedFromOnlyOffice);
    }

    public function testVerifyPeerOff(): void
    {
        $configuration = new Configuration(['serverUrl' => 'https://docs.example.com']);
        $client = new DocumentServerClient($configuration);
        $this->assertNotSame(false, $client->getCurlOptions('https://docs.example.com')[CURLOPT_SSL_VERIFYPEER]);

        $configuration->verifyPeerOff = true;
        $options = $client->getCurlOptions('https://docs.example.com');
        $this->assertFalse($options[CURLOPT_SSL_VERIFYPEER]);
        $this->assertSame(0, $options[CURLOPT_SSL_VERIFYHOST]);

        // Plain HTTP requests keep the default options
        $this->assertSame(CURLHelper::getOptions(), $client->getCurlOptions('http://documentserver'));
    }

    public function testShortSecretIsNotConfigured(): void
    {
        $configuration = new Configuration(['serverUrl' => 'https://docs.example.com', 'jwtSecret' => 'short']);
        $this->assertFalse($configuration->isConfigured());
    }
}
