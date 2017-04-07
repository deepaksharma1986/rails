<?php
class Userinboxmodel extends CI_Model{

    public function __construct()
    {
        // Call the CI_Model constructor
        parent::__construct();
    }


    public function getChatUsers($user_id=''){

        #$sql_query="SELECT tc.receiver_id,tc.sender_id,tu.fname,tu.lname FROM tbl_communication tc left join tbl_users tu ON sender_id=tu.id where receiver_id=".$user_id." group by sender_id  ORDER BY tc.add_date DESC";
        #$sql_query="SELECT tc.receiver_id,tc.sender_id,tu.fname,tu.lname, SUM(IF(tc.status='1',1,0)) as unread FROM tbl_users tu  left join tbl_communication tc ON sender_id=tu.id where tu.status='1' AND ( receiver_id=$user_id OR sender_id=$user_id )  group by sender_id ORDER BY tc.add_date DESC ";
        //$sql_query="select tu.fname,tu.lname,tu.user_image,chat.* from tbl_users as tu INNER JOIN (SELECT * FROM( SELECT id,sender_id,receiver_id, IF(tc.receiver_id=$user_id,tc.sender_id,tc.receiver_id ) as userid,add_date FROM tbl_communication as tc where (receiver_id=$user_id OR sender_id=$user_id) ORDER BY id DESC)AS temp_chat
        $sql_query="select chat.*,tu.fname,tu.lname,tu.user_image,tu.company_logo,tu.user_type from tbl_users as tu INNER JOIN (SELECT * FROM( SELECT tc.id,sender_id,receiver_id,te.enq_id,tl.address1, IF(tc.receiver_id=$user_id,tc.sender_id,tc.receiver_id ) as userid,tc.add_date FROM tbl_communication as tc LEFT JOIN tbl_enquiry as te ON tc.enq_id=te.enq_id LEFT JOIN tbl_listing as tl ON te.property_id=tl.id where (receiver_id=$user_id OR sender_id=$user_id) GROUP BY te.enq_id ORDER BY id DESC )AS temp_chat ) as chat ON chat.userid=tu.id AND userid!=$user_id AND tu.status='1' ORDER BY chat.add_date desc ";
        //echo $sql_query; die;
        $res_query=$this->db->query($sql_query);
        $result = array();
        if($res_query->num_rows()){

             foreach($res_query->result_array() as $res){

                 $slq_query1="SELECT SUM(IF(tc.status='1',1,0)) as unread FROM tbl_communication tc where receiver_id=$user_id AND sender_id=".$res['userid'];
                 $res_query1=$this->db->query($slq_query1);
                if($res_query1->num_rows()){
                    $unread_arr=$res_query1->result_array();
                    $unread=$unread_arr[0]['unread'];

                 }
                 $res['unread']=$unread;
                 $result[]=$res;

             }


        }
         return $result;

    }

    public function getUserChat($sender_id='',$receiver_id='',$enq_id=''){

        //$sql_query="SELECT tc.*,tu.fname,tu.lname,ttu.fname,ttu.lname FROM tbl_communication as tc LEFT JOIN tbl_users as tu ON tc.sender_id=tu.id LEFT JOIN tbl_users as ttu ON tc.receiver_id=ttu.id where receiver_id IN(".$receiver_id.','.$sender_id.") AND sender_id IN(".$receiver_id.','.$sender_id.")";
        //$sql_query="SELECT * FROM (SELECT tc.message,tc.sender_id,tc.receiver_id,tc.add_date,tc.original_name,tc.attachment,tu.fname,tu.lname,tu.user_image,IF(receiver_id=$receiver_id,receiver_delete,sender_delete) as msgdelete FROM tbl_communication as tc LEFT JOIN tbl_users as tu ON tc.sender_id=tu.id where receiver_id IN($receiver_id ,$sender_id) AND sender_id IN($receiver_id,$sender_id)) as temp_chat WHERE msgdelete=1 ";
        $sql_query="SELECT * FROM (SELECT tc.message,tc.sender_id,tc.receiver_id,tc.add_date,tc.original_name,tc.attachment,tu.fname,tu.lname,tu.user_image,IF(receiver_id=$receiver_id,receiver_delete,sender_delete) as msgdelete FROM tbl_communication as tc LEFT JOIN tbl_users as tu ON tc.sender_id=tu.id where receiver_id IN($receiver_id ,$sender_id) AND sender_id IN($receiver_id,$sender_id) AND enq_id=$enq_id ) as temp_chat WHERE msgdelete=1 ";
         //echo $sql_query; die;
        $res_query=$this->db->query($sql_query);
        $result = array();
        if($res_query->num_rows()){
            $result=$res_query->result_array();
        }
        $slq_query2="UPDATE tbl_communication SET status='0' where status='1' AND receiver_id=$receiver_id AND sender_id=$sender_id AND enq_id=$enq_id";
        $this->db->query($slq_query2);

        /******** Get total unread message  *************/
            $total_unread=$this->getTotalUnreadMessage($receiver_id);
        /********  till here *************/
        $arr_with_unread=array();
        $arr_with_unread['total_unread']=$total_unread;
        $arr_with_unread['data']=$result;
        return $arr_with_unread;
    }

