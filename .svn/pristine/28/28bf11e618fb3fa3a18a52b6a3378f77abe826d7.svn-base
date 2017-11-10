<?php 
namespace back\operate\controller;
use think\Controller;
use back\extra\controller\Base;
use think\Request;
use think\Db;
use think\Paginator;
use back\extra\model\Seo;

/**
 * 运营SEO
 */
class Opex extends Base{


    /**
     * 陵园SEO列表
     *
     * @return void
     */
    public function cemetoryseo() {
        $getdata = input('get.');
        // $where['type'] = array('in',array(config('seo_type.cemetery_home'),config('seo_type.cemetery_list')));
        $where['type'] = array('in',array(config('seo_type.cemetery_home'),config('seo_type.cemetery_list'),config('seo_type.funeral_list'),config('seo_type.funeral_liyi')));
        if(!empty($getdata['province_id'])){
            $where['province_id'] =$getdata['province_id'];
        }
        $regionWhere['level']=array('in',array('0','1'));
        $regionWhere['status']= config('normal_status');
        $region = $this-> getRegionData($regionWhere,array('id','name'));
        // $seoList = Seo::with('Category')->where($where)->order('created_time desc')->select();
        $pageSize = config('page_size');

        $seoList = Seo::with('Province')->where($where)->order('created_time desc')->paginate($pageSize,false,['query' => $getdata]);
        $pageshow = $seoList->render();
        $this->assign('page',$pageshow);
        $this->assign('list',$seoList);
        $this->assign('region',$region);
        $this->assign('seoType',config('seo_type'));
        return $this->fetch();
    }
    /*
     * 添加SEO
     * return void
     */
    public function add() {
        if(Request::instance()->isPost()){
            $info = input('post.');
            $act = $info['act'];
            $insertdata['type'] = $info['type'];
            $insertdata['seo_title'] = $info['seo_title'];
            $insertdata['seo_keywords'] = $info['seo_keywords'];
            $insertdata['seo_description'] = $info['seo_description'];
            $insertdata['province_id'] = $info['province_id'];
            $insertdata['created_time'] = date('Y-m-d H:i:s');
            $insertdata['admin_id'] = session('admin_id');
            $result = array('flag'=>0,'msg'=>'操作失败');

            if($act=='cemeteryseo'){
                //判断该地区和类型  数据是否存在
                $data = Db::name('Seo')->where('province_id='.$info['province_id'].' and type='.$info['type'])->find();
                if(!empty($data)){
                    $result['flag'] =0;
                    $result['msg'] ='该数据已经存在';
                }else{
                   $finalresult = Db::name('Seo')->insert($insertdata);
                   if($finalresult){
                        $result['flag'] =1;
                        $result['msg'] ='操作成功';
                    }
                }
            }else if($act=='funeralseo'){
                //判断数据是否存在
                $data = Db::name('Seo')->where('province_id='.$info['province_id'].' and type='.$info['type'])->find();
                if(!empty($data)){
                    $result['flag'] =0;
                    $result['msg'] ='该数据已经存在';
                }else{
                   $finalresult = Db::name('Seo')->insert($insertdata);
                   if($finalresult){
                        $result['flag'] =1;
                        $result['msg'] ='操作成功';
                    }
                }
            }else if($act=='newsSeo'){
                $datatype = input('post.');
                //判断数据是否存在
                $type = $datatype['type'];
                if ($type == 4) {
                    $data = Db::name('Seo')->where('type='.$type.' and status = 1')->field('id')->find();
                } else {
                    $data = Db::name('Seo')->where('type='.$type.' and  status = 1 and category_id='.$info['category_id'])->field('id')->find();
                }
                if(!empty($data)){
                    $result['flag'] =0;
                    $result['msg'] ='该数据已经存在';
                }else{
                    $insertdata['category_id'] = $info['category_id'];
                    $finalresult = Db::name('Seo')->insert($insertdata);
                   if($finalresult){
                        $result['flag'] =1;
                        $result['msg'] ='操作成功';
                    }
                }
            }else if($act == 'financeseo'){
                //判断数据是否存在
                $data = Db::name('Seo')->where('type='.I('type'))->find();
                if(!empty($data)){
                    $result['flag'] =0;
                    $result['msg'] ='该数据已经存在';
                }else{
                   $insertdata['category_id'] = $info['type'];
                   $finalresult = Db::name('Seo')->insert($data);
                   if($finalresult){
                        $result['flag'] =1;
                        $result['msg'] ='操作成功';
                    }
                }
            }
            echo json_encode($result);
        }
    }
    /**
     * 编辑修改
     */
    public function edit(){
        if(Request::instance()->isPost()){
            $data = input('post.');
            $id = $data['id'];
            $data['updated_time'] =  date('Y-m-d H:i:s');
            $final = Db::name('Seo')->where('id='.$id)->update($data);
            if($final){
                $result['flag'] = 1;
                $result['msg'] = '修改成功';
            }else{
                $result['flag'] = 0;
                $result['msg'] = '修改失败！';
            }
        }else{
            $final = input('get.');
            $id = $final['id'];
            $result = array('flag'=>0,'data'=>array());
            if(!empty($id)){
                $data = Db::name('Seo')->where('id='.$id)->find();
                if(!empty($data)){
                    $result['flag'] = 1;
                    $result['data'] = $data;
                }
            }
        }
        echo json_encode($result);
    }


