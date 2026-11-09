<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace euro_office\unit;

use euro_office\EuroOfficeTestCase;
use humhub\modules\euro_office\models\CreateDocument;
use humhub\modules\euro_office\services\DocumentTemplate;
use ZipArchive;

class DocumentTemplateTest extends EuroOfficeTestCase
{
    public function testToLocale(): void
    {
        $this->assertSame('de-DE', DocumentTemplate::toLocale('de'));
        $this->assertSame('en-US', DocumentTemplate::toLocale('en'));
        $this->assertSame('en-GB', DocumentTemplate::toLocale('en-GB'));
        $this->assertSame('pt-BR', DocumentTemplate::toLocale('pt_BR'));
        $this->assertSame('sv-SE', DocumentTemplate::toLocale('sv'));
        $this->assertSame('en-US', DocumentTemplate::toLocale('invalid language'));
    }

    public function testDocumentLanguage(): void
    {
        $this->assertStringContainsString('w:val="de-DE"', $this->getXml(DocumentTemplate::getContent('docx', 'de'), 'word/styles.xml'));
        $this->assertStringContainsString('lang="fr-FR"', $this->getXml(DocumentTemplate::getContent('pptx', 'fr'), 'ppt/slideMasters/slideMaster1.xml'));
        $this->assertNotEmpty(DocumentTemplate::getContent('xlsx', 'de'));
    }

    public function testCreateDocument(): void
    {
        $this->becomeUser('User1');

        $model = new CreateDocument(['fileName' => 'Report', 'fileType' => 'xlsx']);
        $file = $model->save();

        $this->assertNotNull($file);
        $this->assertSame('Report.xlsx', $file->file_name);
        $this->assertGreaterThan(0, $file->size);

        $model = new CreateDocument(['fileName' => '../invalid', 'fileType' => 'docx']);
        $this->assertNull($model->save());
        $this->assertTrue($model->hasErrors('fileName'));
    }

    private function getXml(string $content, string $name): string
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'test');
        file_put_contents($tempFile, $content);
        $zip = new ZipArchive();
        $zip->open($tempFile);
        $xml = (string)$zip->getFromName($name);
        $zip->close();
        unlink($tempFile);

        return $xml;
    }
}
