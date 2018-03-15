<?php 
namespace api\models;

use Yii;
use yii\db\ActiveRecord;

class Cat extends ActiveRecord
{
    const STATUS_ACTIVE = 'active';
    const STATUS_DELETED = 'deleted';
    
    public static function tableName()
    {
        return '{{%cats}}';
    }
    
    public function fields()
    {
        return [
            'id',
            'name',
            'nickname',
            'color',
            'age',
            'sex',
            'weight',
            'castrated',
            'description',
            'type',
            'next_flea_treatment',
                
            'avatar_url',
            'avatar_url_thumbnail',
            'photos',
                
            'permission',
            'feedstation'
        ];
    }
    
    public function rules()
    {
        return [
            [['name', 'nickname', 'color'], 'required'],
            [['age'], 'integer'],
            [['weight'], 'double'],
            [['castrated'], 'boolean', 'trueValue' => true, 'falseValue' => false, 'strict' => true],
            [['description', 'sex'], 'string'],
            [['type'], 'required', 'on' => ['stray', 'pet']],
            ['type', 'default', 'value' => 'pet'],
            [['next_flea_treatment'], 'integer'],
                
            [['avatar_url', 'avatar_url_thumbnail'], 'url']
        ];
    }
    
    public static function find()
    {
        return parent::find()->where(['status' => self::STATUS_ACTIVE]);
    }
    
    public function addPhotos($urls)
    {
        foreach ($urls as $url) {
            $model = new CatPhotos;
            $model->photo = $url['photo'];
            $model->thumbnail = $url['thumbnail'];
            $model->cat_id = $this->id;
            $model->save();
        }
    } // end addPhotos
    
    public function getPhotos()
    {
        return $this->hasMany(CatPhotos::className(), ['cat_id' => 'id'])
            ->where(['is_delete' => false]);
    } // end getPhotos
    
    public function getUsers()
    {
        return $this->hasMany(User::className(), ['id' => 'user_id'])
            ->viaTable('cat_group_members', ['cat_id' => 'id']);
    } // end getGroupMembers
    
    public function getFeedstation()
    {
        return $this->hasOne(Feedstation::className(), ['id' => 'feedstation_id'])
            ->viaTable('feedstation_cats', ['cat_id' => 'id']);
    } // end getFeedstation
    
//     public function getFeedstations($viaFilters = null)
//     {
//         if ($viaFilters === null) {
//             $viaFilters = [];
//         }
//         $viaFilters['cat_id'] = 'id';
        
//         return $this->hasMany(Feedstation::className(), ['id' => 'feedstation_id'])
//             ->viaTable('feedstation_cats', $viaFilters);
//     } // end getFeedstations

    public function getPermission($where = null)
    {
        if ($where === null) {
            $where = [
                'user_id' => Yii::$app->user->identity->id
            ];
        }
    
        return $this->hasOne(CatPermission::className(), ['cat_id' => 'id'])
            ->where($where);
    } // end getPermission
}