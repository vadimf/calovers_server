<?php 
namespace api\controllers;

use Yii;
use yii\rest\ActiveController;
use yii\filters\auth\HttpBearerAuth;
use yii\web\UploadedFile;
use yii\web\BadRequestHttpException;

use api\models\Feedstation;
use api\models\User;
use api\models\Invite;
use api\models\FeedstationPermission;

use api\models\UploadImages;

use api\behaviors\TransactionBehavior;

class FeedstationController extends ActiveController
{
    /**
     * @inheritdoc
     */
    public $updateScenario = Feedstation::SCENARIO_UPDATE;
    /**
     * @inheritdoc
     */
    public $createScenario = Feedstation::SCENARIO_CREATE;
    
    public $modelClass = 'api\models\Feedstation';
    
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
    
    public function actions() {
        $actions = parent::actions();
        
        // disable the "delete" and "create" actions
        unset($actions['delete']);
        
        $actions['index']['prepareDataProvider'] = function ($action) {
            return Yii::$app->user->identity->feedstations;
        };
        
        return $actions;
    } // end actions
    
    public function actionReport()
    {
        $idFeedstation = Yii::$app->request->getQueryParam('feedstation_id');
        
        $model = Feedstation::findOne($idFeedstation);
        
        $model->scenario = Feedstation::SCENARIO_REPORT;
        $model->status = Feedstation::STATUS_REPORTED;
        
        if ($model->save() === false && !$model->hasErrors()) {
            throw new ServerErrorHttpException('Failed to update the object for unknown reason.');
        }
        
        return $model;
        
    } // end actionReport
    
    public function beforeAction($action)
    {
        $result = parent::beforeAction($action);

        $body = Yii::$app->getRequest()->getBodyParams();
        
        if (in_array($action->id, ['create'])) {
            $body['is_public'] = true;
        }
        
        Yii::$app->getRequest()->setBodyParams($body);

        return $result;
    } // end beforeAction
    
    public function afterAction($action, $result)
    {
        if ($action->id === 'create' && !empty($result['id'])) {
            
            $currentPermission = new FeedstationPermission;
            $currentPermission->feedstation_id = $result['id'];
            $currentPermission->user_id = Yii::$app->user->identity->id;
            $currentPermission->role = 'admin';
            $currentPermission->status = FeedstationPermission::STATUS_JOINED;
            $currentPermission->save();
            
//             $result->link('users', Yii::$app->user->identity, ['role' => 'admin', 'status' => 'joined']);
        }
        
        if (in_array($action->id, ['create', 'update']) && !empty($result['id'])) {
        
            // for call yii\web\MultipartFormDataParser::parse()
            $body = Yii::$app->getRequest()->getBodyParams();
            try {
                $uploadedFiles = UploadedFile::getInstancesByName('images');
            } catch (\Exception $e) {
                throw new BadRequestHttpException('Can\'t upload files');
            }
        
            $model = new UploadImages;
            $model->uploadPath = 'feedstations/'.$result['id'].'/';
            $model->images = $uploadedFiles;
        
            $imageURLs = $model->upload();
        
            if ($imageURLs) {
                $result->addPhotos($imageURLs);
            }
        }
        
        $body = Yii::$app->getRequest()->getBodyParams();
        
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
        
            $result = Feedstation::findOne($result['id']);
        }

