<?php 
namespace api\controllers;

use Yii;
use yii\rest\ActiveController;
use yii\filters\auth\HttpBearerAuth;

use api\models\EventType;

class EventController extends ActiveController
{
    public $modelClass = 'api\models\Event';
    
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['authenticator'] = [
            'class' => HttpBearerAuth::className(),
        ];
        return $behaviors;
    } // end behaviors
    
    public function actions() {
        $actions = parent::actions();
        
        // disable the "delete" and "create" actions
        unset($actions['delete']);
        
        $actions['index']['prepareDataProvider'] = function ($action) {
            return Yii::$app->user->identity->events;
        };
        
        return $actions;
    } // end actions

    public function checkAccess($action, $model = null, $params = [])
    {
        if (in_array($action, ['update'])) {
            
            if ($model->user->id !== Yii::$app->user->identity->id) {
                throw new \yii\web\ForbiddenHttpException('You can\'t '.$action.' this event.');
            }
        }
    } // end checkAccess
    
    public function actionTypes()
    {
        $data = EventType::find()->all();
        
        $events = array();
        foreach ($data as $row) {
            $events[$row['category']][] = $row;
        }
        
        return $events;
    } // end actionTypes
}
