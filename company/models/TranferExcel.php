<?php 
namespace company\models;

use yii\base\Model;

class TranferExcel extends Model
{
    /**
     * @var UploadedFile
     */
    public $excel;

    public function rules()
    {
        return [
            [['excel'], 'file', 'skipOnEmpty' => false, 'checkExtensionByMimeType' => false, 'extensions' => 'xlsx,xls'],
        ];
    }
}
