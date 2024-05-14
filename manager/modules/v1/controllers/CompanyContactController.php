<?php

namespace manager\modules\v1\controllers;

use Yii;
use yii\data\ActiveDataProvider;
use manager\models\CompanyContact;
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

        $company = Yii::$app->user->identity
            ->getCompany()->one();

        $query = $company->getCompanyContacts()
            ->orderBy('created_at ASC');

        if($q) {
            $query->joinWith(['contact','contactEmails', 'contactPhones'])
                ->andWhere([
                    'OR',
                    ['like', 'contact.contact_name', $q],
                    ['like', 'contact.contact_email', $q],
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

    /**
     * retrun access details
     * @return \manager\models\CompanyContact|null
     */
    public function actionViewCompanyContact() {

        $company = Yii::$app->user->identity
            ->getCompany()->one();

        $contact_uuid = Yii::$app->request->get('contact_uuid');

        return CompanyContact::find()
            ->andWhere(['company_id' => $company->company_id, 'contact_uuid' => $contact_uuid])
            ->one();
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
        $company = Yii::$app->user->identity
            ->getCompany()->one();

        $model = $company->getContacts()->andWhere(['contact_uuid' => $id])->one();

        if ($model !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}
