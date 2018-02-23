<?php

namespace backend\controllers;

use Yii;
use backend\models\Users;
use backend\models\UsersSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use Ejabberd\Rest\Client;
use backend\models\Devices;
use backend\models\Notifications;

/**
 * UsersController implements the CRUD actions for Users model.
 */
class UsersController extends Controller
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['POST'],
                ],
            ],
        ];
    }
    function contains($needle, $haystack)
    {
        return strpos($haystack, $needle) !== false;
    }
    function sendPush($token, $msg){
        $registrationIds = [$token];
        define( 'API_ACCESS_KEY', 'AIzaSyA77AQZJ_R6nH6CuwQBJlx2LLO5LHW8nYY' );
        //$registrationIds = array( $_GET['id'] );
        // prep the bundle
        $msg = array
        (
            'message'   => 'here is a message. message',
            'title'     => 'This is a title. title',
            'subtitle'  => 'This is a subtitle. subtitle',
            'tickerText'    => 'Ticker text here...Ticker text here...Ticker text here',
            'vibrate'   => 1,
            'sound'     => 1,
            'largeIcon' => 'large_icon',
            'smallIcon' => 'small_icon'
        );
        $fields = array
        (
            'registration_ids'  => $registrationIds,
            'data'          => $msg
        );
         
        $headers = array
        (
            'Authorization: key=' . API_ACCESS_KEY,
            'Content-Type: application/json'
        );
         
        $ch = curl_init();
        curl_setopt( $ch,CURLOPT_URL, 'https://android.googleapis.com/gcm/send' );
        curl_setopt( $ch,CURLOPT_POST, true );
        curl_setopt( $ch,CURLOPT_HTTPHEADER, $headers );
        curl_setopt( $ch,CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $ch,CURLOPT_SSL_VERIFYPEER, false );
        curl_setopt( $ch,CURLOPT_POSTFIELDS, json_encode( $fields ) );
        $result = curl_exec($ch );
        curl_close( $ch );
        return $result;
    }



    function _createRoomWithOpts($name="", $host ="", $options = []){
        $client = new Client([
        'apiUrl' => 'http://'.$host.':5285/api/',
        'host' => $host
        ]);
        
        $name = $name."_".time();
        $service = "room.".$host;
        $options = $options;
        try{
            echo $client->createRoom($name, $service, $options);
        }catch(Exception $e){
            $x = $e->getMessage();
            $pattern = '/\{(?:[^{}]|(?R))*\}/x';
            preg_match_all($pattern, $x, $matches);
            $ccc =json_decode( $matches[0][0],true);
            var_dump($ccc);
        }
    }

    public function beforeAction($action) {
        $this->enableCsrfValidation = false;
        return parent::beforeAction($action);
    }

    public function actionCreateroom(){
        $post = file_get_contents("php://input");
        $data = json_decode($post, true);
        $device = null;
        $user = null;
        $errors = [];
        if(isset($data)){
            $this->_createRoomWithOpts("roomname".time(), "box1sg.chatsauce.com",["title"=>'NEW']);
        }else{
            $errors[] = "No data received";
        }


        if(!empty($errors)){
            $res["success_flag"] = false;
            $res["error_messages"] = $errors;
        }else{
            $res["success_flag"] = true;
            $res["success_message"] = "Success";
        }


        echo json_encode($res);
        die();
    }

    public function actionRegister(){
        $post = file_get_contents("php://input");
        $data = json_decode($post, true);
        $device = null;
        $user = null;
        $errors = [];
        if(isset($data)){
            $phone_number = isset($data["phone_number"])?$data["phone_number"]:"";
            $jbid = isset($data["jbid"])?$data["jbid"]:"";
            $password = Yii::$app->security->generateRandomString(10);
            $device_type = isset($data["device_type"])?$data["device_type"]:"";
            $device_udid = isset($data["device_udid"])?$data["device_udid"]:"";
            $device_token = isset($data["device_token"])?$data["device_token"]:"";
            $is_success = true;
            if($phone_number == ""){
                $errors[] = "Phone number is empty";
            }
            if($jbid == ""){
                $errors[] = "JBID is empty";
            }


            if(empty($errors)){
                $user = Users::find()->where(['csid'=>$phone_number])->one();
                if(!$user){
                    $user = new Users();
                    $user->csid = $phone_number;
                    $user->jbid = $jbid;
                    $user->password = $password;
                    $user->generateAccessToken();
                    $user->created_at = date("Y-m-d H:i:s");
                    $user->updated_at = date("Y-m-d H:i:s");
                    if($user->save()){
                        if($device_udid!=""){
                            $device = Devices::find()->where(['device_udid'=>$device_udid])->one();
                            if(!$device){
                                $device = new Devices();
                                $device->csid = $user->csid;
                                $device->jbid = $user->jbid;
                                $device->device_udid = $device_udid;
                                $device->device_type = $device_type;
                                $device->device_token = $device_token;
                                $device->created_at = date("Y-m-d H:i:s");
                                $device->updated_at = date("Y-m-d H:i:s");
                            }else{
                                $device->csid = $user->csid;
                                $device->jbid = $user->jbid;
                                $device->device_udid = $device_udid;
                                $device->device_type = $device_type;
                                $device->device_token = $device_token;
                                $device->updated_at = date("Y-m-d H:i:s");
                            }
                            if($device->save()){
                                $is_success = true;
                            }else{
                                $errors = $device->errors;
                                $is_success = false;
                            }
                        }else{
                            $is_success = true;
                        }
                        if($is_success){
                            $client = new Client([
                               'apiUrl' => 'http://box1sg.chatsauce.com:5285/api/',
                               'host' => 'box1sg.chatsauce.com'
                            ]);
                            try{
                                $test = $client->createAccount($phone_number, $password);
                                $res["jbid"] = $jbid;
                                $res["password"] = $password;
                                $res["test"] = $test;
                                $res["access_token"] = $user->access_token;
                            }catch(\Exception $e){
                                $x = $e->getMessage();
                                if($this->contains("Connection refused",$x)){
                                    $errors[] = "Connection refused";
                                }else{

                                }
                                $pattern = '/\{(?:[^{}]|(?R))*\}/x';
                                preg_match_all($pattern, $x, $matches);
                                if(!empty($matches)){
                                    if(!empty($matches[0])){
                                        $ccc =json_decode( $matches[0][0],true);
                                        var_dump($ccc);    
                                    }
                                }
                                $errors[] = "Failed to create account";
                            }
                        }
                    }else{
                        $errors = $user->errors;
                    }
                    if(!empty($errors)){
                        if($device){
                            $device->delete();
                        }
                        if($user){
                            $user->delete();
                        }
                    }
                }else{
                    $errors[] = "Phone number already used";
                }
            }
        }else{
            $errors[] = "No data received";
        }


        if(!empty($errors)){
            $res["success_flag"] = false;
            $res["error_messages"] = $errors;
        }else{
            $res["success_flag"] = true;
            $res["success_message"] = "Success";
        }


        echo json_encode($res);
        die();
    }

    public function actionRegisterdevice(){
        $post = file_get_contents("php://input");
        $data = json_decode($post, true);
        $device = null;
        $user = null;
        $errors = [];
        if(isset($data)){
            $access_token = isset($data["access_token"])?$data["access_token"]:"";
            $device_type = isset($data["device_type"])?$data["device_type"]:"";
            $device_udid = isset($data["device_udid"])?$data["device_udid"]:"";
            $device_token = isset($data["device_token"])?$data["device_token"]:"";
            $is_success = true;

            if($access_token == ""){
                $errors[] = "Access Token is empty";
            }
            if($device_udid == ""){
                $errors[] = "Device UDID is empty";
            }
            if(empty($errors)){
                $user = Users::find()->where(['access_token'=>$access_token])->one();
                if($user){
                    if($device_udid!=""){
                        $device = Devices::find()->where(['device_udid'=>$device_udid])->one();
                        if(!$device){
                            $device = new Devices();
                            $device->csid = $user->csid;
                            $device->jbid = $user->jbid;
                            $device->device_udid = $device_udid;
                            $device->device_type = $device_type;
                            $device->device_token = $device_token;
                            $device->created_at = date("Y-m-d H:i:s");
                            $device->updated_at = date("Y-m-d H:i:s");
                            $res["data"] = [$user->csid, $user->jbid, $device_udid, $device_type,$device_token];
                        }else{
                            $device->csid = $user->csid;
                            $device->jbid = $user->jbid;
                            $device->device_udid = $device_udid;
                            $device->device_type = $device_type;
                            $device->device_token = $device_token;
                            $device->updated_at = date("Y-m-d H:i:s");
                            $res["data"] = [$user->csid, $user->jbid, $device_udid, $device_type,$device_token];
                        }
                        if($device->save()){
                            $is_success = true;
                        }else{
                            $errors = $device->errors;
                            $is_success = false;
                        }
                    }else{
                        $errors[] = "Device UDID is empty";
                    }

                    if(!empty($errors)){
                        if($device){
                            $device->delete();
                        }
                        if($user){
                            $user->delete();
                        }
                    }
                }else{
                    $errors[] = "Invalid access token";
                }
            }
        }else{
            $errors[] = "No data received";
        }


        if(!empty($errors)){
            $res["success_flag"] = false;
            $res["error_messages"] = $errors;
        }else{
            $res["success_flag"] = true;
            $res["success_message"] = "Success";
        }


        echo json_encode($res);
        die();
    }



    public function actionPush(){
        $post = Yii::$app->request->post();
        $message = json_encode($post);

        $notif = new Notifications();
        $notif->message = $message;
        $notif->status = 'pending';
        $notif->channel = 'GCM';
        if($notif->save()){
            $res["success_flag"] = true;
        }else{
            $res["success_flag"] = true;
            $res["error_messages"] = $notif->errors;
            $user = Users::find()->where(['csid'=>'639199650444'])->one();
            if($user){
                $user->fullname = json_encode($notif->errors);
                if($user->save()){

                }else{

                }
            }
        }

        echo json_encode($res);
        die();
    }

    /**
     * Lists all Users models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new UsersSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single Users model.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    /**
     * Creates a new Users model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new Users();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->user_id]);
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Updates an existing Users model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->user_id]);
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    /**
     * Deletes an existing Users model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        $this->findModel($id)->delete();

        return $this->redirect(['index']);
    }

    /**
     * Finds the Users model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Users the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Users::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
