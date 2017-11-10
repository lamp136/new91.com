<?php
namespace back\article\controller;
use think\Controller;
use back\extra\controller\Base;
use think\Request;
use think\Db;
use think\Paginator;
use back\extra\model\Epitaph;

/**
 * 搜索词词库
 * 
 */
class Wanlian extends Base
{
	/**
	 * 碑文挽联
	 * @return void;
	 */
	public function index(){
  		$get = input('get.');
      $pageSize = Config('page_size');
      $search = array();
      if(!empty($get['type'])){
      	$search['category']= $get['type'];
      }
  		$list = Db::name('Epitaph')->where($search)->order('created_time desc')->paginate($pageSize,false,['query'=>$get]);
  		$page = $list->render();
      $type= config('wanlian');
  		$this->assign('list',$list);
  		$this->assign('type',$type);
  		$this->assign('page',$page);
      return $this->fetch();	
    }


    /**
	 * 添加碑文挽联
	 */
	public function add(){
		$postData = input('post.');
        $str = trim($postData['content'],'。');
		$data =explode('。',$str);
		foreach($data as $v){
			$list[]= [
 				'created_time'=>time(),
 				'content'=>$v.'。',
                'category'=>$postData['type'],
 				'admin_id'=>session('admin_id'),
 			];
		}
		$sub = new Epitaph;
 		$final = $sub->saveAll($list);
		if($final){
			$result['flag'] = 1;
			$result['msg'] = '操作成功';
		}
		echo json_encode($result);
	}

	/**
	 * 编辑碑文挽联
	 */
	public function edit(){
         $result = array('flag'=>0,'data'=>array());

        if(Request::instance()->isPost()){
        	$postData = input('post.');
        	$id = $postData['id'];
        	$data['update_time'] = time();
            $data['content'] = $postData['content'];
        	$data['admin_id'] = session('admin_id');
            if(Db::name('Epitaph')->where('id='.$id)->update($data)){
                $result['flag'] = 1;
                $result['msg'] = '操作成功';
            }
        }else{
        	$getInfo = input('get.');
        	$id = $getInfo['id'];
            if(!empty($id)){
                $data = Db::name('Epitaph')->find($id);
                if(!empty($data)){
                    $result['flag'] = 1;
                    $result['data'] = $data;
                }
            }
        }
        echo json_encode($result);
    }



    /**
     * 删除碑文挽联
     */
    public function del(){
    	if(Request::instance()->isPost()){
            $id = input('id');
            $result = array('flag'=>0,'msg'=>'操作失败');
            if(Db::name('Epitaph')->where('id='.$id)->delete()){
                $result['flag'] = 1;
                $result['msg'] = '操作成功';
            }
            echo json_encode($result);
        }
    }


    /**
     * 导入词库文件
     */

    public function uploadFile(){  
      if(Request::instance()->isPost()){  
        $files = request()->file('txt_file'); 
        $path = './public/uploadfile/wanlian/'; // 获取表单提交过来的文件   
        $error = $_FILES['txt_file']['error'];
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
                  $content = $sheet->getCell('A'.$row)->getValue(); 
                  $type = $sheet->getCell('B'.$row)->getValue(); 
                  $data = array();  
                  $data = array(  
                      'content'  =>  $content,  
                      'admin_id'  =>  session('admin_id'),  
                      'created_time'    =>  time(),  
                      'category'    =>  $type,  
                    );  
                   $ret =  Db::name('Epitaph')->insert($data);  
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

  

 
}	