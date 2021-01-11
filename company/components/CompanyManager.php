<?php
namespace company\components;

use company\models\Company;
use Yii;
/**
 * Company management
 */
class CompanyManager
{
    // Stores this Company manages
    /**
     * @var \company\models\Company
     */
    public $company = null;

    /**
     * Sets up the CompanyManager component for use to manage companys
     *
     * @param  array
     * $config name-value pairs that will be used to initialize the object properties
     * @throws \yii\base\InvalidParamException if token is empty or not valid
     */
    public function __construct($config = [])
    {
        // This component must only be usable if agent is logged in
        if(Yii::$app->user->isGuest) {
            die("ILLEGAL USAGE OF STORE MANAGER, THROW IN JAIL");
        }

        $cacheDuration = 60*1; //1 minute then delete from cache

        $this->company = Company::getDb()->cache(function($db) {
            $company_id = \Yii::$app->session->get('company_id',null);
            if ($company_id) {
                $company = Yii::$app->user->identity->getCompanies()->andWhere(['company_id'=>$company_id])->one();
                    if ($company) {
                        return  $company;
                    }
            } else {
                $company = Yii::$app->user->identity->getCompanies()->one();
                return $company;
            }

        }, $cacheDuration);//$cacheDependency
    }

    /**
     * Returns the companys managed by this agent
     * @return \company\models\Company
     */
    public function getCompany(){
        return $this->company;
    }

    /**
     * Return company
     * @param type $company_id
     * @return type
     * @throws \yii\web\BadRequestHttpException
     *
     * */
//    public function getManagedCompany($company_id) {
//
//        foreach($this->company as $company){
//            if($company->company_id == $company_id)
//                return $company;
//        }
//
//        throw new \yii\web\BadRequestHttpException('You do not manage this company.');
//    }

}
