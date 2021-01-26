<?php

namespace company\modules\v1\controllers;

use Yii;
use yii\data\ActiveDataProvider;
use common\models\CompanyContact;
use yii\web\NotFoundHttpException;

/**
 * CompanyContact controller - Manage CompanyContact as Staff
 */
class CompanyContactController extends BaseController
{
    /**
     * Return a List of CompanyContact Accounts available.
     * @return ActiveDataProvider
     */
    public function actionList()
    {
        $q = Yii::$app->request->get('query');

        $company = Yii::$app->companyManager->getCompany();

        $query = $company->getContacts()
            ->orderBy('contact_created_at ASC');

        if($q) {
            $query->joinWith(['contactEmails', 'contactPhones'])
                ->andWhere([
                    'OR',
                    ['like', 'contact_name', $q],
                    ['like', 'contact_email.email_address', $q],
                    ['like', 'contact_phone.phone_number', $q]
                ]);
        }

        return new ActiveDataProvider([
            'query' => $query
        ]);
    }

    /**
     * load company contact details
     * @param $id
     * @return CompanyContact
     * @throws NotFoundHttpException
     */
    public function actionView($id)
    {
        return $this->findModel($id);
    }

    public function actionRemoveMember($id) {
//        return Yii::$app->user->identity->role;
        return Yii::$app->companyManager->getCompany();
        $contact = $this->findModel($id);
        if ($contact->role == 'Owner') {
            return [
                "operation" => "error",
                "message" => Yii::t('app', 'Your are not authorize to remove Owner')
            ];
        }
        if ($contact->contact_uuid == Yii::$app->user->getId()) {
            return [
                "operation" => "error",
                "message" => Yii::t('app', 'Your are not authorize to remove Own')
            ];
        }
    }

    /**
     * Finds the CompanyContact model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return CompanyContact the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        $company = Yii::$app->companyManager->getCompany();

        $model = $company->getCompanyContacts()->filterWhere(['contact_uuid' => $id])->one();

        if ($model !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}
