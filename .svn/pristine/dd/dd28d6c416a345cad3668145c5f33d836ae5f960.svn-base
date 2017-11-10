<?php
namespace back\privilege\controller;
use think\Controller;
use think\Request;
use think\Db;
use back\extra\controller\Base;
use think\Paginator;
use back\extra\model\Role;
use back\extra\model\RoleUser;

/**
 * 这是权限划分方法
 * 主要包括:添加职位，编辑，删除，添加人员，查看人员，权限分配
 * @author  wangdong 
 */
class Position extends Base{
	public function index(){
		$list = Db::name('role')->select();
        $treeData = $this->getAllRoleData($list, 0);
        $this->assign('list',$treeData);
        return  $this->fetch('index');
    }

   
     /**
     * 获取菜单权限信息,取前三级的权限
     */
    public function getprivilege(){
        $privilegedatas = db('role')->field('id,title,level,pid')
                ->where('level in (0,1,2,3) and status='.config('normal_status'))
                ->select();
        $result['flag'] = 0;
        $result['data'] = array();
        $user = new Role;
        $privilegeTree = $user->getPrivilegeTree($privilegedatas, 0);
        if(!empty($privilegeTree)){
            $result['flag'] =1;
            $result['data'] =$privilegeTree;
        }
        echo json_encode($result);
    }


        
     /**
     * 添加菜单
     */
    public function addposition() {
            $data = input('post.');
            $info = $data['info'];
            if($info){
            $info['level'] = 0;
            $info['status'] =config('normal_status');
            $RoleModel = db('role');
            $pid = $info['pid'];
            if($pid!=0){
                $RoleData = $RoleModel->field('level')->where('id='.$pid)->find();
                if(!empty($RoleData)){
                    $info['level'] = $RoleData['level']+1;
                }
            }
            $resultdata = $RoleModel->insert($info);
            if($resultdata){
                $result['flag'] = 1;
                $result['msg'] = '添加成功';
            }else{
                $result['flag'] = 0;
                $result['msg'] = '添加失败';
            }
            echo json_encode($result);
        }
    }


     /**
     * 删除职位
     */
    public function delposition(){
    	$post = input('post.');
        if($post){
            $id = $post['id'];
            if($post['status'] == config('delete_status')){
            	$info['status'] = config('normal_status');
            }else{
            	$info['status'] = config('delete_status');
            }
            $roleModel = db('role');
            $result = $roleModel->where('pid='.$id)->field('id')->find();
            if($roleModel->where('pid='.$id)->field('id')->find()){
                $result['flag'] = 0;
                $result['msg'] = '有子职位存在，不能删除！';
            }else if($roleModel->where('id',$id)->update($info)){
                 $result['flag']=1;
                 $result['msg'] = '更改状态成功';
            }else{
                 $result['flag']=0;
                 $result['msg'] = '更改状态失败';
            }
            echo json_encode($result);
       }
    }



   

     /**
     * 修改菜单
     */
    public function editposition(){
        $id = input('id');
        $data = input('post.');
        if($id){
            $result['flag'] = 0;
            $currentprivilege = db('role')->where('id='.$id)->find();
            $privilegedatas = db('role')->field('id,title,level,pid')
                ->where('level in (0,1,2,3) and status='.config('normal_status'))
                ->select();
            $privilegeModel = new Role;
            $privilegeTree = $privilegeModel->getPrivilegeTree($privilegedatas, 0);
            if(!empty($currentprivilege)){
                $result['flag'] = 1;
                $result['privilegeTree'] = $privilegeTree;
                $result['currentprivilege'] = $currentprivilege;
            }
            echo json_encode($result);
        }elseif($data){
            $editData = $data['editinfo'];
            $id = $editData['id'];
            //获取该权限原来的pid
            $oldprivilege = db('role')->field('pid')->where('id='.$id)->find();
            $privilegeModel = new Role;
            // $privilegeModel = db('privilege');
            if(!empty($oldprivilege)){
                //获取该类别下的菜单下的所有子类
                $childPrivilegeIds = $privilegeModel->getChildPrivilege($id);
                if(in_array($editData['pid'], $childPrivilegeIds)){
                    $result['flag'] = 0;
                    $result['msg'] = '修改后其上级菜单不能为自己的子菜单';
                }else{
                    $preprivilege = $privilegeModel->field('level')->where('id='.$editData['pid'])->find();
                    if(!empty($preprivilege)){
                        $editData['level'] = $preprivilege['level']+1;
                    }else{
                        $editData['level'] = 0;
                    }
                    
                    //此处批量更新保存  明天继续
                    $pri_update = $privilegeModel->where('id',$id)->update($editData);
                    if($pri_update){
                        $result['flag'] = 1;
                        $result['msg'] = '修改成功';
                    }else{
                        $result['flag'] = 0;
                        $result['msg'] = '修改失败';
                    }
                }
            }else{
                $result['flag'] = 0;
                $result['msg'] = '操作失败';
            }
            echo json_encode($result);
        }
    }


