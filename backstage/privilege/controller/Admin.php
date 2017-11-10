<?php
namespace back\privilege\controller;
use think\Controller;
use back\extra\controller\Base;
use think\Request;
use think\Db;
use think\Paginator;
/**
 * 管理员控制器
 * 主要包含：管理员添加，密码修改，禁用，编辑功能
 * @author wangdong
 * @version 1.0
 */

class Admin extends Base
{	

	public function adminList(){
		 //筛选条件
        $get = input('get.');
		// $status = $get['member_status'];
		if(empty($get['member_status'])){
            $where['status'] = config('normal_status');
        }else if($get['member_status'] == 'all'){
            $where['status'] = array('in',array('1','-1'));
        }else{
            $where['status'] = $get['member_status']; 
        }
        $pageSize = Config('page_size');
		$list = Db::name('admin')->where($where)->order('created_time desc')->paginate($pageSize,false,['query' => $get]);
        $page = $list->render();
		$count = Db::name('admin')->where($where)->count();
        $countPage = ceil($count/$pageSize);
        // $list = Db::name('admin')->where($where)->select();
        $this->assign('page',$page);
        $this->assign('countPage',$countPage);
        $this->assign('list',$list);
		return $this->fetch('adminList');
	}

	/**添加管理人员**/
	public function addPerson(){

		$data = input('post.');
		if($data){
			$action['name'] = trim($data['name']);
			$action['login_name'] = trim($data['login_name']);
			$action['pwd'] = trim(encryptAdmin($data['pwd']));
			$action['mobile'] = trim($data['mobile']);
			$action['email'] = trim($data['email']);	
			$action['status'] = config('normal_status');
			$action['created_time'] = date('Y-m-d,H:i:s',time());
			if($data['remark']){
				$action['remark'] = $data['remark'];	
			}

			$where['login_name'] = trim($data['login_name']);
			$isset  = Db::name('admin')->where($where)->count();
			if($isset){
				$result['flag'] = 2;
				$result['msg'] = '该用户已存在';
			}else{
				$resultdata = Db::name('admin')->insert($action);
				if($resultdata){
					$result['flag'] = 1;
					$result['msg'] = '添加成功';
				}
			}
			echo json_encode($result);
		}
	}


	/**
     * 修改密码
     */
    public function editPassword()
    {
        $result = array();
        $adminId = input('post.id');
        $postpwd = input('post.pwd');
        $pwd = trim($postpwd);
        if(!empty($adminId)){
            $data['pwd'] = encryptAdmin($pwd);
            $data['updated_time'] = date('Y-m-d H:i:s');
            $res = Db::name('admin')->where('id',$adminId)->update($data);
            if($res){
                $result['flag'] = 1;
                $result['msg'] = '修改成功！';
            }else{
                $result['flag'] = 0;
                $result['msg'] = '修改失败！';
            }
        }else {
            $result['flag'] = 0;
            $result['msg'] = '操作失败！';
        }
        echo json_encode($result);
    }

	/**管理员列表人员启用禁用按钮**/
	public function changeStatus(){
		$status = input('post.status');
		$id = input('post.id');
		if($status==config('normal_status')){
			//如果为1  则是正常状态-》删除
			$action['status'] = config('delete_status');
		}else{
			//如果不为1  则是删除状态-》正常
			$action['status'] = config('normal_status');
		}
            $action['updated_time'] = date('Y-m-d H:i:s');

		$info = Db::name('admin')->where('id',$id)->update(['status'=>$action['status']],['updated_time'=>$action['updated_time']]);
		if($info){
			$result['flag'] = 1;
			$result['msg'] = '更新成功';
		}
		echo json_encode($result);
	}


	/**编辑管理人员信息**/
	public function editorAdmin(){
		$id = input('id');
		$post = input('post.');
		if(empty($post)){
			$info = Db::name('admin')->where('id',$id)->find();
	        $result['flag'] = 1;
	        $result['data'] = $info;
		}else{
            $map['id'] = $id;
			$info = Db::name('admin')->where($map)->find();
            $id = $info['id'];
            $where['login_name'] = $post['login_name'];
            $where['id'] = array('neq',$id);
            $isset = Db::name('admin')->where($where)->count();
			if($isset){
				$result['flag'] = 2;
	        	$result['msg'] = '该信息已经存在';
			}else{
				$id = input('post.id');
				$action['name'] =  trim($post['name']);
				$action['login_name'] = trim($post['login_name']);
				$action['mobile'] = trim($post['mobile']);
				$action['email'] = trim($post['email']);	
				$action['status'] = config('normal_status');
				$action['updated_time'] = date('Y-m-d,H:i:s',time());
				$admin = db('admin');
				$resultdata = $admin->where('id',$id)->update($action);
				if(!is_bool($resultdata)){
					$result['flag'] = 3;
	        		$result['msg'] = '更新成功';
				}
			}
		}
			echo json_encode($result);
	}


}	