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
        
        $query = CompanyContact::find()
            ->orderBy('contact_created_datetime ASC');

        if($q) {
            $query->joinWith(['companyContactEmails', 'companyContactPhones'])
                ->andWhere([
                    'OR',
                    ['like', 'contact_name', $q],
                    ['like', 'company_contact_email.email_address', $q],
                    ['like', 'company_contact_phone.phone_number', $q]
                ]);
        }
        $query->filterWhere(['company_id' => Yii::$app->user->getId()]);
        
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
     * Finds the CompanyContact model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return CompanyContact the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = CompanyContact::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}
