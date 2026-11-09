<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace euro_office\unit;

use euro_office\EuroOfficeTestCase;
use humhub\modules\euro_office\services\DocumentKey;
use humhub\modules\euro_office\services\EditorConfigBuilder;
use humhub\modules\euro_office\services\JwtService;
use humhub\modules\user\models\User;
use Yii;

class EditorConfigBuilderTest extends EuroOfficeTestCase
{
    public function testEditConfig(): void
    {
        $this->becomeUser('User1');
        $file = $this->createFile('Report.docx');
        $configuration = $this->getEuroOfficeModule()->getConfiguration();

        $config = (new EditorConfigBuilder($configuration))->build($file, Yii::$app->user->getIdentity(), true);

        $this->assertSame('word', $config['documentType']);
        $this->assertSame('docx', $config['document']['fileType']);
        $this->assertSame(DocumentKey::forFile($file), $config['document']['key']);
        $this->assertTrue($config['document']['permissions']['edit']);
        $this->assertSame('edit', $config['editorConfig']['mode']);
        $this->assertSame(Yii::$app->user->getIdentity()->guid, $config['editorConfig']['user']['id']);
        $this->assertMatchesRegularExpression('~callback(/|%2F)track~', $config['editorConfig']['callbackUrl']);
        $this->assertMatchesRegularExpression('~callback(/|%2F)download~', $config['document']['url']);

        // The signed token contains the complete configuration
        $signed = (new JwtService($configuration))->decode($config['token']);
        unset($config['token']);
        $this->assertEquals($config, $signed);
    }

    public function testViewConfig(): void
    {
        $this->becomeUser('User1');
        $file = $this->createFile('Sheet.ods');

        $config = (new EditorConfigBuilder($this->getEuroOfficeModule()->getConfiguration()))
            ->build($file, User::findOne(['username' => 'User1']), false);

        $this->assertSame('cell', $config['documentType']);
        $this->assertFalse($config['document']['permissions']['edit']);
        $this->assertSame('view', $config['editorConfig']['mode']);
        $this->assertArrayNotHasKey('callbackUrl', $config['editorConfig']);
    }

    public function testCustomHumhubUrl(): void
    {
        $this->becomeUser('User1');
        $configuration = $this->getEuroOfficeModule()->getConfiguration();
        $configuration->humhubUrl = 'http://humhub.internal:8080/';

        $config = (new EditorConfigBuilder($configuration))
            ->build($this->createFile(), Yii::$app->user->getIdentity(), true);

        $this->assertStringStartsWith('http://humhub.internal:8080/', $config['document']['url']);
        $this->assertStringStartsWith('http://humhub.internal:8080/', $config['editorConfig']['callbackUrl']);
    }
}
