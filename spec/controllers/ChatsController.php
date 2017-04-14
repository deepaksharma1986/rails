<?php
App::uses('AppController', 'Controller');
App::uses('AuthComponent', 'Controller/Component');
App::import('Vendor', 'ImageTool');
/**
 * Users Controller
 *
 * @property User $User
 * @property PaginatorComponent $Paginator
 */
class ChatsController extends AppController {

    /**
     * Components
     *
     * @var array
     */
    public $components = array('Paginator','Session', 'Cookie',  'Auth', 'RequestHandler');
    public $helpers  = array('Html','Basic');
    public $uses = array('User','Chat','ChatRequest');

    public $paginate = array(
        'limit' => 25,
        'conditions' => array('status' => '1'),
        'order' => array('User.username' => 'asc')
    );

    public function beforeFilter() {
        parent::beforeFilter();
        $this->Auth->allow(
            'chatroom','index','getUserAvailability','deleteMsg'
        );
    }

    /*
     * Login action for admin
     *
     * */
    public function index() {
    	
        $this->autoRender = false;
        $response['success']=false;
        $response['status']=0;
        $myid = $this->Session->read("Auth.Member.id");
        if($myid){
            if($this->request->is('ajax')){
            	
           // 	echo date('Y/m/d H:i:s');
            	
            if(isset($_GET['rq'])){
                switch($_GET['rq']){
                    case 'new':
                        $msg = $_POST['msg'];
                        $fid = $this->toInternalId($_POST['fid']);
                        //A User can send max 3 message
                        
                        $user_recieve_meaasge =$this->Chat->find('count',array('conditions'=>array('Chat.sender'=>$fid,'Chat.receiver'=>$myid)));
                        
                        $user_send_meaasge =$this->Chat->find('count',array('conditions'=>array('Chat.sender'=>$myid,'Chat.receiver'=>$fid)));

                       // echo $user_recieve_meaasge;
                       // echo $user_send_meaasge;
                      //  die;
                        
                    /*     if($user_recieve_meaasge==0&&$user_send_meaasge>2)
                        {
                        	$response['msg']="You can not send more message";
                        	die;
                        }
                         */
                        
                        if(empty($msg) || $fid=="" || !$this->User->exists($fid) ){
                            //$json = array('status' => 0, 'msg'=> 'Enter your message!.');
                            $response['msg']="Oops Some error has been occurred";
                        }
                        else if($user_recieve_meaasge==0 && $user_send_meaasge>2)
                        {
                        	$response['msg']="You can send only 3 messages until you get a response";

                        }
                        else{
                        //	echo time();
                        //	echo "The time is " . date("h:i:sa");
                        //	die;
                            // $qur = mysql_query('insert into msg set `to`="'.$fid.'", `from`="'.$myid.'", `msg`="'.$msg.'", `status`="1"');
                            $data['Chat']['receiver'] = $fid;
                            $data['Chat']['sender'] =   $myid;
                            $data['Chat']['msg'] =      $msg;
                            $data['Chat']['status'] =   1;
                            $data['Chat']['time'] =    time();
                            
                            //adding First Chat Entry in Chat request Table
                            
                            $IsChatRequestExists = $this->ChatRequest->find('count',array('conditions'=>array('ChatRequest.sender_id'=>$myid,'ChatRequest.receiver_id'=>$fid)));
                            	
                            if($fid && $myid && $msg){
                                $this->Chat->create();
                                if( $this->Chat->save($data)){
                                	
                                	if($IsChatRequestExists==0)
                                	{
                                		$ChatData['ChatRequest']['receiver_id'] = $fid;
                                		$ChatData['ChatRequest']['sender_id'] = $myid;
                                		$ChatData['ChatRequest']['message'] = $msg;
                                		$ChatData['ChatRequest']['send_on'] = time();
                                		$this->ChatRequest->save($ChatData);
                                		//$ChatData['ChatRequest']['time'] = time();
                                	               
                                	}
                                    // $qurGet = mysql_query("select * from msg where id='".mysql_insert_id()."'");
                                	$options = array('recursive'=>-1,'fields' => array('User.username','User.user_image','User.gender'),'conditions' => array('User.' . $this->User->primaryKey =>$myid));
                                	$userData= $this->User->find('first', $options);
                                	$optionsReciever = array('recursive'=>-1,'fields' => array('User.gcm_id','User.iphonedeviceId'),'conditions' => array('User.' . $this->User->primaryKey =>$fid));
                                	$recieverData= $this->User->find('first', $optionsReciever);
                                	$getRecieverSetting=$this->getUserSetting($fid,'',array('user_settings.app_favorite'));
                                	//  if($getRecieverSetting['user_settings']['app_favorite']==1){
                                	
                                	
                                //	$path = 'http://'.$_SERVER['HTTP_HOST'].'/meet_dating/timthumb/image?src=';
                                	
                                	$site_url = 	Router::url('/', true);
                                	
                                	$path = $site_url.'/timthumb/image?src=';
                                	$homeUrl= Router::url('/', true);
                                	$userImg=$path.$homeUrl.'app/webroot/img/uploads/profile_pic/';
                                	$userData['User']['user_image'] = $userImg.$userData['User']['user_image'];
                                	$message = $msg;
                                	
                                	if($recieverData['User']['gcm_id']){
                                	$url = 'https://android.googleapis.com/gcm/send';
                                	$message = array("userId"=>$myid,"message" => $message,"sender"=>$userData['User']['username'],"sender_image"=>$userData['User']['user_image'],"gender"=>$userData['User']['gender'],"type"=>1,"time"=>time());
                                	$registatoin_ids = array($recieverData['User']['gcm_id']);
                                	$fields = array(
                                			'registration_ids' => $registatoin_ids,
                                			'data' => $message,
                                	);
                                	define("GOOGLE_API_KEY", "AIzaSyDy7j3XQfN2Xu-LOW-tO60WqFh3GKbjGaM");
                                	$headers = array(
                                			'Authorization: key=' . GOOGLE_API_KEY,
                                			'Content-Type: application/json'
                                	);
                                	// Open connection
                                	$ch = curl_init();
                                	
                                	// Set the url, number of POST vars, POST data
                                	curl_setopt($ch, CURLOPT_URL, $url);
                                	
                                	curl_setopt($ch, CURLOPT_POST, true);
                                	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                                	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                                	
                                	// Disabling SSL Certificate support temporarly
                                	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                                	
                                	curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
                                	
                                	// Execute post
                                	//	pr($ch);
                                	$result = curl_exec($ch);
                                	if ($result === FALSE) {
                                		die('Curl failed: ' . curl_error($ch));
                                	}
                                	
                                	// Close connection
                                	curl_close($ch);
                                	$response['success'] = true;
                                	$response['result'] =  $result;
                                	}else if($recieverData['User']['iphonedeviceId'])
                                	{
                                		 
                                		$result = "";
                                		//	$message = array("userId"=>$data['userId'],"message" => $message,"sender"=>$userData['User']['username'],"sender_image"=>$userData['User']['user_image'],"type"=>1,"time"=>time());
                                		$body = array();
                                		 
                                		$body['aps']['alert'] = $message;
                                		$body['aps']['sound'] = 'default';
                                		 
                                		$body['notification']['message'] =  $message;
                                		//	$body['aps']['notifurl'] = SITEURL;
                                		//	$body['aps']['key'] = 60;
                                		$body['notification']['sender'] = $userData['User']['username'];
                                		$body['notification']['userId'] = $myid;
                                		$body['notification']['msg_type'] = 'chat';
                                		$body['notification']['time'] = time();
                                		$body['notification']['type']=1;
                                		$body['notification']['sender_image']=$userData['User']['user_image'];
                                		$registatoinids = array($recieverData['User']['iphonedeviceId']);
                                		$results = $this->send_iphonenotification($registatoinids, $body);
                                		$response['result'] =  $results;
                                	}
                                	
                                	
                                	//      }
              
                                    $options = array('conditions' => array('Chat.' . $this->Chat->primaryKey => $this->Chat->id));
                                    $data = $this->Chat->find('first', $options);
                                 //   $time_before = $this->timeAgo($data['Chat']['time']);
                                    //  pr($this->request->data); die;
                                  //  $response = array('status' => 1, 'msg' => $data['Chat']['msg'], 'lid' =>$this->Chat->id, 'time' =>$time_before);
                                   $response = array('status' => 1, 'msg' => $data['Chat']['msg'], 'lid' =>$this->Chat->id, 'time' =>$data['Chat']['time']);
                                }else{
                                    $response = array('status' => 0, 'msg'=> 'Unable to process request.');
                                }
                            }
                        }
                        break;
                    case 'msg':
                        $myid = $this->Session->read("Auth.Member.id");
                        $fid = (isset($_POST['fid']) && $_POST['fid']!='')? $_POST['fid'] : '';
                        $fid = $this->toInternalId($fid);
                        $lid = (isset($_POST['lid']) && $_POST['lid']!='')? $_POST['lid'] : '';
                        if( $myid=="" || $fid=="" || !$this->User->exists($fid) ){
                            //$json = array('status' => 0, 'msg'=> 'Enter your message!.');
                             $response['msg']="Oops Some error has been occurred";
                        }else{
                            $options = array('conditions' => array('Chat.receiver' => $myid,'Chat.sender' => $fid,'Chat.status' =>1));
                            $data_count = $this->Chat->find('count', $options);
                            
                            //for read or unread message on friends Side
                            
                            $message_unread = $this->Chat->find('count',array('conditions'=>array('Chat.status'=>1,'Chat.receiver'=>$fid,'Chat.sender'=>$myid)));
                            if($message_unread>0)
                            {
                            	$read_status = 1;
                            }
                            else
                            {
                            	$read_status = 0;
                            }
                            
                            if($data_count > 0){
                                $response = array('status' => 1,'read_status'=>$read_status);
                            }else{
                                $response = array('status' => 0,'read_status'=>$read_status);
                            }
                        }
                        break;
                    case 'NewMsg':
                        $myid = $this->Session->read("Auth.Member.id");
                        $fid = $this->toInternalId($_POST['fid']);
                        if( $myid=="" || $fid=="" || !$this->User->exists($fid) ){
                            //$json = array('status' => 0, 'msg'=> 'Enter your message!.');
                            $response['msg']="Oops Some error has been occurred";
                        }else{
                            $options = array('conditions' => array('Chat.receiver' => $myid,'Chat.sender' => $fid,'Chat.status' =>1));
                            $data= $this->Chat->find('first', $options);
                            $decoded_msg=json_decode('"'.$data['Chat']['msg'].'"', JSON_UNESCAPED_UNICODE);
                             
                          //  $time_before = $this->timeAgo($data['Chat']['time']);
                          //  $response = array('status' => 1, 'msg' => '<div>'.$data['Chat']['msg'].'</div>', 'lid' => $data['Chat']['id'], 'time'=> $time_before);
                            $response = array('status' => 1, 'msg' => '<div>'.$decoded_msg.'</div>', 'lid' => $data['Chat']['id'], 'time'=> $data['Chat']['time']);
                            // update status
                            $this->Chat->id=$data['Chat']['id'];
                            $data['Chat']['status']=0;
                            $this->Chat->Save($data);
                        }

                        break;
                }
            }
            }else{
                $response['msg']="Oops Some error has been occurred";
            }
        }else{
            $response['msg']="Please login to process";
        }
        echo json_encode($response);
        die;


    }
    public function chatroom() {
        $this->layout = 'ajax';
        $this->autoRender=false;
        $response['success']=false;
        $response['status']=0;
        $id = $this->Session->read("Auth.Member.id");
        if($id){
            if($this->request->is('ajax')){
                $options = array('conditions' => array(
                    "OR"=>array(
                        'Chat.receiver' => $id,
                        'Chat.sender' => $id
                    )
                )
                );
                
                $query = "SELECT COUNT(*) FROM chats WHERE (sender=".$id.") AND (sender_delete=1)";
                $query1 = "SELECT COUNT(*) FROM chats WHERE ( receiver = ".$id.") AND ( receiver_delete=1)";
                 
                $num = $this->Chat->query($query);
                $num1 = $this->Chat->query($query1);
  
                $chatCount= $this->Chat->find('count', $options);
                if($chatCount>0 &&($num[0][0]['COUNT(*)']!=0 || $num1[0][0]['COUNT(*)']!=0)){
                    /* Set up new view that won't enter the ClassRegistry */
                    $view = new View($this, false);
                    /* Grab output into variable without the view actually outputting! */
                    $view_output = $view->render('chat');
                    $response['data']=$view_output;
                    $response['success']=true;
                    $response['status']=1;
                }else{
                    $response['msg']="No friends to chat";
                }

            }else{
                $response['msg']="Oops Some error has been occurred";
            }
        }else{
            $response['msg']="Please login to process";
        }

        echo json_encode($response);
        die;

    }
    public function userChat(){
        $this->autoRender = false;
        if($this->request->is('ajax')){
            $userId = $_POST['userId'];
            $userEncriptId=$userId;
            $userId=$this->toInternalId($userId);
            if ($this->User->exists($userId)) {
                $conditions=array('User.id'=>$userId);
                $options = array( 'fields' => array('User.username', 'User.login_status','User.user_image','User.gender'),'conditions'=>$conditions);
                $userData= $this->User->find('first', $options);
                $myid = $this->Session->read("Auth.Member.id");

                $conditions2="select count(*) AS count from (SELECT `Chat`.*, if( `Chat`.`receiver` =".$myid.", `Chat`.`receiver_delete`, `Chat`.`sender_delete`) AS deletemsg
                FROM `chats` AS `Chat` WHERE  `Chat`.`sender` IN (".$myid.", ".$userId.")
                AND `Chat`.`receiver` IN (".$userId.", ".$myid.") AND `status` = '1') as temp_chat where deletemsg=1 ";

                $unread_msg= $this->Chat->query($conditions2);
                $unread_msg=$unread_msg[0][0]['count'];

                if($unread_msg>=20){
                    $conditions3="select *  from (SELECT `Chat`.*, if( `Chat`.`receiver` =".$myid.", `Chat`.`receiver_delete`, `Chat`.`sender_delete`) AS deletemsg
                FROM `chats` AS `Chat` WHERE  `Chat`.`sender` IN (".$myid.", ".$userId.")
                AND `Chat`.`receiver` IN (".$userId.", ".$myid.") AND `status` = '1') as temp_chat where deletemsg=1 ";

                    $old_msg= $this->Chat->query($conditions3);
                }else{
                    $query="select *  from (SELECT `Chat`.*, if( `Chat`.`receiver` =".$myid.", `Chat`.`receiver_delete`, `Chat`.`sender_delete`) AS deletemsg
                FROM `chats` AS `Chat` WHERE  `Chat`.`sender` IN (".$myid.", ".$userId.")
                AND `Chat`.`receiver` IN (".$userId.", ".$myid.") ) as temp_chat where deletemsg=1 ORDER BY `temp_chat`.`id` DESC LIMIT 20 ";

                   $old_msg= array_reverse($this->Chat->query($query));
                }

