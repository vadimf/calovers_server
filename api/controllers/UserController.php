<?php 
namespace api\controllers;

use Yii;
use yii\rest\Controller;
use yii\filters\auth\HttpBearerAuth;
use yii\web\UploadedFile;

use api\models\UploadImages;

class UserController extends Controller
{
    
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['authenticator'] = [
            'class' => HttpBearerAuth::className(),
        ];
        return $behaviors;
    } // end behaviors

    public function checkAccess($action, $model = null, $params = [])
    {
        if (in_array($action, ['update'])) {
            if ($model->id !== Yii::$app->user->identity->id) {
                throw new \yii\web\ForbiddenHttpException('You can\'t '.$action.' this user.');
            }
        }
    } // end checkAccess
    
    public function actionView()
    {
        $user = Yii::$app->user->identity;
        
        return $user;
    } // end actionView
    
    public function actionUpdate()
    {
        $user = Yii::$app->user->identity;
        
        // HOWTO: it is need for PUT method and fill $_FILES
        $body = Yii::$app->getRequest()->getBodyParams();
        try {
            $avatarFile = UploadedFile::getInstanceByName('avatar');
        } catch (\Exception $e) {
            throw new BadRequestHttpException('Can\'t upload avatar');
        }
        
        $model = new UploadImages;
        $model->uploadPath = 'users/'.$user->id.'/';
        $model->images = [$avatarFile];
        
        $avatarURL = $model->upload();
        
        if (!empty($avatarURL)) {
            $avatarURL = $avatarURL[0];
            $user->avatar_url = $avatarURL['photo'];
            $user->avatar_url_thumbnail = $avatarURL['thumbnail'];
        }
        
        if (!empty($body['avatar_delete'])) {
            $user->avatar_url = null;
            $user->avatar_url_thumbnail = null;
        }
        
        if (isset($body['name'])) {
            $user->name = $body['name'];
        }

        if ($user->save() === false) {
            return $user;
        }
        
        return $user;
    } // end actionUpdate
}
