<?php

namespace common\components;


class Excel extends \common\components\PhpExcel {

    /**
     * saving the xls file to download or to path
     */
    public function writeFile($sheet) {
        if (!isset($this->format))
            $this->format = 'Xlsx';
        
        $objectwriter = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($sheet, $this->format);
        $path = 'php://output';
        if (isset($this->savePath) && $this->savePath != null) {
            $path = $this->savePath . '/' . $this->getFileName();
        }
        $objectwriter->setOffice2003Compatibility($this->compatibilityOffice2003);
        $objectwriter->setPreCalculateFormulas($this->preCalculationFormula);
        $objectwriter->save($path);
        //if ($path == 'php://output')
        //    exit();
    }
}
