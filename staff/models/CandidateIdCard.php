<?php

namespace staff\models;

use yii\helpers\Url;
use yii\helpers\FileHelper;
use dosamigos\qrcode\QrCode;
use common\components\Excel;

/**
 * This is the model class for table "candidate_id_card".
 *
 * @property integer $id
 * @property integer $candidate_id
 * @property string $expiry_date
 * @property string $created_at
 * @property string $updated_at
 *
 * @property Candidate $candidate
 */
class CandidateIdCard extends \common\models\CandidateIdCard
{
    /**
     * @inheritdoc
     */
    public function fields()
    {
        $fields = parent::fields();

	    $fields['candidate'] = function($model) {
	    	return $this->candidate;
	    };

        return $fields;
    }

    /**
     * @param string $modelClass
     * @return \yii\db\ActiveQuery
     */
    public function getCandidate($modelClass = "\common\models\Candidate")
    {
        return parent::getCandidate($modelClass);
    }

    public static function createZip($candidates)
    {       
        $path = sys_get_temp_dir().'/'.time();

        FileHelper::createDirectory($path);

        //create excel file

        self::createExcel($candidates, $path);
        
        //create zip 

        $zipname = 'IdCards.zip';

        $zip = new \ZipArchive();

        if (!$zip->open($path.'/'.$zipname, \ZipArchive::CREATE))
        {
            Yii::$app->response->statusCode = 500;

            return [
                'operation' => 'error',
                'message' => 'Cannot create a zip file'
            ];
        }

        $zip->addFile($path.'/export.xlsx', 'export.xlsx');

        //crate QR images

        FileHelper::createDirectory($path.'/QR');

        foreach ($candidates as $key => $value) {
            QrCode::jpg(
                'https://v.studenthub.co/'.$value->candidate_uid,
                $path.'/QR/'.$value->employeeId.'.jpg',
                0,
                14
            );
        }

        //add QR folder to zip

        foreach (glob($path.'/QR/*') as $file) {
            $zip->addFile($file, 'QR/'.basename($file));
        }

        //add candidate photos to zip

        FileHelper::createDirectory($path.'/photos');

        foreach ($candidates as $key => $value) {

            if($value->candidate_personal_photo)
            {
                $source = Url::to('@s3/'.$value->candidate_personal_photo);
                $destination = $path.'/photos/'.$value->employeeId.'.'.pathinfo($value->candidate_personal_photo, PATHINFO_EXTENSION);

                @copy($source, $destination);
            }
        }

        //add photo folder to zip

        foreach (glob($path.'/photos/*') as $file) {
            $zip->addFile($file, 'photos/'.basename($file));
        }

        $zip->close();

        return [
            'operation' => 'success',
            'zip' => $path.'/'.$zipname
        ];
    }

    public static function createExcel($candidates, $path) 
    {            
        Excel::export([
            'isMultipleSheet' => false,
            'models' => $candidates,
            'savePath' => $path,
            'fileName' => 'export.xlsx',
            'asAttachment' => false,
            'columns' => [
                'employeeId',
                'candidate_name_ar',
                [
                   'header' => 'University Name',
                   'format' => 'text',
                   'value' => function($model) {
                        if($model->university)
                        {
                            return $model->university->university_name_ar;
                        }else{
                            return '';
                        }
                   },
                ],
                //'university.university_name_ar',
                'candidate_civil_id'
            ],
            'headers' => [
                'employeeId' => 'Employee ID',
                'candidate_name_ar' => 'Employee Name',
                //'university.university_name_ar' => 'University Name',
                'candidate_civil_id' => 'Civil ID Number'
            ]
        ]);
    }
}