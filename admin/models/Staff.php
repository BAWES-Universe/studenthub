<?php
namespace admin\models;

use Yii;
use yii\db\Expression;
use yii\helpers\Url;

/**
 * This is the model class for table "Staff".
 * It extends from \common\models\Staff but with custom functionality for this application module
 */
class Staff extends \common\models\Staff {

    /**
     * @return array|string[]
     */
    public function extraFields()
    {
        return array_merge(
            [
                'staffSalaries',
                'totalAssigned' => function ($model) {
                    $start_date = Yii::$app->request->get('start_date', null);
                    $end_date = Yii::$app->request->get('end_date', null);
                    $query = $model->getCandidateWorkHistories();
                    if ($start_date) {
                        $query->andWhere(new Expression("DATE(start_date) >= DATE('".
                            date('Y-m-d', strtotime ($start_date)) ."')"));
                    }
                    if ($end_date) {
                        $query->andWhere(new Expression("DATE(start_date) <= DATE('".
                            date('Y-m-d', strtotime ($start_date)) ."')"));
                    }
                    return $query->count();
                },
                'totalRequests' => function ($model) {
                    $start_date = Yii::$app->request->get('start_date', null);
                    $end_date = Yii::$app->request->get('end_date', null);

                    $query = $model->getRequests();

                    if ($start_date) {
                        $query->andWhere(new Expression("DATE(request_created_datetime) >= DATE('".
                            date('Y-m-d', strtotime ($start_date)) ."')"));
                    }
                    if ($end_date) {
                        $query->andWhere(new Expression("DATE(request_created_datetime) <= DATE('".
                            date('Y-m-d', strtotime ($start_date)) ."')"));
                    }

                    return $query->count();
                },
                'totalNotes' => function ($model) {
                    $start_date = Yii::$app->request->get('start_date', null);
                    $end_date = Yii::$app->request->get('end_date', null);

                    $query = $model->getNotes();
                    if ($start_date) {
                        $query->andWhere(new Expression("DATE(note_created_datetime) >= DATE('".
                            date('Y-m-d', strtotime ($start_date)) ."')"));
                    }
                    if ($end_date) {
                        $query->andWhere(new Expression("DATE(note_created_datetime) <= DATE('".
                            date('Y-m-d', strtotime ($end_date)) ."')"));
                    }
                    return $query->count();
                },
                'totalStories' => function ($model) {
                    $start_date = Yii::$app->request->get('start_date', null);
                    $end_date = Yii::$app->request->get('end_date', null);

                    $query = $model->getStories();

                    if ($start_date) {
                        $query->andWhere(new Expression("DATE(story_created_at) >= DATE('".
                            date('Y-m-d', strtotime ($start_date)) ."')"));
                    }
                    if ($end_date) {
                        $query->andWhere(new Expression("DATE(story_created_at) <= DATE('".
                            date('Y-m-d', strtotime ($start_date)) ."')"));
                    }

                    return $query->count();
                },
                'totalAcceptedInvitations' => function ($model) {

                    $start_date = Yii::$app->request->get('start_date');
                    $end_date = Yii::$app->request->get('end_date');

                    $query = $model->getInvitations();
                    $query->andWhere(['invitation_status'=>3]);
                    if($start_date) {
                        $query->andWhere(new Expression("DATE(invitation_created_at) >= DATE('".
                            date('Y-m-d', strtotime ($start_date)) ."')"));
                    }

                    if($end_date) {
                        $query->andWhere(new Expression("DATE(invitation_created_at) <= DATE('".
                            date('Y-m-d', strtotime ($end_date))."')"));
                    }

                    return (int) $query
                        ->count();
                },
                'totalRejectedInvitations' => function ($model) {

                    $start_date = Yii::$app->request->get('start_date');
                    $end_date = Yii::$app->request->get('end_date');

                    $query = $model->getInvitations();
                    $query->andWhere(['invitation_status'=>2]);
                    if($start_date) {
                        $query->andWhere(new Expression("DATE(invitation_created_at) >= DATE('".
                            date('Y-m-d', strtotime ($start_date)) ."')"));
                    }

                    if($end_date) {
                        $query->andWhere(new Expression("DATE(invitation_created_at) <= DATE('".
                            date('Y-m-d', strtotime ($end_date))."')"));
                    }

                    return (int) $query
                        ->count();
                },
                'totalSuggestions' => function ($model) {

                    $start_date = Yii::$app->request->get('start_date');
                    $end_date = Yii::$app->request->get('end_date');

                    $query = $model->getNotes();
                    $query->andWhere(['note_type'=>'Suggested']);
                    if($start_date) {
                        $query->andWhere(new Expression("DATE(note_created_datetime) >= DATE('".
                            date('Y-m-d', strtotime ($start_date)) ."')"));
                    }

                    if($end_date) {
                        $query->andWhere(new Expression("DATE(note_created_datetime) <= DATE('".
                            date('Y-m-d', strtotime ($end_date))."')"));
                    }

                    return (int) $query
                        ->count();
                },
                'companies'
            ],
            parent::extraFields()
        );
    }

