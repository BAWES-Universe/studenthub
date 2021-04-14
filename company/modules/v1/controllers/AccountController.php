<?php
namespace company\modules\v1\controllers;

use company\models\Contact;
use company\models\ContactEmail;
use company\models\ContactPhone;
use Yii;


/**
 * Account controller will return the actual Instagram Accounts and all controls associated
 */
class AccountController extends BaseController
{
    /**
     * return profile details
     */
    public function actionView() {
        return Contact::findOne(Yii::$app->user->getId());
    }

    /**
     * Update email address
     * @return type
     */
    public function actionUpdateEmail() {

        $contact = Contact::findOne(Yii::$app->user->getId());

        $new_email = Yii::$app->request->getBodyParam("email");

        if (!$new_email) {
            return [
                "operation" => "error",
                "message" => Yii::t('company', "Contact new email address required")
            ];
        }

        if ($new_email == $contact->email || $new_email == $contact->new_email) {
            return [
                "operation" => "error",
                "message" => Yii::t('company', "Candidate new email address is same as old email")
            ];
        }

        $contact->scenario = "updateEmail";

        $contact->contact_new_email = $new_email;

        if ($contact->save()) {

            $contact->sendVerificationEmail();

            return [
                "operation" => "success",
                "message" => Yii::t('company', "Contact Account Info Updated Successfully, please check email to verify new email address"),
            ];
        } else {
            return [
                "operation" => "error",
                "message" => $contact->errors
            ];
        }
    }

    /**
     * Update account details
     * @param $id
     * @return array
     */
    public function actionUpdate()
    {
        $model = Yii::$app->user->identity;
        $new_email = Yii::$app->request->getBodyParam("email");
        $model->contact_name = Yii::$app->request->getBodyParam("name");
        //$model->contact_position = Yii::$app->request->getBodyParam("position");
        $model->contact_receive_email = Yii::$app->request->getBodyParam("receive_email");
        $model->contact_receive_notification = Yii::$app->request->getBodyParam("receive_notification");

        if ($new_email && ($new_email != $model->contact_email)) {
            $model->contact_new_email = Yii::$app->request->getBodyParam("email");
        } else {
            $model->contact_email = $new_email;
        }

        $emails = Yii::$app->request->getBodyParam("emails");
        $phones = Yii::$app->request->getBodyParam("phones");

        if (!$model->save())
        {
            if(isset($model->errors)){
                return [
                    "operation" => "error",
                    "message" => $model->errors
                ];
            }else{
                return [
                    "operation" => "error",
                    "message" => Yii::t("company","We've faced a problem updating the account details, please contact us for assistance.")
                ];
            }
        }

        ContactEmail::deleteAll(['contact_uuid' => $model->contact_uuid]);
        ContactPhone::deleteAll(['contact_uuid' => $model->contact_uuid]);

        if(!$emails) {
            $emails = [];
        }

        foreach($emails as $email) {

            if(!$email['email_address'])
                continue;

            $em = new ContactEmail;
            $em->contact_uuid = $model->contact_uuid;
            $em->email_address = $email['email_address'];
            $em->save();
        }

        if(!$phones) {
            $phones = [];
        }

        foreach($phones as $phone) {

            if(!$phone['phone_number'])
                continue;

            $em = new ContactPhone;
            $em->contact_uuid = $model->contact_uuid;
            $em->phone_number = $phone['phone_number'];
            $em->save();
        }

        $msg = Yii::t("company","Account details successfully updated");
        if($model->contact_new_email) {
            $model->sendVerificationEmail();
            $msg = Yii::t('company', "Contact Account Info Updated Successfully, please check email to verify new email address");
        }

        return [
            "operation" => "success",
            "message" => $msg
        ];
    }

    /**
     * ability to update password after login
     */
    public function actionChangePassword()
    {
        $model = Yii::$app->user->identity;

        $oldPassword = Yii::$app->request->getBodyParam("old_password");
        $newPassword = Yii::$app->request->getBodyParam("new_password");

        if (empty($oldPassword)) {
            return [
                "operation" => "error",
                "message" => "Empty old password"
            ];
        } else if (empty($newPassword)) {
            return [
                "operation" => "error",
                "message" => Yii::t("company","Empty new password")
            ];
        }

        if ($oldPassword === $newPassword) {
            return [
                "operation" => "error",
                "message" => Yii::t("company","New password should not be same as old password")
            ];
        }

        if (!$model->validatePassword($oldPassword)) {
            return [
                "operation" => "error",
                "message" => Yii::t("company","Invalid Old Password")
            ];
        }

        if (strlen($newPassword) < 5) {
            return [
                "operation" => "error",
                "message" => Yii::t("company","New password length should be great then equal to 5")
            ];
        }

        $model->setPassword($newPassword);
        
        if ($model->save(false)) {
            return [
                "operation" => "success",
                "message" => Yii::t("company","Password changed successfully!")
            ];
        }
    }
}
