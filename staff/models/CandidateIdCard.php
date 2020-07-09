<?php

namespace staff\models;

use Yii;
use yii\helpers\Url;
use yii\helpers\FileHelper;
use common\components\Excel;
use Da\QrCode\QrCode;


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

    /**
     * Create Zip of Id Cards
     * @param  [type] $candidates [description]
     * @return [type]             [description]
     */
    public static function createIdCards($candidates)
    {
        $path = sys_get_temp_dir().'/'.time();

        FileHelper::createDirectory($path);

        // Create zip
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

        // Create card images
        
        foreach ($candidates as $key => $value) {
            
            if(!$value->candidateIdCard) {
                continue;
            }
            
            $card_url = Yii::$app->urlManagerStaff->createAbsoluteUrl("/candidate-id-cards/".$value->candidateIdCard->id);

            $webkitPath = Yii::getAlias('@app') . '/../webkit2png.py';

            // --debug
            exec("python " .$webkitPath . " -D " . $path . " --ignore-ssl-check -o ". $value->candidate_uid ." -F -W 564 -H 1738 " . $card_url);
        }

        // Add QR folder to zip
        foreach (glob($path.'/*') as $file) {
            $zip->addFile($file, basename($file));
        }

        $zip->close();

        return [
            'operation' => 'success',
            'zip' => $path.'/'.$zipname
        ];
    }
    
    /**
     * Create Zip
     * @param  [type] $candidates [description]
     * @return [type]             [description]
     */
    public static function createZip($candidates)
    {
        $path = sys_get_temp_dir().'/'.time();

        FileHelper::createDirectory($path);

        // Create excel file
        self::createExcel($candidates, $path);

        // Create zip
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

        // Create QR images
        FileHelper::createDirectory($path.'/QR');

        foreach ($candidates as $key => $value) {
            
            $writer = new \Da\QrCode\Writer\JpgWriter();
            
            $qrCode = (new QrCode('https://v.studenthub.co/'.$value->candidate_uid, null, $writer))
                ->setSize(250)
                ->setMargin(5);

            $qrCode->writeFile($path.'/QR/'.$value->employeeId.'.jpg');
        }

        // Add QR folder to zip
        foreach (glob($path.'/QR/*') as $file) {
            $zip->addFile($file, 'QR/'.basename($file));
        }

        // Add candidate photos to zip
        FileHelper::createDirectory($path.'/photos');

        foreach ($candidates as $key => $value) {

            if($value->candidate_personal_photo)
            {
                $source = Url::to('@s3/'.$value->candidate_personal_photo);
                $destination = $path.'/photos/'.$value->employeeId.'.'.pathinfo($value->candidate_personal_photo, PATHINFO_EXTENSION);

                @copy($source, $destination);
            }
        }

        // Add photo folder to zip
        foreach (glob($path.'/photos/*') as $file) {
            $zip->addFile($file, 'photos/'.basename($file));
        }

        $zip->close();

        return [
            'operation' => 'success',
            'zip' => $path.'/'.$zipname
        ];
    }

    /**
     * Create Excel
     * @param  [type] $candidates [description]
     * @param  [type] $path       [description]
     * @return [type]             [description]
     */
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