 	/**
     * 将role表输出成树状结构
     *
     * @param array $roleDatas
     * @param int $pid
     * @return array
     */
    static function getAllRoleData($roleDatas,$pid=0) {
        static $datas;
        if ($roleDatas != NULL || !empty($roleDatas)) {
            foreach ($roleDatas as $key =>$roleData) {
                if($roleData['pid']==$pid){
                    $datas[] = $roleData;
                    self::getAllRoleData($roleDatas,$roleData['id']);
                }
            }
        }
      return $datas;
    }

    /**
     * 获取role中所有的正常的数据
     * @param int $type
     * @return array
     */
    public function getData($type=''){
        if($type===0 || $type===1){
            $map['type'] = $type;
        }
        $data = Db::name('role')->where($map)->order('sort DESC')->select();
        return  $data;
    }


    /**
     * 添加职位上的人员
     */
    public function adduser() {
        $roleuserModel = db('role_user');
        if(Request::instance()->isPost()){
            $postdata = input('post.');
            $positionId =$postdata['position_id'];
            $uid = $postdata['uid'];
            $result['flag'] = 0;
            $result['msg'] = '新增失败';
            if(empty($uid)){
                $result['flag'] = 0;
                $result['msg'] = '没有选择新增的人员';
            }else{
                $role = Db::name('role')->where('id='.$positionId)->field('pid')->find();
                if(!empty($role)){
                    foreach($uid as $v){
                        $roleuserData['role_id'] = $role['pid'];
                        $roleuserData['role_job_id'] = $positionId;
                        $roleuserData['user_id'] = $v;
                        $roleuserModel->insert($roleuserData);
                        session('BusinessMen','');
                    }
                    $result['flag'] = 1;
                    $result['msg'] = '新增成功';
                }
            }
        }else{
            $id = input('id');
            //获取所有的用户信息
            $adminModel = Db::name('admin');
            $admindata = $adminModel->field('id,name,login_name')->where("status=".config('normal_status'). " and login_name!='".config('admin_name')."'")->select(); 
            //获取该职位下的人员
            $roleuser = $roleuserModel->field('user_id')->where('role_job_id='.$id)->select();
            $roleuserinfo = array();
            if(!empty($roleuser)){
                foreach($roleuser as $v){
                    $roleuserinfo[] = $v['user_id'];
                }
            }
           
            if(!empty($admindata)){
                $result['flag'] = 1;
                $result['admindata'] = $admindata;
                $result['roleuser'] = $roleuserinfo;
            }else{
                $result['flag'] = 0;
            }
        }
        echo json_encode($result);
    }

     /**
     * 查看职位对应的人员
     */
    public function positionuser(){
        if(Request::instance()->isPost()){
            $positionId = input('id');
            $roleuser = RoleUser::with('relation')->where('role_job_id='.$positionId)->select();
            if(empty($roleuser)){
                $result['flag'] = 0;
                $result['data'] = '没有记录';
            }else{
                $result['flag'] = 1;
                $result['data'] = $roleuser;
            }
            echo json_encode($result);
        }
    }


    /**
     * 通过职位id和用户id 删除职位下对应的人员
     */
    public function delpositionuser(){
        if(Request::instance()->isPost()){
            $postdata  = input('post.');
            $positionId = $postdata['positionId'];
            $userId = $postdata['userId'];
            $data = Db::name('role_user')->where('role_job_id='.$positionId.' and user_id='.$userId)->delete();
            if($data){
                session('BusinessMen','');
                $result['flag'] = 1;
                $result['msg'] = '删除成功';
            }else{
                $result['flag'] = 0;
                $result['msg'] = '删除失败';
            }
            echo json_encode($result);
        }
    }
    
    
    


}