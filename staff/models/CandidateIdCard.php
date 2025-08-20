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
        $basePath = Yii::getAlias("@staff/web/assets/id-cards");
        
        // Create a unique directory for this request
        $requestId = Yii::$app->security->generateRandomString(16);
        $path = $basePath . '/' . $requestId;
        
        // Create the directory if it doesn't exist
        if (!is_dir($path)) {
            FileHelper::createDirectory($path, 0775, true);
        }

        $binPath = "wkhtmltopdf";
        $pdfFiles = []; // To store generated PDF files
        $hasErrors = false;
        
        try {
            // First pass: Generate all PDF files
            foreach ($candidates as $key => $value) {
                if (!$value->candidateIdCard) {
                    continue;
                }
                
                $candidateDir = $path . '/' . $value->candidate_uid;
                if (!is_dir($candidateDir)) {
                    FileHelper::createDirectory($candidateDir);
                }
                
                $authHeader = Yii::$app->request->getHeaders()->get('Authorization');
                $token = $authHeader ? trim(str_ireplace('Bearer', '', $authHeader)) : null;
                
                $card_url = Yii::$app->urlManagerStaff->createAbsoluteUrl(
                    "/candidate-id-cards/".$value->candidateIdCard->id.'/'.$token);

                // Generate PDFs with unique filenames
                $frontPdf = $candidateDir . "/front.pdf";
                $backPdf = $candidateDir . "/back.pdf";
                
                // Generate front and back PDFs
                $output = exec($binPath . " " . $card_url . "?side=front " . $frontPdf);
                $output = exec($binPath . " " . $card_url . "?side=back " . $backPdf);
                
                // Verify both PDFs were created
                if (file_exists($frontPdf) && file_exists($backPdf)) {
                    $pdfFiles[] = [
                        'front' => $frontPdf,
                        'back' => $backPdf,
                        'uid' => $value->candidate_uid
                    ];
                } else {
                    Yii::error("Failed to generate PDFs for candidate: " . $value->candidate_uid);
                    $hasErrors = true;
                }
            }
            
            if (empty($pdfFiles)) {
                throw new \Exception('No valid candidate ID cards were processed');
            }
            
            if ($hasErrors) {
                Yii::warning('Some candidates had errors during PDF generation');
            }
            
            // Create zip with unique name
            $zipname = 'IdCards_' . $requestId . '.zip';
            $zipPath = $path . '/' . $zipname;
            
            // Create zip and add all files
            $zip = new \ZipArchive();
            if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
                throw new \Exception('Cannot create zip file');
            }
            
            try {
                // Add all PDFs to zip
                foreach ($pdfFiles as $file) {
                    $zip->addFile($file['front'], $file['uid'] . '/front.pdf');
                    $zip->addFile($file['back'], $file['uid'] . '/back.pdf');
                }
                
                if ($zip->numFiles === 0) {
                    throw new \Exception('No files were added to the zip');
                }
                
                $zip->close();
                
                // Schedule cleanup of old directories (older than 30 minutes)
                self::cleanupOldDirectories($basePath);
                
                return [
                    'operation' => 'success',
                    'zip' => $zipPath,
                    'tempDir' => $path  // Return the temp dir for cleanup after download
                ];
                
            } catch (\Exception $e) {
                if (isset($zip) && $zip instanceof \ZipArchive) {
                    $zip->close();
                }
                throw $e; // Re-throw to be caught by outer try-catch
            }
            
        } catch (\Exception $e) {
            // Clean up on error
            if (isset($zip) && $zip instanceof \ZipArchive) {
                @$zip->close();
            }
            if (isset($zipPath) && file_exists($zipPath)) {
                @unlink($zipPath);
            }
            
            Yii::error("Error generating ID cards: " . $e->getMessage());
            return [
                'operation' => 'error',
                'message' => 'Error generating ID cards: ' . $e->getMessage()
            ];
        }
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

    /**
     * Clean up temporary directories older than 30 minutes
     * @param string $basePath Base directory containing temp directories
     */
    private static function cleanupOldDirectories($basePath)
    {
        try {
            $dirs = glob(rtrim($basePath, '/') . '/*', GLOB_ONLYDIR);
            $now = time();
            $thirtyMinutes = 1800; // 30 minutes in seconds
            
            foreach ($dirs as $dir) {
                if (is_dir($dir) && ($now - filemtime($dir)) > $thirtyMinutes) {
                    try {
                        FileHelper::removeDirectory($dir);
                        Yii::info("Cleaned up old directory: {$dir}", __METHOD__);
                    } catch (\Exception $e) {
                        Yii::error("Error cleaning up directory {$dir}: " . $e->getMessage(), __METHOD__);
                    }
                }
            }
        } catch (\Exception $e) {
            Yii::error("Error in cleanupOldDirectories: " . $e->getMessage(), __METHOD__);
        }
    }
}
