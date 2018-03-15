<?php 
namespace api\controllers;

use Yii;
use yii\rest\ActiveController;
use yii\filters\auth\HttpBearerAuth;

class BusinessController extends ActiveController
{
    public $modelClass = 'common\models\Business';
    
    public function behaviors()
    {
        $behaviors = parent::behaviors();
//         $behaviors['authenticator'] = [
//             'class' => HttpBearerAuth::className(),
//         ];
        return $behaviors;
    } // end behaviors
    
    public function actions() {
        $actions = parent::actions();
        
        // disable the "delete" and "create" actions
        unset($actions['index']);
        unset($actions['update']);
        unset($actions['delete']);
        
//         $actions['index']['prepareDataProvider'] = function ($action) {
//             return Yii::$app->user->identity->businesses;
//         };
        
        return $actions;
    } // end actions

    public function checkAccess($action, $model = null, $params = [])
    {
        if (in_array($action, ['update'])) {
            
            if ($model->user->id !== Yii::$app->user->identity->id) {
                throw new \yii\web\ForbiddenHttpException('You can\'t '.$action.' this business.');
            }
        }
    } // end checkAccess
    
}
