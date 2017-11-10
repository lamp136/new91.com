<?php
namespace back\plugin\controller;
use think\Controller;
use back\extra\controller\Base;
use think\Request;
use think\Db;
use think\Paginator;
use common\phpmailer\phpmailer;

	
class Email extends Base
{
 	/*
     * 乐融留言邮件列表
     * return void;
     */
    public function lrMessageEmailList(){
    	$data = input('get.');
        $arr = array(1,4);
        $condition = array('in',$arr);
        $where['type'] = $condition;
        $pageSize = Config('page_size');
        $list = Db::name('EmailLog')->where($where)->order('id DESC')->paginate($pageSize,false,['query'=>$data]);
        //分页
        $page = $list->render();
        $this->assign('list',$list);
        $this->assign('page',$page);
       	return $this->fetch();
    }
     /*
     * 陵园合作申请邮件列表
     * return void;
     */
    public function cemHezuoEmailList(){
    	$data = input('get.');
        $arr = array(2,5);
        $condition = array('in',$arr);
        $where['type'] = $condition;
        $pageSize = Config('page_size');
        $list = Db::name('EmailLog')->where($where)->order('id DESC')->paginate($pageSize,false,['query'=>$data]);
        //分页
        $page = $list->render();
        $this->assign('list',$list);
        $this->assign('page',$page);
       	return $this->fetch();
    }
    /*
     * 预约看墓邮件列表
     * return void;
     */
    public function appiontEmailList(){
    	$data = input('get.');

        $arr = array(3,6);
        $condition = array('in',$arr);
        $where['type'] = $condition;
        $pageSize = Config('page_size');
        $list = Db::name('EmailLog')->where($where)->order('id DESC')->paginate($pageSize,false,['query'=>$data]);
        //分页
        $page = $list->render();
        $this->assign('list',$list);
        $this->assign('page',$page);
       	return $this->fetch();
    }


      /*
     * 预约服务商邮件列表
     * return void;
     */
    public function appiontService(){
        $data = input('get.');
        $condition = array_search('预约服务商',config('business_email_msg'));
        $where['type'] = $condition;
        $pageSize = Config('page_size');
        $list = Db::name('EmailLog')->where($where)->order('id DESC')->paginate($pageSize,false,['query'=>$data]);
        //分页
        $page = $list->render();
        $this->assign('list',$list);
        $this->assign('page',$page);
        return $this->fetch();
    }
    
    
    
    
    
    /*
     * 发送邮件
     * return string(json);
     */
    public function sendMail(){
        $result = array('flag'=>0,'msg'=>'发送失败！');
        $id = input('id');
        if(!empty($id)){
            $emailLogModel = Db::name('EmailLog');
            $data = $emailLogModel->field('email_address,title,content')->find($id);
            if(!empty($data)){
            	$istrue = $this->sendMailtocustomer($data['email_address'],$data['title'],$data['content']);
                if($istrue){
                    $updata['status'] = 1;
                    $updata['send_time'] = date('Y-m-d H:i:s');
                    if($emailLogModel->where('id ='.$id)->update($updata)){
                        $result = array('flag'=>1,'msg'=>'成功发送！');
                    }
                }
            }
        }
        echo json_encode($result);
    }



	/*
	 * 邮件发送函数
	 * @input $to|邮箱地址（群发为数组）;
	 * @input $subject|邮件标题;
	 * @input $content|邮件内容;
	 * @return boolean;
	 */

	public function sendMailtocustomer($to, $subject, $content) {
	    $mail = new phpmailer();
	    // 装配邮件服务器
	    if (config('mail_smtp')) {
	        $mail->IsSMTP();
	    }
	    $mail->Host = config('mail_host');
	    $mail->SMTPAuth = config('mail_smtpauth');
	    $mail->Username = config('mail_username');
	    $mail->Password = config('mail_password');
	    $mail->CharSet = config('mail_charset');
	    // 装配邮件头信息
	    $mail->From = config('mail_username');
	        //判断是否为群以邮件
	        if(is_array($to)){
	            $to_num = count($to);
	            for($i=0;$i<$to_num;$i++){
	                $mail->AddAddress($to[$i]);
	            }
	        }else{
	            $mail->AddAddress($to);
	        }
	    $mail->FromName = config('mail_fromname');
	    $mail->IsHTML(config('mail_ishtml'));
	    // 装配邮件正文信息
	    $mail->Subject = $subject;
	    $mail->Body = $content;
	    // 发送邮件
	    if (!$mail->Send()) {
	        return false;
	    } else {
	        return true;
	    }
	}