                /*  update fields after get unread msg */
               // $conditionsUpdate=array('Chat.status'=>1,'Chat.sender'=>array($myid,$userId),'Chat.receiver'=>array($userId,$myid));
                $conditionsUpdate=array('Chat.status'=>1,'Chat.receiver'=>$myid,'Chat.sender'=>$userId);
                $this->Chat->updateAll(array('status'=>0), $conditionsUpdate);
                
                //condition for checking last message read or not
                
              

                $msg='';
                if(!empty($old_msg)){
                    foreach($old_msg as $message){
                        //$var='\uD83D\uDE07';
                        //echo "vikas:".json_decode('"'.$var.'"').":";
                        //$message['temp_chat']['msg']='\uD83D\uDE07\uD83D\uDE02\uD83D\uDE09\uD83D\uDE07\uD83D\uDE21\uD83D\uDE21\uD83D\uDE32\uD83D\uDE31\uD83D\uDE25\uD83D\uDE33\uD83D\uDE35\uD83D\uDE0D\uD83D\uDE30\uD83D\uDE29\uD83D\uDE23\uD83D\uDE37\uD83D\uDE1E\uD83D\uDE33\uD83D\uDE1C\uD83D\uDE0D';
                        $decoded_msg=json_decode('"'.$message['temp_chat']['msg'].'"', JSON_UNESCAPED_UNICODE);
                        //$decoded_msg=json_decode('\uD83D\uDE07\uD83D\uDE02\uD83D\uDE09\uD83D\uDE07\uD83D\uDE21\uD83D\uDE21\uD83D\uDE32\uD83D\uDE31\uD83D\uDE25\uD83D\uDE33\uD83D\uDE35\uD83D\uDE0D\uD83D\uDE30\uD83D\uDE29\uD83D\uDE23\uD83D\uDE37\uD83D\uDE1E\uD83D\uDE33\uD83D\uDE1C\uD83D\uDE0D', JSON_UNESCAPED_UNICODE );
                        //$decoded_msg=$message['temp_chat']['msg'];

                        if($myid==$message['temp_chat']['sender']){
                            $msg .= '<div class="float-fix">'.
                                '<div class="m-rply">'.
                                '<div class="msg-bg">'.
                                '<div class="msgA" style="font-family:ZawgyiOne !important;"><span class="deleteMsg" style="display:none"><a href="javascript:void(0)" id="'.$message['temp_chat']['id'].'" >X</a></span>'.
                                $decoded_msg.
                                '<div class="">'.
                                '<div class="msg-time" data-livestamp="'.$message['temp_chat']['time'].'"></div>'.
                                '<div class="myrply-i"></div>'.
                                '</div>'.
                                '</div>'.
                                '</div>'.
                                '</div>'.
                                '</div>';
                        }else{
                            $msg .=  '<div class="float-fix">'.
                                '<div class="f-rply">'.
                                '<div class="msg-bg">'.
                                '<div class="msgA" style="font-family:ZawgyiOne !important;"><span class="deleteMsg" style="display:none"><a href="javascript:void(0)" id="'.$message['temp_chat']['id'].'">X</a></span>'.
                                $decoded_msg.
                                '<div class="">'.
                                '<div class="msg-time" data-livestamp="'.$message['temp_chat']['time'].'"></div>'.
                                '<div class="myrply-f"></div>'.
                                '</div>'.
                                '</div>'.
                                '</div>'.
                                '</div>'.
                                '</div>';
                        }
                    }
                }

