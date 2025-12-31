<?php

namespace apidoc\controllers;

use Yii;
use yii\web\Controller;
use apidoc\components\OpenApiGenerator;

/**
 * API Documentation Controller
 */
class ApiController extends Controller
{
    public $layout = false;

    /**
     * Display Swagger UI
     * @return string
     */
    public function actionIndex()
    {
        return $this->render('index');
    }

    /**
     * Generate and return OpenAPI JSON specification
     * @return \yii\web\Response
     */
    public function actionOpenapi()
    {
        $generator = new OpenApiGenerator();
        $spec = $generator->generate();

        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        Yii::$app->response->data = $spec;
        
        return Yii::$app->response;
    }
}