     /**
     * 删除和开启
     */
    public function button(){
        if(Request::instance()->isPost()){
            $result = array('flag'=>0,'msg'=>'操作失败!');
            $postdata = input('post.');
            $data['status'] =  '';
            $id = $postdata['id'];
            if(!empty($id)){
                $data['status']= $postdata['status'];
                $data['updated_time'] = date('Y-m-d H:i:s');
                $final = Db::name('Seo')->where('id='.$id)->update($data);
                if( $final){
                    $result['flag'] = 1;
                    $result['msg'] = '操作成功!';
                }
            }
            echo json_encode($result);
        }
    }



   /*
     * 91乐融SEO
     * return void();
     */
    public function financeseo(){
        $getdata = input('type');
        if(empty($getdata)){
            $tmp = array();
            $fina = config('finance_seo');
            foreach($fina as $key=>$val){
                $tmp[] = $key; 
            }
            $where['type'] = array('in',$tmp);
        }else{
            $where['type'] = $getdata;
        }
        $pageSize = config('page_size');
        $seoList = Seo::with('Province')->where($where)->order('created_time desc')->paginate($pageSize,false,['query' => $getdata]);
        $pageshow = $seoList->render();
        $this->assign('page',$pageshow);
        $this->assign('list',$seoList);
        $this->assign('type',config('finance_seo'));
        return $this->fetch();
        
    }


    /**
     * 新闻SEO
     */
    public function newsseo(){
        $getdata = input('get.');
        $where['type'] = array('in',array(config('seo_type.news_home'),config('seo_type.news_class')));
        if(!empty($getdata['category_id'])){
            $where['category_id'] =$getdata['category_id'];
        }

        $regionWhere['level']=array('in',array('0','1'));
        $regionWhere['status']= config('normal_status');
        $region = $this-> getRegionData($regionWhere,array('id','name'));
        $pageSize = config('page_size');
        $seoList = Seo::with('Category')->where($where)->order('created_time desc')->paginate($pageSize,false,['query' => $getdata]);
        $pageshow = $seoList->render();

        //取出新闻的分类
        $category = Db::name('Category')->where('is_show='.config('normal_status'))->select();
        $newsCategory = $this->categoryTree($category,config('article'));
        $this->assign('newsCategory',$newsCategory);
        $this->assign('page',$pageshow);
        $this->assign('list',$seoList);
        $this->assign('region',$region);
        $this->assign('seoType',config('seo_type'));
        return $this->fetch();
    }

