<?php 
namespace api\models;

use Yii;
use yii\db\ActiveRecord;
use yii\db\Expression;

class Feedstation extends ActiveRecord
{
    const SCENARIO_UPDATE = 'update';
    
    const SCENARIO_CREATE = 'create';
    
    const SCENARIO_REPORT = 'report';
    
    const STATUS_ACTIVE = 'active';
    const STATUS_REPORTED = 'reported';
    
    const STATUS_FEED_NORMAL = 'normal';
    const STATUS_FEED_HUNGRY = 'hungry';
    const STATUS_FEED_STARVING = 'starving';
    
    public $distance;
    
    public static function tableName()
    {
        return '{{%feedstations}}';
    }
    
    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios[self::SCENARIO_REPORT] = [
            'status'
        ];
        $scenarios[self::SCENARIO_UPDATE] = [
            'name', 'description', 'time_to_feed_morning', 
            'time_to_feed_evening', 'last_feeding'
        ];
        $scenarios[self::SCENARIO_CREATE] = [
            'name', 'lat', 'lng', 'description', 'address', 'is_public', 
            'time_to_feed_morning', 'time_to_feed_evening'
        ];
        return $scenarios;
    } // end scenarios
    
    public function fields()
    {
        return [
            'id',
            'name',
            'description',
            'lat',
            'lng',
            'address',
            'is_public',
                
            'time_to_feed_morning', 
            'time_to_feed_evening',
            'last_feeding',
                
            'status',
                
            'feed_status',
                
            'created',
            'created_at',
                
            'photos',
                
            'permission'
        ];
    }
    
    public function rules()
    {
        return [
            [['name', 'lat', 'lng'], 'required'],
            [['description', 'address'], 'string'],
            [['lat', 'lng'], 'double'],
//             [['created'], 'integer'],
//             [['is_public'], 'boolean'],
//             ['is_public', 'default', 'value' => '1'],
            [['status'], 'required', 'on' => [self::STATUS_ACTIVE, self::STATUS_REPORTED]],
            [['time_to_feed_morning', 'time_to_feed_evening', 'last_feeding'], 'integer'],
//             [['time_to_feed'], 'date', 'format' => 'yyyy-M-d H:m:s'],
//             ['time_to_feed', 'default', 'value' => 'NULL'],
        ];
    }
    
    public function getFeed_status()
    {
        $currentTime = time();
        
        if (abs($currentTime - $this->last_feeding) <= 150 * 60) {
            return self::STATUS_FEED_NORMAL;
        }
        
        $startOfDay = strtotime('today');
        $startMorningFeeding = $startOfDay + $this->time_to_feed_morning;
        
        $status = $this->_getFeedStatus($startMorningFeeding);
        
        if ($status !== self::STATUS_FEED_NORMAL) {
            return $status;
        }
        
        $startEveningFeeding = $startOfDay + $this->time_to_feed_evening;
        
        $status = $this->_getFeedStatus($startEveningFeeding);
        
        return $status;
    } // end getFeed_status
    
    private function _getFeedStatus($startFeeding)
    {
        $currentTime = time();
        $durationOfFeeding = 90 * 60;
        
        $endFeeding = $startFeeding + $durationOfFeeding;
        
        $hungryRange = [
            $startFeeding - 30 * 60,
            $endFeeding - 30 * 60
        ];
        $starvingRange = [
            $endFeeding - 30 * 60,
            $endFeeding + 30 * 60
        ];
        
        if ($hungryRange[0] <= $currentTime && $hungryRange[1] >= $currentTime) {
            return self::STATUS_FEED_HUNGRY;
        }
        
        if ($starvingRange[0] <= $currentTime && $starvingRange[1] >= $currentTime) {
            return self::STATUS_FEED_STARVING;
        }
        
        return self::STATUS_FEED_NORMAL;
    } // end _getFeedStatus

    public function addPhotos($urls)
    {
        foreach ($urls as $url) {
            $model = new FeedstationPhotos;
            $model->feedstation_id = $this->id;
            $model->photo = $url['photo'];
            $model->thumbnail = $url['thumbnail'];
            $model->save();
        }
    } // end addPhotos
    
    public function getPhotos()
    {
        return $this->hasMany(FeedstationPhotos::className(), ['feedstation_id' => 'id'])
            ->where(['is_delete' => false]);
    } // end getPhotos
    
    public function getPermission($where = null)
    {
        if ($where === null) {
            $where = [
                'user_id' => Yii::$app->user->identity->id
            ];
        }
        
        return $this->hasOne(FeedstationPermission::className(), ['feedstation_id' => 'id'])
            ->where($where);
    } // end getPermission
    
    public function getUsers($filters = null)
    {
        $query = $this->hasMany(FeedstationPermission::className(), ['feedstation_id' => 'id'])
            ->where(['<>', 'feedstation_users.status', 'deleted']);
    
        if ($filters !== null) {
            $query->andWhere($filters);
        }
            
        return $query;
        
//         return $this->hasMany(User::className(), ['id' => 'user_id'])
//             ->viaTable('feedstation_users', ['feedstation_id' => 'id'], function ($query) use ($viaFilters) {
//                 if ($viaFilters !== null) {
//                     $query->andWhere($viaFilters);
//                 }
//                 $query->andWhere(['<>', 'status', 'deleted']);
//             });
    } // end getUsers
    
    public function getCats()
    {
        return $this->hasMany(Cat::className(), ['id' => 'cat_id'])
            ->viaTable('feedstation_cats', ['feedstation_id' => 'id']);
    } // end getCats
    
    public function beforeSave($insert)
    {
        $result = parent::beforeSave($insert);
        
        if ($result) {
            if (!empty($this->lng) && !empty($this->lat)) {
                $this->location = new Expression(
                    'ST_SetSRID(ST_MakePoint('.$this->lng.', '.$this->lat.'), 4326)'
                );
            }
            
            if ($insert) {
                $this->created = Yii::$app->user->identity->id;
            }
        }
        
        return $result;
    } // end beforeSave
    
    public static function findNearby(GeoSearch $model)
    {
        return static::find()
            ->select([
                '{{%feedstations}}.*',
                new \yii\db\Expression("(location::geography <-> 'SRID=4326;POINT($model->lng $model->lat)'::geography) / 1000 AS distance"),
            ])
            ->leftJoin(
                'feedstation_users', 
                'feedstation_users.feedstation_id = feedstations.id AND feedstation_users.user_id = '.Yii::$app->user->identity->id
            )
            ->where(
                "(location::geography <-> 'SRID=4326;POINT($model->lng $model->lat)'::geography) / 1000 <= $model->distance"
            )
            ->andFilterWhere([
                'or',
                ['is_public' => true],
                new Expression("feedstation_users.id IS NOT NULL AND feedstation_users.status <> 'deleted'")
            ])
            ->all();
    } // end findNearby
    
    public static function findNeighbor(GeoSearch $model)
    {
        return static::find()
            ->select([
                '{{%feedstations}}.*',
                new \yii\db\Expression("(location::geography <-> 'SRID=4326;POINT($model->lng $model->lat)'::geography) / 1000 AS distance"),
            ])
            ->with('cats')
            ->leftJoin(
                'feedstation_users', 
                'feedstation_users.feedstation_id = feedstations.id AND feedstation_users.user_id = '.Yii::$app->user->identity->id
            )
            ->where(
                "(location::geography <-> 'SRID=4326;POINT($model->lng $model->lat)'::geography) / 1000 <= $model->distance"
            )
            ->andWhere([
                'is_public' => true,
            ])
            ->andWhere(new Expression("feedstation_users.id IS NULL"))
            ->all();
    } // end findNeighbor
    
}