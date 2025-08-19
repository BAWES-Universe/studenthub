<?php

namespace staff\models;

use Yii;
use yii\helpers\Url;
use yii\helpers\FileHelper;
use common\components\Excel;
//use Da\QrCode\QrCode;
//use Spatie\Browsershot\Browsershot;


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
        $path = Yii::getAlias("@staff/web/assets/id-cards");
        //Yii::getAlias("@common/runtime/id-cards");
        //
            //

        //remove old content

        //FileHelper::removeDirectory($path);

        //create directory if not exists 

        if (!is_dir($path)) {
            FileHelper::createDirectory($path, 0775, true);
        }

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

        $binPath = "wkhtmltopdf";
        //Yii::getAlias("@common"). "/bin/png-linux-arm";//linux-arm linux-386

        // Create card images
        
        foreach ($candidates as $key => $value) {
            
            if(!$value->candidateIdCard) {
                continue;
            }
            
            $authHeader = Yii::$app->request->getHeaders()->get('Authorization');
            $token = $authHeader ? trim(str_ireplace('Bearer', '', $authHeader)) : null;
            
            $card_url = Yii::$app->urlManagerStaff->createAbsoluteUrl(
                "/candidate-id-cards/".$value->candidateIdCard->id.'/'.$token);

            if (!is_dir($path. '/' . $value->candidate_uid)) {
                FileHelper::createDirectory($path . '/' . $value->candidate_uid);
            }

                //$command = $binPath . " " . $card_url. "?side=front " . $path . '/' . $value->candidate_uid . "/front.png";

                $output = exec($binPath . " " . $card_url. "?side=front " . $path . '/' . $value->candidate_uid . "/front.pdf");
                $output = exec($binPath . " " . $card_url. "?side=back " . $path . '/' . $value->candidate_uid . "/back.pdf");
               /*
                //Yii::debug($command);
                try {
                    $htmlContent = file_get_contents($path . '/' . $value->candidate_uid . "/front.pdf");
                    $image = new \Imagick();
                    $image->setResolution(638, 1011);
                    $image->readImageBlob($htmlContent);
                    $image->setImageFormat('png');
                    $image->writeImage($path . '/' . $value->candidate_uid . '/front.png');
                    $image->clear();
                    $image->destroy();
                } catch (\Exception $e) {
                    Yii::error($e->getMessage());
                    return [
                        'operation' => 'error',
                        "htmlContent" => $htmlContent,
                        "message" => $e->getMessage()
                    ];
                }

              /*  try {
                    $htmlContent = file_get_contents($path . '/' . $value->candidate_uid . "/back.pdf");
                    $image = new \Imagick();
                    $image->setResolution(638, 1011);
                    $image->readImageBlob($htmlContent);
                    $image->setImageFormat('png');
                    $image->writeImage($path . '/' . $value->candidate_uid . '/back.png');
                    $image->clear();
                    $image->destroy();
                } catch (\Exception $e) {
                    Yii::error($e->getMessage());
                    return [
                        'operation' => 'error',
                        "htmlContent" => $htmlContent,
                        "message" => $e->getMessage()
                    ];
                } */

                //exec($command . " > /dev/null 2>&1");//, $output, $returnVar);

                //$output = shell_exec($command);

                /*if ($output) {
                    return [
                        'operation' => 'error',
                        // 'zip' => $path.'/'.$zipname,
                        "output" => $output,
                        "command" => $command
                    ];
                }/*/

                //sleep(5);

                /*if ($returnVar !== 0) {
                    Yii::error("Command failed: " . implode("\n", $output));
                    return [
                        'operation' => 'success',
                        'message' => 'Command failed: ' .$command . " " . implode("\n", $output)
                    ];
                }*/

                //Yii::debug(var_dump($output) . ":" . var_dump($returnVar));

                /*Browsershot::url($card_url . '?side=front')
                    ->timeout(0)
                    ->waitUntilNetworkIdle()
                    ->windowSize(638, 1011)
                    ->save($path . '/' . $value->candidate_uid . '/front.png');

                Browsershot::url($card_url . '?side=back')
                    ->timeout(0)
                    ->waitUntilNetworkIdle()
                    ->windowSize(638, 1011)
                    ->save($path . '/' . $value->candidate_uid . '/back.png');*/

                // Add photo folder to zip

            try {
                $zip->addFile($path . '/' . $value->candidate_uid . '/front.pdf', $value->candidate_uid . '/front.pdf');

                $zip->addFile($path . '/' . $value->candidate_uid . '/back.pdf', $value->candidate_uid . '/back.pdf');
              //  $zip->addFile($path . '/' . $value->candidate_uid . '/front.pdf', $value->candidate_uid . '/front.png');
              //  $zip->addFile($path . '/' . $value->candidate_uid . '/back.pdf', $value->candidate_uid . '/back.png');
            } catch ( \yii\base\ErrorException $e) {
                return [
                    'operation' => 'error',
                    // 'zip' => $path.'/'.$zipname,
                    //"output" => $output,
                    //"command" => $command,
                    "message" => $e->getMessage()
                ];
            }
            catch (\Exception $e) {
                return [
                    'operation' => 'error',
                    // 'zip' => $path.'/'.$zipname,
                    //"output" => $output,
                    //"command" => $command,
                    "message" => $e->getMessage()
                ];
            }

          //  }
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
            
            $path = (YII_ENV == 'prod') ? "https://v.studenthub.co/" : "https://v.dev.studenthub.co/";
            
            $qrCode = (new QrCode($path . $value->candidate_uid, null, $writer))
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