    /**
     * @inheritdoc
     */
    public function fields()
    {
        $fields = parent::fields();

        // remove fields that contain sensitive information
        unset($fields['staff_auth_key'],
        $fields['staff_password_hash'],
        $fields['staff_password_reset_token']);

        $fields['staff_gmail_password'] = function ($model) {
            return \staff\models\Staff::decryptPass($model->staff_gmail_password);
        };

        return $fields;
    }
    
    /**
     * Send new password to customer
     * @param Candidate $model
     * @param $password
     * @return bool
     */
    public static function passwordMail($model, $password)
    {
        Yii::$app->mailer->htmlLayout = 'layouts/html';
        
        return Yii::$app->mailer->compose("staff-password",
            [
                "model" => $model,
                "password" => $password,
                'logo_1' => Url::to('@web/images/logo.png', true),
                'logo_2' => ''
            ])
            ->setFrom([Yii::$app->params['supportEmail'] => Yii::$app->params['appName']])
            ->setTo($model->staff_email)
            ->setSubject('Your account password has been reset')
            ->send();
    }

    /**
     * Access tokens used to login on devices
     * @return \yii\db\ActiveQuery
     */
    public function getAccessTokens($modelClass = "\common\models\StaffToken")
    {
        return parent::getAccessTokens($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getNotes($modelClass = "\admin\models\Note")
    {
        return parent::getNotes($modelClass);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCompanies($modelClass = "\admin\models\Company")
    {
        return parent::getCompanies($modelClass);
    }

    public static function find()
    {
        return new query\StaffQuery(get_called_class());
    }

    /**
     * Set logo from S3 temp url
     * @param string $url
     */
    public function setLogo($staff_photo) {

        if(!Yii::$app->temporaryBucketResourceManager->fileExists($staff_photo)) {
            $this->addError('staff_photo', Yii::t('app', 'Image not available to save.'));
            return false;
        }

        $url = Yii::$app->temporaryBucketResourceManager->getUrl($staff_photo);

        $filename = Yii::$app->security->generateRandomString();

        // deleting old pic

        if ($this->staff_photo) {
            $this->deleteLogoFromCloudinary();
        }

        try {
            $path = (YII_ENV == 'prod') ? "staff-photo/" : "dev/staff-photo/" ;
            $result = Yii::$app->cloudinaryManager->upload(
                $url,
                [
                    'public_id' =>  $path . $filename,
                    "eager" => [
                        [
                            "width" => 200, "height" => 200, "crop" => "thumb", "gravity" => "face"
                        ]
                    ]
                ]
            );

            if ($result) {
                $this->staff_photo = basename($result['url']);
                return true;
            }

        } catch (\Cloudinary\Error $e) {

            Yii::error($e->getMessage(), 'common');

            $this->addError('staff_photo', Yii::t('app', 'Please try again.'));

            return false;

        } catch (\Exception $e) {

            Yii::error($e->getMessage(), 'common');

            $this->addError('staff_photo', Yii::t('app', 'Image not available to save.'));

            return false;
        }
    }

    /**
     * delete old logo from cloudinary
     * @return boolean
     */
    public function deleteLogoFromCloudinary() {

        try {
            $path = (YII_ENV == 'prod') ? "staff-photo/" : "dev/staff-photo/" ;
            $response = Yii::$app->cloudinaryManager->delete( $path . $this->staff_photo);
            if ($response && $response['result'] == 'not found') {
                $this->addError('staff_photo', Yii::t('app', 'Image not available to save.'));
                return false;
            }
        } catch (\Cloudinary\Error $e) {

            Yii::error($e->getMessage(), 'common');

            //$this->addError('brand_logo', Yii::t('app', 'Please try again.'));

            return false;

        } catch (\Exception $e) {

            Yii::error($e->getMessage(), 'common');

            //$this->addError('brand_logo', Yii::t('app', 'Image not available to save.'));

            return false;
        }
    }
}