	/*
     * 查看收件人员
     */
    public function viewList(){
        $result = array('flag'=>0,'msg'=>'操作失败！');
        $type = input('type');
        if(!empty($type)){
            $data = Db::name('EmailSheet')->field('id,name,email_address,phone,remark')->where('type ='.$type)->select();
            if(empty($data)){
                $result = array('flag'=>2,'msg'=>'暂无人员信息');
            }else{
                $result = array('flag'=>1,'data'=>$data);
            }
        }
        echo json_encode($result);
    }

     /*
     * 删除Email
     * return string(json);
     */
    public function delEmail(){
        $result = array('flag'=>0,'msg'=>'操作失败！');
        $id = input('id');
        if(!empty($id)){
            $res = Db::name('EmailSheet')->where('id='.$id)->delete();
            if($res){
                $result = array('flag'=>1,'msg'=>'操作成功！');
            }
        }
        echo json_encode($result);
    }
    /*
     * 添加收件人员
     * return string(void);
     */
    public function addperson(){

        $result = array('flag'=>0,'msg'=>'添加失败！');
        $data = input('post.');
        $emailSheetModel = Db::name('EmailSheet');
        $countName = count($data['name']);
        $countEmailAddress = count($data['email_address']);
        if($countName == $countEmailAddress){
            $dataArray = array();
            $condition = array();
            for($i = 0;$i<$countName;$i++){
                $dataArray[] = array('type'=>$data['type'],'name'=>$data['name'][$i],'email_address'=>$data['email_address'][$i],'phone'=>$data['phone'][$i],'creat_time'=>date('Y-m-d H:i:s')); 
                $condition[] = $data['email_address'][$i];
            }
            $where['email_address'] = array('in',$condition);
            $where['type'] = $data['type'];
            $find = $emailSheetModel->where($where)->count();
            if($find>0){
                $result = array('flag'=>0,'msg'=>'请检查用户是否已存在！');
            }else{
                if($emailSheetModel->insertAll($dataArray)){
                    $result = array('flag'=>1,'msg'=>'成功添加！');
                }
            }
        } 
        
        echo json_encode($result);
    }

    /**Email人员列表**/
    public function businessList(){
        $input = input('get.');
        $searchtype  = '';
        if(!empty($input)){
            $searchtype = $input['type'];
        }
        if(!empty($searchtype)){
            $pageSize = Config('page_size');
            $list = Db::name('EmailSheet')->where('type='.$searchtype)->order('type')->select();
        }else{
             $type = array_keys(config('business_email_msg'));
             $where['type'] = array('in',$type);
             $list = Db::name('EmailSheet')->where($where)->order('type')->select();
        }
        //分页
        $this->assign('list',$list);
        $call_arr = config('business_email_msg');
        $this->assign('call_arr',$call_arr);
        $this->assign('workmen',$this->getBusinessMen(false,true));
        return $this->fetch();

    }

    /**更改状态**/
    public function changestatus(){
        $postdata= input('post.');
        $sign = $postdata['sign'];
        if($sign == 'react'){
            // $map['type'] = $postdata['type'];
            // $map['is_sendmsg'] = config('normal_status');
            // $info = Db::name('EmailSheet')->where($map)->count();
            // if($info){
            //     $result['flag'] = 0;
            //     $result['msg'] = '请先取消该类型下其他工作人员接收短信资格!';
            // }else{
                $where['type'] = $postdata['type'];
                $where['email_address'] = $postdata['email'];
                $type['is_sendmsg'] = config('normal_status');
                $type['update_time'] = date('Y-m-d');
                $final = Db::name('EmailSheet')->where($where)->update($type);
                if($final){
                    $result['flag'] = 1;
                    $result['msg'] = '更改状态成功';
                }
            // }
        }elseif($sign=='refuse'){
            $where['type'] = $postdata['type'];
            $where['email_address'] = $postdata['email'];
            $type['update_time'] = date('Y-m-d');
            $type['is_sendmsg'] = config('delete_status');
            $final = Db::name('EmailSheet')->where($where)->update($type);
            if($final){
                    $result['flag'] = 1;
                    $result['msg'] = '更改状态成功';
            }

        }
        
        echo json_encode($result);
    }

    


     /**添加商务人员邮箱信息**/
    public function addbusinessinfo(){
        if(Request::instance()->isPost()){
            $info = input('post.');
            $userid = $info['admin_id'];
            $username = Db::name('admin')->where('id='.$userid)->field('name')->find();

            //将信息传入email_sheet里
            $data['name'] = $username['name'];
            $data['email_address'] = $info['email'];
            $data['type'] = $info['get_coupon_type'];
            $data['status'] = config('default_status');
            $data['admin_id'] = $info['admin_id'];
            $data['creat_time'] =date('Y-m-d H:i:s');
            $data['phone'] = $info['phone'];
            $result = Db::name('EmailSheet')->insert($data);
            if($result){
                $final['flag'] = 1;
                $final['msg'] = '添加成功';        
            } 
        }
        echo json_encode($final);
    }


}