        return parent::afterAction($action, $result);
    } // end afterAction
    
    public function checkAccess($action, $model = null, $params = [])
    {
        if (in_array($action, ['update', 'users'])) {
            
            $permission = $model->getPermission([
                'user_id' => Yii::$app->user->identity->id, 
                'role' => 'admin'
            ]);
            if ($permission->one() === NULL) {
                throw new \yii\web\ForbiddenHttpException('You can\'t '.$action.' this feedstation.');
            }
        }
    } // end checkAccess
    
    public function actionInvitations()
    {
        $user = Yii::$app->user->identity;
        
        $feedstations = Feedstation::find()
            ->innerJoinWith('users')
            ->where([
                'feedstation_users.status' => FeedstationPermission::STATUS_INVITED,
                'feedstation_users.user_id' => $user->id
            ])
            ->all();
            
        return $feedstations;
    } // end actionGetInvitation
    
    public function actionUsers()
    {
        $idFeedstation = Yii::$app->request->getQueryParam('feedstation_id');
        
        $model = Feedstation::findOne($idFeedstation);
        
//         $this->checkAccess('users', $model);
        
        return $model->users;
    } // end actionUsers
    
    public function actionCats()
    {
        $idFeedstation = Yii::$app->request->getQueryParam('feedstation_id');
        
        $model = Feedstation::findOne($idFeedstation);
        
        return $model->cats;
    } // end actionCats
    
    public function actionFollow()
    {
        $idFeedstation = Yii::$app->request->getQueryParam('feedstation_id');
        $feedstation = Feedstation::findOne($idFeedstation);
        
        $user = Yii::$app->user->identity;
        
        $currentPermission = $this->_changeFeedstationPermissionStatus(
            $feedstation, 
            $user, 
            FeedstationPermission::STATUS_REQUESTED
        );

        return $currentPermission;
        
//         $query = $feedstation->getPermission([
//             'user_id' => $user->id
//         ]);
        
//         $status = FeedstationPermission::STATUS_REQUESTED;
//         $currentPermission = $query->one();
//         if ($currentPermission !== null) {
//             $oldStatus = $currentPermission->status;
//             if ($oldStatus !== $status) {
                
//                 if ($oldStatus === FeedstationPermission::STATUS_INVITED) {
//                     $status = FeedstationPermission::STATUS_JOINED;
//                 }
                
//                 $currentPermission->status = $status;
//                 $currentPermission->save();
//             }
//         } else {
//             $currentPermission = new FeedstationPermission;
//             $currentPermission->feedstation_id = $idFeedstation;
//             $currentPermission->user_id = $user->id;
//             $currentPermission->role = 'user';
//             $currentPermission->status = $status;
//             $currentPermission->save();
// //             $feedstation->link('users', $user, ['role' => 'user', 'status' => $status]);
//         }
        
//         return $currentPermission;
    } // end actionFollow
    
    public function actionJoin()
    {
        $idFeedstation = Yii::$app->request->getQueryParam('feedstation_id');
        $feedstation = Feedstation::findOne($idFeedstation);
        $user = Yii::$app->user->identity;
        
        $currentPermission = $this->_changeFeedstationPermissionStatus(
            $feedstation,
            $user,
            FeedstationPermission::STATUS_JOINED
        );
        
        return $currentPermission;
        
//         $query = $feedstation->getPermission([
//             'user_id' => $user->id
//         ]);
        
//         $currentPermission = $query->one();
//         if ($currentPermission === null) {
//             throw new yii\web\NotFoundHttpException('Invite not found');
//         }
        
//         if ($currentPermission->status !== FeedstationPermission::STATUS_INVITED) {
//             throw new yii\web\ForbiddenHttpException('You do not have permission');
//         }
        
//         $status = FeedstationPermission::STATUS_JOINED;
//         $currentPermission->status = $status;
//         $currentPermission->save();
        
//         return $currentPermission;
    } // end actionJoined
    
    public function actionUnfollow()
    {
        $idFeedstation = Yii::$app->request->getQueryParam('feedstation_id');
        $feedstation = Feedstation::findOne($idFeedstation);
        
        $user = Yii::$app->user->identity;
        
        $currentPermission = $this->_changeFeedstationPermissionStatus(
            $feedstation,
            $user,
            FeedstationPermission::STATUS_DELETED
        );
        
        return $currentPermission;
        
//         $query = $feedstation->getPermission([
//             'user_id' => $user->id
//         ]);
        
//         $currentPermission = $query->one();
        
//         if (!$currentPermission) {
//             throw new yii\web\NotFoundHttpException('Permission not found');
//         }
        
//         $currentPermission->status = FeedstationPermission::STATUS_DELETED;
//         $currentPermission->save();
        
//         return $currentPermission;
    } // end actionUnfollow
    
    public function actionInvite()
    {
        $idFeedstation = Yii::$app->request->getQueryParam('feedstation_id');
        $feedstation = Feedstation::findOne($idFeedstation);
        
        $this->checkAccess('users', $feedstation);
        
        $model = new Invite;
        
        $postData = Yii::$app->request->post();
        if ($model->load($postData, '') && $model->validate()) {
        
            $user = User::findByPhoneAuth($model->phone);
        
            if (!$user) {
                $user = User::createUser($model);
            }
            
            $currentPermission = $this->_changeFeedstationPermissionStatus(
                $feedstation,
                $user,
                FeedstationPermission::STATUS_INVITED
            );
            
            return $currentPermission;

//             $query = $feedstation->getPermission([
//                 'user_id' => $user->id
//             ]);

//             $status = FeedstationPermission::STATUS_INVITED;
//             $currentPermission = $query->one();
//             if ($currentPermission !== null) {
//                 $oldStatus = $currentPermission->status;
//                 if ($oldStatus !== $status) {
            
//                     if ($oldStatus === FeedstationPermission::STATUS_REQUESTED) {
//                         $status = FeedstationPermission::STATUS_JOINED;
//                         $currentPermission->status = $status;
//                         $currentPermission->save();
//                     } else if ($oldStatus === FeedstationPermission::STATUS_JOINED) {
                        
//                     } else {
//                         $currentPermission->status = $status;
//                         $currentPermission->save();
//                     }
//                 }
            
//             } else {
//                 $currentPermission = new FeedstationPermission;
//                 $currentPermission->feedstation_id = $idFeedstation;
//                 $currentPermission->user_id = $user->id;
//                 $currentPermission->role = 'user';
//                 $currentPermission->status = $status;
//                 $currentPermission->save();
// //                 $feedstation->link('users', $user, ['role' => 'user', 'status' => $status]);
//             }
            
//             return $currentPermission;
        }
        
        return $model;
    } // end actionInvite
    
    public function actionUninvite()
    {
        $idFeedstation = Yii::$app->request->getQueryParam('feedstation_id');
        $idUser = Yii::$app->request->getQueryParam('user_id');
        
        $feedstation = Feedstation::findOne($idFeedstation);

        $this->checkAccess('users', $feedstation);
    
        $user = User::findOne($idUser);
    
        if (!$user) {
            throw new yii\web\NotFoundHttpException('User not found');
        }

        $currentPermission = $this->_changeFeedstationPermissionStatus(
            $feedstation,
            $user,
            FeedstationPermission::STATUS_DELETED
        );
        
        return $currentPermission;
        
//         $query = $feedstation->getPermission([
//             'user_id' => $user->id
//         ]);
        
//         $permission = $query->one();
        
//         if (!$permission) {
//             throw new yii\web\NotFoundHttpException('Permission not found');
//         }
        
//         $permission->status = FeedstationPermission::STATUS_DELETED;
//         $permission->save();
        
//         return $permission;
    } // end actionUninvite
    
    public function actionJoinedUsers()
    {
        $user = Yii::$app->user->identity;
        
        $feedstations = $user->getFeedstations([
                'status' => FeedstationPermission::STATUS_JOINED
            ])
            ->all();
        
        $users = [];
        foreach ($feedstations as $feedstation) {
            
            $permissions = $feedstation->getUsers(['status' => 'joined'])->all();
            foreach ($permissions as $user) {
                if ($user->user_id != $user->id) {
                    $users[$user->user_id] = $user;
                }
            }
        }
        
        return array_values($users);
    } // end actionJoinedUsers
    
    private function _changeFeedstationPermissionStatus(Feedstation $feedstation, User $user, $newStatus)
    {
        $query = $feedstation->getPermission([
            'user_id' => $user->id
        ]);
        
        $permission = $query->one();
        
        if ($permission !== null) {
            
            if ($permission->role === 'admin') {
                throw new BadRequestHttpException('Can\'t change permission for this feedstation'); 
            }
            
            $oldStatus = $permission->status;
            
            if ($oldStatus === $newStatus) {
                return $permission;
            }
            
            if ($newStatus === FeedstationPermission::STATUS_DELETED) {
                $permission->status = FeedstationPermission::STATUS_DELETED;
                $permission->save();
                return $permission;
            }
            
            if ($oldStatus === FeedstationPermission::STATUS_JOINED) {
                return $permission;
            }
            
            if (
                $newStatus === FeedstationPermission::STATUS_JOINED &&
                !in_array($oldStatus, array(FeedstationPermission::STATUS_INVITED, FeedstationPermission::STATUS_REQUESTED))
            ) {
                throw new yii\web\NotFoundHttpException('Invite not found');
            }
            
            if (
                $oldStatus === FeedstationPermission::STATUS_REQUESTED && $newStatus === FeedstationPermission::STATUS_INVITED ||
                $oldStatus === FeedstationPermission::STATUS_INVITED && $newStatus === FeedstationPermission::STATUS_REQUESTED || 

                $newStatus === FeedstationPermission::STATUS_JOINED
            ) {
                $permission->status = FeedstationPermission::STATUS_JOINED;
                $permission->save();
                
                $this->_refriend($feedstation, $user);
                
                return $permission;
            }
            
            if (in_array($newStatus, array(FeedstationPermission::STATUS_INVITED, FeedstationPermission::STATUS_REQUESTED))) {
                $permission->status = $newStatus;
                $permission->save();
                return $permission;
            }
            
        } else {
            if ($newStatus === FeedstationPermission::STATUS_JOINED) {
                throw new yii\web\NotFoundHttpException('Invite not found');
            }

            if ($newStatus === FeedstationPermission::STATUS_DELETED) {
                throw new yii\web\NotFoundHttpException('Permission not found');
            }
            
            
            $permission = new FeedstationPermission;
            $permission->feedstation_id = $feedstation->id;
            $permission->user_id = $user->id;
            $permission->role = 'user';
            $permission->status = $newStatus;
            $permission->save();
        }
        
        return $permission;
    } // end _changeFeedstationPermissionStatus
    
    private function _refriend(Feedstation $feedstation, User $user)
    {
        if ($feedstation->is_public) {
            return true;
        }
        
        $adminPermission = $feedstation
            ->getPermission(['role' => 'admin'])
            ->one();
        
        $adminId = $adminPermission->user_id;
        
        $privateFeedstation = $user->getFeedstations(['role' => 'admin'])
            ->andWhere(['is_public' => false])
            ->one();

        if (!$privateFeedstation) {
            return true;
        }
        
        $permission = $privateFeedstation
            ->getPermission([
                'user_id' => $adminId
            ])
            ->one();
        
        if ($permission === null) {
            $permission = new FeedstationPermission;
            $permission->feedstation_id = $privateFeedstation->id;
            $permission->user_id = $adminId;
            $permission->role = 'user';
        }
        
        $permission->status = FeedstationPermission::STATUS_JOINED;
        $permission->save();
        
    } // end _refriend
}
