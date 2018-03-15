<?php 
namespace api\behaviors;

use Yii;
use yii\rest\ActiveController;
use yii\base\Behavior;

class TransactionBehavior extends Behavior
{
    private $_transaction = null;
    
    public function events()
    {
        return [
            ActiveController::EVENT_BEFORE_ACTION => 'beforeAction',
            ActiveController::EVENT_AFTER_ACTION  => 'afterAction',
        ];
    } // end events

    public function beforeAction()
    {
        $this->_transaction = Yii::$app->getDb()->beginTransaction();
    } // end beforeAction
    
    public function afterAction($event)
    {
        if ($this->_transaction !== null) {
         
            $errors = array();
            if ($event->result && $event->result instanceOf yii\db\ActiveRecord) {
                $errors = $event->result->getErrors();
            }
            
            if (empty($errors)) {
                $this->_transaction->commit();
            } else {
                $this->_transaction->rollBack();
            }
               
        }
    } // end afterAction
}