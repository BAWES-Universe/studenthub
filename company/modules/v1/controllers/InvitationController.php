<?php

namespace company\modules\v1\controllers;

use Yii;
use yii\data\ActiveDataProvider;
use yii\rest\Controller;
use company\models\ContactInvitation;
use company\models\CompanyContact;

/**
 * Invitation Controller
 */
class InvitationController extends Controller {

    public function behaviors() {
        $behaviors = parent::behaviors();

        // remove authentication filter for cors to work
        unset($behaviors['authenticator']);

        // Allow XHR Requests from our different subdomains and dev machines
        $behaviors['corsFilter'] = [
            'class' => \yii\filters\Cors::className(),
            'cors' => [
                'Origin' => Yii::$app->params['allowedOrigins'],
                'Access-Control-Request-Method' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'],
                'Access-Control-Request-Headers' => ['*'],
                'Access-Control-Allow-Credentials' => null,
                'Access-Control-Max-Age' => 86400,
                'Access-Control-Expose-Headers' => [
                    'X-Pagination-Current-Page',
                    'X-Pagination-Page-Count',
                    'X-Pagination-Per-Page',
                    'X-Pagination-Total-Count'
                ],
            ],
        ];

        // Bearer Auth checks for Authorize: Bearer <Token> header to login the user
        $behaviors['authenticator'] = [
            'class' => \yii\filters\auth\HttpBearerAuth::className(),
        ];

        // avoid authentication on CORS-pre-flight requests (HTTP OPTIONS method)
        $behaviors['authenticator']['except'] = [
            'by-otp',
            'options'
        ];

        return $behaviors;
    }

