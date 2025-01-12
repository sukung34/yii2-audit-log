<?php

namespace ruturajmaniyar\mod\audit\behaviors;

use ruturajmaniyar\mod\audit\models\AuditEntry;
use Yii;
use yii\base\Behavior;
use yii\base\Exception;
use yii\db\ActiveRecord;
use yii\db\Expression;

/**
 * Class AuditEntryBehaviour
 *
 * @package ruturajmaniyar\mod\audit\behaviours
 */
class AuditEntryBehaviors extends Behavior
{
    /**
     * string
     */
    const NO_USER_ID = "NO_USER_ID";

    public $attributes = [];

    /**
     * @param $class
     * @param $attribute
     *
     * @return string
     */
    public static function getLabel($class, $attribute)
    {
        $labels = $class::attributeLabels();
        if (isset($labels[$attribute])) {
            return $labels[$attribute];
        } else {
            return ucwords(str_replace('_', ' ', $attribute));
        }
    }

    /**
     * @return array
     */
    public function events()
    {
        return [
            ActiveRecord::EVENT_AFTER_UPDATE => 'afterSave',
            ActiveRecord::EVENT_AFTER_INSERT => 'afterSave',
            ActiveRecord::EVENT_AFTER_DELETE => 'afterDelete',
        ];
    }

    /**
     * @param      $event
     *
     * @param null $attributes
     *
     * @return mixed
     */
    public function afterSave($event, $attributes = null)
    {
        $identity = Yii::$app->user->identity;
        if (isset($identity)) {
            $userId = Yii::$app->user->identity->getId();
        } else {
            $userId = self::NO_USER_ID;
        }
        $userIpAddress = Yii::$app->request->getUserIP();

        $newAttributes = $this->owner->getAttributes();
        $oldAttributes = $event->changedAttributes;

        $action = Yii::$app->controller->action->id;

        if (!$this->owner->isNewRecord) {
            // compare old and new
            foreach ($oldAttributes as $name => $oldValue) {
                if (!empty($newAttributes)) {
                    $newValue = $newAttributes[$name];
                } else {
                    $newValue = 'NA';
                }
                if ($oldValue != $newValue) {
                    $log = new AuditEntry();
                    $log->audit_entry_old_value = $oldValue;
                    $log->audit_entry_new_value = $newValue;
                    $log->audit_entry_operation = 'UPDATE';
                    $log->audit_entry_model_name = substr(get_class($this->owner), strrpos(get_class($this->owner), '\\') + 1);
                    $log->audit_entry_field_name = $name;
                    $log->audit_entry_timestamp = new Expression('unix_timestamp(NOW())');
                    $log->audit_entry_user_id = $userId;
                    $log->audit_entry_ip = $userIpAddress;

                    if (is_array($this->attributes)) {
                        foreach ($this->attributes as $ownerAttr => $auditAttr) {
                            if (isset($this->owner->{$ownerAttr}) && $log->hasProperty($auditAttr)) {
                                $log->{$auditAttr} = $this->owner->{$ownerAttr};
                            }
                        }
                    }

                    $log->save(false);
                }
            }
        } else {
            foreach ($newAttributes as $name => $value) {
                $log = new AuditEntry();
                $log->audit_entry_old_value = 'NA';
                $log->audit_entry_new_value = $value;
                $log->audit_entry_operation = 'INSERT';
                $log->audit_entry_model_name = substr(get_class($this->owner), strrpos(get_class($this->owner), '\\') + 1);
                $log->audit_entry_field_name = $name;
                $log->audit_entry_timestamp = new Expression('unix_timestamp(NOW())');
                $log->audit_entry_user_id = $userId;
                $log->audit_entry_ip = $userIpAddress;

                if (is_array($this->attributes)) {
                    foreach ($this->attributes as $ownerAttr => $auditAttr) {
                        if (isset($this->owner->{$ownerAttr}) && $log->hasProperty($auditAttr)) {
                            $log->{$auditAttr} = $this->owner->{$ownerAttr};
                        }
                    }
                }

                $log->save();
            }
        }
        return true;
    }

    /**
     * This function is fo save data to Audit Trail after the delete action.
     *
     * @return bool
     */
    public function afterDelete()
    {
        $identity = Yii::$app->user->identity;
        if (isset($identity)) {
            $userId = Yii::$app->user->identity->getId();
        } else {
            $userId = self::NO_USER_ID;
        }
        $userIpAddress = Yii::$app->request->getUserIP();

        $log = new AuditEntry();
        $log->audit_entry_old_value = 'NA';
        $log->audit_entry_new_value = 'NA';
        $log->audit_entry_operation = 'DELETE';
        $log->audit_entry_model_name = substr(get_class($this->owner), strrpos(get_class($this->owner), '\\') + 1);
        $log->audit_entry_field_name = 'N/A';
        $log->audit_entry_timestamp = new Expression('unix_timestamp(NOW())');
        $log->audit_entry_user_id = $userId;
        $log->audit_entry_ip = $userIpAddress;
        if (is_array($this->attributes)) {
            foreach ($this->attributes as $ownerAttr => $auditAttr) {
                if (isset($this->owner->{$ownerAttr}) && $log->hasProperty($auditAttr)) {
                    $log->{$auditAttr} = $this->owner->{$ownerAttr};
                }
            }
        }
        $log->save();

        return true;
    }

}