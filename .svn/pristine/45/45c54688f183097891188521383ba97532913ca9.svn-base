<?php
namespace back\privilege\controller;
use think\Controller;
use think\Request;
use think\Db;
use think\Paginator;
use back\extra\model\Privilege;
use back\extra\controller\Base;

/**
 * 这是菜单管理方法
 * 主要包括:添加菜单，编辑，删除，列表
 * @author  wangdong
 */
class Privilegecont extends Base 
{


    /**
     * 菜单列表
     */
    public function index(){
        //获取level为零的菜单
        $map['pid'] = 0;
        $map['status'] = config('normal_status');
        $privilegeMenu = db('privilege')->field('title,id')->where($map)->order('sort desc')->select();
        //获取点击的左侧菜单显示菜单
        $first = array();
        $two = array();
        $three = array();
        $id = input('id');
        //获取当前菜单
        if(empty($id)){
           $currentPrivilege = Db::name('privilege')->field('id,title')->where('status='.config('normal_status').' and pid=0')->order('sort desc')->find();
           $id = $currentPrivilege['id'];
        }else{
            $currentPrivilege = Db::name('privilege')->field('id,title')->where('status='.config('normal_status').' and id='.$id)->order('sort desc')->find();
        }
        //获取该菜单下的功能块
        $first = Db::name('privilege')->field('id,title')->where('pid='.$id)->order('sort desc')->select();
        if(!empty($first)){
            foreach($first as $v){
                $ids[] = $v['id'];
            }
            //该功能块下的菜单
            $where['pid'] = array('in', $ids);
            $twoPrivilege = Db::name('privilege')->field('id,title,pid')->where($where)->order('sort desc, id desc')->select();
            //获取该菜单下的操作方法
            if(!empty($twoPrivilege)){
                $childIds = array();
                foreach ($twoPrivilege as $val){
                    $childIds[] = $val['id'];
                    $two[$val['pid']][] = $val;
                }
                $whereThree['pid'] = array('in', $childIds);
                $subPrivilege = Db::name('privilege')->field('id,title,pid')->where($whereThree)->order('sort desc, id desc')->select();
                if(!empty($subPrivilege)){
                    foreach ($subPrivilege as $value){
                        $three[$value['pid']][] = $value;
                    }
                }
            }
        }

        $this->assign('privilegeMenu',$privilegeMenu);
        $this->assign('currentPrivilege',$currentPrivilege);
        $this->assign('first',$first);
        $this->assign('two',$two);
        $this->assign('three',$three);
        return $this->fetch('index');
    }


     /**
     * 获取菜单权限信息,取前三级的权限
     */
    public function getprivilege(){
        $privilegedatas = Db::name('privilege')->field('id,title,level,pid')
                ->where('level in (0,1,2) and status='.config('normal_status'))
                ->select();
        $result['flag'] = 0;
        $result['data'] = array();
        $user = new Privilege;
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
    public function addprivilege() {
            $data = input('post.');
            $info = $data['Privilege'];
            if($info){
            $info['level'] = 0;
            $info['create_time'] = time();
            $info['admin_id'] = session('admin_id');
            $privilegeModel = Db::name('privilege');
            $pid = $info['pid'];
            if($pid!=0){
                $preprivilege = $privilegeModel->field('level')->where('id='.$pid)->find();
                if(!empty($preprivilege)){
                    $info['level'] = $preprivilege['level']+1;
                }
            }
            $status= $data['pri_status']['status'];
            if($status){
                $info['status'] = $status;
            }
            $resultdata = $privilegeModel->insert($info);
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
     * 修改菜单
     */
    public function editprivilege(){
        $id = input('id');
        $data = input('post.');
        if($id){
            $result['flag'] = 0;
            $currentprivilege = Db::name('privilege')->where('id='.$id)->find();
            $privilegedatas = Db::name('privilege')->field('id,title,level,pid')
                ->where('level in (0,1,2) and status='.config('normal_status'))
                ->select();
            $privilegeModel = new Privilege;
            $privilegeTree = $privilegeModel->getPrivilegeTree($privilegedatas, 0);
            if(!empty($currentprivilege)){
                $result['flag'] = 1;
                $result['privilegeTree'] = $privilegeTree;
                $result['currentprivilege'] = $currentprivilege;
            }
            echo json_encode($result);
        }elseif($data){
            $editData = $data['editPrivilege'];
            $id = $editData['id'];
            //获取该权限原来的pid
            $oldprivilege = Db::name('privilege')->field('pid')->where('id='.$id)->find();
            $privilegeModel = new Privilege;
            // $privilegeModel = Db::name('privilege');
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
                    if($data['status']['status']){
                        $editData['status'] = $data['status']['status'];
                    }
                    $editData['update_time'] = time();
                    //此处批量更新保存  
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


     /*
     * 删除菜单
     * data:id;
     * return json;
     */
    public function delprivilege(){
        $result = array('flag'=>0,'msg'=>'删除失败！');
        $id = input('id');
        if(!empty($id)){
            $privilegeModel = db('privilege');
            if($privilegeModel->where('pid='.$id)->field('id')->find()){
                $result['flag'] = 0;
                $result['msg'] = '有子菜单存在，不能删除！';
            }else if($privilegeModel->where('id='.$id)->delete()){
                $result['flag'] = 1;
                $result['msg'] = '删除成功！';
            }
        }
        echo json_encode($result);
    }



    /**
     * 权限分配页
     * @param  input|void;
     * @return  output|void;
     */
    public function privilegeset()
    {
        $id = input('id');
        if(!empty($id)){
            if(Request::instance()->isPost()){
                $post = Request::instance()->post();
                $pri = $post['rules'];
                $priData = implode(',', $pri);
                if(empty($priData)){
                    $info['privilege'] = '';
                }else{
                    $info['privilege'] = $priData;
                }
                $info['id'] = $post['id'];
                $res = Db::name('role')->update($info);
                if(!(is_bool($res))){
                    $this->success('编辑成功!', 'position/index');
                }else{
                    $this->error('编辑失败!');
                }
            }else{
                //获取所有的权限
                $priWhere['status'] = Config("normal_status");
                $privilegeData = Db::name('privilege')->where($priWhere)->order('sort DESC')->select();
                foreach($privilegeData as $val){
                    if($val['pid'] == Config("default_status")){
                        $firstPrivilege[] = $val;
                    }
                }
                $privilegeTree =$this->getTree($privilegeData,0);
                //dump($privilegeTree);die;
                //获得该职位的权限
                $roleWhere['id'] = $id;
                $roleData = Db::name('role')->where($roleWhere)->field('privilege,title')->find();
                $role = explode(',', $roleData['privilege']);
                $this->assign('first_privilege',$firstPrivilege);
                $this->assign('privilegeTree',$privilegeTree);
                $this->assign('role',$role);
                $this->assign('rolename',$roleData['title']);
                $this->assign('id',$id);
                return $this->fetch();
            }
        }else{
            $this->error('操作失败!');
        }
    }
    //处理权限关系
    private function getTree($data,$pid=0){
        $result = array();
        foreach ($data as $v){
            if ($v['pid'] == $pid){
                $v['child'] = $this->getTree($data,$v['id']);
                $result[] = $v;
            }
        }
        return $result;
    }    
  


}