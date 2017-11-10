<?php
namespace back\plugin\controller;
use back\extra\controller\Base;
use think\Session;
use think\Request;
use think\Db;
use back\extra\model\Comment;


class Commentinfo extends Base
{
	/*
     * 墓地评论列表
     * 
     */
    public function index(){
        $getMap = input('get.');
        if(isset($getMap['comment_status']) && $getMap['comment_status'] != 0){
            $where['comment_status'] = $getMap['comment_status'];
        }else{
            $where['comment_status'] = array('egt',config('comment_status.unpass'));
        }
        $pageSize = Config('page_size');
        $time = 'created_time';
        $data = Comment::with(array('findstore'))->where($where)->order(''.$time.' desc')->paginate($pageSize,false,['query' => $getMap]);
       	$page = $data->render();
       	$map['level'] = config('normal_status');
        $map['status'] = config('normal_status');
        $province = Db::name('region')->where($map)->column('id,name');
        $this->assign('province',$province);
        $this->assign('page',$page);
        $this->assign('data',$data);
        $this->assign('getMap',$getMap);
       	return  $this->fetch();
    }


    /**
     * 审核方法
     */
    public function changestatus(){
    	$val = input('post.');
    	$data['comment_status'] = $val['status'];
    	$finaresult = Db::name('comment')->where('id='.$val['id'])->update($data);
    	if($finaresult){
    		$result['flag'] = 1;
    		$result['msg'] = '操作成功！';
    	}
    	echo json_encode($result);
    }


    
	/**
	 * 导入数据
	 */
	public function uploadFile(){
		if(Request::instance()->isPost()){
	        $files = request()->file('flow_file'); 
	        $path = './public/uploadfile/pinglun/'; // 获取表单提交过来的文件   
	        $error = $_FILES['flow_file']['error'];
	        if(!$error){  
	          $dir = $path;  
	          // 验证文件并移动到框架应用根目录/public/uploads/ 目录下  
	          $info = $files->validate(['size'=>3145728,'ext'=>'xls,xlsx,csv'])->rule('uniqid')->move($dir); 
	          /*判断是否符合验证*/  
	          if($info){    //  符合类型  
	            $ext = $info->getExtension();// 获取后缀
	            $filename = $path.$info->getSaveName();// 文件保存名字

	            // vendor('PHPExcel.PHPExcel');
	            vendor('PHPExcel.PHPExcel.IOFactory');
	            vendor('PHPExcel.PHPExcel.Reader.Excel5');// Excel5:xls;Excel2007:xlsx;
	            vendor('PHPExcel.PHPExcel.Reader.Excel2007');// Excel5:xls;Excel2007:xlsx;
	            $reader = \PHPExcel_IOFactory::createReader('Excel5');//设置以Excel5格式
	            if($ext == 'xlsx'){
	                $reader = \PHPExcel_IOFactory::createReader('Excel2007');//设置以Excel2007格式
	            }
	            $PHPExcel = $reader->load($filename); // 载入excel文件  
	            $sheet = $PHPExcel->getSheet(0); // 读取第一個工作表  
	            $highestRow = $sheet->getHighestRow(); // 取得总行数  
	            $highestColumm = $sheet->getHighestColumn(); // 取得总列数  
	               /** 循环读取每个单元格的数据 */  
	                for ($row = 2; $row <= $highestRow; $row++){//行数是以第1行开始，这里示例中excel有3列字段  
	                  $store_id = $sheet->getCell('A'.$row)->getValue(); 
	                  $member_name = $sheet->getCell('B'.$row)->getValue(); 
	                  $mobile = $sheet->getCell('C'.$row)->getValue();  
	                  $content = $sheet->getCell('D'.$row)->getValue();  
	                  $environment = $sheet->getCell('E'.$row)->getValue();  
	                  $service = $sheet->getCell('F'.$row)->getValue();  
	                  $price = $sheet->getCell('G'.$row)->getValue();  
	                  $traffic = $sheet->getCell('H'.$row)->getValue();  
	                  $comment_time = $sheet->getCell('I'.$row)->getValue();
	                  $com_time = gmdate("Y-m-d H:i:s", \PHPExcel_Shared_Date::ExcelToPHP($comment_time));
	                  $data = array();  
	                  $data = array(  
	                      'store_id'  =>  $store_id,  
	                      'member_name'  =>  $member_name,  
	                      'mobile'    =>  $mobile,  
	                      'content'    =>  $content,  
	                      'environment'    =>  $environment,  
	                      'service'    =>  $service,  
	                      'price'    =>  $price,  
	                      'traffic'    =>  $traffic,  
	                      'comment_time'    =>  $com_time,  
	                      'created_time'    =>  time(),  
	                      'comment_status'    =>  config('normal_status'), 
	                      'admin_id'		 =>session('admin_id'), 
	                    );  
	                   $ret =  Db::name('Comment')->insert($data);  
	                }  
	                if($ret){
	                    // unlink($filename);
	                    $result['flag'] = 1;
	                    $result['msg'] = '数据上传成功';
	                }else{
	                	$result['flag'] = 0;
	                    $result['msg'] = '数据上传失败';
	                } 
	              } else{ //  不符合类型业务  
	                $result['flag'] = 2;
	                $result['msg'] = '请选择上传3MB内的excel表格文件...';
	              }  
	            }else{  
	              $result['flag'] = 3;
	              $result['msg'] = '请选择需要上传的文件...';  
	            }  
	            echo json_encode($result);
	        }  
    }  

	/**
	 * 获取商家下的商品
	 */
	public function getgoodsinfo(){
        $result = array('code'=>0,'data'=>'');
		$data = input('post.');
		$store_id = $data['store_id'];
		$goods = Db::name('goods')->where('store_id='.$store_id)->field(
			'id,goods_name')->select();
		if($goods){
			$result['code'] = 1;
			$result['data'] = $goods;
		}
		echo json_encode($result);
	}

	/**
	 * 后台添加评论
	 */
	public function addcomment(){
		$data = input('post.');
		$info = $data['info'];
		$info['store_id'] = $info['store_id'];
		$info['created_time'] = time();
		$info['comment_status'] = config('comment_status.pass');
		$info['goods_id'] = $data['goods_id'];
		unset($info['province_id']);
		unset($info['city_id']);
		$final = Db::name('Comment')->insert($info);
		if($final){
			$result['flag'] = 1;
			$result['msg'] = '添加成功!';
		}
		echo json_encode($result);
	}


	 //查看评论的内容
    public function lookreason(){
        $id = input('id');
        $reason = Db::name('Comment')->where('id='.$id)->field('content')->find();
        if($reason){
            $result['flag'] = 1;
            $result['data'] = $reason['content'];
        }
        echo json_encode($result);
    }

}