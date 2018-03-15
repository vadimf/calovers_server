<?php
namespace api\controllers;

use Yii;
use yii\rest\Controller;
use yii\filters\auth\HttpBearerAuth;

use api\models\GeoSearch;
use api\models\Feedstation;
use common\models\Business;
use api\models\Event;

/**
 * GeoSearch controller
 */
class GeoSearchController extends Controller
{

    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['authenticator'] = [
            'class' => HttpBearerAuth::className(),
        ];
        return $behaviors;
    } // end behaviors
    
    public function actionIndex()
    {
        $model = new GeoSearch;
        
        if ($model->load(Yii::$app->request->post(), '') && $model->validate()) {
            
            $result = array();
//             $model->distance = 2200;
            $result['feedstations'] = $this->_getFeedstations($model);
            
            $result['businesses'] = $this->_getBusinesses($model);
            
            $result['events'] = $this->_getEvents($model);
            
            return $result;
        }
        
        return $model;
    } // end actionIndex
    
    public function actionFeedstation()
    {
        return $this->actionIndex();
    } // end actionFeedstation
    
    public function actionCat()
    {
        $model = new GeoSearch;
        
        if ($model->load(Yii::$app->request->post(), '') && $model->validate()) {
            
//             $model->distance = 2200;
            $feedstations = Feedstation::findNeighbor($model);
            
            $cats = array();
            foreach ($feedstations as $feedstation) {
                $cats = array_merge($cats, $feedstation->cats);
            }
            
            return $cats;
        }
        
        return $model;
    } // end actionCat
    
    private function _getFeedstations(GeoSearch $model)
    {
        $result = Feedstation::findNearby($model);

        return $this->_prepareResult($result);
    } // end _getFeedstations
    
    private function _getBusinesses(GeoSearch $model)
    {
        $result = Business::findNearby($model);

        return $this->_prepareResult($result);
    } // end _getBusinesses
    
    private function _getEvents(GeoSearch $model)
    {
        $result = Event::findNearby($model);

        return $this->_prepareResult($result);
    } // end _getEvents
    
    private function _prepareResult($result)
    {
        $data = array();
        foreach ($result as $obj) {
            
            $item = $obj->toArray();
            $item['distance'] = $obj->distance;
            
//             $tmp = explode('\\', get_class($obj));
//             $item['type'] = array_pop($tmp);
        
            $data[] = $item;
        }
        
        return $data;
    } // end _prepareResult
    
}
