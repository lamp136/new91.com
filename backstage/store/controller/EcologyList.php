<?php
namespace back\store\controller;
use think\Controller;
use back\extra\controller\Base;
use think\Session;
use think\Request;
use think\Db;



/**
 * 生态葬葬式类
 */
class EcologyList extends Base
{	

	/**
	 * 生态葬葬式列表
	 */
	public function index(){
		$get = input('get.');
		if(!empty($get['type'])){
			$where['type'] = $get['type'];
		}
		$where['status'] = config('normal_status');
		$pageSize = Config('page_size');
		$list = Db::name('EcologicalTable')->where($where)->order('created_time desc')->paginate($pageSize,false,['query' => $get]);
        $page = $list->render();
        $this ->assign('type',config('ecology_type'));
        $this->assign('page',$page);
        $this->assign('list',$list);
		return  $this->fetch();
	}

	 //查看介绍
    public function lookreason(){
        $id = input('id');
        $reason = Db::name('EcologicalTable')->where('id='.$id)->field('introduce')->find();
        if($reason){
            $result['flag'] = 1;
            $result['data'] = $reason['introduce'];
        }
        echo json_encode($result);
    }

    /**添加生态葬葬式信息**/
    public function infoAdd(){
    	$post = Request::instance()->post();
        $info = $post['info'];
        $info['updated_time'] = time();
        $info['created_time'] = time();
        $info['status'] = Config('normal_status');
        $imagesRet = uploadOne('image',Config('ecology_image'),false,false);
        if($imagesRet['ok'] != 0){
          $info['bill_image'] = $imagesRet['images'][0];
        }
        $final = Db::name('EcologicalTable')->insert($info);
        if($final){
        	$result['flag'] = 1;
            $result['msg'] = '添加成功';
        }
        echo json_encode($result);
    }

    /**
     * 编辑葬式葬法信息
     */
    public function infoEdit(){
    	if(Request::instance()->isPost()){
    		$data = input('post.');
    		$id = $data['id'];
    		$info =  $data['info'];
            if($_FILES['image']['error'] == 0 && !empty($_FILES['image']['tmp_name'])){
                $imagesRet = uploadOne('image',Config('ecology_image'),false,false);
                if($imagesRet['ok'] != 0){
                  $info['bill_image'] = $imagesRet['images'][0];
                }
                
            }
          
            $info['updated_time'] = time();
            $final = Db::name('EcologicalTable')->where('id='.$id)->update($info);
            if($final){
                $result['flag'] = 1;
                $result['msg'] = '编辑成功';
            }
           echo json_encode($result);

    	}else{
    		$id = input('id');
    		$data  = Db::name('EcologicalTable')->where('id='.$id)->field('name,type,introduce,sort,bill_image')->find();
    		if($data){
    			$result['flag'] = 1;
    			$result['data'] = $data;
    		}
    	   echo json_encode($result);
        }
    }

    /**
     * 删除
     */
    public function delete(){
    	$id = input('id');
    	$data  = Db::name('EcologicalTable')->where('id='.$id)->delete();
    	if($data){
    			$result['flag'] = 1;
    			$result['msg'] = '删除成功';
    	}
    	echo json_encode($result);
    }
}