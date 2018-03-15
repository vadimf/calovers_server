<?php
namespace frontend\controllers;

use Yii;
use yii\web\Controller;

use common\models\Business;

class SiteController extends Controller
{

    /**
     * @inheritdoc
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
        ];
    }

    public function actionIndex()
    {
        $model = new Business; 
        
        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect('/partners');
        } else {
            return $this->render('bussiness', [
                'model' => $model,
                'apiUrl' => Yii::$app->params['api_url']
            ]);
        }
    }
}
