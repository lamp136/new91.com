<?php
namespace back\index\controller;
use think\Controller;
use think\Session;//session类
use back\extra\controller\Base;
use think\Db;

class Index extends Base
{   
    //插件
    public function index()
    {	
        return $this->fetch('index');
    }

    //后台首页
    public function shouye(){
        $login_name = session('login_name');
        if($login_name){
            $data = array('msg'=>'尊敬的'.$login_name.'欢迎你登陆后台', 'menu'=>'菜单');
            $this->assign('data', $data);
            return $this->fetch('shouye');
        }else{
            $this->redirect('/login/login/login');
        }
    }
    /**
     * 个人中心
     * @param  input|void;
     * @return  output|void;
     */
    public function personCenter()
    {
        $id = session('admin_id');
        if(!empty($id)){
            $adminWhere['id'] = $id;
            $adminWhere['status'] = Config('normal_status');
            $data= Db::name('Admin')->where($adminWhere)->find();
            $this->assign('data',$data);
            return $this->fetch();
        }else{
            $this->error('操作失败!');
        }
    }
     /**
     * 修改密码
     * @param  input|void;
     * @return  output|json(string);
     */
    public function editPassword()
    {
        $result = array('flag'=>0,'msg'=>'修改失败！');
        $post = input('post.');
        if(!empty($post)){
            //dump($post);die;
            $where['id'] = session('admin_id');
            $where['status'] = Config('normal_status');
            $where['pwd'] = encryptAdmin($post['oldPW']);
            if(Db::name('Admin')->where($where)->count()){
                $updateWhere['id'] = session('admin_id');
                $data['updated_time'] = date('Y-m-d H:i:s');
                $data['pwd'] = encryptAdmin($post['newPW']);
                if(Db::name('Admin')->where($updateWhere)->update($data)){
                    $result= array('flag'=>'1','msg'=>'修改成功！');
                }
            }else{
                $result= array('flag'=>'2','msg'=>'原密码错误！');
            }
        }
        echo json_encode($result);
    }
    
    /**
     * 我的工作
     * @param  input|void;
     * @return  output|void;
     */
    public function myWork()
    {
        $input = input('post.');
        $where['status'] =  config('default_status');
        $where['admin_id'] =  session('admin_id');
        $pageSize = Config('page_size');
        $data = Db::name('worktable')->where($where)->order('create_time desc')->paginate($pageSize,false,['query' => $input]);
        $page = $data->render();
        $type = config('mywork');
        $arr = array('0'=>'未处理','1'=>'已处理');
        $this->assign('arr',$arr);
        $this->assign('type',$type);
        $this->assign('page',$page);
        $this->assign('data',$data);
        return $this->fetch();
    }

    public function checkNotify() {
        // 实例化自定义的模型类
        $Notify = Db::name("worktable");
        // 获取当前用户的id
        $admin_id = session('admin_id');
        //获取商务部所有人员ID
        $arr = $this->getBusinessMen(true,true);
        $ids = array_keys($arr);
        // $ids = array('27','34');
        //如果用户ID在商务部内 则判断有无任务，不在则返回2 停止循环
        if(in_array($admin_id,$ids)){
            // 由于Ajax5分钟才执行一次，所以新数据的插入时间要晚于查询的的请求时间（当前时间）5分钟
            $time = time() - 1000*60*5;
            // 准备查询条件
            $where['admin_id'] = $admin_id;
            $where['status'] = config('default_status');
            $where['create_time'] = array('gt',$time);
             // 查找数据库中是否有新数据插入
            $bool = $Notify->where($where)->select();
            // 如果查询结果非空，则输出结果集第零条数据的type参数，即提醒类型，然后再从数据库对应表中获取提醒内容
            //本测试默认5分钟中内只有一条消息，如果想更加精确，也可以缩短请求时间
            if ($bool != null) {
                $result['flag'] = 1;
                $result['data'] = $bool;
            }else{
                $result['flag'] = 0;
                $result['data'] = '';
            }
        }else{
            $result = array('flag'=>2,'data'=>'');
        }
        echo json_encode($result);
    }

    //处理

    public function dealing(){
        $post = input('post.');
        $type = $post['type'];
        $id = $post['id'];
        $data['status'] =config('normal_status');
        $data['update_time'] =time();
        $final = Db::name('worktable')->where('id='.$id)->update($data);
        if($final){
            $result['flag'] = 1;
            $result['type'] = $type;
        }
        echo json_encode($result);

    }

}
