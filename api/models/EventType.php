<?php 
namespace api\models;

use yii\db\ActiveRecord;

class EventType extends ActiveRecord
{
    
    public static function tableName()
    {
        return '{{%event_types}}';
    } // end tableName
    
    public function fields()
    {
        return [
            'id',
            'category',
            'name',
        ];
    } // end fields
}