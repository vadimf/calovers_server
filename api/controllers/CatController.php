<?php 
namespace api\controllers;

use Yii;
use yii\rest\ActiveController;
use yii\filters\auth\HttpBearerAuth;
use yii\web\UploadedFile;
use yii\web\ServerErrorHttpException;

use api\models\Feedstation;
use api\models\Invite;
use api\models\Cat;
use api\models\User;
use api\models\FeedstationPermission;

use api\models\UploadImages;

use api\behaviors\TransactionBehavior;

class CatController extends ActiveController
{
    public $modelClass = 'api\models\Cat';

    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['authenticator'] = [
            'class' => HttpBearerAuth::className(),
        ];
        
        $behaviors[] = [
            'class' => TransactionBehavior::className(),
        ];
        
        return $behaviors;
    } // end behaviors

    public function actions()
    {
        $actions = parent::actions();

        // disable the "delete" and "create" actions
        unset($actions['delete']);

        $actions['index']['prepareDataProvider'] = function ($action) {
            $user = Yii::$app->user->identity;
            
            $feedstatinos = $user->getFeedstations([
                    'status' => 'joined'
                ])
                ->with('cats')
                ->all();
            
            $cats = array();
            foreach ($feedstatinos as $feedstatino) {
                $cats = array_merge($cats, $feedstatino->cats);
            }
            
            return $cats;
        };

        return $actions;
    } // end actions
    
    public function actionDelete()
    {
        $idCat = Yii::$app->request->getQueryParam('id');
        $model = Cat::findOne($idCat);
        
        $this->checkAccess('delete', $model);

        $model->status = Cat::STATUS_DELETED;
        
        if ($model->save() === false) {
            throw new ServerErrorHttpException('Failed to delete the object for unknown reason.');
        }
        
        Yii::$app->getResponse()->setStatusCode(204);
    } // end actionDelete
    
    public function checkAccess($action, $model = null, $params = [])
    {
        $body = Yii::$app->getRequest()->getBodyParams();
        
        if (in_array($action, ['delete', 'update', 'users'])) {
            
            $found = false;
            $cats = Yii::$app->user->identity->cats;
            foreach ($cats as $cat) {
                if ($cat->id === $model->id) {
                    $found = true;
                }
            }
            
            if (!$found) {
                throw new \yii\web\ForbiddenHttpException('Not access for cat_id = '.$model->id.'.');
            }

            if (in_array($action, ['delete'])) {
                
            }
        }
        
        if (in_array($action, ['create']) && !empty($body['feedstation_id'])) {
            $feedstationsQuery = Yii::$app->user->identity->getFeedstations([
                'feedstation_id' => $body['feedstation_id'],
//                 'role' => 'admin'
                'status' => 'joined'
            ]);
            if ($feedstationsQuery->one() === null) {
                throw new \yii\web\ForbiddenHttpException('Not access for feedstation_id = '.$body['feedstation_id'].'.');
            }
        }
    } // end checkAccess
    
    public function beforeAction($action)
    {
        $body = Yii::$app->getRequest()->getBodyParams();
        $result = parent::beforeAction($action);
    
        if (in_array($action->id, ['create', 'update'])) {
            $body = Yii::$app->getRequest()->getBodyParams();
            if (isset($body['castrated'])) {
                $body['castrated'] = $body['castrated'] === true || $body['castrated'] === 'true';
            }
            Yii::$app->getRequest()->setBodyParams($body);
        }
    
        return $result;
    } // end beforeAction
    
    public function afterAction($action, $result)
    {
        if ($action->id === 'create' && !empty($result['id'])) {
            $feedstationToJoin = $this->_getFeedstationToJoin($result);
            $result->link('feedstation', $feedstationToJoin);
            $result->link('users', Yii::$app->user->identity, ['role' => 'admin', 'status' => 'joined']);
        }
        
        if (in_array($action->id, ['create', 'update']) && !empty($result['id'])) {
            
            // for call yii\web\MultipartFormDataParser::parse()
            $body = Yii::$app->getRequest()->getBodyParams();
            try {
                $uploadedFiles = UploadedFile::getInstancesByName('images');
            } catch (\Exception $e) {
                throw new ServerErrorHttpException('Can\'t upload files');
            }
            
            $model = new UploadImages;
            $model->uploadPath = 'cats/'.$result['id'].'/';
            $model->images = $uploadedFiles;
            
            $imageURLs = $model->upload();
            
            if ($imageURLs) {
                $result->addPhotos($imageURLs);
            }
            
            try {
                $avatarFile = UploadedFile::getInstanceByName('avatar');
            } catch (\Exception $e) {
                throw new ServerErrorHttpException('Can\'t upload files');
            }
            
            $model = new UploadImages;
            $model->uploadPath = 'cats/'.$result['id'].'/';
            $model->images = [$avatarFile];
            
            $avatarURL = $model->upload();
            if ($avatarURL) {
                $avatarURL = $avatarURL[0];
                $result->avatar_url = $avatarURL['photo'];
                $result->avatar_url_thumbnail = $avatarURL['thumbnail'];
                
                if ($result->save() === false) {
                    throw new \Exception('Can\'t update avatar for cat');
                }
            }
            
            if (!empty($body['images_delete']) && is_array($body['images_delete'])) {
                $photos = array();
                foreach ($result->photos as $index => $photo) {
                    if (in_array($photo->id, $body['images_delete'])) {
                        $photo->is_delete = true;
                        
                        $photo->save();
                    } else {
                        $photos[] = $photo;
                    }
                }
                
                $result = Cat::findOne($result['id']);
            }
            
            
        }
    
        return parent::afterAction($action, $result);
    } // end afterAction
    
    private function _getFeedstationToJoin($model)
    {
        $body = Yii::$app->getRequest()->getBodyParams();
        
        $feedstationToJoin = null;
        
        // join to private feedstation
        if (empty($body['feedstation_id'])) {
            $feedstations = Yii::$app->user->identity->feedstations;
        
            foreach ($feedstations as $feedstation) {
                
                if (!$feedstation->is_public) {
                    
                    $feedstationPermission = $feedstation->permission;
                    if ($feedstationPermission->role === FeedstationPermission::ROLE_ADMIN) {
                        $feedstationToJoin = $feedstation;
                        break;
                    }
                    
                }
            }
        
            if ($feedstationToJoin === null) {
                $feedstationToJoin = new Feedstation();
                $feedstationToJoin->name = $model['name']."'s private feedstation";
                $feedstationToJoin->description = 'Automatically generated foodstation item';
                $feedstationToJoin->is_public = 0;
                 
                if (empty($body['lat']) || empty($body['lng']) || empty($body['address']) ) {
//                 if (empty($body['lat']) || empty($body['lng'])) {
                    throw new ServerErrorHttpException('Not found location params (lat or lng or address)');
                }
                
                $feedstationToJoin->lat = $body['lat'];
                $feedstationToJoin->lng = $body['lng'];
                $feedstationToJoin->address = $body['address'];
                 
                if ($feedstationToJoin->save()) {
                     
                    $currentPermission = new FeedstationPermission;
                    $currentPermission->feedstation_id = $feedstationToJoin->id;
                    $currentPermission->user_id = Yii::$app->user->identity->id;
                    $currentPermission->role = FeedstationPermission::ROLE_ADMIN;
                    $currentPermission->status = FeedstationPermission::STATUS_JOINED;
                    $currentPermission->save();
                     
                } else {
                    throw new \Exception('it is not possible to create a private feedstation');
                }
                

//                 $feedstationToJoin->link(
//                     'users',
//                     Yii::$app->user->identity,
//                     ['role' => 'admin', 'status' => 'joined']
//                 );
            }
        
        } else {
            $feedstationToJoin = Feedstation::findOne($body['feedstation_id']);
        }
        
        return $feedstationToJoin;
    } // end _getFeedstationToJoin

    public function actionUsers()
    {
        $idCat = Yii::$app->request->getQueryParam('cat_id');
        $cat = Cat::findOne($idCat);
        
        $feedstation = $cat->feedstation;
        
        return $feedstation->users;
    
//         $this->checkAccess('users', $model);
    
//         return $model->users;
    } // end actionUsers
    
    public function actionInvite()
    {
        $idFeedstation = Yii::$app->request->getQueryParam('cat_id');
    
        $postData = Yii::$app->request->post();
    
        $model = new Invite;
    
        if ($model->load(Yii::$app->request->post(), '') && $model->validate()) {
    
            $cat = Cat::findOne($idFeedstation);
            $user = User::findByPhoneAuth($model->phone);
    
            try {

                if (!$user) {
                    $user = User::createUser($model);
                }
                
                $cat->link('users', $user, ['role' => 'user', 'status' => 'invited']);
            
            } catch (\yii\db\IntegrityException $e) {

                if ($e->errorInfo[0] != 23505) {
                    throw $e;
                }
            } catch (\Exception $e) {
                throw $e;
            } catch (\Throwable $e) {
                throw $e;
            }
            
            return $user;
        }
    
        return $model;
    } // end actionInvite
    
    public function actionUninvite()
    {
        $idCat = Yii::$app->request->getQueryParam('cat_id');
        $idUser = Yii::$app->request->getQueryParam('user_id');
    
        $cat = Cat::findOne($idCat);
    
        $usersQuery = $cat->getUsers([
            'user_id' => Yii::$app->user->identity->id,
            'role' => 'admin'
        ]);
        if ($usersQuery->one() === NULL) {
            throw new \yii\web\ForbiddenHttpException('You can\'t edit this cat.');
        }
    
        $user = User::findOne($idUser);
    
        if (!$user) {
            throw new yii\web\NotFoundHttpException('User not found');
        }
    
        $isDelete = true;
        $cat->unlink('users', $user, $isDelete);
    
        return $user;
    } // end actionUninvite
    
}
