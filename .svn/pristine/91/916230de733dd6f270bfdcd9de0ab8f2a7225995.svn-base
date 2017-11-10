<?php
namespace back\index\controller;
use back\extra\controller\Base;
use think\Session;
use think\Request;
use think\Db;
use back\extra\model\Category;

class Categoryinfo extends Base
{
    public function index(){
        $data = Db::name('category')->field('id,pid,name,is_show,sort')->select();
        $category = new Category();
        $list = $category->categoryTree($data);
        $this->assign('list',$list);
        return $this->fetch();
    }

    /**
     * 修改分类
     */
    public function handlecategory(){
        $input = input('post.');
        $category = new Category();
        $result = ['code' => 0,'msg' => '操作失败'];
        $data['admin_id'] = Session::get('admin_id');
        if(!empty($input['name'])){
            $data['name'] = $input['name'];
        }
        if(!empty($input['sort'])){
            $data['sort'] = $input['sort'];
        }
        if(!empty($input['id'])){
            $childs = $category->getChildCat($input['id']);
            if(in_array($input['pid'],$childs)){
                $result = ['code' => 0,'msg' => '父类不能移动到子类下'];
            }else{
                if($input['id'] != $input['pid']){
                    $data['pid'] = $input['pid'];
                }
                $data['id'] = $input['id'];
                $editRet = Db::name('category')->data($data)->update();
                if($editRet){
                    $result = ['code' => 1,'msg' => '修改成功'];
                }
            }
        }else{
            $data['pid'] = $input['pid'];
            $data['is_show'] = config('normal_status');
            $addRet = Db::name('category')->data($data)->insert();
            if($addRet){
                $result = ['code' => 1,'msg' => '添加成功'];
            }
        }

        echo json_encode($result);
    }

    /**
     * 修改分类状态
     */
    public function changeisshow(){
        $input = input('get.');
        $result = ['code' => 0,'msg' => '操作失败'];
        if($input['id']){
            $info = Db::name('category')->where('pid',$input['id'])->count();
            if($info > 0){
                $result = ['code' => 0,'msg' => '存在子级分类'];
            }else{
                $data = [
                    'id' => $input['id'],
                    'is_show' => $input['is_show'],
                ];
                $ret = Db::name('category')->data($data)->update();
                if($ret){
                    $result = ['code' => 1,'msg' => '操作成功'];
                }
            }
        }

        echo json_encode($result);
    }
}