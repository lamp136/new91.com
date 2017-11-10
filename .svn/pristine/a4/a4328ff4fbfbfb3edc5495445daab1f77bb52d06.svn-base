<?php 
namespace back\operate\controller;
use think\Controller;
use back\extra\controller\Base;
use think\Request;
use think\Db;
use think\Paginator;
/**
 * 资金流入记录
 */
class Capitalflow extends Base
{

	/**
	 * 列表页
	 */
	public function index(){
		$getdata = input('get.');
        $pageSize = Config('page_size');
		$list =Db::name('Inject')->order('created_time' ,'desc')->paginate($pageSize,false,['query' => $getdata]);
        $page = $list->render();

         //获取审批人
        $this->assign('approver',config('approver_name'));
        //资金用途分类
        $this->assign('moneyUser',$this->getCategoryData(array('pid'=>config('money_user_category_id')),array('id,name'),'',true));
        $this->assign('list',$list);
        $this->assign('page',$page);
		return $this->fetch();
	}

	/**
     * 通过用途分类获取上期余额
     */
    public function oldMoney(){
        $category_id = input('category_id');
        //查询最后一次注入的记录
        $injectObj = Db::name('inject')->where('category_id='.$category_id.' and status=1')->order('id desc')->field('category_id,id,total_amount')->find();
        if($injectObj){
            //计算总支出
            $map['inject_id'] = $injectObj['id'];
            $map['status'] = config('normal_status');
            $map['category_id'] = $injectObj['category_id'];
            $countCost = Db::name('disburse')->where($map)->sum('consume');

            //上期结余
            $result['oldBance'] = round($injectObj['total_amount']-$countCost,2);
            $result['lastId'] = $injectObj['id'];
            $result['flag'] = 1;
        }else{
            $result['oldBance'] = '0';
            $result['flag'] = '0';
        }
        echo json_encode($result);
    }

    /**
     * 添加资金注入记录
     */
    public function addInject(){
        if(Request::instance()->isPost()){
            $addModel = Db::name('inject');
            $postInfo = input('post.');
            Db::startTrans();
            $postInfo['investment_time'] = strtotime($postInfo['investment_time']);
            $postInfo['apply_time'] = strtotime($postInfo['apply_time']);
            $postInfo['created_time'] = time();
            $postInfo['proposer_id'] = session('admin_id');
            $adminWhere['id'] = array('in',array($postInfo['proposer_id'],$postInfo['approver_id']));
            $adminName =Db::name('admin')->where($adminWhere)->column('id,name');
            $postInfo['proposer'] = $adminName[$postInfo['proposer_id']];
            $postInfo['approver'] = $adminName[$postInfo['approver_id']];
            //修改上一条数据的status
            if(!empty($postInfo['lastId'])){
                $data['status'] = config('delete_status');
                $lid = $postInfo['lastId'];
                unset($postInfo['lastId']);
                $injectObj = $addModel->insert($postInfo);
                $beforeInject = $addModel->where('category_id='.$postInfo['category_id'].' and status=1 and id='.$lid)->update($data);
                if($injectObj && $beforeInject){
                    Db::commit();
                    $result['flag'] = 1;
                    $result['msg'] = '操作成功';
                }else{
                    Db::rollback();
                    $result['flag'] = 0;
                    $result['msg'] = '操作失败';
                }
            }else{
                unset($postInfo['lastId']);
                $injectObj = $addModel->insert($postInfo);
                if($injectObj){
                    Db::commit();
                    $result['flag'] = 1;
                    $result['msg'] = '操作成功';
                }else{
                    Db::rollback();
                    $result['flag'] = 0;
                    $result['msg'] = '操作失败';
                }
            }
            echo json_encode($result);
        }
    }


    /**
     * 编辑注入记录
     */
    public function editInject(){
        $injectModel = Db::name('inject');
        if(Request::instance()->isPost()){
            $postData = input('post.');
            $nickName = Db::name('admin')->where('id = '.$postData['approver_id'])->field('name')->find();
            $postData['approver'] = $nickName['name']; 
            $postData['investment_time'] = strtotime($postData['investment_time']);
            $postData['apply_time'] = strtotime($postData['apply_time']);
            $postData['update_time'] = time();
            $result = array('flag'=>0,'msg'=>'操作失败');
            if($injectModel->where('id='.$postData['id'])->update($postData)){
                $result['flag'] = 1;
                $result['msg'] = '操作成功';
            }
            echo json_encode($result);
        }else{
            $getInfo = input('get.');
            $id = $getInfo['id'];
            $result = array('flag'=>0,'data'=>array());
            if(!empty($id)){
                $data = $injectModel->find($id);
                $data['investment_time'] = date('Y-m-d',$data['investment_time']);
                $data['apply_time'] =  date('Y-m-d',$data['apply_time']);
                if(!empty($data)){
                    $result['flag'] = 1;
                    $result['data'] = $data;
                }
            }
            echo json_encode($result);
        }
    }


     /**
     * 获取分类数据
     * @param  array   $where  条件
     * @param  array   $fields 字段
     * @param  int     $num    条数
     * @param  boolean $bool   获取方式
     * @return array
     */
    public function getCategoryData($where=array(), $fields=array(), $num=null, $bool=false){
        $categoryModel = DB::name('category');
        if(!$bool){
            if($num == 1){
                $category = $categoryModel->where($where)->field($fields)->find();
            }else if($num != 1 && empty($num)){
                $category = $categoryModel->where($where)->field($fields)->select();
            }
        }else{
            $tmp = implode(',',$fields);
            $category = $categoryModel->where($where)->column($tmp);
        }
        return $category;
    }



}