    /******** Get total unread message  *************/
    public function getTotalUnreadMessage($receiverId){

        $sql_query3="SELECT SUM(IF(tc.status='1',1,0)) as total_unread FROM tbl_communication tc where receiver_id=$receiverId AND tc.status='1'";
        $res_query3=$this->db->query($sql_query3);
        $total_unread=0;

        if($res_query3->num_rows()){
            $unread_arr=$res_query3->result_array();
            $total_unread=$unread_arr[0]['total_unread'];

        }
        return $total_unread;

    }

    /********  till here *************/
    public function getUserLatestMsg($sender_id='',$receiver_id='',$enquiry_id=''){

        //$sql_query="SELECT tc.id,tc.message,tc.sender_id,tc.receiver_id,tc.add_date,tc.original_name,tc.attachment,tu.fname,tu.lname FROM tbl_communication as tc LEFT JOIN tbl_users as tu ON tc.sender_id=tu.id  where  sender_id=$sender_id AND receiver_id=$receiver_id AND tc.status='1'";
        $sql_query="SELECT tc.id,tc.message,tc.sender_id,tc.receiver_id,tc.add_date,tc.original_name,tc.attachment,tu.fname,tu.lname FROM tbl_communication as tc LEFT JOIN tbl_users as tu ON tc.sender_id=tu.id  where  sender_id=$sender_id AND receiver_id=$receiver_id AND enq_id=$enquiry_id AND tc.status='1'";
        //echo $sql_query; die;
        $res_query=$this->db->query($sql_query);
        $result = array();
        $ids_arr = array();
        if($res_query->num_rows()){
            foreach($res_query->result_array() as $data){
                $result[]=$data;
                $ids_arr[]=$data['id'];
            }
            $update_ids=implode(',',$ids_arr);
            $slq_query2="UPDATE tbl_communication SET status='0' where id IN($update_ids)";
            $this->db->query($slq_query2);
        }
        return $result;

    }

    public function getMsgWithUserDetails($message_id=''){

        $sql_query="SELECT tc.id,tc.message,tc.sender_id,tc.receiver_id,tc.add_date,tc.original_name,tc.attachment,tu.fname,tu.lname FROM tbl_communication as tc LEFT JOIN tbl_users as tu ON tc.sender_id=tu.id  where  tc.id=".$message_id ;
        $res_query=$this->db->query($sql_query);
        $result = array();
        if($res_query->num_rows()){
            $result=$res_query->result_array();
        }
        return $result;

    }


    public function getUserLatestMsgForProfile($user_id){

        $sql_query="SELECT tc.id,tc.message,tc.sender_id,tc.receiver_id,tc.add_date,te.enq_id,tl.address1,tu.user_image,tu.company_logo,tu.fname,tu.lname,tu.user_type,IF(tc.status='1',1,0) as unread FROM tbl_communication as tc LEFT JOIN tbl_enquiry as te ON tc.enq_id=te.enq_id LEFT JOIN tbl_listing as tl ON te.property_id=tl.id LEFT JOIN tbl_users as tu ON tc.sender_id=tu.id where receiver_id=$user_id ORDER BY add_date DESC LIMIT 0,2 ";
        //echo $sql_query; die;
        $res_query=$this->db->query($sql_query);
        $result = array();
        if($res_query->num_rows()){
            $result= $res_query->result_array();

        }
        return $result;

    }


}