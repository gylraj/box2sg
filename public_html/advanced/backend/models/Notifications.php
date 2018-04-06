<?php

namespace backend\models;

use Yii;

/**
 * This is the model class for table "notifications".
 *
 * @property int $notif_id
 * @property string $message
 * @property string $status
 * @property string $start_date
 * @property string $end_date
 * @property string $channel
 * @property string $created_at
 * @property string $updated_at
 */
class Notifications extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'notifications';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['message'], 'required'],
            [['message','notif_token'], 'string'],
            [['start_date', 'end_date', 'created_at', 'updated_at'], 'safe'],
            [['status', 'channel'], 'string', 'max' => 10],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'notif_id' => 'Notif ID',
            'notif_token' => 'Notif Token',
            'message' => 'Message',
            'status' => 'Status',
            'start_date' => 'Start Date',
            'end_date' => 'End Date',
            'channel' => 'Channel',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }
}
