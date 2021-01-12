<?php
namespace company\modules\v1\controllers;

use Yii;


/**
 * Account controller will return the actual Instagram Accounts and all controls associated
 */
class AccountController extends BaseController
{
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
                "message" => "Empty new password"
            ];
        }

        if ($oldPassword === $newPassword) {
            return [
                "operation" => "error",
                "message" => "New password should not be same as old password"
            ];
        }

        if (!$model->validatePassword($oldPassword)) {
            return [
                "operation" => "error",
                "message" => "Invalid Old Password"
            ];
        }

        if (strlen($newPassword) < 5) {
            return [
                "operation" => "error",
                "message" => "New password length should be great then equal to 5"
            ];
        }

        $model->setPassword($newPassword);
        
        if ($model->save(false)) {
            return [
                "operation" => "success",
                "message" => "Password changed successfully!"
            ];
        }
    }
}
