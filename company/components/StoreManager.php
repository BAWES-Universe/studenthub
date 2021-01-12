<?php
namespace company\components;

use Yii;
use company\models\Store;
use yii\helpers\ArrayHelper;


/**
 * Store management
 */
class StoreManager
{
    // Stores this Employer manages
    /**
     * @var \company\models\Store
     */
    private $managedStores = false;

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

        // Getting a list of companys this agent manages
        /*$cacheDependency = Yii::createObject([
            'class' => 'yii\caching\DbDependency',
            'reusable' => true,
            'sql' => 'SELECT COUNT(*) FROM store WHERE company_id="'.Yii::$app->user->getId() .'" OR ',
            //"'.Yii::$app->user->identity->agent_uuid.'", MAX(created_at),
            // we SELECT agent_uuid as well to make sure every cached sql statement is unique to this agent
            // don't want agents viewing the cached content of another agent
        ]);*/

        $cacheDuration = 60*1; //1 minute then delete from cache

        $this->_managedStores = Store::getDb()->cache(function($db) {

            $company = Yii::$app->companyManager->getCompany();

            $subCompanies = $company->getSubCompanies()->all();

            $companyIds = ArrayHelper::getColumn ($subCompanies, 'company_id');

            $companyIds[] = $company->company_id;

            return Store::find()
                ->filterWhere(['in', 'company_id', $companyIds])
                ->andWhere(['store.deleted' => 0])
                ->asArray()
                ->all();

        }, $cacheDuration);//$cacheDependency

        // No cache
        //$this->_managedStores = Yii::$app->user->identity->getCompanys()->all();

        //parent::__construct($config);
    }

    /**
     * Returns the companys managed by this agent
     * @return \company\models\Company
     */
    public function getManagedStores(){
        return $this->_managedStores;
    }

    /**
     * Return company
     * @param type $company_id
     * @return type
     * @throws \yii\web\BadRequestHttpException
     *
    public function getManagedCompany($company_id) {

        foreach($this->managedCompanies as $company){
            if($company->company_id == $company_id)
                return $company;
        }

        throw new \yii\web\BadRequestHttpException('You do not manage this company.');
    }*/
    
    /**
     * Return store
     * @param type $store_id
     * @return type
     * @throws \yii\web\BadRequestHttpException
     */
    public function getManagedStore($store_id) {
        
        foreach($this->_managedStores as $store){
            if($store->store_id == $store_id)
                return $store;
        }

        throw new \yii\web\BadRequestHttpException('You do not manage this store.');
    }
}
