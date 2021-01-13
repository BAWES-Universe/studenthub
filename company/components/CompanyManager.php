<?php
namespace company\components;

use common\models\CompanyContact;
use company\models\Company;
use yii\helpers\ArrayHelper;
use Yii;


/**
 * Company management
 * Validate user if user has access to company
 * get single company access to user
 */
class CompanyManager
{
    // Stores this Company manages
    /**
     * @var \company\models\Company
     */
    public $companies = null;
    public $currentCompany = null;

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
            die("ILLEGAL USAGE OF COMPANY MANAGER, THROW IN JAIL");
        }

        $cacheDuration = 60*1; //1 minute then delete from cache

        //All the parent companies, user can access 

        $this->companies = Company::getDb()->cache(function($db) {
            return Yii::$app->user->identity->getManagedCompanies()->all();
        }, $cacheDuration);//$cacheDependency
    }

    /**
     * Returns the current selcted company managed by this agent
     * @return \company\models\Company
     */
    public function getCompany() {
        $company_id = \Yii::$app->request->headers->get('Company-Id');

        //use first company as selected if not specified

        if(!$company_id && $this->companies) {
            return $this->companies[0];
        }


        foreach ($this->companies as $company) {

            if($company->company_id == $company_id) {
                return $company;
            }
        }

        throw new \yii\web\BadRequestHttpException('You do not manage this company.');
    }

    /**
     * Return company
     * @param type $company_id
     * @return type
     * @throws \yii\web\BadRequestHttpException
     *
     * */
    public function getManagedCompany($company_id) {

        foreach($this->companies as $company){
            if($company->company_id == $company_id)
                return $company;
        }

        throw new \yii\web\BadRequestHttpException('You do not manage this company.');
    }

    /**
     * company id of current company and childs 
     */
    public function getCompanyIds() {

        $company = Yii::$app->companyManager->getCompany();

        $companyIds = ArrayHelper::getColumnn($company->getSubCompanies()->all(), 'company_id');

        $companyIds[] = $company->company_id;

        return $companyIds;
    }
}
