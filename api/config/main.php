<?php
use yii\base\ActionEvent;
use yii\base\Controller;
use yii\base\Event;

$params = array_merge(
    require __DIR__ . '/../../common/config/params.php',
    require __DIR__ . '/../../common/config/params-local.php',
    require __DIR__ . '/params.php',
    require __DIR__ . '/params-local.php'
);

return [
    'id' => 'app-api',
    'basePath' => dirname(__DIR__),
    'controllerNamespace' => 'api\controllers',
    'bootstrap' => [
        'log',
        function () {
            Event::on(Controller::className(), Controller::EVENT_AFTER_ACTION, function (ActionEvent $event) {
                Yii::info('REQUEST: ' . json_encode($_REQUEST), 'user');
            });
        },
    ],
    'modules' => [],
    'components' => [
        'request' => [
            'csrfParam' => '_csrf-api',
            'parsers' => [
                'multipart/form-data' => 'yii\web\MultipartFormDataParser',
                'application/json' => 'yii\web\JsonParser',
            ],
        ],
        'response' => [
            'class' => 'yii\web\Response',
                
            'on beforeSend' => function ($event) {
                
                // FIXME:
                $activeTransaction = Yii::$app->getDb()->getTransaction();
                if ($activeTransaction !== null) {
                    $activeTransaction->rollBack();
                }
            
                $response = $event->sender;
                $result = $response->data !== null ? $response->data : [];
        
                if (!$response->isSuccessful) {
        
                    if (isset($result['previous'])) {
                        $result = $result['previous'];
                    }
        
                    if ($response->statusCode !== 500) {
                        $result = [
                            'message' => $response->statusText,
                            'code'    => $response->statusCode
                        ];
        
                        if ($response->statusCode === 422) {
                            $result['fields'] = $response->data;
                        }
                    } else {
                        $result = [
                            'message' => $result['message'] ,
                            'code'    => $result['code']
                        ];
                    }
                }
        
                $response->data = [
                    'success' => $response->isSuccessful,
                    'data'    => $result
                ];
                $response->statusCode = 200;
            },
            
            'on afterSend' => function ($event) {
                $response = $event->sender;
                $result = $response->data !== null ? $response->data : [];
        
                Yii::info('RESPONSE: ' . json_encode($result), 'user');
            }
        ],
        'user' => [
            'identityClass' => 'api\models\User',
            'enableAutoLogin' => true,
            'enableSession' => false,
            'identityCookie' => ['name' => '_identity-api', 'httpOnly' => true],
            'loginUrl' => null,
        ],
        'session' => [
            // this is the name of the session cookie used for login on the api
            'name' => 'advanced-api',
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
                [
                    'class' => 'yii\log\FileTarget',
                    'categories' => ['user'],
                    'prefix' => function ($message) {
                        $user = Yii::$app->has('user', true) ? Yii::$app->get('user') : null;
                        $userID = $user ? $user->getId(false) : '-';
                        
                        return "[user_id: $userID]";
                    },
                    'logFile' => '@runtime/logs/user.log'
                ],
            ],
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'enableStrictParsing' => true,
            'rules' => [
                'POST signin' => 'site/signin',
                ['class' => 'yii\rest\UrlRule', 'controller' => 'cat'],

                'POST cats/<cat_id:\d+>/users' => 'cat/invite',
                'DELETE cats/<cat_id:\d+>/users/<user_id:\d+>' => 'cat/uninvite',
                'GET cats/<cat_id:\d+>/users' => 'cat/users',
                
                ['class' => 'yii\rest\UrlRule', 'controller' => 'feedstation'],
                    
                'GET feedstations/users/joined' => 'feedstation/joined-users',
                    
                'GET feedstations/invitations' => 'feedstation/invitations',

                'POST feedstations/<feedstation_id:\d+>/follow' => 'feedstation/follow',
                'DELETE feedstations/<feedstation_id:\d+>/follow' => 'feedstation/unfollow',
                
                'POST feedstations/<feedstation_id:\d+>/join' => 'feedstation/join',
                
                'POST feedstations/<feedstation_id:\d+>/users' => 'feedstation/invite',
                'DELETE feedstations/<feedstation_id:\d+>/users/<user_id:\d+>' => 'feedstation/uninvite',
                
                'GET feedstations/<feedstation_id:\d+>/users' => 'feedstation/users',
                    
                'GET feedstations/<feedstation_id:\d+>/cats' => 'feedstation/cats',
                
                'POST feedstations/<feedstation_id:\d+>/report' => 'feedstation/report',
                
                ['class' => 'yii\rest\UrlRule', 'controller' => 'business'],
                ['class' => 'yii\rest\UrlRule', 'controller' => 'event'],
                
                'POST geo/search/feedstations' => 'geo-search/feedstation',
                'POST geo/search/cats' => 'geo-search/cat',
                'POST geo/search' => 'geo-search/feedstation',
                    
                'GET user' => 'user/view',
                'PUT user' => 'user/update',
                    
                'GET events/types' => 'event/types',
                    
                'POST feedback' => 'site/feedback'
            ]
        ],

        'awssdk' => [
            'class' => 'fedemotta\awssdk\AwsSdk',
            'credentials' => [ //you can use a different method to grant access
                'key' => 'XXXXX',
                'secret' => 'YYYYY',
            ],
            'version' => 'latest',
            'region' => 'eu-central-1',
        ],
    ],
    'params' => $params,
];
