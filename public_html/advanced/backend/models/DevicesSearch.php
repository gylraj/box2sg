<?php

namespace backend\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use backend\models\Devices;

/**
 * DevicesSearch represents the model behind the search form of `backend\models\Devices`.
 */
class DevicesSearch extends Devices
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['device_id'], 'integer'],
            [['csid', 'device_udid', 'device_type', 'created_at', 'updated_at'], 'safe'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function search($params)
    {
        $query = Devices::find();

        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        // grid filtering conditions
        $query->andFilterWhere([
            'device_id' => $this->device_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ]);

        $query->andFilterWhere(['like', 'csid', $this->csid])
            ->andFilterWhere(['like', 'device_udid', $this->device_udid])
            ->andFilterWhere(['like', 'device_type', $this->device_type]);

        return $dataProvider;
    }
}
