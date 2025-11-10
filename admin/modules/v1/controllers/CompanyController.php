<?php

namespace admin\modules\v1\controllers;

use admin\models\TransferCandidate;
use common\models\CandidateWorkHistory;
use common\models\CompanyContact;
use common\models\Contact;
use Illuminate\Support\Facades\Date;
use Yii;
use yii\rest\Controller;
use yii\data\ActiveDataProvider;
use admin\models\File;
use admin\models\Company;
use yii\filters\Cors;
use yii\filters\auth\HttpBearerAuth;
use yii\web\NotFoundHttpException;


/**
 * Company controller - Manage company accounts as Admin
 * 
 * @OA\Tag(
 *     name="Company Management",
 *     description="Manage company accounts, sub-companies, files, and company settings"
 * )
 */
class CompanyController extends Controller
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();

        // remove authentication filter for cors to work
        unset($behaviors['authenticator']);

        // Allow XHR Requests from our different subdomains and dev machines
        $behaviors['corsFilter'] = [
            'class' => Cors::class,
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
            'class' => HttpBearerAuth::class,
        ];
        // avoid authentication on CORS-pre-flight requests (HTTP OPTIONS method)
        $behaviors['authenticator']['except'] = ['options'];

        return $behaviors;
    }

    /**
     * @inheritdoc
     */
    public function actions()
    {
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
     * Login as company (admin impersonation)
     * 
     * @OA\Post(
     *     path="/company/{id}/login",
     *     summary="Login as company",
     *     description="Generate auth key and redirect URL to login as a company (admin impersonation)",
     *     tags={"Company Management"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Company ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Login URL generated",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="redirect", type="string", description="Redirect URL with auth key")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Company not found"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     * @param $id
     * @return \yii\web\Response
     * @throws NotFoundHttpException
     */
    public function actionLogin($id)
    {
        $company = $this->findModel($id);

        $model = $company->getContacts()
            ->one();

        if(!$model) {
            throw new NotFoundHttpException('The requested page does not exist.');
        }

        $model->generateAuthKey(null);

        if(!$model->save(false)) {
            return [
                "operation" => "error",
                'message' => $model->errors,
                'redirect' => Yii::$app->params['companyAppUrl']
            ];
        }

        $url = Yii::$app->params['companyAppUrl']. '?auth_key='.$model->contact_auth_key;

        return [
            'redirect' => $url
        ];
    }

    /**
     * List companies
     * 
     * @OA\Get(
     *     path="/company/list",
     *     summary="List companies",
     *     description="Get a paginated list of companies with optional filtering",
     *     tags={"Company Management"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by status: 0=all, 1=active, 2=inactive, 3=active 40+ days without payment",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="name",
     *         in="query",
     *         description="Filter by company name",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="staff_id",
     *         in="query",
     *         description="Filter by staff ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="approved_to_hire",
     *         in="query",
     *         description="Filter by approved to hire status",
     *         @OA\Schema(type="boolean")
     *     ),
     *     @OA\Parameter(
     *         name="fields",
     *         in="query",
     *         description="Comma-separated list of fields to return",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="isParent",
     *         in="query",
     *         description="Filter parent companies only",
     *         @OA\Schema(type="boolean", default=true)
     *     ),
     *     @OA\Parameter(
     *         name="currency",
     *         in="header",
     *         description="Currency code (default: KWD)",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of companies",
     *         @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Company"))
     *     ),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     * @return ActiveDataProvider
     */
    public function actionList()
    {
        $currency = Yii::$app->request->headers->get("currency", "KWD");

        $status = Yii::$app->request->getQueryParam("status",0);
        $name = Yii::$app->request->getQueryParam("name",0);
        $staff_id = Yii::$app->request->getQueryParam("staff_id",0);
        $approved_to_hire = Yii::$app->request->getQueryParam("approved_to_hire");
        $fields = Yii::$app->request->getQueryParam("fields", null);
        $pagination = Yii::$app->request->getQueryParam("pagination", True);
        $isParent = Yii::$app->request->getQueryParam("isParent", True);
        
        $query = Company::find();

        if($isParent){
            $query->filterParent();
        }

        // Apply field selection if provided
        if ($fields) {
            // Split comma-separated fields and sanitize to prevent SQL injection
            $allowedFields = ['company_id', 'company_name', 'company_email', 'country_id']; // Define allowed fields
            $requestedFields = array_map('trim', explode(',', $fields));
            $selectedFields = array_intersect($requestedFields, $allowedFields);
            if (!empty($selectedFields)) {
                $query->select($selectedFields);
            }
        }
        
        if($currency) {
            $query->andWhere(['company.currency_code' => $currency]);
        }

        if ($status == 1) {
            $query->filterActive();
        }

        if ($status == 2) {
            $query->filterInActive();
        }

        if ($status == 3) {
            $query->filterByActive40DaysPassedWithoutPayment();
        }

        if ($name) {
            $query->filterByName($name);
        }

        if ($staff_id) {
            $query->filterByStaff($staff_id);
        }

        if (!is_null($approved_to_hire) && in_array ($approved_to_hire, [0, 1])) {
            $query->filterByApprovedToHire($approved_to_hire);
        }
        
        return new ActiveDataProvider([
            'query' => $query,
            'pagination' => $pagination ? [
                'pageSizeParam' => 'per-page',
                'pageSize' => Yii::$app->request->get('per-page', 20),
            ] : false,
        ]);
    }

    /**
     * List companies needing followups
     * 
     * @OA\Get(
     *     path="/company/followups",
     *     summary="List companies needing followups",
     *     description="Get list of companies that need followup attention",
     *     tags={"Company Management"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="currency",
     *         in="header",
     *         description="Currency code (default: KWD)",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of companies needing followups",
     *         @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Company"))
     *     ),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     * @return ActiveDataProvider
     */
    public function actionFollowups()
    {
        $currency = Yii::$app->request->headers->get("currency", "KWD");

        $query = Company::find()
            ->with([
                'subCompanies',
                'stores',
            ])   
            ->followups();

        if($currency) {
            $query->andWhere(['company.currency_code' => $currency]);
        }

        return new ActiveDataProvider([
            'query' => $query
        ]);
    }

    /**
     * Get sub-companies
     * 
     * @OA\Get(
     *     path="/company/{id}/sub-companies",
     *     summary="Get sub-companies",
     *     description="Get list of sub-companies for a parent company",
     *     tags={"Company Management"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Parent company ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="currency",
     *         in="header",
     *         description="Currency code (default: KWD)",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of sub-companies",
     *         @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Company"))
     *     ),
     *     @OA\Response(response=404, description="Company not found"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     * @param $id
     * @return ActiveDataProvider
     */
    public function actionSubCompanies($id)
    {
        $currency = Yii::$app->request->headers->get("currency", "KWD");

        $query = Company::find()
            ->with([
                'stores.candidates', 
                'stores.candidates.store', 
                'stores.candidates.company', 
                'stores.candidates.bank',
                'stores.candidates.university'
            ])    
            ->childCompany($id);

        if($currency) {
            $query->andWhere(['company.currency_code' => $currency]);
        }

        return new ActiveDataProvider([
            'query' => $query
        ]);
    }

    /**
     * Create company account
     * 
     * @OA\Post(
     *     path="/company/create",
     *     summary="Create company",
     *     description="Create a new company account (parent or sub-company)",
     *     tags={"Company Management"},
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             required={"name"},
     *             @OA\Property(property="name", type="string", description="Company name"),
     *             @OA\Property(property="email", type="string", format="email", description="Company email (required for parent company)"),
     *             @OA\Property(property="password", type="string", format="password", description="Password (required for parent company)"),
     *             @OA\Property(property="parent", type="integer", description="Parent company ID (for sub-company)"),
     *             @OA\Property(property="country_id", type="integer", description="Country ID"),
     *             @OA\Property(property="currency_code", type="string", description="Currency code"),
     *             @OA\Property(property="hourly_rate", type="number", description="Hourly rate"),
     *             @OA\Property(property="bonus_commission", type="number", description="Bonus commission"),
     *             @OA\Property(property="common_name_en", type="string", description="Common name (English)"),
     *             @OA\Property(property="common_name_ar", type="string", description="Common name (Arabic)"),
     *             @OA\Property(property="description_en", type="string", description="Description (English)"),
     *             @OA\Property(property="description_ar", type="string", description="Description (Arabic)"),
     *             @OA\Property(property="website", type="string", description="Website URL"),
     *             @OA\Property(property="logo", type="string", description="Logo URL"),
     *             @OA\Property(property="commercial_licence", type="string", description="Commercial licence"),
     *             @OA\Property(property="approved_to_hire", type="boolean", description="Approved to hire flag")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Company created successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="operation", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Company account successfully created")
     *         )
     *     ),
     *     @OA\Response(response=400, description="Validation error"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     * @return array
     */
    public function actionCreate()
    {
        // Attempt to create new account
        $model = new Company();
        $model->currency_code = Yii::$app->request->getBodyParam('currency_code', "KWD");
        $model->country_id = Yii::$app->request->getBodyParam('country_id');

        if(!$model->currency_code) {
            $model->currency_code = Yii::$app->request->headers->get("Currency", "KWD");
        }

        $model->scenario = 'adminCreate';
        $transaction = Yii::$app->db->beginTransaction();

        if (Yii::$app->request->getBodyParam('parent')) {
            $model->scenario = "newSubAccount";
            $model->parent_company_id =Yii::$app->request->getBodyParam("parent");
        } else {
            $model->scenario = "newAccount";
            $model->company_email =Yii::$app->request->getBodyParam("email");
        }

        if ($model->scenario == "newAccount") {

            $contactModel = new Contact();

            $contactModel->contact_name = ucfirst(Yii::$app->request->getBodyParam("name"));
            $contactModel->contact_email = Yii::$app->request->getBodyParam("email");
            $contactModel->contact_password_hash = Yii::$app->security->generatePasswordHash(Yii::$app->request->getBodyParam("password"));
            $contactModel->contact_receive_email = 1;

            if (!$contactModel->sendVerificationEmail()) {

                $transaction->rollBack();

                return [
                    "operation" => "error",
                    "message" => $contactModel->errors
                ];
            }
        }

        $model->company_name = Yii::$app->request->getBodyParam("name");
        $model->company_hourly_rate = Yii::$app->request->getBodyParam("hourly_rate");
        $model->company_bonus_commission = Yii::$app->request->getBodyParam("bonus_commission");
        $model->company_common_name_en = Yii::$app->request->getBodyParam("common_name_en");
        $model->company_common_name_ar = Yii::$app->request->getBodyParam("common_name_ar");
        $model->company_description_en = Yii::$app->request->getBodyParam("description_en");
        $model->company_description_ar = Yii::$app->request->getBodyParam("description_ar");
        $model->company_website = Yii::$app->request->getBodyParam("website");
        $model->company_logo = Yii::$app->request->getBodyParam("logo");
        $model->commercial_licence = Yii::$app->request->getBodyParam("commercial_licence");
        $model->company_approved_to_hire = Yii::$app->request->getBodyParam("approved_to_hire");

        if (!$model->save()) {
            $transaction->rollBack();
            if(isset($model->errors)){
                return [
                    "operation" => "error",
                    "message" => $model->errors
                ];
            } else {
                return [
                    "operation" => "error",
                    "message" => "We've faced a problem creating the account, please contact us for assistance."
                ];
            }
        }

        if ($model->scenario == "newAccount") {
            $companyContact = new CompanyContact();
            $companyContact->company_id = $model->company_id;
            $companyContact->contact_uuid = $contactModel->contact_uuid;
            $companyContact->contact_position = 'CEO';
            $companyContact->allow_access = true;

            if (!$companyContact->save()) {
                $transaction->rollBack();

                return [
                    "operation" => "error",
                    "message" => $companyContact->errors
                ];
            }
        }
        $transaction->commit();

        Yii::info('['.$model->company_name.' Company Account Created] Company created by '.Yii::$app->user->identity->admin_name, __METHOD__);

        return [
            "operation" => "success",
            "message" => "Company account successfully created"
        ];

        // Check SQL Query Count and Duration
        return Yii::getLogger()->getDbProfiling();
    }

    /**
     * Get company details
     * 
     * @OA\Get(
     *     path="/company/{id}",
     *     summary="Get company",
     *     description="Get detailed information about a company",
     *     tags={"Company Management"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Company ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Company details",
     *         @OA\JsonContent(ref="#/components/schemas/Company")
     *     ),
     *     @OA\Response(response=404, description="Company not found"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     * @param $id
     * @return array|null|\yii\db\ActiveRecord
     */
    public function actionView($id)
    {
        $company = Company::find()
            ->filterCompany($id)
            ->one();

        if(!$company){
            throw new NotFoundHttpException('The requested page does not exist.');
        }

        return $company;
    }

    /**
     * Update company account
     * 
     * @OA\Patch(
     *     path="/company/{id}",
     *     summary="Update company",
     *     description="Update an existing company account",
     *     tags={"Company Management"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Company ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="name", type="string", description="Company name"),
     *             @OA\Property(property="email", type="string", format="email", description="Company email"),
     *             @OA\Property(property="parent", type="integer", description="Parent company ID"),
     *             @OA\Property(property="country_id", type="integer", description="Country ID"),
     *             @OA\Property(property="currency_code", type="string", description="Currency code"),
     *             @OA\Property(property="hourly_rate", type="number", description="Hourly rate"),
     *             @OA\Property(property="bonus_commission", type="number", description="Bonus commission"),
     *             @OA\Property(property="common_name_en", type="string", description="Common name (English)"),
     *             @OA\Property(property="common_name_ar", type="string", description="Common name (Arabic)"),
     *             @OA\Property(property="description_en", type="string", description="Description (English)"),
     *             @OA\Property(property="description_ar", type="string", description="Description (Arabic)"),
     *             @OA\Property(property="website", type="string", description="Website URL"),
     *             @OA\Property(property="logo", type="string", description="Logo URL"),
     *             @OA\Property(property="commercial_licence", type="string", description="Commercial licence"),
     *             @OA\Property(property="approved_to_hire", type="boolean", description="Approved to hire flag")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Company updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="operation", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Company account successfully updated")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Company not found"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     * @param $id
     * @return array
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel((int) $id);

        if (!$model) {
            return [
                    "operation" => "error",
                    "message" => "Company account not found"
                ];
        }
        
        $model->scenario = 'adminUpdate';
        $model->currency_code = Yii::$app->request->getBodyParam('currency_code', "KWD");

        if(!$model->currency_code) {
            $model->currency_code = Yii::$app->request->headers->get("Currency", "KWD");
        }

        $model->country_id = Yii::$app->request->getBodyParam('country_id');
        $model->company_name = Yii::$app->request->getBodyParam("name");
        $model->company_email =Yii::$app->request->getBodyParam("email");
        $model->parent_company_id = Yii::$app->request->getBodyParam("parent");
        $model->company_hourly_rate = Yii::$app->request->getBodyParam("hourly_rate");
        $model->company_bonus_commission = Yii::$app->request->getBodyParam("bonus_commission");
        $model->company_common_name_en = Yii::$app->request->getBodyParam("common_name_en");
        $model->company_common_name_ar = Yii::$app->request->getBodyParam("common_name_ar");
        $model->company_description_en = Yii::$app->request->getBodyParam("description_en");
        $model->company_description_ar = Yii::$app->request->getBodyParam("description_ar");
        $model->company_website = Yii::$app->request->getBodyParam("website");
        $model->company_logo = Yii::$app->request->getBodyParam("logo");
        $model->commercial_licence = Yii::$app->request->getBodyParam("commercial_licence");
        $model->company_approved_to_hire = Yii::$app->request->getBodyParam("approved_to_hire");

        if (!$model->save()) {
            if (isset($model->errors)) {
                return [
                    "operation" => "error",
                    "message" => $model->errors
                ];
            } else {
                return [
                    "operation" => "error",
                    "message" => "We've faced a problem updating the account, please contact us for assistance"
                ];
            }
        }

        Yii::info('['.$model->company_name.' Company Account Updated] Company updated by '.Yii::$app->user->identity->admin_name, __METHOD__);

        return [
            "operation" => "success",
            "message" => "Company account successfully updated"
        ];

        // Check SQL Query Count and Duration
        return Yii::getLogger()->getDbProfiling();
    }

    /**
     * Delete company account
     * 
     * @OA\Delete(
     *     path="/company/{id}",
     *     summary="Delete company",
     *     description="Soft delete a company account (only if no stores, transfers, or sub-companies)",
     *     tags={"Company Management"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Company ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Company deleted successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="operation", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Company account successfully deleted")
     *         )
     *     ),
     *     @OA\Response(response=400, description="Cannot delete (has stores/transfers/sub-companies)"),
     *     @OA\Response(response=404, description="Company not found"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     * @param  integer $id
     * @return array
     */
    public function actionDelete($id)
    {
        $company = $this->findModel((int) $id);

        if ($company) {

            if (count($company->stores)>0) {
                return [
                    "operation" => "error",
                    "message" => "Company has multiple store."
                ];
            }

            if (count($company->transfers)>0) {
                return [
                    "operation" => "error",
                    "message" => "Company has multiple transfers."
                ];
            }

            if (count($company->subCompanies) > 0) {
                return [
                    "operation" => "error",
                    "message" => "Company has multiple Sub Company."
                ];
            }

            Yii::info('[Company Account Soft Deleted] Company "'.$company->company_name.'" soft deleted by Admin: "'.Yii::$app->user->identity->admin_name.'"', __METHOD__);

            // Delete the account
            $company->softDelete();

            return [
                "operation" => "success",
                "message" => "Company account successfully deleted"
            ];

        }else{
            return [
                "operation" => "error",
                "message" => "Company account not found or already deleted"
            ];
        }

        // Error for cases not accounted for
        return [
            "operation" => "error",
            "message" => "Unknown error occurred, please contact us for assistance"
        ];

        // Check SQL Query Count and Duration
        return Yii::getLogger()->getDbProfiling();
    }

    /**
     * Delete company file
     * 
     * @OA\Delete(
     *     path="/company/{id}/delete-file",
     *     summary="Delete company file",
     *     description="Delete a file/document associated with a company",
     *     tags={"Company Management"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="File UUID",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="File deleted successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="operation", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Company Document successfully deleted")
     *         )
     *     ),
     *     @OA\Response(response=404, description="File not found"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     * @param  integer $id
     * @return array
     */
    public function actionDeleteFile($id)
    {
        $model = File::findOne(['file_uuid'=>$id]);

        if (!$model) {
            return [
                "operation" => "error",
                "message" => "Invalid File"
            ];
        }

        if (!$model->deleteDocument()) {
            return [
                "operation" => "error",
                "message" => $model->errors
            ];
        }
        if (!$model->delete()) {
            if (isset($model->errors)) {
                return [
                    "operation" => "error",
                    "message" => $model->errors
                ];
            } else {
                return [
                    "operation" => "error",
                    "message" => "We've faced a problem updating the account, please contact us for assistance"
                ];
            }
        }
        return [
            "operation" => "success",
            "message" => "Company Document successfully deleted"
        ];
        // Check SQL Query Count and Duration
        return Yii::getLogger()->getDbProfiling();
    }
    
    /**
     * Finds the Company model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Company the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Company::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }

    /**
     * Upload company file
     * 
     * @OA\Post(
     *     path="/company/{id}/create-file",
     *     summary="Upload company file",
     *     description="Upload a file/document for a company",
     *     tags={"Company Management"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Company ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             required={"file_title", "file_s3_path"},
     *             @OA\Property(property="file_title", type="string", description="File title"),
     *             @OA\Property(property="file_description", type="string", description="File description"),
     *             @OA\Property(property="file_s3_path", type="string", description="S3 path to the file")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="File uploaded successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="operation", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Company document uploaded successfully")
     *         )
     *     ),
     *     @OA\Response(response=400, description="Validation error"),
     *     @OA\Response(response=404, description="Company not found"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     * @param $id
     * @return array
     */
    public function actionCreateFile($id)
    {
        $model = new File();
        $model->scenario = 'create';
        $model->file_title = Yii::$app->request->getBodyParam("file_title");
        $model->file_description =Yii::$app->request->getBodyParam("file_description");
        $model->file_s3_path = Yii::$app->request->getBodyParam("file_s3_path");
        $model->file_name = $model->file_s3_path;
        $model->company_id = $id;

        if (!$model->save()) {
            if (isset($model->errors)) {
                return [
                    "operation" => "error",
                    "message" => $model->errors
                ];
            } else {
                return [
                    "operation" => "error",
                    "message" => "We've faced a problem updating the account, please contact us for assistance"
                ];
            }
        }

        Yii::info('['.$model->file_title. ' document upload for company '.$model->company->company_name.'] Company updated by '.Yii::$app->user->identity->admin_name, __METHOD__);

        return [
            "operation" => "success",
            "message" => "Company document uploaded successfully"
        ];

        // Check SQL Query Count and Duration
        return Yii::getLogger()->getDbProfiling();
    }

    /**
     * Update company file
     * 
     * @OA\Patch(
     *     path="/company/{id}/update-file",
     *     summary="Update company file",
     *     description="Update file metadata (title, description)",
     *     tags={"Company Management"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="File UUID",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="file_title", type="string", description="File title"),
     *             @OA\Property(property="file_description", type="string", description="File description")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="File updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="operation", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Company document data updated successfully")
     *         )
     *     ),
     *     @OA\Response(response=404, description="File not found"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     * @param $id
     * @return array|string[]
     */
    public function actionUpdateFile($id)
    {
        $model = File::findOne(['file_uuid'=>$id]);

        if (!$model) {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
        
        $model->scenario = 'update';
        $model->file_title = Yii::$app->request->getBodyParam("file_title");
        $model->file_description =Yii::$app->request->getBodyParam("file_description");

        if (!$model->save()) {
            if (isset($model->errors)) {
                return [
                    "operation" => "error",
                    "message" => $model->errors
                ];
            } else {
                return [
                    "operation" => "error",
                    "message" => "We've faced a problem updating the account, please contact us for assistance"
                ];
            }
        }

        Yii::info('['.$model->file_title. ' document upload for company '.$model->company->company_name.'] Company updated by '.Yii::$app->user->identity->admin_name, __METHOD__);

        return [
            "operation" => "success",
            "message" => "Company document data updated successfully"
        ];

        // Check SQL Query Count and Duration
        return Yii::getLogger()->getDbProfiling();
    }

    /**
     * Change company status
     * 
     * @OA\Post(
     *     path="/company/{id}/change-status",
     *     summary="Change company status",
     *     description="Override company status (active/inactive)",
     *     tags={"Company Management"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Company ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             required={"status"},
     *             @OA\Property(property="status", type="integer", description="Status override value")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Status changed successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="operation", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Company account status changed successfully")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Company not found"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     * @param $id
     * @return array|string[]
     * @throws NotFoundHttpException
     */
    public function actionChangeStatus($id) {

        $model = $this->findModel((int) $id);

        $model->scenario = 'updateStatus';

        $model->company_status_override = Yii::$app->request->getBodyParam("status");

        if (!$model->save()) {
            if (isset($model->errors)) {
                return [
                    "operation" => "error",
                    "message" => $model->errors
                ];
            } else {
                return [
                    "operation" => "error",
                    "message" => "We've faced a problem updating the account, please contact us for assistance"
                ];
            }
        }

        Yii::info('['.$model->company_name.' Company Account Updated] Company status updated by '.Yii::$app->user->identity->admin_name, __METHOD__);

        return [
            "operation" => "success",
            "message" => "Company account status changed successfully"
        ];

        // Check SQL Query Count and Duration
        return Yii::getLogger()->getDbProfiling();
    }

    /**
     * Update company assigned staff
     * 
     * @OA\Patch(
     *     path="/company/{id}/update-staff",
     *     summary="Update assigned staff",
     *     description="Update the staff member assigned to manage this company",
     *     tags={"Company Management"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Company ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             required={"staff_id"},
     *             @OA\Property(property="staff_id", type="integer", description="Staff ID to assign")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Staff updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="operation", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Company account staff changed successfully")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Company not found"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     * @param $id
     * @return array|string[]
     * @throws NotFoundHttpException
     */
    public function actionUpdateStaff($id) {

        $model = $this->findModel((int) $id);

        $model->scenario = 'updateStaff';

        $model->staff_id = Yii::$app->request->getBodyParam("staff_id");

        if (!$model->save()) {
            if (isset($model->errors)) {
                return [
                    "operation" => "error",
                    "message" => $model->errors
                ];
            } else {
                return [
                    "operation" => "error",
                    "message" => "We've faced a problem updating the account, please contact us for assistance"
                ];
            }
        }

        Yii::info('['.$model->company_name.' Company Account Updated] Company Manage by updated by '.Yii::$app->user->identity->admin_name, __METHOD__);

        return [
            "operation" => "success",
            "message" => "Company account staff changed successfully"
        ];

        // Check SQL Query Count and Duration
        return Yii::getLogger()->getDbProfiling();
    }

    /**
     * Update company followup status
     * 
     * @OA\Patch(
     *     path="/company/{id}/update-followup",
     *     summary="Update followup status",
     *     description="Update the followup status for a company",
     *     tags={"Company Management"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Company ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             required={"followup"},
     *             @OA\Property(property="followup", type="string", description="Followup status")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Followup updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="operation", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Company account followup status changed successfully")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Company not found"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     * @param $id
     * @return array|string[]
     * @throws NotFoundHttpException
     */
    public function actionUpdateFollowup($id) {

        $model = $this->findModel((int) $id);

        if (!$model) {
            return [
                "operation" => "error",
                "message" => "Company account not found"
            ];
        }

        $model->scenario = 'updateFollowup';

        $model->company_followup = Yii::$app->request->getBodyParam("followup");

        if (!$model->save()) {
            if (isset($model->errors)) {
                return [
                    "operation" => "error",
                    "message" => $model->errors
                ];
            } else {
                return [
                    "operation" => "error",
                    "message" => "We've faced a problem updating the account, please contact us for assistance"
                ];
            }
        }

        Yii::info('['.$model->company_name.' Company Account Updated] Company followup status updated by '.Yii::$app->user->identity->admin_name, __METHOD__);

        return [
            "operation" => "success",
            "message" => "Company account followup status changed successfully"
        ];

        // Check SQL Query Count and Duration
        return Yii::getLogger()->getDbProfiling();
    }

    /**
     * Update company followup interval
     * 
     * @OA\Patch(
     *     path="/company/{id}/update-followup-interval",
     *     summary="Update followup interval",
     *     description="Update the followup interval in weeks for a company",
     *     tags={"Company Management"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Company ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             required={"followup_interval_weeks"},
     *             @OA\Property(property="followup_interval_weeks", type="integer", description="Followup interval in weeks")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Followup interval updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="operation", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Company account followup interval changed successfully")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Company not found"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     * @param $id
     * @return array|string[]
     * @throws NotFoundHttpException
     */
    public function actionUpdateFollowupInterval($id) {

        $model = $this->findModel((int) $id);

        if (!$model) {
            return [
                "operation" => "error",
                "message" => "Company account not found"
            ];
        }

        $model->scenario = 'updateFollowupInterval';

        $model->company_followup_interval_weeks = Yii::$app->request->getBodyParam("followup_interval_weeks");

        if (!$model->save()) {
            if (isset($model->errors)) {
                return [
                    "operation" => "error",
                    "message" => $model->errors
                ];
            } else {
                return [
                    "operation" => "error",
                    "message" => "We've faced a problem updating the account, please contact us for assistance"
                ];
            }
        }

        Yii::info('['.$model->company_name.' Company Account Updated] Company followup interval updated by '.Yii::$app->user->identity->admin_name, __METHOD__);

        return [
            "operation" => "success",
            "message" => "Company account followup interval changed successfully"
        ];
    }

    /**
     * Get company year report
     * 
     * @OA\Get(
     *     path="/company/year-report",
     *     summary="Get year report",
     *     description="Get monthly statistics for a year (requests, suggestions, hired candidates)",
     *     tags={"Company Management"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="year",
     *         in="query",
     *         description="Year (default: current year)",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="company_id",
     *         in="query",
     *         description="Filter by company ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Year report data",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="request", type="integer", description="Number of requests"),
     *                 @OA\Property(property="suggestions", type="integer", description="Number of suggestions"),
     *                 @OA\Property(property="hired", type="integer", description="Number of hired candidates"),
     *                 @OA\Property(property="month", type="string", description="Month name"),
     *                 @OA\Property(property="month_number", type="string", description="Month number")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     * @return array|\yii\db\DataReader
     * @throws \yii\db\Exception
     */
    public function actionYearReport() {
        $year = Yii::$app->request->get("year", date('Y'));
        $company_id = Yii::$app->request->get("company_id" , null);

        $stats = [];
        foreach(range(1,12) as $key => $value) {
            $value = str_pad($value,2,"0", STR_PAD_LEFT);
            // request
            $q = 'SELECT count(*) as total, MONTH(request_created_datetime) as month, YEAR(request_created_datetime) as year, monthname(str_to_date(MONTH(request_created_datetime),"%m")) as monthName FROM `request` WHERE';
            if ($company_id) {
                $q .= ' `company_id`=' . $company_id . ' AND ';
            }
            $q .= ' YEAR(request_created_datetime) = '.$year.' AND MONTH(request_created_datetime) = '.$value;

            $records = \Yii::$app->db->createCommand($q)->queryOne();

            $stats[$key]['request'] = $records['total'];
            $stats[$key]['month'] = date("F", mktime(0, 0, 0, $value, 10));
            $stats[$key]['month_number'] = $value;

            // suggestions
            $q = 'SELECT count(*) as total FROM `suggestion` left join request on request.request_uuid = suggestion.request_uuid WHERE';
            if ($company_id) {
                $q .= ' `request`.`company_id`=' . $company_id . ' AND ';
            }
            $q .= ' YEAR(suggestion_datetime) = '.$year.' AND MONTH(suggestion_datetime) = '.$value;

            $suggestion = \Yii::$app->db->createCommand($q)->queryOne();

            $stats[$key]['suggestions'] = $suggestion['total'];

            // hired
            $q = 'SELECT count(*) as total FROM `candidate_work_history` WHERE';
            if ($company_id) {
                $q .= ' (`company_id`=' . $company_id . ' OR `parent_company_id`=' . $company_id . ') AND ';
            }
            $q .= ' YEAR(start_date) = '.$year.' AND MONTH(start_date) = '.$value;

            $joined = \Yii::$app->db->createCommand($q)->queryOne();

            $stats[$key]['hired'] = $joined['total'];
        }

        return $stats;
    }

    /**
     * Download company candidates Excel
     * 
     * @OA\Get(
     *     path="/company/{id}/download-candidates-excel",
     *     summary="Download candidates Excel",
     *     description="Download Excel file with all candidates who worked for the company",
     *     tags={"Company Management"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Company ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Excel file download",
     *         @OA\MediaType(
     *             mediaType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
     *         )
     *     ),
     *     @OA\Response(response=404, description="Company not found"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     * @return void
     * @throws \yii\db\Exception
     */
    public function actionDownloadCandidatesExcel($id)
    {
        /*$sql = "SELECT candidate.candidate_id, candidate_name, candidate_phone, candidate_email, start_date, end_date,
            candidate_work_history.store_id, store.store_name, candidate_work_history.candidate_hourly_rate 
            FROM `candidate_work_history`
            left join candidate on candidate.candidate_id = candidate_work_history.candidate_id
            left join store on store.store_id = candidate_work_history.store_id
            where candidate_work_history.company_id=".$id." 
            order by end_date DESC";

        SELECT `candidate_work_history`.* FROM `candidate_work_history`
        LEFT JOIN `candidate` WHERE `candidate_work_history`.`company_id`='23' ORDER BY `end_date` DESC",
        */

        $candidates = CandidateWorkHistory::find()
           // ->joinWith(['candidate', 'store']) //not listing deleted candidates
            ->leftJoin('candidate', "candidate.candidate_id = candidate_work_history.candidate_id")
            ->leftJoin('store', "store.store_id = candidate_work_history.store_id")
            ->andWhere(["candidate_work_history.company_id" => $id])
            ->orderBy("end_date DESC")
            ->all();

        $transfers = [];

        foreach ($candidates as $candidate) {

            if(empty($candidate['candidate_id']))
                continue;

            //select SUM(candidate_total) as candidate_total, SUM(company_total - candidate_total - transfer_cost) as revenue
            // from transfer_candidate where candidate_id=836 and store_id=111;

            $transfers[$candidate['candidate_id']] = TransferCandidate::find()
                ->select("SUM(candidate_total) as candidateTotal, SUM(company_total - candidate_total) as revenue")
                ->andWhere([
                    'candidate_id' => $candidate['candidate_id'],
                    "company_id" => $id,
                    "paid" => 1 //paid by company
                ])
                ->asArray()
                //->groupBy("candidate_id")
                ->one();
        }

        header('Access-Control-Allow-Origin: *');

        \common\components\PhpExcel::export([
            'isMultipleSheet' => false,
            'models' => $candidates,
            'columns' => [
                [
                    'attribute'=> 'candidate.candidate_id',
                    'label'=> 'Candidate ID',
                ],
                [
                    'attribute'=> 'candidate.candidate_name',
                    'label'=> 'Candidate Name',
                ],
                [
                    'attribute'=> 'candidate.candidate_phone',
                    'label'=> 'Phone',
                ],
                [
                    'attribute'=> 'candidate.candidate_email',
                    'label'=> 'Email',
                ],
                'start_date',
                "end_date",
                [
                    'attribute'=> "store_id",
                    //"store_id"
                ],
                [
                    'attribute'=> "store.store_name",
                    'label'=> "Store",
                ],
                [
                    'attribute'=> "candidate_hourly_rate",
                    'label'=>"Candidate Hourly Rate",
                ],
                [
                    'attribute'=> "Total money transferred",
                    'label'=> "Total money transferred",
                    'value' => function($model) use ($transfers) {
                        return isset($transfers[$model['candidate_id']])?
                            $transfers[$model['candidate_id']]['candidateTotal']: null;
                    }
                ],
                [
                    'attribute'=> 'Total money we made',
                    'label'=> "Total money we made",
                    'value' => function($model) use ($transfers) {
                        return isset($transfers[$model['candidate_id']])?
                            $transfers[$model['candidate_id']]['revenue']: null;
                    }
                ],
            ]
        ]);
    }

    /**
     * Download companies list Excel
     * 
     * @OA\Get(
     *     path="/company/download-list-excel",
     *     summary="Download companies Excel",
     *     description="Download Excel file with list of companies",
     *     tags={"Company Management"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by status: 0=all, 1=active, 2=inactive, 3=active 40+ days without payment",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="name",
     *         in="query",
     *         description="Filter by company name",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="approved_to_hire",
     *         in="query",
     *         description="Filter by approved to hire status",
     *         @OA\Schema(type="boolean")
     *     ),
     *     @OA\Parameter(
     *         name="last_payment_from",
     *         in="query",
     *         description="Filter by last payment date (from)",
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="last_payment_to",
     *         in="query",
     *         description="Filter by last payment date (to)",
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Excel file download",
     *         @OA\MediaType(
     *             mediaType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     * @return ActiveDataProvider
     */
    public function actionDownloadListExcel()
    {
        $status = Yii::$app->request->getQueryParam("status",0);
        $name = Yii::$app->request->getQueryParam("name",0);
        $approved_to_hire = Yii::$app->request->getQueryParam("approved_to_hire");
        $last_payment_from = Yii::$app->request->getQueryParam("last_payment_from");
        $last_payment_to = Yii::$app->request->getQueryParam("last_payment_to");

        $query = Company::find()
            ->filterParent();

        if($last_payment_from && $last_payment_to) {
            $query->filterByLastPaymentRange(
                date("Y-m-d", strtotime($last_payment_from)),
                date("Y-m-d", strtotime($last_payment_to))
            );
        }

        if ($status == 1) {
            $query->filterActive();
        }

        if ($status == 2) {
            $query->filterInActive();
        }

        if ($status == 3) {
            $query->filterByActive40DaysPassedWithoutPayment();
        }

        if ($name) {
            $query->filterByName($name);
        }

        if (!is_null($approved_to_hire) && in_array ($approved_to_hire, [0, 1])) {
            $query->filterByApprovedToHire($approved_to_hire);
        }

        header('Access-Control-Allow-Origin: *');

        \common\components\PhpExcel::export([
            'isMultipleSheet' => false,
            'models' => $query->all(),
            'columns' => [
                'company_id',
                'company_name',
                'company_common_name_en',
                'company_common_name_ar',
                'company_email',
                [
                    'attribute'=>'company_status',
                    'label'=>'Company Status',
                    'value'=>function($model) {
                        return ($model->company_status) ? 'Active':'InActive';
                    }
                ],
                [
                    'attribute'=>'total_suggestions',
                    'label'=>'Total Suggestions',
                    'value'=>function($model) {
                        return $model->getSuggestions()->count();
                    }
                ],
                [
                    'attribute'=>'company_bonus_commission',
                    'label'=>'company bonus commission',
                    'value'=>function($model) {
                        if($model->company_bonus_commission)
                            return (double)$model->company_bonus_commission;

                        if($model->parentCompany)
                            return (double)$model->parentCompany->company_bonus_commission;
                    }
                ],
                [
                    'attribute'=>'company_hourly_rate',
                    'label'=>'Company Hourly Rate',
                    'value'=>function($model) {
                        if($model->company_hourly_rate)
                            return (double)$model->company_hourly_rate;

                        if($model->parentCompany)
                            return (double)$model->parentCompany->company_hourly_rate;
                    }
                ],
                [
                    'attribute'=>'total_candidates',
                    'label'=>'Total Candidates',
                    'value'=>function($model) {
                        return (int)\common\models\Company::getTotalCandidateCount($model->company_id);
                    }
                ],
                [
                    'attribute'=>'total_subcompanies',
                    'label'=>'Total SubCompanies',
                    'value'=>function($model) {
                        return (int)$model->getSubCompanies()->count();
                    }
                ],
                /*[
                    'attribute'=>'total_subcompanies',
                    'label'=>'Total SubCompanies',
                    'value'=>function($model) {
                        return (int)$model->getSubCompanies()->count();
                    }
                ],*/
                [
                    'attribute'=>'total_stores',
                    'label'=>'Total Stores',
                    'value'=>function($model) {
                        return (int)$model->getStores()->count();
                    }
                ],
                "last_payment_datetime"
            ]
        ]);
    }
}
