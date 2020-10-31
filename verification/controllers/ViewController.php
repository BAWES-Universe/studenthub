<?php
namespace verification\controllers;

use Yii;
use yii\web\Controller;
use common\models\Candidate;


/**
 * View controller
 */
class ViewController extends Controller
{
    /**
     * @return \yii\web\Response
     * 400 code for missing variable
     * redirect to home page
     */
    public function actionError()
    {
        $exception = \Yii::$app->errorHandler->exception;

        if ($exception->statusCode == 400) {
            return $this->redirect('https://studenthub.co');
        }
    }

    /**
     * redirect to candidate resume
     * @param $candidate_uid
     * @return string
     * @throws \yii\web\NotFoundHttpException
     */
    public function actionResume($candidate_uid)
    {
        $candidate = Candidate::find()
            ->where([
                'candidate_uid' => $candidate_uid
            ])
            ->one();

        if(!$candidate || !$candidate->candidate_resume)
        {
            return $this->redirect('https://studenthub.co');
        }

        $url = Yii::$app->resourceManager->getUrl("candidate-resume/" . $candidate->candidate_resume);

        return $this->redirect($url)->send();
    }

    /**
     * redirect to candidate video
     * @param $candidate_uid
     * @return string
     * @throws \yii\web\NotFoundHttpException
     */
    public function actionVideo($candidate_uid)
    {
        $candidate = Candidate::find()
            ->where([
                'candidate_uid' => $candidate_uid
            ])
            ->one();

        if(!$candidate || !$candidate->candidate_video)
        {

            return $this->redirect('https://studenthub.co');
        }

        $url = Yii::$app->resourceManager->getUrl('candidate-video/' . $candidate->candidate_video . '.mp4');

        return $this->redirect($url)->send();//302
    }
}