    /**
     * @inheritdoc
     */
    public function actions() {
        $actions = parent::actions();
        $actions['options'] = [
            'class' => 'yii\rest\OptionsAction',
            // optional:
            'collectionOptions' => ['GET', 'POST', 'HEAD', 'OPTIONS'],
            'resourceOptions' => ['GET', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'],
        ];
        return $actions;
    }

    /**
     * View invitation by OTP sent in email when owner invite 
     * @return ActiveDataProvider
     */
    public function actionByOtp($otp) {
        $invitation = ContactInvitation::find()
                ->andWhere([
                    'otp' => $otp
                ])
                ->one();

        return [
            'invitation' => $invitation
        ];
    }

    /**
     * List pending invitations
     */
    public function actionPending() {
        
        $query = ContactInvitation::find()
                ->filterByCurrentContact()
                ->filterByPendingInvitations()
                ->innerJoinWith('company');

        return new ActiveDataProvider([
            'query' => $query,
            'pagination' => false
        ]);
    }


    /**
     * list sent Invitation
     */

    /**
     * @param $id
     * @return array|ActiveDataProvider
     */
    public function actionInvitationList($id) {

        $query = Yii::$app->request->get('query');
        // sent invitation
        $invitedSentQuery = ContactInvitation::find()
            ->joinWith(['invitedContact'])
            ->andWhere(['company_id' => $id])
            ->andWhere('accepted IS NULL');

        if($query && strlen($query) > 0) {
            $invitedSentQuery->andWhere([
                'OR',
                ['like', 'email_to_invite', $query],
            ]);
        }
        $result['invitationSent'] = $invitedSentQuery->all();

        // received invitation
        $receivedInviteQuery = ContactInvitation::find()
            ->joinWith(['invitedContact'])
            ->andWhere('accepted IS NULL');
        $receivedInviteQuery->andWhere(['email_to_invite'=> Yii::$app->user->identity->contact_email]);

        $result['invitationReceived'] = $receivedInviteQuery->all();
        return $result;
    }
    /**
     * Invite agent by email 
     */
    public function actionInvite() 
    {
        $company_id = Yii::$app->request->getBodyParam("company_id");
        $role = Yii::$app->request->getBodyParam("role");
        $email_to_invite = Yii::$app->request->getBodyParam("email_to_invite");

        //agent can't send invitation to him self 

        if ($email_to_invite == Yii::$app->user->identity->contact_email) {
            return [
                "operation" => "error",
                "message" => Yii::t('company', 'Contact can not send invitation to him self')
            ];
        }

        //don't send invitation if already accepted or pending 
        
        $checkAlreadyReceivedRequest = ContactInvitation::find()
            ->filterByEmail($email_to_invite)
            ->filterByCompanyID($company_id)
            ->filterByActiveInvitations()
            ->exists();

        if ($checkAlreadyReceivedRequest) {
            return [
                "operation" => "error",
                "direct" => "1",
                "message" => Yii::t('company', "Request already sent to email {email}", [
                    'email' => $email_to_invite
                ])
            ];
        }

        $model = new ContactInvitation();
        $model->contact_uuid = Yii::$app->user->identity->contact_uuid;
        $model->company_id = $company_id;
        $model->email_to_invite = $email_to_invite;
        $model->role = $role;

        if (!$model->save()) {
            return [
                "operation" => "error",
                "message" => $model->getErrors()
            ];
        }

        return [
            "operation" => "success",
            "message" => Yii::t('company', "Invitation sent successfully"),
        ];
    }

    /**
     * @param $id
     * @return array
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     */
    public function actionAccept($id) {
        $model = ContactInvitation::find()
                ->filterByCurrentContact()
                ->andWhere([
                    'contact_invitation_uuid' => $id
                ])
                ->one();

        if (!$model) {
            return [
                'operation' => 'error',
                'message' => Yii::t('company', 'Item not found')
            ];
        }

        if ($model->accepted === ContactInvitation::ACCEPTED_FALSE) {
            return [
                'operation' => 'error',
                'message' => Yii::t('company', 'Invitation cancelled')
            ];
        }

        $model->accepted = ContactInvitation::ACCEPTED_TRUE;

        if (!$model->save()) {

            return [
                'operation' => 'error',
                'message' => $model->getErrors()
            ];
        }

        $model->sendAcceptedInvitationEmail();

        $model->delete();

        //add agent to team 

        $companyContact = new CompanyContact();
        $companyContact->company_id = $model->company_id;
        $companyContact->contact_uuid = Yii::$app->user->identity->contact_uuid;
        $companyContact->role = $model->role;

        if (!$companyContact->save()) {
            return [
                'operation' => 'error',
                'message' => $companyContact->getErrors()
            ];
        }

        return [
            'operation' => 'success',
            'message' => Yii::t('company', 'Invitation accepted successfully')
        ];
    }

    /**
     * Reject invitation 
     * @param $id     
     * @return array
     */
    public function actionReject($id) {
        $agent = Yii::$app->user->identity;

        $model = ContactInvitation::find()
                ->andWhere([
                    'contact_invitation_uuid' => $id
                ])
                ->one();

        if (!$model) {
            return [
                'operation' => 'error',
                'message' => Yii::t('company', 'Item not found')
            ];
        }

        /**
         * agent can reject his invitation + he should be owner to reject invitation to other members in his team 
         */
        if ($model->email_to_invite != $agent->email) {
            return [
                'operation' => 'error',
                'message' => Yii::t('company', 'You should be owner to remove invitation')
            ];
        }

        $model->accepted = ContactInvitation::ACCEPTED_FALSE;

        if ($model->save()) {
            return [
                'operation' => 'success',
                'message' => Yii::t('company', 'Invitation rejected successfully')
            ];
        } else {
            return [
                'operation' => 'error',
                'message' => $model->getErrors()
            ];
        }

        $model->delete();
    }

    /**
     * delete invitation sent by login user 
     * @param $id
     * @return array
     */
    public function actionDelete($id) {
        
        $model = ContactInvitation::find()
                ->andWhere([
                    'contact_invitation_uuid' => $id
                ])
                ->one();

        if (!$model) {
            return [
                'operation' => 'error',
                'message' => Yii::t('company', 'Item not found')
            ];
        }

        if (!$model->delete()) {
            return [
                'operation' => 'error',
                'message' => $model->getErrors()
            ];
        }

        return [
            'operation' => 'success',
            'message' => Yii::t('company', "Invitation Deleted Successfully")
        ];
    }

}