                $json = array('success' =>true,'username' =>$userData['User']['username'],'uid'=>$userEncriptId,'pid'=>$userId,'image' =>$userData['User']['user_image'],'gender' =>$userData['User']['gender'],'login_status' =>$userData['User']['login_status'],'messages' =>$msg);

            }else{
                $json = array('success' =>false);
            }
        }else{
            $this->redirect('/');
        }
        header('Content-type: application/json');
        echo json_encode($json);
    }


    public function getUserAvailability(){
        $this->autoRender = false;
        $myid = $this->Session->read("Auth.Member.id");
        $response['success']=false;
        $response['status']=0;
        if($myid){
            if($this->request->is('ajax')){
                $type=$this->request->data['type'];
                if($type=='all'){
              /*      $query="select `User`.`id` , `User`.`username` ,
            `User`.`login_status` , `User`.`user_image` ,`User`.`gender`,
            `Chat`.`sender` , `Chat`.`receiver`
             from users as `User` inner join (SELECT id, `receiver` , `sender` ,
             if(`receiver`=".$myid.",`sender`, `receiver` )
             as userid
             FROM chats
             WHERE  (`receiver`=".$myid." or `sender`=".$myid.") and status<>2 group by userid)
             as Chat on Chat.userid=`User`.id ";*/
             $query = " SELECT `User`.`id` , `User`.`username` , `User`.`login_status` , `User`.`user_image` , `User`.`gender` , `Chat`.`sender` , `Chat`.`receiver`, Chat.time
FROM users AS `User`INNER JOIN (SELECT * FROM (SELECT id, `receiver` , `sender` , if( `receiver` =".$myid.", `sender` , `receiver` ) AS userid, time FROM chats
WHERE (`receiver` =".$myid." OR `sender` =".$myid." ) AND STATUS <>2 ORDER BY id DESC ) AS temp_chat
GROUP BY userid) AS Chat ON Chat.userid = `User`.id WHERE  User.is_deleted=0 ORDER BY Chat.time desc LIMIT 0 , 30 ";
                
                
                }
                elseif($type=='online'){

          /*          $query="select `User`.`id` , `User`.`username` ,
                    `User`.`login_status` , `User`.`user_image` ,`User`.`gender`,
                    `Chat`.`sender` , `Chat`.`receiver`
                     from users as `User` inner join (SELECT id, `receiver` , `sender` ,
                      if(`receiver`=".$myid.",`sender`, `receiver` ) as userid
                    FROM chats
                    WHERE  (`receiver`=".$myid." or `sender`=".$myid.")  group by userid )
                     as Chat on Chat.userid=`User`.id where User.login_status=1 "; */
                	$query = " SELECT `User`.`id` , `User`.`username` , `User`.`login_status` , `User`.`user_image` , `User`.`gender` , `Chat`.`sender` , `Chat`.`receiver`, Chat.time
FROM users AS `User`INNER JOIN (SELECT * FROM (SELECT id, `receiver` , `sender` , if( `receiver` =".$myid.", `sender` , `receiver` ) AS userid, time FROM chats
WHERE (`receiver` =".$myid." OR `sender` =".$myid." ) AND STATUS <>2 ORDER BY id DESC ) AS temp_chat
GROUP BY userid) AS Chat ON Chat.userid = `User`.id where User.login_status=1 AND User.is_deleted=0 ORDER BY Chat.time desc LIMIT 0 , 30 ";
                }elseif($type=='chat'){

                	/*$query="select `User`.`id` , `User`.`username` ,
                        `User`.`login_status` , `User`.`user_image` ,`User`.`gender`,
                        `Chat`.`sender` , `Chat`.`receiver`  from users as `User`
                        inner join (select a.id, a.`receiver` , a.`sender` ,
                        if(a.`receiver`=".$myid.",a.`sender`, a.`receiver` ) as userid from chats as a inner join chats as b
                        on a.receiver=b.sender and b.receiver=a.sender
                        where (a.`receiver`=".$myid." or a.`sender`=".$myid.") group by userid
                        ) as Chat on Chat.userid=`User`.id ";*/
                	$query = " SELECT `User`.`id` , `User`.`username` , `User`.`login_status` , `User`.`user_image` , `User`.`gender` , `Chat`.`sender` , `Chat`.`receiver` FROM users AS `User`
INNER JOIN ( SELECT * FROM ( SELECT a.id, a.`receiver` , a.`sender` , if( a.`receiver` =".$myid.", a.`sender` , a.`receiver` ) AS userid, a.time
FROM chats AS a INNER JOIN chats AS b ON a.receiver = b.sender AND b.receiver = a.sender WHERE (a.`receiver` =".$myid." OR a.`sender` =".$myid.")
ORDER BY a.time DESC) AS temp_chat GROUP BY userid order by time desc) AS Chat ON Chat.userid = `User`.id WHERE  User.is_deleted=0 LIMIT 0 , 30  ";

                }
          //  echo $query;
        //    die;
                $userData= $this->User->query($query);
                /* get unread message*/
                $options2 = array('fields'=>array('Chat.sender','COUNT(Chat.sender) AS count'),'group' => array('Chat.sender'),'conditions' => array('Chat.receiver' => $myid,'Chat.status' =>1));
                $unreadCount = $this->Chat->find('all', $options2);
                $totalUnreadCount = array('conditions' => array('Chat.receiver' => $myid,'Chat.status' =>1));
                $totalUnreadCount = $this->Chat->find('count', $totalUnreadCount);
                if(!empty($unreadCount)){
                    foreach($unreadCount as $unread){
                        $unreadArr[$unread['Chat']['sender']]=$unread[0]['count'];
                    }
                }
                /*  till here*/

                $userHtml='';
                $userHtml2 = '';
                if(!empty($userData)){
                    foreach($userData as $data){
                    	
                       
                //   $query = "SELECT COUNT(*) FROM chats WHERE ((sender=".$myid." AND receiver = ".$data['User']['id'].") OR (sender=".$data['User']['id']." AND receiver = ".$myid.")) AND (sender_delete=1 OR receiver_delete=1)";

                    	$query = "SELECT COUNT(*) FROM chats WHERE ((sender=".$myid." AND receiver = ".$data['User']['id'].")) AND (sender_delete=1)";
                    	$query1 = "SELECT COUNT(*) FROM chats WHERE ((sender=".$data['User']['id']." AND receiver = ".$myid.")) AND ( receiver_delete=1)";
           
                    	$num = $this->Chat->query($query);
                    	$num1 = $this->Chat->query($query1);
                  	if($num[0][0]['COUNT(*)']!=0 || $num1[0][0]['COUNT(*)']!=0){ 
                        /* unread message html*/
                        $unreadHtml='';
                        if(!empty($unreadArr)){
                            if (array_key_exists($data['User']['id'], $unreadArr)) {
                                $unreadHtml='<em class="notifyrequest notifyrequest1">'.$unreadArr[$data['User']['id']].'</em>';
                                // unset($unreadArr[$data['User']['id']]);
                            }
                        }
                        /* till here*/

                        if($data['User']['login_status']==1){
                            $onlineStatus=' onlineUser';
                        }else{
                            $onlineStatus=' offlineUser';
                        }
                        if($data['User']['gender']==1)
                        {
                            $color = "color:#0597c5";
                            $default_img="default_user_male_300.png";
                        }else{
                            $color = "color:#f41467";
                            $default_img="default_user_female_300.png";
                        }
                        if($data['User']['user_image']){
                            $userImg='<img src='.$this->webroot.'timthumb/image?src=/'.$this->webroot.'app/webroot/img/uploads/profile_pic/'.$data['User']['user_image'].'&q=80&a=c&zc=1&ct=1&w=32&h=32>';
                            //$userImg= $this->Timthumb->image('/img/uploads/profile_pic/'.$data['User']['user_image'], array('width' => 32, 'height' => 32),array( "alt" => "Profile"));
                        }else{
                            $userImg='<img src='.$this->webroot.'timthumb/image?src=/'.$this->webroot.'app/webroot/img/'.$default_img.'&q=80&a=c&zc=1&ct=1&w=32&h=32>';
                            //$userImg= $this->Timthumb->image('/img/default_user_male_300.png', array('width' => 32, 'height' => 32),array( "alt" => "Profile"));
                        }


                $userHtml.= '<li data-color='.$color.' data-id="'.$this->toPublicId($data['User']['id']) .'" id="'.$this->toPublicId($data['User']['id']).'" pid="'.$data['User']['id'].'" >
               
                  		
                <div class="chatuserStatus"> <i class="fa fa-circle'.$onlineStatus.'"></i></div>
                 <div class="chatuserStatus unreadMsg" id="unreadMsg_'.$data['User']['id'].'">'.$unreadHtml.' </div>		
                <div class="chatuserImg">'.$userImg.'</div>
                <div class="chatuserName">'.$data['User']['username'].'</div>
                 <div class="message_status"></div>	
                	<div class="deleteAllChat">	<a id="'.$data['User']['id'].'" href="javascript:void(0)">X</a>	</div>
                <div class="clearfix"></div>
            </li>';
                $userHtml2.= '<li data-color='.$color.' data-id="'.$this->toPublicId($data['User']['id']) .'" id="'.$this->toPublicId($data['User']['id']).'" pid="'.$data['User']['id'].'" >
                
                <div class="chatuserStatus"> <i class="fa fa-circle'.$onlineStatus.'"></i></div>
                <div class="chatuserStatus unreadMsg" id="unreadMsg_'.$data['User']['id'].'">'.$unreadHtml.' </div>		
                <div class="chatuserImg">'.$userImg.'</div>
                <div class="chatuserName">'.$data['User']['username'].'</div>
                <div class="message_status"></div>
                
                <div class="clearfix"></div>
            </li>';
                    	}
                     }
                }

                $response = array('status' => 1,'success' => true,'totalUnread'=>$totalUnreadCount,'userHtml'=>$userHtml,'userHtml2'=>$userHtml2);
            }else{
                $this->redirect('/');
            }
        }else{
            $response['msg']="Please login to process";
        }
        echo json_encode($response);
        die;
    }

    public function deleteMsg(){
        $this->autoRender = false;
        $myid = $this->Session->read("Auth.Member.id");
        $response['success']=false;
        $response['status']=0;
        if($myid){
          if($this->request->is('ajax')){
              $msgId=$this->request->data['msgId'];
              if($msgId){
                  $chatData=$this->Chat->findById($msgId);

                  if(!empty($chatData)){
                      $chatId=$chatData['Chat']['id'];
                      $type="";
                      if($chatData['Chat']['sender']==$myid){
                          $type="sender";
                      }elseif($chatData['Chat']['receiver']==$myid){
                          $type="receiver";
                      }

                      if($type=="sender"){
                          $this->Chat->id=$chatId;
                          $ChatSave['Chat']['sender_delete']=0;
                          if($this->Chat->save($ChatSave)){
                              $response['success']=true;
                              $response['status']=1;
                          }

                      }elseif($type=="receiver"){
                          $this->Chat->id=$chatId;
                          $ChatSave['Chat']['receiver_delete']=0;
                          if($this->Chat->save($ChatSave)){
                              $response['success']=true;
                              $response['status']=1;
                          }
                      }
                      else{
                          $response['msg']="Not a valid message";
                      }
                  }else{
                      $response['msg']="Oops Some error has been occurred";
                  }

              }else{
                  $response['msg']="Oops Some error has been occurred";
              }


          }else{
              $this->redirect('/');
          }

        }else{
            $response['msg']="Please login to process";
        }
        echo json_encode($response);
        die;

    }

    public function deleteUserChat(){
        $this->autoRender = false;
        $myid = $this->Session->read("Auth.Member.id");
        $response['success']=false;
        $response['status']=0;
        if($myid){
     
                $userId=$this->request->data['userId'];
                if($userId){
                    $userId = 	$this->toInternalId($userId);
                   
  /*     $query =    "UPDATE `Chats`
SET `Chats`.`sender_delete` = CASE WHEN  `Chats`. `sender`= ".$myid." AND `Chats`.`receiver`= ".$userId." THEN 0
  END
 , `Chats`.`receiver_delete` = CASE WHEN `Chats`. `sender`= ".$userId." AND `Chats`.`receiver`= ".$myid." THEN 0   
          END
;
    ";*/
                    
        
                    
          
            	$query = "UPDATE `chats` SET `chats`.`sender_delete`=0 WHERE `chats`.`sender`= ".$myid." AND `chats`.`receiver`= ".$userId." ";

               
            	$query1 = "UPDATE `chats` SET `chats`.`receiver_delete`=0 WHERE `chats`.`sender`= ".$userId." AND `chats`.`receiver`= ".$myid." ";
            	
            	$query2 = "DELETE FROM `chat_requests` WHERE `chat_requests`.`sender_id`= ".$myid." AND `chat_requests`.`receiver_id`= ".$userId." ";
            
                	$this->Chat->query($query);
                	$this->Chat->query($query1);
                	$this->ChatRequest->query($query2);
                 //   $this->Chat->query($query);
                	$response['success']=true;
                	$response['status']=1;
                	$response['msg']="Message Deleted successfully";
                	
                }else{
                    $response['msg']="Oops Some error has been occurred";
                }


        }else{
            $response['msg']="Please login to process";
        }
        echo json_encode($response);
        die;

    }

    public function test(){
        $myid=12;
        $userId=20;

        $this->User->recursive = -1;

        /*$options2 = array('fields'=>array('Chat.sender','COUNT(Chat.sender) AS count'),'group' => array('Chat.sender'),'conditions' => array('Chat.receiver' => 1,'Chat.status' =>1));
        $unreadCount = $this->Chat->find('all', $options2);*/
        $chatId=87;
        $options = array('conditions' => array(
            "OR"=>array(
                'Chat.receiver' => $myid,
                'Chat.sender' => $myid
            ),
            'Chat.id'=>$chatId
        )
        );
        //$data= $this->Chat->find('count', $options);


        $conditions2=array('Chat.sender'=>array($myid,$userId),'Chat.receiver'=>array($userId,$myid),'status'=>1);
        $unread_msg= $this->Chat->find('find', array('fields'=>array("Chat.*","if( receiver =12, sender_delete, receiver_delete) AS deletemsg"),'conditions'=>$conditions2));


    }
    
    
    
    public function send_iphonenotification($registatoin_ids,$message) {
    
    	$homeUrl= Router::url('/', true);
    
    	$ctx = stream_context_create();
    	stream_context_set_option($ctx, 'ssl', 'passphrase','');
    
    	/* stream_context_set_option($ctx, 'ssl', 'local_cert', 'CertificatesDev.pem');
    	$fp = stream_socket_client('ssl://gateway.sandbox.push.apple.com:2195', $err, $errstr, 60, STREAM_CLIENT_CONNECT, $ctx);
    */    
    	stream_context_set_option($ctx, 'ssl', 'local_cert', 'CertificatesPrdctn.pem');
    	$fp = stream_socket_client('ssl://gateway.push.apple.com:2195', $err, $errstr, 60, STREAM_CLIENT_CONNECT, $ctx);
    	
    
    	stream_set_blocking ($fp, 1);
    	if (!$fp) {
    		echo "Failed to connect (stream_socket_client): $err $errstr";
    	}
    	else {
    	$apple_expiry = time() + (90 * 24 * 60 * 60);
    	foreach($registatoin_ids  as $key=>$value){
    	$apple_identifier = $key;
    	$deviceToken = $value;
    	$payload = json_encode($message);
    	$msg = pack("C", 1) . pack("N", $apple_identifier) . pack("N", $apple_expiry) . pack("n", 32) . pack('H*', str_replace(' ', '', $deviceToken)) . pack("n", strlen($payload)) . $payload;
    	fwrite($fp, $msg);
    	//checkAppleErrorResponse($fp);
    	}
    	}
    	//	echo "hhhh";
    	//	die;
    	usleep(500000);
    	//checkAppleErrorResponse($fp);
    	//mysql_close($con);
    	fclose($fp);
    	return true;
    }
}
