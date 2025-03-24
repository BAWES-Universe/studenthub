<?php

namespace console\controllers;

use admin\models\Staff;
use common\models\CronLog;
use Yii;
use yii\db\Expression;

/**
 * cron command: ./yii report/recruiter
 */
class ReportController extends \yii\console\Controller
{
    //no of invitation, assignment, story completed, requests updated, notes added etc per staff every day
    public function actionRecruiter() {

        $today = (new \DateTime())->format("jS F, Y");

        $staffs = Staff::find()
            ->andWhere(["staff_role" => Staff::ROlE_RECRUITER])
            //->asArray()
            ->all();

        //$report = [];

        foreach ($staffs as $staff) {

            $data = [
                "staff_email" => $staff->staff_email,
                "staff_name" => $staff->staff_name,
                'totalAssigned' => $staff->getTotalAssigned(new Expression("DATE(start_date) >= DATE('" .
                    date('Y-m-d') . "')")),
                'totalRequests' => $staff->getTotalRequests(new Expression("DATE(request_created_datetime) >= DATE('" .
                    date('Y-m-d') . "')")),
                'totalNotes' => $staff->getTotalNotes(new Expression("DATE(note_created_datetime) >= DATE('" .
                    date('Y-m-d') . "')")),
                'totalStories' => $staff->getTotalStories(new Expression("DATE(story_created_at) >= DATE('" .
                    date('Y-m-d') . "')")),
                'totalAcceptedInvitations'=> $staff->getTotalAcceptedInvitations(new Expression("DATE(invitation_created_at) >= DATE('" .
                    date('Y-m-d') . "')")),
                'totalRejectedInvitations' => $staff->getTotalRejectedInvitations(new Expression("DATE(invitation_created_at) >= DATE('" .
                    date('Y-m-d') . "')")),
                'totalSuggestions' => $staff->getTotalSuggestions(new Expression("DATE(note_created_datetime) >= DATE('" .
                    date('Y-m-d') . "')")),
                'totalInvitations' => $staff->getTotalInvitations(new Expression("DATE(invitation_created_at) >= DATE('" .
                    date('Y-m-d') . "')")),
                'totalCompletedStories' => $staff->getTotalCompletedStories(new Expression("DATE(story_created_at) >= DATE('" .
                    date('Y-m-d') . "')")),
                'totalStoryEmployees' => $staff->getTotalStoryEmployees(new Expression("DATE(story_created_at) >= DATE('" .
                    date('Y-m-d') . "')")),
                'timeForCompletedStories' => $staff->getTimeForCompletedStories(new Expression("DATE(story_created_at) >= DATE('" .
                    date('Y-m-d') . "')")),
            ];

            Yii::$app->eventManager->track('Recruitment Report',
                $data
            );

            Yii::$app->slack->send($staff->staff_name . ': Recruitment Report ['.$today.']', 'https://avatars.slack-edge.com/2020-07-17/1240789773942_faacc2fa0634b304a43b_72.png', [
                [
                    // attachment object
                    'text' => 'Assigned: ' . $data['totalAssigned'] .
                        ' Notes: '. $data['totalNotes'].
                        ' Stories: ' . $data['totalStories'] .
                        ' Accepted Invitations: ' . $data['totalAcceptedInvitations'] .
                        ' Rejected Invitations: ' . $data['totalRejectedInvitations'] .
                        ' Suggestions: ' .$data['totalSuggestions'] .
                        ' Invitations: ' .$data['totalInvitations'] .
                        ' Completed Stories: ' .$data['totalCompletedStories'] .
                        ' Story Employees: ' .$data['totalStoryEmployees'] .
                        ' Time for Completed Stories: ' .$data['timeForCompletedStories'],
                    //'pretext' => $staff->staff_name,
                ],
            ]);

            //$report[] = $data;
        }

        //todo: send to notion, email pdf report etc,...

        CronLog::updateAll(['last_ran_at' => date('Y-m-d H:i:s')],
            ['task' => 'report/recruiter']);
    }
}