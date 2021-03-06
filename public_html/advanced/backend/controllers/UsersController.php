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
use backend\models\GroupMessageStatus;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

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
        define( 'API_ACCESS_KEY', 'AIzaSyAeiQv1KfuP711T0p5PeraUyJ8eMLaqmO0' );
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

    function actionTest(){
        $data = isset($_GET["data"])?$_GET["data"]:"data";
        $notif_id = isset($_GET["notif_id"])?$_GET["notif_id"]:"notif_id";
        // try{
        //     $connection = new AMQPStreamConnection('box3sg.chatsauce.com', 5672, 'admin', 'hive1234');
        //     $channel = $connection->channel();

        //     $channel->queue_declare('cs_rabbit_pusher', false, true, false, false);

        //     if(empty($data)){

        //     }else{
        //         $msg = new AMQPMessage($data,
        //             array('delivery_mode' => 2) # make message persistent
        //         );

        //         $channel->basic_publish($msg, '', 'cs_rabbit_pusher');
        //     }

        //     $channel->close();
        //     $connection->close();
        //     echo "$data";
        // }catch(Exception $e){ 
        //     var_dump($e);
        // }
        // $this->sendRabbitQueue($data);
        if($notif_id){
            $sp = $this->sendPush($notif_id, "TEST 1");
            var_dump($sp);
        }
    }

    function sendRabbitQueue($data){
        try{
            $connection = new AMQPStreamConnection('box3sg.chatsauce.com', 5672, 'admin', 'hive1234');
            $channel = $connection->channel();

            $channel->queue_declare('cs_rabbit_pusher', false, true, false, false);

            if(empty($data)){

            }else{
                $msg = new AMQPMessage($data,
                    array('delivery_mode' => 2) # make message persistent
                );

                $channel->basic_publish($msg, '', 'cs_rabbit_pusher');
            }

            $channel->close();
            $connection->close();
        }catch(Exception $e){ 
            //var_dump($e);
        }
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
            $this->_createRoomWithOpts("roomname".time(), Yii::$app->params['xmppServer']['zoneUrl'],["title"=>'NEW']);
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
                            $completeUrl = Yii::$app->params['xmppServer']['completeUrl'];
                            $zone = Yii::$app->params['xmppServer']['zone'];
                            $baseUrl = Yii::$app->params['xmppServer']['baseUrl'];
                            $client = new Client([
                               'apiUrl' => $completeUrl.'/api/',
                               'host' => $zone.'.'.$baseUrl
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

    public function actionLeavegroup(){
        $post = file_get_contents("php://input");
        $data = json_decode($post, true);
        $res = [];
        $res["success_flag"] = true;
        $res["success_message"] = "";
        $res["error_message"] = "";
        if(isset($data)){
            $access_token = isset($data["access_token"])?$data["access_token"]:"";
            $host = isset($data["host"])?$data["host"]:"";
            $roomname = isset($data["roomname"])?$data["roomname"]:"";
            if($access_token != "" && $host != "" && $roomname != ""){
                $user = Users::find()->where(["access_token"=>$access_token])->one();
                if($user){
                    $csid = $user->csid;
                    $jbid = $user->jbid;
                    $curl = curl_init();
                    curl_setopt_array($curl, array(
                      CURLOPT_PORT => "5280",
                      CURLOPT_URL => "http://".$host.":5280/api/set_room_affiliation",
                      CURLOPT_RETURNTRANSFER => true,
                      CURLOPT_ENCODING => "",
                      CURLOPT_MAXREDIRS => 10,
                      CURLOPT_TIMEOUT => 30,
                      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                      CURLOPT_CUSTOMREQUEST => "POST",
                      CURLOPT_POSTFIELDS => '{
                        "name": "'.$roomname.'",
                        "service":"room.'.$host.'",
                        "jid": "'.$jbid.'",
                        "affiliation": "none"
                        }',
                      CURLOPT_HTTPHEADER => array(
                        "cache-control: no-cache",
                        "content-type: application/json"
                      ),
                    ));
                    $response = curl_exec($curl);
                    $err = curl_error($curl);
                    curl_close($curl);
                    if ($err) {
                        //echo "cURL Error #:" . $err;
                        $res["success_flag"] = false;
                        $res["success_message"] = "";
                        $res["error_message"] = $err;
                    } else {
                        //echo $response;
                        $res["success_flag"] = true;
                        $res["success_message"] = "left the group";
                        $res["error_message"] = "";
                    }
                }else{
                    $res["success_flag"] = false;
                    $res["success_message"] = "";
                    $res["error_message"] = "Invalid Access Token";
                }
            }else{
                $res["success_flag"] = false;
                $res["success_message"] = "";
                $res["error_message"] = "Missing Param";
            }
        }else{
            $res["success_flag"] = false;
            $res["success_message"] = "";
            $res["error_message"] = "Missing Param";
        }

        echo json_encode($res);
        die();
    }



    public function actionRemovesubscription(){
        $post = file_get_contents("php://input");
        $data = json_decode($post, true);
        $res = [];
        $res["success_flag"] = true;
        $res["success_message"] = "";
        $res["error_message"] = "";
        if(isset($data)){
            $access_token = isset($data["access_token"])?$data["access_token"]:"";
              // "user": "639199650445@sgzone3.chatsauce.com",
              // "room": "5ad74ba2bd04fb2e96837536@room.sgzone3.chatsauce.com"
            $user = isset($data["user"])?$data["user"]:"";
            $room = isset($data["room"])?$data["room"]:"";
            $host = isset($data["host"])?$data["host"]:"";
            if($access_token != "" && $user != "" && $room != "" && $host != ""){
                $cur_user = Users::find()->where(["access_token"=>$access_token])->one();
                if($cur_user){
                    $curl = curl_init();
                    curl_setopt_array($curl, array(
                      CURLOPT_PORT => "5280",
                      CURLOPT_URL => "http://".$host.":5280/api/unsubscribe_room",
                      CURLOPT_RETURNTRANSFER => true,
                      CURLOPT_ENCODING => "",
                      CURLOPT_MAXREDIRS => 10,
                      CURLOPT_TIMEOUT => 30,
                      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                      CURLOPT_CUSTOMREQUEST => "POST",
                      CURLOPT_POSTFIELDS => '{
                        "user": "'.$user.'",
                        "room":"'.$room.'"
                        }',
                      CURLOPT_HTTPHEADER => array(
                        "cache-control: no-cache",
                        "content-type: application/json"
                      ),
                    ));
                    $response = curl_exec($curl);
                    $err = curl_error($curl);
                    curl_close($curl);
                    if ($err) {
                        //echo "cURL Error #:" . $err;
                        $res["success_flag"] = false;
                        $res["success_message"] = "";
                        $res["error_message"] = $err;
                    } else {
                        //echo $response;
                        $res["success_flag"] = true;
                        $res["success_message"] = "unsubscribed";
                        $res["error_message"] = "";
                    }
                }else{
                    $res["success_flag"] = false;
                    $res["success_message"] = "";
                    $res["error_message"] = "Invalid Access Token";
                }
            }else{
                $res["success_flag"] = false;
                $res["success_message"] = "";
                $res["error_message"] = "Missing Param";
            }
        }else{
            $res["success_flag"] = false;
            $res["success_message"] = "";
            $res["error_message"] = "Missing Param";
        }

        echo json_encode($res);
        die();
    }

    public function actionSetmessagestatusxmpp(){
        $data = $_POST;
        $res = [];
        $res["success_flag"] = true;
        $res["success_message"] = "";
        $res["error_message"] = "";
        if(isset($data)){
            $jbid = isset($data["jbid"])?$data["jbid"]:"";
            $msgId = isset($data["msgId"])?$data["msgId"]:"";
            $status = isset($data["status"])?$data["status"]:"";
            if($jbid != "" && $msgId != "" && $status != ""){
                $csid = explode("@",$jbid)[0];
                $user = Users::find()->where(["csid"=>$csid])->one();
                if($user){
                    $param = ["msgId"=>$msgId];
                    if($status == "read"){
                        $param["readId"] = $user->jbid;
                    }else{
                        $param["deliveredId"] = $user->jbid;
                    }

                    $gms = GroupMessageStatus::find()->where($param)->one();
                    if(!$gms){
                        $gms = new GroupMessageStatus();
                        $gms->msgId = $msgId;
                        if($status == "read"){
                            $gms->readId = $user->jbid;
                            $gms->deliveredId = "";
                        }else{
                            $gms->readId = "";
                            $gms->deliveredId = $user->jbid;
                        }
                        $gms->datetime = date("Y-m-d H:i:s");
                        if($gms->save()){
                            $res["success_flag"] = true;
                            $res["success_message"] = "Success set status";
                            $res["error_message"] = "";
                        }else{
                            $res["success_flag"] = false;
                            $res["success_message"] = "";
                            $res["error_message"] = "Failed to set status";
                            $res["errors"] = $gms->errors;
                        }
                    }else{
                        $res["success_flag"] = true;
                        $res["success_message"] = "Already Done";
                        $res["error_message"] = "";
                    }

                }else{
                    $res["success_flag"] = false;
                    $res["success_message"] = "";
                    $res["error_message"] = "Invalid Access Token";
                }
            }else{
                $res["success_flag"] = false;
                $res["success_message"] = "";
                $res["error_message"] = "Missing Param";
            }
        }else{
            $res["success_flag"] = false;
            $res["success_message"] = "";
            $res["error_message"] = "Missing Param";
        }

        echo json_encode($res);
        die();
    }

    public function actionSetmessagestatusxmpp2(){
        $data = $_POST;
        $res = [];
        $res["success_flag"] = true;
        $res["success_message"] = "";
        $res["error_message"] = "";
        if(isset($data)){
            $jbid = isset($data["jbid"])?$data["jbid"]:"";
            $msgId = isset($data["msgId"])?$data["msgId"]:"";
            $status = isset($data["status"])?$data["status"]:"";
            if($jbid != "" && $msgId != "" && $status != ""){
                $user = Users::find()->where(["jbid"=>$jbid])->one();
                if($user){
                    $param = ["msgId"=>$msgId];
                    if($status == "read"){
                        $param["readId"] = $user->jbid;
                    }else{
                        $param["deliveredId"] = $user->jbid;
                    }

                    $gms = GroupMessageStatus::find()->where($param)->one();
                    if(!$gms){
                        $gms = new GroupMessageStatus();
                        $gms->msgId = $msgId;
                        if($status == "read"){
                            $gms->readId = $user->jbid;
                            $gms->deliveredId = "";
                        }else{
                            $gms->readId = "";
                            $gms->deliveredId = $user->jbid;
                        }
                        $gms->datetime = date("Y-m-d H:i:s");
                        if($gms->save()){
                            $res["success_flag"] = true;
                            $res["success_message"] = "Success set status";
                            $res["error_message"] = "";
                        }else{
                            $res["success_flag"] = false;
                            $res["success_message"] = "";
                            $res["error_message"] = "Failed to set status";
                            $res["errors"] = $gms->errors;
                        }
                    }else{
                        $res["success_flag"] = true;
                        $res["success_message"] = "Already Done";
                        $res["error_message"] = "";
                    }

                }else{
                    $res["success_flag"] = false;
                    $res["success_message"] = "";
                    $res["error_message"] = "Invalid Access Token";
                }
            }else{
                $res["success_flag"] = false;
                $res["success_message"] = "";
                $res["error_message"] = "Missing Param";
            }
        }else{
            $res["success_flag"] = false;
            $res["success_message"] = "";
            $res["error_message"] = "Missing Param";
        }

        echo json_encode($res);
        die();
    }

    public function actionSetmessagestatus(){
        $post = file_get_contents("php://input");
        $data = json_decode($post, true);
        $res = [];
        $res["success_flag"] = true;
        $res["success_message"] = "";
        $res["error_message"] = "";
        if(isset($data)){
            $access_token = isset($data["access_token"])?$data["access_token"]:"";
            $msgId = isset($data["msgId"])?$data["msgId"]:"";
            $status = isset($data["status"])?$data["status"]:"";
            if($access_token != "" && $msgId != "" && $status != ""){
                $user = Users::find()->where(["access_token"=>$access_token])->one();
                if($user){
                    $param = ["msgId"=>$msgId];
                    if($status == "read"){
                        $param["readId"] = $user->jbid;
                    }else{
                        $param["deliveredId"] = $user->jbid;
                    }

                    $gms = GroupMessageStatus::find()->where($param)->one();
                    if(!$gms){
                        $gms = new GroupMessageStatus();
                        $gms->msgId = $msgId;
                        if($status == "read"){
                            $gms->readId = $user->jbid;
                            $gms->deliveredId = "";
                        }else{
                            $gms->readId = "";
                            $gms->deliveredId = $user->jbid;
                        }
                        $gms->datetime = date("Y-m-d H:i:s");
                        if($gms->save()){
                            $res["success_flag"] = true;
                            $res["success_message"] = "Success set status";
                            $res["error_message"] = "";
                        }else{
                            $res["success_flag"] = false;
                            $res["success_message"] = "";
                            $res["error_message"] = "Failed to set status";
                            $res["errors"] = $gms->errors;
                        }
                    }else{
                        $res["success_flag"] = true;
                        $res["success_message"] = "Already Done";
                        $res["error_message"] = "";
                    }

                }else{
                    $res["success_flag"] = false;
                    $res["success_message"] = "";
                    $res["error_message"] = "Invalid Access Token";
                }
            }else{
                $res["success_flag"] = false;
                $res["success_message"] = "";
                $res["error_message"] = "Missing Param";
            }
        }else{
            $res["success_flag"] = false;
            $res["success_message"] = "";
            $res["error_message"] = "Missing Param";
        }

        echo json_encode($res);
        die();
    }



    public function actionMessagestatus(){
        $post = file_get_contents("php://input");
        $data = json_decode($post, true);
        $res = [];
        $res["success_flag"] = true;
        $res["success_message"] = "";
        $res["error_message"] = "";
        if(isset($data)){
            $access_token = isset($data["access_token"])?$data["access_token"]:"";
            $msgId = isset($data["msgId"])?$data["msgId"]:"";
            if($access_token != "" && $msgId != ""){
                $user = Users::find()->where(["access_token"=>$access_token])->one();
                if($user){
                    $gms = GroupMessageStatus::find()->where(["msgId"=>$msgId])->all();
                    $res["data"]["readIds"] = [];
                    $res["data"]["deliveredIds"] = [];
                    $res["data"]["msgId"] = $msgId;
                    foreach ($gms as $gm) {
                        if($gm->readId != ""){
                            $res["data"]["readIds"][] = $gm->readId."_".$gm->timestamp;
                        }
                        if($gm->deliveredId != ""){
                            $res["data"]["deliveredIds"][] = $gm->deliveredId."_".$gm->timestamp;
                        }
                    }
                    $res["success_flag"] = true;
                    $res["success_message"] = "Success";
                    $res["error_message"] = "";
                }else{
                    $res["success_flag"] = false;
                    $res["success_message"] = "";
                    $res["error_message"] = "Invalid Access Token";
                }
            }else{
                $res["success_flag"] = false;
                $res["success_message"] = "";
                $res["error_message"] = "Missing Param";
            }
        }else{
            $res["success_flag"] = false;
            $res["success_message"] = "";
            $res["error_message"] = "Missing Param";
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
        $json = json_decode($message, true);
        $type = "chat";
        $ids = [];
        if($json["type"] == "chat"){
            $type = "chat";
            $ids[] = $json["to"];
        }else if($json["type"] == "groupchat"){
            $type = "groupchat";
            $ids = explode("|",$json["offline"]); 
        }else if($json["type"] == "normal"){
            $type = "normal";
            $ids[] = $json["to"];
        }
        foreach($ids as $id ){
            if($id != ""){
                $idx = explode("/", $id)[0];
                $notif = new Notifications();
                $notif->notif_token = "not_".round(microtime(true) * 1000);
                $notif->message = $message;
                $notif->status = 'pending';
                $notif->channel = '';
                $notif->type = $type;
                $notif->to = $idx;
                $notif->start_date = date("Y-m-d H:i:s");
                $notif->created_at = date("Y-m-d H:i:s");
                $notif->updated_at = date("Y-m-d H:i:s");
                if($notif->save()){
                    $res["success_flag"] = true;
                    $this->sendRabbitQueue($notif->notif_token);
                }else{
                    $res["success_flag"] = true;
                    $res["error_messages"] = $notif->errors;
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
    public function actionIndex(){
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
