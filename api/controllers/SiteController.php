<?php
namespace api\controllers;

use Aws\Ses\SesClient;

use Yii;
use yii\rest\Controller;
use yii\web\ForbiddenHttpException;
use yii\web\UnauthorizedHttpException;

use api\models\User;
use api\models\UserProfile;
use api\models\UserAuthorization;

use api\models\AmazonAuth;

use api\models\UserFeedback;

/**
 * Site controller
 */
class SiteController extends Controller
{
    public function actionSignin()
    {
        $model = new AmazonAuth();
        
        if ($model->load(Yii::$app->request->post(), '') && $model->validate()) {
            try {
                $user = $model->getUser();
            } catch (\Exception $exp) {
                throw new \Exception(
                    $exp->getAwsErrorMessage(), 
                    $exp->getStatusCode()
                );
            }

            $authData = $model->getAuthData();
            
            $data = $authData->get('UserAttributes');
            $attributes = array();
            foreach ($data as $item) {
                $attributes[$item['Name']] = $item['Value'];
            }
            
            $transaction = User::getDb()->beginTransaction();
            try {
                if ($user) {
                    
                    if ($user->status === 'deleted') {
                        throw new ForbiddenHttpException('User was deleted');
                    }
    
                    $needUpdate = false;
//                     if ($user->name != $attributes['name']) {
//                         $user->name = $attributes['name'];
//                         $needUpdate = true;
//                     }
                    
                    if ($user->status === 'invited') {
                        $user->status = 'accepted';
                        $needUpdate = true;
                    }
//                     var_dump($attributes);exit;
                    if ($needUpdate) {
                        if ($user->save() === false) {
                            throw new \Exception('Can\'t update user');
                        }
                    }
                    
                    $token = $user->getAuthKey();
        
                    $auth = $user->userAuthorization;
                    if (!$token) {
                        $auth = new UserAuthorization;
                        $auth->user_id = $user->getPrimaryKey();
                        $auth->generateToken();
                        
                        if ($auth->save() === false) {
                            throw new \Exception('Can\'t create authorization');
                        }
                    }
    
                } else {
                    
                    $user = new User();
                    $user->phone = $attributes['phone_number'];
                    $user->name = $attributes['name'];
                    $user->status = User::STATUS_ACCEPTED;
                    
                    if ($user->save() !== false) {
                        $profile = new UserProfile;
                        $profile->user_id = $user->getPrimaryKey();
                        $profile->type = 'phone';
                        $profile->ident = $attributes['phone_number'];
                        
                        if ($profile->save() === false) {
                            throw new \Exception('Can\'t create user profile');
                        }
    
                        $auth = new UserAuthorization;
                        $auth->user_id = $user->getPrimaryKey();
                        $auth->generateToken();
                        if ($auth->save() === false) {
                            throw new \Exception('Can\'t create authorization');
                        }
                    } else {
                        throw new \Exception('Can\'t create user');
                    }
                }
                
                $transaction->commit();
            } catch (\Exception $e) {
                $transaction->rollBack();
                throw $e;
            } catch (\Throwable $e) {
                $transaction->rollBack();
                throw $e;
            }
            
            return $auth;
        }
        
        return $model;
    } // end actionSignin
    
    public function actionFeedback()
    {
//         if (Yii::$app->user->isGuest) {
//             throw new UnauthorizedHttpException();
//         }
        
//         $client = SesClient::factory(array(
//             'credentials' => Yii::$app->awssdk->credentials,
//             'region'  => 'eu-west-1',
//             'version' => Yii::$app->awssdk->version,
//         ));
        
//         $result = $client->sendEmail(array(
//             'Source' => Yii::$app->params['adminEmail'],
//             'Destination' => array(
//                 'ToAddresses' => array(
//                     Yii::$app->params['supportEmail']
//                 ),
//                 'CcAddresses' => array(
//                     'mmassalskiy@varteq.com',
//                     'bredotius@gmail.com'
//                 )
//             ),
//             'Message' => array(
//                 'Subject' => array(
//                     'Data' => 'User feedback',
//                     'Charset' => 'utf-8',
//                 ),
//                 'Body' => array(
//                     'Text' => array(
//                         'Data' => 'test message',
//                         'Charset' => 'utf-8',
//                     ),
//                     'Html' => array(
//                         // Data is required
//                         'Data' => 'test message',
//                         'Charset' => 'utf-8',
//                     ),
//                 ),
//             ),
//         ));
        
//         var_dump($result);
//         exit;
        
        $model = new UserFeedback;
        
        $model->load(Yii::$app->request->post(), '');
        
        if ($model->validate()) {
            
            if ($model->save()) {
                    
            } elseif (!$model->hasErrors()) {
                throw new ServerErrorHttpException('Failed to create the object for unknown reason.');
            }
        }
        
        return $model;
    } // end actionFeedback
    
    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
        ];
    }
}
