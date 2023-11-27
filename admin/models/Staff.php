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
        
        $mailer = Yii::$app->mailer->compose("staff-password",
            [
                "model" => $model,
                "password" => $password,
                'logo_1' => Url::to('@web/images/logo.png', true),
                'logo_2' => ''
            ])
            ->setFrom([Yii::$app->params['supportEmail'] => Yii::$app->params['appName']])
            ->setTo($model->staff_email)
            ->setSubject('Your account password has been reset');

        try {
            $mailer->send();
        } catch (\Swift_TransportException $e) {
            Yii::error($e->getMessage(), "password");
        }
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

    public function sendVerificationEmail() {

        $webUrl = Yii::$app->params['staffAppUrl'] . 'update-password/' . $this->staff_password_reset_token;

        $mailer = Yii::$app->mailer->compose("staff/password-reset-html",
            [
                "webUrl" => $webUrl,
                "logo" => \yii\helpers\Url::to('@web/images/logo.png', 'https'),
                "email" => $this->staff_email,
                "name" => $this->staff_name
            ])
            ->setFrom([Yii::$app->params['supportEmail'] => Yii::$app->params['appName']])
            ->setTo($this->staff_email)
            ->setSubject('Reset your StudentHub password');

        try {
            $mailer->send();
        } catch (\Swift_TransportException $e) {
            Yii::error($e->getMessage(), "password");
        }
    }
}