     /**运营推广网站**/
    public function webAccountList(){
        $name = input('name');
        $where = '';
        if(!empty($name)){
            $where['web_name'] = array('like','%'.$name.'%');
        }
        $webAccountManageModel = Db::name('WebAccountManage');
        $pageSize = config('page_size');
        $webcount = $webAccountManageModel->where($where)->order('created_time desc')->paginate($pageSize,false,['query' => $name]);
        $pageshow = $webcount->render();
        $list = $webAccountManageModel->where($where)->order('id desc')->select();
        $listNum = count($list);
        for($i=0;$i<$listNum;$i++ ){
            $list[$i]['password'] = $list[$i]['password'];
        }
        $this->assign('list',$list);
        $this->assign('page',$pageshow);
        return $this->fetch();
    }

    /*
    * 添加运营推广账号
    */ 
    public function webAccountAdd(){
        if(Request::instance()->isPost()){
            $result = array('flag'=>0,'msg'=>'添加失败！');
            $data = input('post.');
            $data['password'] = $data['password'];
            $data['update_time'] = date('y-m-d h:i:s',time());
            $data['created_time'] = date('y-m-d h:i:s',time());
            $data['operation_id'] = session('admin_id');
            if(Db::name('WebAccountManage')->insert($data)){
                $result['flag'] = 1;
                $result['msg'] = '添加成功！';
            }
        }
        echo json_encode($result);
    }
    /*
    * 编辑运营推广账号
    */ 
    public function webAccountEdit(){
        $result = array('flag'=>0,'msg'=>'操作失败！');
        if(Request::instance()->isPost()){
            $data = input('post.');
            if(!empty($data['id'])){
                $data['password'] = $data['password'];
                if(Db::name('WebAccountManage')->where('id='.$data['id'])->update($data)){
                    $result['flag'] = 1;
                    $result['msg'] = '操作成功！';
                }
            }
        }else if(Request::instance()->isGet()){
            $id = input('id');
            if(!empty($id)){
                $data = Db::name('WebAccountManage')->field('id,web_name,url,account,password,mobile,email,remark')->where('id ='.$id)->find();
                $data['password'] = $data['password'];
                $result['data'] = $data;
                $result['flag'] = 1;
            }
        }
        echo json_encode($result);
    }
    /*
     * 修改密码
     */
    public function webAccountPassword(){
        if(Request::instance()->isPost()){
            $result = array('flag'=>0,'msg'=>'操作失败！');
            $final = input('post.');
            $data['id'] = $final['id'];
            $data['password'] = $final['newpassword'];
            if(!empty( $data['id'])){
                if(Db::name('WebAccountManage')->where('id='.$data['id'])->update($data)){
                    $result['flag'] = 1;
                    $result['msg'] = '操作成功!';
                }
            }
            echo json_encode($result);
        }else if(Request::instance()->isGet()){
            $id = input('id');
            $data = Db::name('WebAccountManage')->field('id,password')->where('id ='.$id)->find();
            $data['password'] = $data['password'];
            echo json_encode($data);
        }
    }
    


     /**
     * 获取省市数据
     * @param  array   $where  条件
     * @param  array   $fields 字段
     * @param  int     $num    条数
     * @param  boolean $bool   获取方式
     * @return array
     */
    private function getRegionData($where=array(), $fields=array(), $num=null, $bool=false){
        $regionModel = Db::name('region');
        if(!$bool){
            if($num == 1){
                $region = $regionModel->where($where)->field($fields)->find();
            }
            if(empty($num)){
                $region = $regionModel->where($where)->field($fields)->select();
            }
        }else{
            $tmp = implode(',',$fields);
            $region = $regionModel->where($where)->column($tmp);
        }
        return $region;
    }


     /**
     * 格式化分类
     * 
     * @param array $data
     * @param int $pid
     * @param int $level
     * @return array
     */
    public function categoryTree($data,$pid=0,$level=0){
        static $result = array();
        foreach($data as $v){
            if($v['pid'] == $pid){
                $v['level'] = $level;
                $result[] = $v;
                $this->categoryTree($data,$v['id'], $level+1);//递归调用
            }
        }
        return $result;
    }
    

   
}