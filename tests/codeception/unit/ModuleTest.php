<?php

/**
 * @link https://www.humhub.org/
 * @copyright Copyright (c) HumHub GmbH & Co. KG
 * @license https://www.humhub.com/licences
 */

namespace euro_office\unit;

use euro_office\EuroOfficeTestCase;
use Yii;
use yii\base\Module;

class ModuleTest extends EuroOfficeTestCase
{
    protected function _after()
    {
        Yii::$app->setModule('pdf-viewer', null);
        parent::_after();
    }

    public function testViewPdfReadOnly(): void
    {
        $this->becomeUser('User1');
        $file = $this->createFile('Document.pdf');

        $this->assertTrue($this->getEuroOfficeModule()->canView($file));
        $this->assertFalse($this->getEuroOfficeModule()->canEdit($file));
    }

    public function testPdfIsLeftToPdfViewer(): void
    {
        $this->becomeUser('User1');
        $pdf = $this->createFile('Document.pdf');
        $docx = $this->createFile('Report.docx');

        Yii::$app->setModule('pdf-viewer', ['class' => Module::class]);

        $this->assertFalse($this->getEuroOfficeModule()->canView($pdf));
        $this->assertTrue($this->getEuroOfficeModule()->canView($docx));
    }
}
