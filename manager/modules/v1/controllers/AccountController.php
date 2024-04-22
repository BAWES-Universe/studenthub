<?php
namespace manager\modules\v1\controllers;

use common\models\StoreManager;
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
        return StoreManager::findOne(Yii::$app->user->getId());
    }

    /**
     * Update email address
     * @return type
     */
    public function actionUpdateEmail() {

        $store_manager = StoreManager::findOne(Yii::$app->user->getId());

        $new_email = Yii::$app->request->getBodyParam("email");

        if (!$new_email) {
            return [
                "operation" => "error",
                "message" => Yii::t('company', "New email address required")
            ];
        }

        if ($new_email == $store_manager->email || $new_email == $store_manager->new_email) {
            return [
                "operation" => "error",
                "message" => Yii::t('company', "Candidate new email address is same as old email")
            ];
        }

        //should not be in use

        $exists = StoreManager::find()
            ->andWhere(['email' => $new_email])
            ->exists();

        if($exists) {
            return [
                "operation" => "error",
                "message" => Yii::t('company', "New email address already registered")
            ];
        }

        $store_manager->scenario = "updateEmail";

        $store_manager->new_email = $new_email;

        if ($store_manager->save()) {

            $store_manager->sendVerificationEmail();

            return [
                "operation" => "success",
                "message" => Yii::t('company', "Account Info Updated Successfully, please check email to verify new email address"),
            ];
        } else {
            return [
                "operation" => "error",
                "message" => $store_manager->errors
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

        $model->name = Yii::$app->request->getBodyParam("name");
        $model->phone_number = Yii::$app->request->getBodyParam("phone_number");

        if ($new_email && ($new_email != $model->email)) {
            $model->new_email = Yii::$app->request->getBodyParam("email");
        } else {
            $model->email = $new_email;
        }

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

        $msg = Yii::t("company","Account details successfully updated");

        if($model->new_email) {
            $model->sendVerificationEmail();
            $msg = Yii::t('company', "Account Info Updated Successfully, please check email to verify new email address");
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
                "message" => Yii::t("company","Empty old password")
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
