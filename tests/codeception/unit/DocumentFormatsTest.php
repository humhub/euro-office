<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace euro_office\unit;

use humhub\modules\euro_office\services\DocumentFormats;
use humhub\modules\euro_office\services\DocumentType;
use humhub\modules\file\models\File;
use tests\codeception\_support\HumHubDbTestCase;

class DocumentFormatsTest extends HumHubDbTestCase
{
    public function testFormats(): void
    {
        $this->assertSame(DocumentType::Word, DocumentFormats::getDocumentType($this->file('Report.DOCX')));
        $this->assertTrue(DocumentFormats::isEditable($this->file('Report.DOCX')));

        $this->assertSame(DocumentType::Cell, DocumentFormats::getDocumentType($this->file('a.xlsx')));
        $this->assertSame(DocumentType::Slide, DocumentFormats::getDocumentType($this->file('a.pptx')));

        $this->assertTrue(DocumentFormats::isSupported($this->file('a.odt')));
        $this->assertFalse(DocumentFormats::isEditable($this->file('a.odt')));

        $this->assertSame(DocumentType::Pdf, DocumentFormats::getDocumentType($this->file('a.PDF')));
        $this->assertFalse(DocumentFormats::isEditable($this->file('a.pdf')));

        $this->assertFalse(DocumentFormats::isSupported($this->file('a.txt')));
        $this->assertFalse(DocumentFormats::isEditable($this->file('noextension')));
        $this->assertNull(DocumentFormats::getDocumentType($this->file('image.png')));
    }

    private function file(string $name): File
    {
        return new File(['file_name' => $name]);
    }
}
