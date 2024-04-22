<?php 
namespace manager\models;

use Yii;
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
            [
                'excel', 
                '\common\components\S3FileExistValidator', 
                'filePath' => '',
                'message' => "Please upload valid excel",
                'resourceManager' => Yii::$app->temporaryBucketResourceManager,
                'extensions' => 'xlsx,xls'
            ]
        ];
    }
}
