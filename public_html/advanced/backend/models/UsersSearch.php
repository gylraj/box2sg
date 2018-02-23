<?php

namespace backend\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use backend\models\Users;

/**
 * UsersSearch represents the model behind the search form of `backend\models\Users`.
 */
class UsersSearch extends Users
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['user_id'], 'integer'],
            [['csid', 'jbid', 'password', 'email', 'fullname', 'phone', 'tags', 'custom_data', 'external_id', 'last_sign_in', 'created_at', 'updated_at'], 'safe'],
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
        $query = Users::find();

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
            'user_id' => $this->user_id,
            'last_sign_in' => $this->last_sign_in,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ]);

        $query->andFilterWhere(['like', 'csid', $this->csid])
            ->andFilterWhere(['like', 'jbid', $this->jbid])
            ->andFilterWhere(['like', 'password', $this->password])
            ->andFilterWhere(['like', 'email', $this->email])
            ->andFilterWhere(['like', 'fullname', $this->fullname])
            ->andFilterWhere(['like', 'phone', $this->phone])
            ->andFilterWhere(['like', 'tags', $this->tags])
            ->andFilterWhere(['like', 'custom_data', $this->custom_data])
            ->andFilterWhere(['like', 'external_id', $this->external_id]);

        return $dataProvider;
    }
}
