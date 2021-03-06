<?php
namespace back\analysis\controller;
use think\Controller;
use back\extra\controller\Base;
use think\Request;
use think\Db;
use think\Session;//session类
use back\extra\model\OrderGrave;
use back\extra\model\SearchKeywords;

/**
 * 统计控制器
 */
class Analysis extends Base
{
    /**
     * 封装订单分析时间
     * @param  input|field;//字段名
     * @param  input|$monthlength;//开始时间为空时当前几个月[默认(0)当月]
     * @return  output|array;
     */
    private function ordertime($field,$monthlength = 0){
        $where = array();
        $start_time = input('start_time');
        $end_time = input('end_time');
        if(!empty($start_time) && empty($end_time)){
            $where[$field] = array('egt',strtotime($start_time));
        }
        if(!empty($end_time) && empty($start_time)){
            $where[$field] = array('elt',strtotime($end_time.' 23:59:59'));
        }
        if(!empty($start_time) && !empty($end_time)){
            $where[$field] = array('BETWEEN',array(strtotime($start_time),strtotime($end_time.' 23:59:59')));
        }
        //默认月处理
        if(empty($start_time)){
            $year = date("Y");
            $month = date('m') - $monthlength;
            $where[$field] = array('egt',strtotime($year."-".$month."-1"));
            $start_time = $year."-".$month."-1";
        }
        //默认当天
        if(empty($end_time)){
            $end_time = date('Y-m-d',time());
        }
        $this->assign('start_time',$start_time);
        $this->assign('end_time',$end_time);
        return $where;
    }
    /**
     * 订单分析
     * @param  void;
     * @return  void;
     */
    public function orderanalysis(){
        //接收处理统计(订单)类型
        $inputdata= input('get.options/a');
        if(empty($inputdata)){
            $options = array();
        }else{
            $options = $inputdata;
        }
        //接收搜索地区
        $search_province_id = input('get.regionData/a');
        $lastData = $this->_orderanalysis($options,$search_province_id);
        //查找地区
        $regionData = $this->getRegionData(array('status'=>config('normal_status'),'level'=>1),array('id,name'),'',true);
        //dump($regionData);die;
        $this->assign('search_province_id',$search_province_id);
        $this->assign('options',$options);
        $this->assign('lastData',$lastData);
        $this->assign('regionData',$regionData);
        return $this->fetch();
    }
   
    /**
     * 订单分析核心方法
     * @param  $options|array;//统计的订单类型
     * @param  $search_province_id|array;//选择统计的省份ID
     * @return  output|array;
     */
    public function _orderanalysis($options,$search_province_id){
        //封装时间
        $where = array();
        $where = $this->ordertime('created_time');
        //处理封装搜索地区
        if(!empty($search_province_id)){
            $where['province_id'] = array('in',$search_province_id); 
        }
        //查询数据
        $where['state'] = ['egt',config('normal_status')];
        $field = 'province_id,status,store_status,send_finance_status';
        $OrderData = Db::name('OrderGrave')->field($field)->order('province_id ASC')->where($where)->select();
        $lastData = array(); 
        foreach($OrderData as $lastDatas){  
            if(array_key_exists($lastDatas['province_id'], $lastData)){
                //订单总数
                $lastData[$lastDatas['province_id']]['all_total'] +=1;
                //订单状态
                if(in_array('check_success',$options)&&($lastDatas['status'] == config('order_status.check_success'))&&($lastDatas['send_finance_status'] == config('normal_status'))){ //待收佣金
                    $lastData[$lastDatas['province_id']]['check_success'] +=1; 
                }else if(in_array('get_money',$options)&&($lastDatas['status'] == config('order_status.get_money'))){ //待返现
                    $lastData[$lastDatas['province_id']]['get_money'] +=1; 
                }else if(in_array('deposit',$options)&&($lastDatas['status'] == config('order_status.deposit'))){ //已交订金
                    $lastData[$lastDatas['province_id']]['deposit'] +=1; 
                }
                //商家(非)会员
                if(in_array('store_status',$options)&&in_array($lastDatas['store_status'],array('19','20','16','14'))){//会员订单
                    $lastData[$lastDatas['province_id']]['store_status'] +=1;
                }else if(in_array('no_store_status',$options)&&($lastDatas['store_status'] == 0)){ //非会员订单
                    $lastData[$lastDatas['province_id']]['no_store_status'] +=1; 
                }
            }else{
                //初始化数据
                $lastData[$lastDatas['province_id']]['check_success'] = 0;
                $lastData[$lastDatas['province_id']]['get_money'] = 0;
                $lastData[$lastDatas['province_id']]['deposit'] = 0;
                $lastData[$lastDatas['province_id']]['success'] = 0; 
                $lastData[$lastDatas['province_id']]['store_status'] = 0;
                $lastData[$lastDatas['province_id']]['no_store_status'] =0;
                //订单总数
                $lastData[$lastDatas['province_id']]['all_total'] =1; 
                //订单状态
                if(in_array('check_success',$options)&&($lastDatas['status'] == config('order_status.check_success'))&&($lastDatas['send_finance_status'] == config('normal_status'))){//待收佣金
                    $lastData[$lastDatas['province_id']]['check_success'] =1; 
                }else if(in_array('get_money',$options)&&($lastDatas['status'] == config('order_status.get_money'))){ //待返现
                    $lastData[$lastDatas['province_id']]['get_money'] =1; 
                }else if(in_array('deposit',$options)&&($lastDatas['status'] == config('order_status.deposit'))){ //订金
                    $lastData[$lastDatas['province_id']]['deposit'] =1; 
                }
                //商家(非)会员
                if(in_array('store_status',$options)&&in_array($lastDatas['store_status'],array('19','20','16','14'))){ //会员订单
                    $lastData[$lastDatas['province_id']]['store_status'] =1; 
                }else if(in_array('no_store_status',$options)&&($lastDatas['store_status'] == 0)){ //非会员订单
                    $lastData[$lastDatas['province_id']]['no_store_status'] =1;
                }
            }
        }
        //dump($lastData);die;
        //查找完成订单数据
        if(in_array('success',$options)){ //完成订单
            $whereSuccess = $this->ordertime('success_time');
            if(!empty($search_province_id)){
                $whereSuccess['province_id'] = array('in',$search_province_id);
            }
            $whereSuccess['state'] = ['egt',config('normal_status')];//config('normal_status');
            $whereSuccess['status'] = config('order_status.success');
            $field = 'province_id';
            $OrderDataSuccess = Db::name('OrderGrave')->field($field)->order('province_id ASC')->where($whereSuccess)->select();
            foreach($OrderDataSuccess as $lastDataSuccess){  
                $lastData[$lastDataSuccess['province_id']]['success'] +=1; 
            }
        }
        return $lastData;
    }
    /**
     * (订单分析)下载
     * @param  void;
     * @return  void;
     */
    public function orderanalysisdown(){
        //接收处理统计(订单)类型
        $inputdata= input('get.options/a');
        if(empty($inputdata)){
            $options = array();
        }else{
            $options = $inputdata;
        }
        //接收搜索地区
        $search_province_id = input('get.regionData/a');
        $lastData = $this->_orderanalysis($options,$search_province_id);
        //查找地区
        $regionData = $this->getRegionData(array('status'=>config('normal_status'),'level'=>1),array('id,name'),'',true);
        // 引入PHPExcel
        vendor('PHPExcel.PHPExcel');
        $phpExcel = new \PHPExcel();

        $phpExcel->getProperties()->setCreator('hgy')
            ->setLastModifiedBy('hgy')
            ->setTitle('Office 2007 XLSX Test Document')
            ->setSubject('Office 2007 XLSX Test Document')
            ->setDescription('Test document for Office 2007 XLSX, generated using PHP classes.')
            ->setKeywords('office 2007 openxml php')
            ->setCategory('Test result file');
       
        // 设置行宽度
        $phpExcel->getActiveSheet()->getColumnDimension('A')->setWidth(20);
        $phpExcel->getActiveSheet()->getColumnDimension('B')->setWidth(14);
        $phpExcel->getActiveSheet()->getColumnDimension('C')->setWidth(14);
        $phpExcel->getActiveSheet()->getColumnDimension('D')->setWidth(14);
        $phpExcel->getActiveSheet()->getColumnDimension('E')->setWidth(14);
        $phpExcel->getActiveSheet()->getColumnDimension('F')->setWidth(14);
        $phpExcel->getActiveSheet()->getColumnDimension('G')->setWidth(14);
        $phpExcel->getActiveSheet()->getColumnDimension('H')->setWidth(14);
        $phpExcel->getActiveSheet()->getColumnDimension('I')->setWidth(14);
             
        $phpExcel->getActiveSheet()->getDefaultStyle()->getFont()->setSize(10);//所有字体大小
        $phpExcel->getActiveSheet()->getStyle('A1:H1')->getFont()->setBold(true);//A1到I1加粗
        $phpExcel->getActiveSheet()->getStyle('A1:H1')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER)->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $phpExcel->getActiveSheet()->getStyle('A1:H1')->getBorders()->getAllBorders()->setBorderStyle(\PHPExcel_Style_Border::BORDER_THIN);
        
        // 设置水平居中
        $phpExcel->getActiveSheet()->getStyle('A')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $phpExcel->getActiveSheet()->getStyle('B')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $phpExcel->getActiveSheet()->getStyle('C')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $phpExcel->getActiveSheet()->getStyle('D')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $phpExcel->getActiveSheet()->getStyle('E')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $phpExcel->getActiveSheet()->getStyle('F')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $phpExcel->getActiveSheet()->getStyle('G')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $phpExcel->getActiveSheet()->getStyle('H')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $phpExcel->getActiveSheet()->getStyle('I')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        // 设置表格标题
        $phpExcel->setActiveSheetIndex(0)->setCellValue('A1','城市')->setCellValue('B1','订单总数')->setCellValue('C1','完成订单')->setCellValue('D1','待收佣金')->setCellValue('E1','已交订金')->setCellValue('F1','待返现')->setCellValue('G1','会员')->setCellValue('H1','非会员');
        // 设置行高度
        $phpExcel->getActiveSheet()->getRowDimension(1)->setRowHeight(22);
        
        //初始化数据
        $i = 1;
        $all_total = 0;
        $check_success = 0;
        $get_money = 0;
        $deposit = 0;
        $success = 0;
        $store_status = 0;
        $no_store_status = 0;
        foreach($lastData as $k=>$val){
            ++$i;
            $phpExcel->getActiveSheet(0)->setCellValue('A'.$i,$regionData[$k]);
            $phpExcel->getActiveSheet(0)->setCellValue('B'.$i,$val['all_total']);
            $phpExcel->getActiveSheet(0)->setCellValue('C'.$i,$val['success']);
            $phpExcel->getActiveSheet(0)->setCellValue('D'.$i,$val['check_success']);
            $phpExcel->getActiveSheet(0)->setCellValue('E'.$i,$val['deposit']);
            $phpExcel->getActiveSheet(0)->setCellValue('F'.$i,$val['get_money']);
            $phpExcel->getActiveSheet(0)->setCellValue('G'.$i,$val['store_status']);
            $phpExcel->getActiveSheet(0)->setCellValue('H'.$i,$val['no_store_status']);
            $phpExcel->getActiveSheet()->getStyle('A'.$i.':H'.$i)->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
            $phpExcel->getActiveSheet()->getStyle('A'.$i.':H'.$i)->getBorders()->getAllBorders()->setBorderStyle(\PHPExcel_Style_Border::BORDER_THIN);
            $phpExcel->getActiveSheet()->getRowDimension($i)->setRowHeight(16);
            
            $all_total += $val['all_total'];
            $check_success += $val['check_success'];
            $get_money += $val['get_money'];
            $deposit += $val['deposit'];
            $success += $val['success'];
            $store_status += $val['store_status'];
            $no_store_status += $val['no_store_status'];
        }
        ++$i;
        $phpExcel->getActiveSheet(0)->setCellValue('A'.$i,'总和');
        $phpExcel->getActiveSheet(0)->setCellValue('B'.$i,$all_total);
        if(in_array('success',$options)){
            $phpExcel->getActiveSheet(0)->setCellValue('C'.$i,$success);
        }
        if(in_array('check_success',$options)){
            $phpExcel->getActiveSheet(0)->setCellValue('D'.$i,$check_success);
        }
        if(in_array('deposit',$options)){
            $phpExcel->getActiveSheet(0)->setCellValue('E'.$i,$deposit);
        }
        if(in_array('get_money',$options)){
            $phpExcel->getActiveSheet(0)->setCellValue('F'.$i,$get_money);
        }
        if(in_array('store_status',$options)){
            $phpExcel->getActiveSheet(0)->setCellValue('G'.$i,$store_status);
        }
        if(in_array('no_store_status',$options)){
            $phpExcel->getActiveSheet(0)->setCellValue('H'.$i,$no_store_status);
        }
        $phpExcel->getActiveSheet()->getStyle('A'.$i.':H'.$i)->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
        $phpExcel->getActiveSheet()->getStyle('A'.$i.':H'.$i)->getBorders()->getAllBorders()->setBorderStyle(\PHPExcel_Style_Border::BORDER_THIN);
        $phpExcel->getActiveSheet()->getRowDimension($i)->setRowHeight(22);
        
        //删除列
        if(!in_array('no_store_status',$options)){
            $phpExcel->getActiveSheet()->removeColumn('H');
        }
        if(!in_array('store_status',$options)){
            $phpExcel->getActiveSheet()->removeColumn('G');
        }
        if(!in_array('get_money',$options)){
            $phpExcel->getActiveSheet()->removeColumn('F');
        }
        if(!in_array('deposit',$options)){
            $phpExcel->getActiveSheet()->removeColumn('E');
        }
        if(!in_array('check_success',$options)){
            $phpExcel->getActiveSheet()->removeColumn('D');
        }
        if(!in_array('success',$options)){
            $phpExcel->getActiveSheet()->removeColumn('C');
        }
        // excel头参数
        header('Content-Type: application/vnd.ms-excel'); // 生成xls/xlsx文件
        // header('Content-Type: text/csv'); // 生成csv文件
        header('Content-Disposition: attachment;filename="订单数据('.input('start_time').'--'.input('end_time').').xls"'); // 后缀名:xls/xlsx/csv
        header('Cache-Control: max-age=0');
        $phpWriter = \PHPExcel_IOFactory::createWriter($phpExcel, 'Excel5'); // Excel5:xls; Excel2007:xlsx; CSV:csv;
        $phpWriter->save('php://output');
    }
     /**
     * 订单详情
     * @param   input   void;
     * @return  output  void;
     */
    public function orderdetails(){
        //接收数据(一个用于分页，一个用于查询条件)
        $input = $inputtmp = input('get.');
        //处理时间
        $where = $this->ordertime($inputtmp['field']);
        //删除不需要的条件
        unset($inputtmp['field']);
        unset($inputtmp['start_time']);
        unset($inputtmp['end_time']);
        unset($inputtmp['page']);
        foreach($inputtmp as $k=>$v){
            if($k == 'store_status' && $v!='0'){
                $where[$k] = ['neq',0];
            }else{
                $where[$k] = $v;
            }
        }
        $where['state'] = ['egt',config('normal_status')];//config('normal_status');
        // $input['status'] = array('in',$input['status']);
        $field = 'id,order_grave_sn,store_name,reservation_person,reservation_phone,buyer,mobile,order_flow_id,success_time,appoint_time,brokerage_percent,paid_in_amount,tomb_price,status';
        $pageSize = Config('page_size');
        $orderData = OrderGrave::with(array('flowman'))->field($field)->order('id DESC')->where($where)->paginate($pageSize,false,['query'=>$input]);
        //分页
        $page = $orderData->render();
        $changestatus = config('order_status_change');
        $this->assign('orderData',$orderData);
        $this->assign('changestatus',$changestatus);
        $this->assign('page',$page);
        $this->assign('input',$input);
        return $this->fetch();
    }
    /**
    * 订单详情下载
    * @param   input   void;
    * @return  output  void;
    */
    public function orderdetailsdown(){
        $info = input('post.');
        $where = $this->ordertime($info['field']);
        //删除不需要的条件
        unset($info['field']);
        unset($info['start_time']);
        unset($info['end_time']);
        unset($info['page']);
        foreach($info as $k=>$v){
            if($k == 'store_status' && $v!='0'){
                $where[$k] = ['neq',0];
            }else{
                $where[$k] = $v;
            }
        }
        $where['state'] = ['egt',config('normal_status')];//config('normal_status');
        $field = 'order_grave_sn,store_id,store_name,reservation_person,reservation_phone,buyer,mobile,order_flow_id,success_time,appoint_time,brokerage_percent,paid_in_amount';
        $orderData = OrderGrave::with(array('flowman','storecontact'))->field($field)->order('id DESC')->where($where)->select();
        //dump($orderData);die;
        // 引入PHPExcel
        vendor('PHPExcel.PHPExcel');
        $phpExcel = new \PHPExcel();
        
        $phpExcel->getProperties()->setCreator('hgy')
            ->setLastModifiedBy('hgy')
            ->setTitle('Office 2007 XLSX Test Document')
            ->setSubject('Office 2007 XLSX Test Document')
            ->setDescription('Test document for Office 2007 XLSX, generated using PHP classes.')
            ->setKeywords('office 2007 openxml php')
            ->setCategory('Test result file');
        // 设置行宽度
        $phpExcel->getActiveSheet()->getColumnDimension('A')->setWidth(14);//购买人
        $phpExcel->getActiveSheet()->getColumnDimension('B')->setWidth(14);//购买手机
        $phpExcel->getActiveSheet()->getColumnDimension('C')->setWidth(30);//陵园名称
        $phpExcel->getActiveSheet()->getColumnDimension('D')->setWidth(55);//陵园联系人信息
        $phpExcel->getActiveSheet()->getColumnDimension('E')->setWidth(14);//佣金比
        $phpExcel->getActiveSheet()->getColumnDimension('F')->setWidth(14);//佣金金额
        $phpExcel->getActiveSheet()->getColumnDimension('G')->setWidth(20);//订单跟踪人
        $phpExcel->getActiveSheet()->getColumnDimension('H')->setWidth(20);//预约时间
        // 设置行高度
        $phpExcel->getActiveSheet()->getRowDimension('1')->setRowHeight(22);
        
        $phpExcel->getActiveSheet()->getDefaultStyle()->getFont()->setSize(10); //所有字体大小
        $phpExcel->getActiveSheet()->getStyle('A1:H1')->getFont()->setBold(true); //A1到H1加粗
        $phpExcel->getActiveSheet()->getStyle('A1:H1')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER)->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $phpExcel->getActiveSheet()->getStyle('A1:H1')->getBorders()->getAllBorders()->setBorderStyle(\PHPExcel_Style_Border::BORDER_THIN);

        // 设置水平居中
        //$phpExcel->getActiveSheet()->getStyle('A')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        //$phpExcel->getActiveSheet()->getStyle('B')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        //$phpExcel->getActiveSheet()->getStyle('C')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        //$phpExcel->getActiveSheet()->getStyle('D')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $phpExcel->getActiveSheet()->getStyle('E')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $phpExcel->getActiveSheet()->getStyle('F')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        //$phpExcel->getActiveSheet()->getStyle('G')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $phpExcel->getActiveSheet()->getStyle('H')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        
        // 设置表格标题
        $phpExcel->setActiveSheetIndex(0)
            ->setCellValue('A1','购买人')
            ->setCellValue('B1','手机号')
            ->setCellValue('C1','陵园名称')
            ->setCellValue('D1','陵园联系人')
            ->setCellValue('E1','佣金比(%)')
            ->setCellValue('F1','佣金金额')
            ->setCellValue('G1','跟踪人')
            ->setCellValue('H1','预约时间');
        $count = count($orderData);
        for($i=0;$i<$count;$i++){
            //判断预约人和购买人
            if(empty($orderData[$i]['buyer'])){
                $phpExcel->getActiveSheet(0)->setCellValue('A'.($i+2),$orderData[$i]['reservation_person']);
                if(empty($orderData[$i]['reservation_phone'])){
                    $orderData[$i]['reservation_phone'] = '';
                }
                $phpExcel->getActiveSheet(0)->setCellValue('B'.($i+2),$orderData[$i]['reservation_phone']); 
            }else{
                $phpExcel->getActiveSheet(0)->setCellValue('A'.($i+2),$orderData[$i]['buyer']);
                if(empty($orderData[$i]['mobile'])){
                    $orderData[$i]['mobile'] = '';
                }
                $phpExcel->getActiveSheet(0)->setCellValue('B'.($i+2),$orderData[$i]['mobile']); 
            }
            $phpExcel->getActiveSheet(0)->setCellValue('C'.($i+2),$orderData[$i]['store_name']);
            //商家联系人处理
            $contact = '';
            $contact = '联系人:'.$orderData[$i]['storecontact']['contact_name'];
            if(!empty($orderData[$i]['storecontact']['mobile'])){
                $contact .= '手机:'.$orderData[$i]['storecontact']['mobile'];
            }
            if(!empty($orderData[$i]['storecontact']['tel'])){
                $contact .= '座机:'.$orderData[$i]['storecontact']['tel'];
            }
            $phpExcel->getActiveSheet(0)->setCellValue('D'.($i+2),$contact);
            if($orderData[$i]['brokerage_percent']){
                $orderData[$i]['brokerage_percent'] = '';
            }
            $phpExcel->getActiveSheet(0)->setCellValue('E'.($i+2),$orderData[$i]['brokerage_percent']);
            if($orderData[$i]['paid_in_amount']){
                $orderData[$i]['paid_in_amount'] = '';
            }
            $phpExcel->getActiveSheet(0)->setCellValue('F'.($i+2),$orderData[$i]['paid_in_amount']);
            $phpExcel->getActiveSheet(0)->setCellValue('G'.($i+2),$orderData[$i]['flowman']['name']);
            $phpExcel->getActiveSheet(0)->setCellValue('H'.($i+2),date('Y-m-d H:i;s',$orderData[$i]['appoint_time']));
            $phpExcel->getActiveSheet()->getStyle('A'.($i+2).':H'.($i+2))->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
            $phpExcel->getActiveSheet()->getStyle('A'.($i+2).':H'.($i+2))->getBorders()->getAllBorders()->setBorderStyle(\PHPExcel_Style_Border::BORDER_THIN);
            $phpExcel->getActiveSheet()->getRowDimension($i+2)->setRowHeight(16);
        }
        // sheet命名
        $phpExcel->getActiveSheet()->setTitle('订单数据表');
        $phpExcel->setActiveSheetIndex(0);
        // excel头参数
        header('Content-Type: application/vnd.ms-excel'); // 生成xls/xlsx文件
        // header('Content-Type: text/csv'); // 生成csv文件
        header('Content-Disposition: attachment;filename="订单数据表('.date('Y-m-d H_i_s').').xls"'); // 后缀名:xls/xlsx/csv
        header('Cache-Control: max-age=0');
        $phpWriter = \PHPExcel_IOFactory::createWriter($phpExcel, 'Excel5'); // Excel5:xls; Excel2007:xlsx; CSV:csv;
        $phpWriter->save('php://output');
    }
    /**
     * (省下边)地区订单柱状图
     * @param   input   void;
     * @return  output  void;
     */
    public function cityorder(){
        $regionId = input('region');
        if(!empty($regionId)){
            $regionWhere['pid']  = $regionId;
            $regionWhere['status']  = config("normal_status");
            $cityData = $this->getRegionData($regionWhere,array('id','name'),'',true);
            $region = $this->getRegionData(array('id'=>$regionId),array('id','name'),1);
            //查询所有数据
            $where = $this->ordertime('created_time',3);
            $where['province_id'] = $regionId;
            $where['state'] = ['egt',config('normal_status')];//config('normal_status');
            $orderDatas = Db::name('OrderGrave')->field('city_id,count(city_id)')->where($where)->group('city_id')->select();
            //冒泡排序
            $dataCount = count($orderDatas);
            for($i=1;$i<$dataCount;$i++){
                for($j = 0;$j<$dataCount-$i;$j++){
                    if($orderDatas[$j]['count(city_id)']<$orderDatas[$j+1]['count(city_id)']){
                        $tmp = $orderDatas[$j];
                        $orderDatas[$j] = $orderDatas[$j+1];
                        $orderDatas[$j+1] = $tmp;
                    }
                }
            }
            //组装数据
            $datas = array();
            $regionname = array();
            foreach($orderDatas as $val){
               $regionname[] = $cityData[$val['city_id']];
               $datas[] = $val['count(city_id)']; 
            }
            $this->assign('orderdata',$orderDatas);
            $this->assign('region',$region);
            
            $this->assign('name',json_encode($region['name'].'订单分布'));
            $this->assign('datas',json_encode($datas));
            $this->assign('regionname',json_encode($regionname));
            return $this->fetch();
        }else{
            $this->error('跳转失败!');
        }
    }


    /**
     * (省下边)地区订单趋势图
     * @param   input   void;
     * @return  output  void;
     */
    public function ordertrend(){
        $regionid = input('region');
        $region = array('name'=>'全国','id'=>'0');
        if(!empty($regionid)){
            $where['province_id'] = $regionid;
            $region = $this->getRegionData(array('id'=>$regionid),array('id','name'),1);
        }
        //获取年份
        $allYear = array();
        $year = date("Y");
        for($i = 2016;$i<=$year;$i++){
            $allYear[] = $i;
        }
        //默认当年
        $nowYear = input('nowYear');
        if(empty($nowYear)){
            $nowYear = date("Y");
        }
        //月份处理
        $nowMonth = input('nowMonth/a');
        $allMonth = array(1=>'一月',2=>'二月',3=>'三月',4=>'四月',5=>'五月',6=>'六月', 7=>'七月',8=>'八月',9=>'九月',10=>'十月',11=>'十一月',12=>'十二月');
        $MonToCuo = array('1'=>2678399,'2'=>2419199,'3'=>2678399,'4'=>2591999,'5'=>2678399,'6'=>2678399,'7'=>2678399,'8'=>2678399,'9'=>2678399,'10'=>2678399,'11'=>2678399,'12'=>2678399);
        if(!empty($nowMonth)){
            if(count($nowMonth) == 1){
                $week = 6; 
                for($i=1;$i<$week;$i++){
                    $timePie[] = array('start'=>(strtotime($nowYear.'-'.$nowMonth[0].'-1')+(86400*7*($i-1))),'end'=>(strtotime($nowYear.'-'.$nowMonth[0].'-1')+(86400*7*$i)));  
                }
            }else{
                foreach($nowMonth as $val){
                    $timePie[] = array('start'=>strtotime($nowYear.'-'.$val.'-1'),'end'=>(strtotime($nowYear.'-'.$val.'-1')+$MonToCuo[$val]));
                }
            }
        }else{
            if($nowYear == date("Y")){
                $monthNum = date('m');
            }else{
                $monthNum = 12;
            }
            for($i=1;$i<=$monthNum;$i++){
                $timePie[] = array('start'=>strtotime($nowYear.'-'.$i.'-1'),'end'=>(strtotime($nowYear.'-'.$i.'-1')+$MonToCuo[$i]));
                $nowMonth[] = $i; 
            }
        }
        //查询数据
        $where['created_time']  = array('BETWEEN',array(strtotime($nowYear.'-1-1'),strtotime($nowYear.'-12-31  23:59:59')));
        $where['state'] = ['egt',config('normal_status')];//config('normal_status');
        $OrderData = Db::name('OrderGrave')->field('created_time')->where($where)->select();
        //初始化数据
        $lastData = array();
        foreach($timePie as $key => $timeVal){
            $lastData[$key] = array('all'=>0,'success'=>0);
        }
        foreach($OrderData as $dataVal){
            foreach($timePie as $key => $timeVal){
                if(($timeVal['start']<$dataVal['created_time'])&&($dataVal['created_time']<$timeVal['end'])){
                    $lastData[$key]['all'] +=1;
                }
            }
        }
        //完成订单
        $where['success_time'] = $where['created_time'];
        unset($where['created_time']);
        $where['status'] =  config('order_status.success');
        $successOrderData = Db::name('OrderGrave')->field('success_time')->where($where)->select();
        foreach($successOrderData as $sudataVal){
            foreach($timePie as $key => $timeVal){
                if(($timeVal['start']<$sudataVal['success_time'])&&($sudataVal['success_time']<$timeVal['end'])){
                    $lastData[$key]['success'] +=1;
                }
            }
        }
        foreach($lastData as $key=>$ld){
            $ldTime[] = date('m-d',$timePie[$key]['start']).'/'.date('m-d',$timePie[$key]['end']);
            $ldDataAll[] = $ld['all'];
            $ldDataSuccess[] = $ld['success'];
        }
        $ldall = array(array('name'=>'总订单','type'=>'line','stack'=>'数量','data'=>$ldDataAll));
        $ldsuccess = array(array('name'=>'完成订单','type'=>'line','stack'=>'数量','data'=>$ldDataSuccess));
        $this->assign('time',json_encode($ldTime));
        $this->assign('ldall',json_encode($ldall));
        $this->assign('ldsuccess',json_encode($ldsuccess));
        $this->assign('allMonth',$allMonth);
        $this->assign('nowMonth',$nowMonth);
        $this->assign('allYear',$allYear);
        //dump($nowYear);die;
        $this->assign('nowYear',$nowYear);
        $this->assign('region',$region);
        $this->assign('regionName',json_encode($region['name']));
        return $this->fetch();
    }
    /**
     * (省下边)地区订单同环比图
     * @param   input   void;
     * @return  output  void;
     */
    public function ordercompare(){
        $regionid = input('region');
        $region = array('name'=>'全国','id'=>'0');
        if(!empty($regionid)){
            $where['province_id'] = $regionid;
            $region = $this->getRegionData(array('id'=>$regionid),array('id','name'),1);
        }
        //获取年份
        $allYear = array();
        $year = date("Y");
        for($i = 2016;$i<=$year;$i++){
            $allYear[] = $i;
        }
        //默认当年
        $nowYear = input('nowYear');
        if(empty($nowYear)){
            $nowYear = date("Y");
        }
        
        //月份处理
        $nowMonth = input('nowMonth');
        $allMonth = array(1=>'一月',2=>'二月',3=>'三月',4=>'四月',5=>'五月',6=>'六月', 7=>'七月',8=>'八月',9=>'九月',10=>'十月',11=>'十一月',12=>'十二月');
        $MonToCuo = array('1'=>2678399,'2'=>2419199,'3'=>2678399,'4'=>2591999,'5'=>2678399,'6'=>2678399,'7'=>2678399,'8'=>2678399,'9'=>2678399,'10'=>2678399,'11'=>2678399,'12'=>2678399);
        if(empty($nowMonth)){
            $nowMonth = date('m');
            if($nowMonth == 1){
                $nowYear = $nowYear-1;
                $nowMonth = 12;
            }else{
                $nowMonth = $nowMonth-1;
            }
        }
        $timePie['本月'] = array('start'=>strtotime($nowYear.'-'.$nowMonth.'-1'),'end'=>(strtotime($nowYear.'-'.$nowMonth.'-1')+$MonToCuo[$nowMonth]));
        $timePie['同比'] = array('start'=>strtotime(($nowYear-1).'-'.$nowMonth.'-1'),'end'=>(strtotime(($nowYear-1).'-'.$nowMonth.'-1')+$MonToCuo[$nowMonth]));
        if($nowMonth == 1){
            $timePie['环比'] = array('start'=>strtotime(($nowYear-1).'-12-1'),'end'=>(strtotime(($nowYear-1).'-12-1')+$MonToCuo['12']));
        }else{
            $timePie['环比'] = array('start'=>strtotime($nowYear.'-'.($nowMonth-1).'-1'),'end'=>(strtotime($nowYear.'-'.($nowMonth-1).'-1')+$MonToCuo[($nowMonth-1)]));
        }
        $where['state'] = ['egt',config('normal_status')];//config('normal_status');
        //查找订单数量数据
        foreach($timePie as $key=>$val){
            $where['created_time'] = array('BETWEEN',array($val['start'],$val['end']));
            $OrderData[$key] = Db::name('OrderGrave')->field('status,created_time')->where($where)->select();
        }
        //查找佣金金额数据
        unset($where['created_time']);
        foreach($timePie as $key=>$val){
            $where['success_time'] = array('BETWEEN',array($val['start'],$val['end']));
            $orderDataMoney[$key] = Db::name('OrderGrave')->field('status,paid_in_amount')->where($where)->select();
        }
        //dump($orderDataMoney);die;
        //处理佣金金额数据
        $lastDataMoney = array();
        foreach($orderDataMoney as $k=>$v){
            //初始化数据
            if(empty($v)){
                $lastDataMoney[$k] = array('money_order'=>0,'money'=>0);
            }
            foreach($v as $lastV){
                $lastDataMoney[$k] = array('money_order'=>0,'money'=>0);
            }
            
            foreach($v as $lastV){
                if($lastV['status'] == config('order_status.success')){
                    $lastDataMoney[$k]['money_order'] += 1;
                    $lastDataMoney[$k]['money'] += $lastV['paid_in_amount']; 
                }
            }
        }
        //处理订单数量数据
        $lastDatas = array();
        foreach($OrderData as $key=>$val){
            //初始化数据
            if(empty($val)){
                $lastDatas[$key] = array('total_order'=>0,'money_order'=>0);
            }
            foreach($val as $lastVal){
                $lastDatas[$key] = array('total_order'=>0,'money_order'=>0);
            }
            
            foreach($val as $lastVal){
                $lastDatas[$key]['total_order'] +=1;
                if($lastVal['status'] == config('order_status.success')){
                    $lastDatas[$key]['money_order'] +=1;
                }
            }
        }
        //总订单
        $totaldata = array(
            $lastDatas['本月']['total_order'],
            $lastDatas['环比']['total_order'],
            $lastDatas['同比']['total_order']
        );
        //完成订单
        $successdata = array(
            $lastDataMoney['本月']['money_order'],
            $lastDataMoney['环比']['money_order'],
            $lastDataMoney['同比']['money_order']
        );
        //佣金订单
        $ordermoney = array(
            $lastDataMoney['本月']['money'],
            $lastDataMoney['环比']['money'],
            $lastDataMoney['同比']['money']
        );
        
        //处理年月
        $nowYear = input('nowYear');;
        if(empty($nowYear)){
            $nowYear = date("Y");
        }
        $nowMonth = input('nowMonth');
        if(empty($nowMonth)){
            $nowMonth = date('m');
            if($nowMonth == 1){
                $nowYear = $nowYear -1;
                $nowMonth = 12;
            }else{
                $nowMonth = $nowMonth - 1;
            }
        }
        $title = array($allMonth[$nowMonth],'环比','同比');
        $this->assign('title',json_encode($title));
        $this->assign('totaldata',json_encode($totaldata));//总订单
        $this->assign('successdata',json_encode($successdata));//完成订单
        $this->assign('ordermoney',json_encode($ordermoney));//佣金订单
        $this->assign('allMonth',$allMonth);
        $this->assign('nowMonth',$nowMonth);
        $this->assign('allYear',$allYear);
        $this->assign('nowYear',$nowYear);
        $this->assign('region',$region);
        $this->assign('regionName',json_encode($region['name']));
        return $this->fetch();
    }
    /**
     * 地区订单分布统计
     * @param void;
     * @return void;
     */
    public function regionorder(){
        $data = $this->_regionorder();
        $this->assign('data',$data);
        return $this->fetch();
    }
    /**
     * 地区订单分布核心方法
     * @param  input|void;
     * @return output|array;
     */
    private function _regionorder(){
        //封装时间
        $where = array();
        $where = $this->ordertime('created_time',3);
        
        //查询数据
        $where['state'] = ['egt',config('normal_status')];//config('normal_status');
        $field = 'province_id,city_id,status,store_status,send_finance_status';
        $OrderData = Db::name('OrderGrave')->field($field)->where($where)->select();
        //dump($OrderData);die;
        $lastData = array(); 
        foreach($OrderData as $lastDatas){  
            if(array_key_exists($lastDatas['city_id'], $lastData)){
                //订单总数
                $lastData[$lastDatas['city_id']]['all_total'] +=1;
                //订单状态
                if($lastDatas['status'] == config('order_status.check_success') && $lastDatas['send_finance_status'] == config('normal_status')){ //待收佣金
                    $lastData[$lastDatas['city_id']]['check_success'] +=1; 
                }else if($lastDatas['status'] == config('order_status.get_money')){ //待返现
                    $lastData[$lastDatas['city_id']]['get_money'] +=1; 
                }else if($lastDatas['status'] == config('order_status.deposit')){ //已交订金
                    $lastData[$lastDatas['city_id']]['deposit'] +=1; 
                }
                //商家(非)会员
                if(in_array($lastDatas['store_status'],array('19','20','16','14'))){//会员订单
                    $lastData[$lastDatas['city_id']]['store_status'] +=1;
                }else if($lastDatas['store_status'] == 0){ //非会员订单
                    $lastData[$lastDatas['city_id']]['no_store_status'] +=1; 
                }
            }else{
                //初始化数据
                $lastData[$lastDatas['city_id']]['check_success'] = 0;
                $lastData[$lastDatas['city_id']]['get_money'] = 0;
                $lastData[$lastDatas['city_id']]['deposit'] = 0;
                $lastData[$lastDatas['city_id']]['success'] = 0; 
                $lastData[$lastDatas['city_id']]['store_status'] = 0;
                $lastData[$lastDatas['city_id']]['no_store_status'] =0;
                $lastData[$lastDatas['city_id']]['province_id'] =$lastDatas['province_id'];

                //订单总数
                $lastData[$lastDatas['city_id']]['all_total'] =1; 
                //订单状态
                if($lastDatas['status'] == config('order_status.check_success') && $lastDatas['send_finance_status'] == config('normal_status')){//待收佣金
                    $lastData[$lastDatas['city_id']]['check_success'] =1; 
                }else if($lastDatas['status'] == config('order_status.get_money')){ //待返现
                    $lastData[$lastDatas['city_id']]['get_money'] =1; 
                }else if($lastDatas['status'] == config('order_status.deposit')){ //订金
                    $lastData[$lastDatas['city_id']]['deposit'] =1; 
                }
                //商家(非)会员
                if(in_array($lastDatas['store_status'],array('19','20','16','14'))){ //会员订单
                    $lastData[$lastDatas['city_id']]['store_status'] =1; 
                }else if($lastDatas['store_status'] == 0){ //非会员订单
                    $lastData[$lastDatas['city_id']]['no_store_status'] =1;
                }
            }
        }
        //dump($lastData);die;
        //查找完成订单数据
        $whereSuccess = $this->ordertime('success_time',3);
        $whereSuccess['state'] = ['egt',config('normal_status')];//config('normal_status');
        $whereSuccess['status'] = config('order_status.success');
        $field = 'city_id';
        $OrderDataSuccess = Db::name('OrderGrave')->field($field)->where($whereSuccess)->select();
        foreach($OrderDataSuccess as $lastDataSuccess){ 
            if(!empty($lastData[$lastDataSuccess['city_id']]['success'])){
                $lastData[$lastDataSuccess['city_id']]['success'] +=1; 
            }

        }
        //查找地区
        $regionData = $this->getRegionData(array('status'=>config('normal_status')),array('id,name'),'',true);
        //dump($regionData);die;
        //关联数组变索引数组
        $data = array();
        foreach($lastData as $key=>$val){
            $val['regionid'] = $key;
            $val['regionname'] ='('.$regionData[$val['province_id']].')'.$regionData[$key];
            $data[] = $val;
        }
        //排序处理
        $datacount = count($data);
        for($i=1;$i<$datacount;$i++){
            for($j=0;$j<$datacount-$i;$j++){
                if($data[$j]['all_total']<$data[$j+1]['all_total']){
                    $tmp = $data[$j];
                    $data[$j] = $data[$j+1];
                    $data[$j+1] = $tmp;
                }
            }
        }
        return $data;
    }

    /**
     * 地区订单分布下载
     * @param void;
     * @return void;
     */
    public function regionorderdown(){
        $lastData = $this->_regionorder();
        // 引入PHPExcel
        vendor('PHPExcel.PHPExcel');
        $phpExcel = new \PHPExcel();

        $phpExcel->getProperties()->setCreator('hgy')
            ->setLastModifiedBy('hgy')
            ->setTitle('Office 2007 XLSX Test Document')
            ->setSubject('Office 2007 XLSX Test Document')
            ->setDescription('Test document for Office 2007 XLSX, generated using PHP classes.')
            ->setKeywords('office 2007 openxml php')
            ->setCategory('Test result file');
       
        // 设置行宽度
        $phpExcel->getActiveSheet()->getColumnDimension('A')->setWidth(20);
        $phpExcel->getActiveSheet()->getColumnDimension('B')->setWidth(14);
        $phpExcel->getActiveSheet()->getColumnDimension('C')->setWidth(14);
        $phpExcel->getActiveSheet()->getColumnDimension('D')->setWidth(14);
        $phpExcel->getActiveSheet()->getColumnDimension('E')->setWidth(14);
        $phpExcel->getActiveSheet()->getColumnDimension('F')->setWidth(14);
        $phpExcel->getActiveSheet()->getColumnDimension('G')->setWidth(14);
        $phpExcel->getActiveSheet()->getColumnDimension('H')->setWidth(14);
        $phpExcel->getActiveSheet()->getColumnDimension('I')->setWidth(14);
             
        $phpExcel->getActiveSheet()->getDefaultStyle()->getFont()->setSize(10);//所有字体大小
        $phpExcel->getActiveSheet()->getStyle('A1:H1')->getFont()->setBold(true);//A1到I1加粗
        $phpExcel->getActiveSheet()->getStyle('A1:H1')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER)->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $phpExcel->getActiveSheet()->getStyle('A1:H1')->getBorders()->getAllBorders()->setBorderStyle(\PHPExcel_Style_Border::BORDER_THIN);
        
        // 设置水平居中
        $phpExcel->getActiveSheet()->getStyle('A')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $phpExcel->getActiveSheet()->getStyle('B')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $phpExcel->getActiveSheet()->getStyle('C')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $phpExcel->getActiveSheet()->getStyle('D')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $phpExcel->getActiveSheet()->getStyle('E')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $phpExcel->getActiveSheet()->getStyle('F')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $phpExcel->getActiveSheet()->getStyle('G')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $phpExcel->getActiveSheet()->getStyle('H')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $phpExcel->getActiveSheet()->getStyle('I')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        // 设置表格标题
        $phpExcel->setActiveSheetIndex(0)->setCellValue('A1','城市')->setCellValue('B1','订单总数')->setCellValue('C1','完成订单')->setCellValue('D1','待收佣金')->setCellValue('E1','已交订金')->setCellValue('F1','待返现')->setCellValue('G1','会员')->setCellValue('H1','非会员');
        // 设置行高度
        $phpExcel->getActiveSheet()->getRowDimension(1)->setRowHeight(22);
        
        //初始化数据
        $i = 1;
        $all_total = 0;
        $check_success = 0;
        $get_money = 0;
        $deposit = 0;
        $success = 0;
        $store_status = 0;
        $no_store_status = 0;
        foreach($lastData as $k=>$val){
            ++$i;
            $phpExcel->getActiveSheet(0)->setCellValue('A'.$i,$val['regionname']);
            $phpExcel->getActiveSheet(0)->setCellValue('B'.$i,$val['all_total']);
            $phpExcel->getActiveSheet(0)->setCellValue('C'.$i,$val['success']);
            $phpExcel->getActiveSheet(0)->setCellValue('D'.$i,$val['check_success']);
            $phpExcel->getActiveSheet(0)->setCellValue('E'.$i,$val['deposit']);
            $phpExcel->getActiveSheet(0)->setCellValue('F'.$i,$val['get_money']);
            $phpExcel->getActiveSheet(0)->setCellValue('G'.$i,$val['store_status']);
            $phpExcel->getActiveSheet(0)->setCellValue('H'.$i,$val['no_store_status']);
            $phpExcel->getActiveSheet()->getStyle('A'.$i.':H'.$i)->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
            $phpExcel->getActiveSheet()->getStyle('A'.$i.':H'.$i)->getBorders()->getAllBorders()->setBorderStyle(\PHPExcel_Style_Border::BORDER_THIN);
            $phpExcel->getActiveSheet()->getRowDimension($i)->setRowHeight(16);
            
            $all_total += $val['all_total'];
            $check_success += $val['check_success'];
            $get_money += $val['get_money'];
            $deposit += $val['deposit'];
            $success += $val['success'];
            $store_status += $val['store_status'];
            $no_store_status += $val['no_store_status'];
        }
        ++$i;
        $phpExcel->getActiveSheet(0)->setCellValue('A'.$i,'总和');
        $phpExcel->getActiveSheet(0)->setCellValue('B'.$i,$all_total);
        $phpExcel->getActiveSheet(0)->setCellValue('C'.$i,$success);
        $phpExcel->getActiveSheet(0)->setCellValue('D'.$i,$check_success);
        $phpExcel->getActiveSheet(0)->setCellValue('E'.$i,$deposit);
        $phpExcel->getActiveSheet(0)->setCellValue('F'.$i,$get_money);
        $phpExcel->getActiveSheet(0)->setCellValue('G'.$i,$store_status);
        $phpExcel->getActiveSheet(0)->setCellValue('H'.$i,$no_store_status);
        $phpExcel->getActiveSheet()->getStyle('A'.$i.':H'.$i)->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
        $phpExcel->getActiveSheet()->getStyle('A'.$i.':H'.$i)->getBorders()->getAllBorders()->setBorderStyle(\PHPExcel_Style_Border::BORDER_THIN);
        $phpExcel->getActiveSheet()->getRowDimension($i)->setRowHeight(22);
        // excel头参数
        header('Content-Type: application/vnd.ms-excel'); // 生成xls/xlsx文件
        // header('Content-Type: text/csv'); // 生成csv文件
        header('Content-Disposition: attachment;filename="订单数据('.input('start_time').'--'.input('end_time').').xls"'); // 后缀名:xls/xlsx/csv
        header('Cache-Control: max-age=0');
        $phpWriter = \PHPExcel_IOFactory::createWriter($phpExcel, 'Excel5'); // Excel5:xls; Excel2007:xlsx; CSV:csv;
        $phpWriter->save('php://output');
    }
    /**
     * 陵园分析 
     * @param void;
     * @return void;
     */
    public function storeorder(){
        $lastData = $this->_storeorder();
        //dump($lastData);die;
        //查找地区
        $regionData = $this->getRegionData(array('status'=>config('normal_status'),'level'=>1),array('id,name'),'',true);
        $search_province_id = input('get.regionData/a');
        $this->assign('search_province_id',$search_province_id);
        $this->assign('regionData',$regionData);
        $this->assign('lastData',$lastData);
        return $this->fetch();
    }



     /**
     * 陵园分析核心方法 
     * @param  void;
     * @return  array;
     */
    private function _storeorder(){
        //封装时间
        $where = array();
        $where = $this->ordertime('created_time',3);
        //封装搜索地区
        $search_province_id = input('get.regionData/a');
        if(!empty($search_province_id)){
            $where['province_id'] = array('in',$search_province_id); 
        }
        //查询数据
        $where['state'] = ['egt',config('normal_status')];//config('normal_status');
        $field = 'store_id,store_name,store_status,status,send_finance_status';
        $datas = Db::name('OrderGrave')->field($field)->where($where)->select();
        //dump($datas);die;
        $lastData = array();
        foreach($datas as $lastDatas){
            if(array_key_exists($lastDatas['store_id'], $lastData)){
                $lastData[$lastDatas['store_id']]['order_total'] +=1;//总订单数
                
                if(($lastDatas['status'] == config('order_status.check_success'))&&($lastDatas['send_finance_status'] == 1)){ //待收佣金
                    $lastData[$lastDatas['store_id']]['check_success'] +=1; 
                }else if($lastDatas['status'] == config('order_status.get_money')){ //待返现
                    $lastData[$lastDatas['store_id']]['get_money'] +=1; 
                }
            }else{
                $lastData[$lastDatas['store_id']]['order_total'] = 1; //总订单数
                $lastData[$lastDatas['store_id']]['store_id'] = $lastDatas['store_id']; //商家ID
                $lastData[$lastDatas['store_id']]['name'] = $lastDatas['store_name']; //商家名称
                $lastData[$lastDatas['store_id']]['store_status'] = $lastDatas['store_status']; //商家状态
                $lastData[$lastDatas['store_id']]['check_success'] = 0;//初始化佣金
                $lastData[$lastDatas['store_id']]['get_money'] = 0;//初始化返现
                $lastData[$lastDatas['store_id']]['success'] = 0;//初始化完成
                
                if(($lastDatas['status'] == config('order_status.check_success'))&&($lastDatas['send_finance_status'] == 1)){//待收佣金
                    $lastData[$lastDatas['store_id']]['check_success'] =1; 
                }else if($lastDatas['status'] == config('order_status.get_money')){ //待返现
                    $lastData[$lastDatas['store_id']]['get_money'] =1; 
                }
            }
        }
        //dump($lastData);die;
        //查找完成订单数据
        $whereSuccess = $this->ordertime('success_time',3);
        $whereSuccess['status'] = config('order_status.success');
        $whereSuccess['state'] = ['egt',config('normal_status')];//config('normal_status');
        if(!empty($search_province_id)){
            $whereSuccess['province_id'] = array('in',$search_province_id); 
        }
        $field = 'store_id,store_name,store_status';
        $OrderDataSuccess = Db::name('OrderGrave')->field($field)->where($whereSuccess)->select();
        //初始化数据
        foreach($OrderDataSuccess as $val){
            if(array_key_exists($val['store_id'], $lastData)){
                $lastData[$val['store_id']]['name'] = $val['store_name']; //商家名称
                $lastData[$val['store_id']]['store_id'] = $val['store_id']; //商家ID
                $lastData[$val['store_id']]['store_status'] = $val['store_status']; //商家状态
                $lastData[$val['store_id']]['check_success'] = 0;//初始化佣金
                $lastData[$val['store_id']]['get_money'] = 0;//初始化返现
                $lastData[$val['store_id']]['success'] = 0;//初始化返现
            }
        }
        foreach($OrderDataSuccess as $lastDataSuccess){ 
            if(!empty($lastData[$lastDataSuccess['store_id']])){
                $lastData[$lastDataSuccess['store_id']]['success'] +=1; 
            }
        }

        //dump($lastData);die;
        $lastDataTmp = array();
        foreach($lastData as $val){
            $lastDataTmp[] = $val;
        }
        unset($lastData);
        //冒泡排序
        $count = count($lastDataTmp);
        for($i=1;$i<$count;$i++){
            for($j=0;$j<$count-$i;$j++){
                if($lastDataTmp[$j]['order_total']<$lastDataTmp[$j+1]['order_total']){
                    $tmp = $lastDataTmp[$j];
                    $lastDataTmp[$j] = $lastDataTmp[$j+1];
                    $lastDataTmp[$j+1] = $tmp;
                }
            }
        }
        return $lastDataTmp;
    }
    /**
     * 查找陵园联系人
     * @param  input|void;
     * @return string|json;
     */
    public function storecontact(){
        $result = array('flag'=>0,'msg'=>'查找失败!');
        $store_id = input('store_id');
        $where['status'] = config('normal_status');
        $where['store_id'] = $store_id;
        $data = Db::name('StoreContact')->field('contact_name,mobile,tel,remark')->where($where)->select();
        if(!empty($data)){
            $result['flag'] = 1;
            $result['msg'] = 'OK';
            $result['data'] = $data;
        }    
        echo json_encode($result);
    }
    /**
     * 陵园分析下载
     * @param void;
     * @return void;
     */
    public function storeorderdown(){
        $downData = $this->_storeorder();
        $count = count($downData);
        //dump($lastDataTmp);die;
        //引入PHPExcel
        vendor('PHPExcel.PHPExcel');
        $phpExcel = new \PHPExcel();
        $phpExcel->getProperties()->setCreator('hgy')
            ->setLastModifiedBy('hgy')
            ->setTitle('Office 2007 XLSX Test Document')
            ->setSubject('Office 2007 XLSX Test Document')
            ->setDescription('Test document for Office 2007 XLSX, generated using PHP classes.')
            ->setKeywords('office 2007 openxml php')
            ->setCategory('Test result file');
        // 设置行宽度
        $phpExcel->getActiveSheet()->getColumnDimension('A')->setWidth(25);
        $phpExcel->getActiveSheet()->getColumnDimension('B')->setWidth(14);
        $phpExcel->getActiveSheet()->getColumnDimension('C')->setWidth(14);
        $phpExcel->getActiveSheet()->getColumnDimension('D')->setWidth(14);
        $phpExcel->getActiveSheet()->getColumnDimension('E')->setWidth(14);
        // 设置行高度
        $phpExcel->getActiveSheet()->getRowDimension('1')->setRowHeight(22);
        $phpExcel->getActiveSheet()->getDefaultStyle()->getFont()->setSize(10);//设置所有字体大小
        $phpExcel->getActiveSheet()->getStyle('A1:E1')->getFont()->setBold(true);//A1到H1加粗
        $phpExcel->getActiveSheet()->getStyle('A1:E1')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER)->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $phpExcel->getActiveSheet()->getStyle('A1:E1')->getBorders()->getAllBorders()->setBorderStyle(\PHPExcel_Style_Border::BORDER_THIN);
        // 设置水平居中
        //$phpExcel->getActiveSheet()->getStyle('A')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $phpExcel->getActiveSheet()->getStyle('B')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $phpExcel->getActiveSheet()->getStyle('C')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $phpExcel->getActiveSheet()->getStyle('D')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $phpExcel->getActiveSheet()->getStyle('E')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        // 设置表格标题
        $phpExcel->setActiveSheetIndex(0)->setCellValue('A1','陵园名称')->setCellValue('B1','总订单量')->setCellValue('C1','完成订单')->setCellValue('D1','待收佣金')->setCellValue('E1','待返现');
        $order_total = 0;
        $check_success = 0;
        $get_money = 0;
        $success = 0;
        for($i=0;$i<$count;$i++){
            $phpExcel->getActiveSheet(0)->setCellValue('A'.($i+2),$downData[$i]['name']);
            $phpExcel->getActiveSheet(0)->setCellValue('B'.($i+2),$downData[$i]['order_total']);
            $phpExcel->getActiveSheet(0)->setCellValue('C'.($i+2),$downData[$i]['success']);
            $phpExcel->getActiveSheet(0)->setCellValue('D'.($i+2),$downData[$i]['check_success']);
            $phpExcel->getActiveSheet(0)->setCellValue('E'.($i+2),$downData[$i]['get_money']);
            $phpExcel->getActiveSheet()->getStyle('A'.($i+2).':E'.($i+2))->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
            $phpExcel->getActiveSheet()->getStyle('A'.($i+2).':E'.($i+2))->getBorders()->getAllBorders()->setBorderStyle(\PHPExcel_Style_Border::BORDER_THIN);
            $phpExcel->getActiveSheet()->getRowDimension($i+2)->setRowHeight(16);
            
            $order_total += $downData[$i]['order_total'];
            $check_success += $downData[$i]['check_success'];
            $get_money += $downData[$i]['get_money'];
            $success += $downData[$i]['success'];
        }
        $i = $i+2;
        $phpExcel->getActiveSheet(0)->setCellValue('A'.$i,'总和');
        $phpExcel->getActiveSheet(0)->setCellValue('B'.$i,$order_total);
        $phpExcel->getActiveSheet(0)->setCellValue('C'.$i,$success);
        $phpExcel->getActiveSheet(0)->setCellValue('D'.$i,$check_success);
        $phpExcel->getActiveSheet(0)->setCellValue('E'.$i,$get_money);
        $phpExcel->getActiveSheet()->getStyle('A'.$i.':E'.$i)->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
        $phpExcel->getActiveSheet()->getStyle('A'.$i.':E'.$i)->getBorders()->getAllBorders()->setBorderStyle(\PHPExcel_Style_Border::BORDER_THIN);
        $phpExcel->getActiveSheet()->getRowDimension($i)->setRowHeight(22);
        // excel头参数
        header('Content-Type: application/vnd.ms-excel'); // 生成xls/xlsx文件
        // header('Content-Type: text/csv'); // 生成csv文件
        $start_time = input('start_time');
        $end_time = input('end_time');
        header('Content-Disposition: attachment;filename="陵园订单统计表('.$start_time.'--'.$end_time.').xls"'); // 后缀名:xls/xlsx/csv
        header('Cache-Control: max-age=0');
        $phpWriter = \PHPExcel_IOFactory::createWriter($phpExcel, 'Excel5'); // Excel5:xls; Excel2007:xlsx; CSV:csv;
        $phpWriter->save('php://output');
    }
    
    /**
     * 来电咨询统计
     * @param  input|void;
     * @return output|void;
     */
    public function ordersource(){
        $inputdata= input('get.options/a');
        if(empty($inputdata)){
            $options = array();
        }else{
            $options = $inputdata;
        }
        //封装时间
        $where = array();
        $where = $this->ordertime('created_time');
        //封装搜索地区
        $search_province_id = input('get.regionData/a');
        if(!empty($search_province_id)){
            $where['province_id'] = array('in',$search_province_id); 
        }
        //查询数据
        $where['state'] = ['egt',config('normal_status')];//config('normal_status');
        $field = 'call_type,id';
        $OrderData = Db::name('OrderGrave')->field($field)->where($where)->select();
        $lastData = array('咨询购墓'=>'','咨询价格'=>'','咨询路线'=>'','咨询班车'=>'','咨询扫墓'=>'','咨询电话'=>'','其他'=>'');
        $totalCount = 0;
        foreach($OrderData as $lastDatas){
            $totalCount +=1;

            if($lastDatas['call_type'] == 1 ){     //1是咨询购墓
                $lastData['咨询购墓'] +=1;
            }else if($lastDatas['call_type'] == 2){ //2是咨询价格
                $lastData['咨询价格'] +=1;
            }else if($lastDatas['call_type'] == 3){ //3是路线
                $lastData['咨询路线'] +=1;
            }else if($lastDatas['call_type'] == 4){ //4是班车
                $lastData['咨询班车'] +=1;
            }else if($lastDatas['call_type'] == 5){ //5是扫墓
                $lastData['咨询扫墓'] +=1;
            }else if($lastDatas['call_type'] == 6){ //6是咨询电话
                $lastData['咨询电话'] +=1;
            }else if($lastDatas['call_type'] == 7){ //7是其他
                $lastData['其他'] +=1;
            }
        }
        //拼接柱状图数据

        foreach ($lastData as $key => $value) {
            $call_type[] = $key;
            $data[] = $value;
        }
        
        //拼接饼状图
         $bing = array(
                array('value'=>$data[0],'name'=>'咨询购墓'),
                array('value'=>$data[1],'name'=>'咨询价格'),             
                array('value'=>$data[2],'name'=>'咨询路线'),             
                array('value'=>$data[3],'name'=>'咨询班车'),             
                array('value'=>$data[4],'name'=>'咨询扫墓'),             
                array('value'=>$data[5],'name'=>'咨询电话'),             
                array('value'=>$data[6],'name'=>'其他'),             
                );

        //查找地区
        $regionData = $this->getRegionData(array('status'=>config('normal_status'),'level'=>1),array('id,name'),'',true);

        $this->assign('data',$regionData);
        $this->assign('datas',json_encode($lastData));
        $this->assign('bing',json_encode($bing));
        $this->assign('call_type',json_encode($call_type));
        $this->assign('number',json_encode($data));
        $this->assign('regionData',$regionData);
        $this->assign('search_province_id',$search_province_id);
        return $this->fetch();
    }


     /**
     * 订单来源统计
     * @param  input|void;
     * @return output|void;
     */
    public function ordercalltype(){
        $inputdata= input('get.options/a');
        if(empty($inputdata)){
            $options = array();
        }else{
            $options = $inputdata;
        }
        //封装时间
        $where = array();
        $where = $this->ordertime('created_time');
        //封装搜索地区
        $search_province_id = input('get.regionData/a');
        if(!empty($search_province_id)){
            $where['province_id'] = array('in',$search_province_id); 
        }
        //查询数据
        $where['state'] = ['egt',config('normal_status')];//config('normal_status');
        $field = 'order_type,id';
        $OrderData = Db::name('OrderGrave')->field($field)->where($where)->select();
        $lastData = array('400电话'=>'','商桥'=>'','手机端'=>'','微信'=>'','自己预约'=>'','朋友推荐'=>'');
        $totalCount = 0;
        foreach($OrderData as $lastDatas){
            $totalCount +=1;

            if($lastDatas['order_type'] == 1 ){     //400电话
                $lastData['400电话'] +=1;
            }else if($lastDatas['order_type'] == 2){ //2是商桥
                $lastData['商桥'] +=1;
            }else if($lastDatas['order_type'] == 3){ //3是手机端
                $lastData['手机端'] +=1;
            }else if($lastDatas['order_type'] == 4){ //4是
                $lastData['微信'] +=1;
            }else if($lastDatas['order_type'] == 5){ //5是自己预约
                $lastData['自己预约'] +=1;
            }else if($lastDatas['order_type'] == 6){ //6是朋友推荐
                $lastData['朋友推荐'] +=1;
            }
        }
        //拼接柱状图数据

        foreach ($lastData as $key => $value) {
            $call_type[] = $key;
            $data[] = $value;
        }
        //查找地区
        $regionData = $this->getRegionData(array('status'=>config('normal_status'),'level'=>1),array('id,name'),'',true);
        //拼接饼状图
        $bing = array(
                array('value'=>$data[0],'name'=>'400电话'),
                array('value'=>$data[1],'name'=>'咨询价格'),             
                array('value'=>$data[2],'name'=>'手机端'),             
                array('value'=>$data[3],'name'=>'微信'),             
                array('value'=>$data[4],'name'=>'自己预约'),             
                array('value'=>$data[5],'name'=>'朋友推荐'),             
                );
        
        $this->assign('bing',json_encode($bing));
        $this->assign('data',$regionData);
        $this->assign('datas',json_encode($lastData));
        $this->assign('call_type',json_encode($call_type));
        $this->assign('number',json_encode($data));
        $this->assign('regionData',$regionData);
        $this->assign('search_province_id',$search_province_id);
        return $this->fetch();
    }

    /**
     * 订单分析同比环比分析
     * @param  input|void;
     * @return  output|void;
     */
    public function ordersamecompare(){
        $nowYear = input('year');
        //获取年份
        $allYear = array();
        $year = date("Y");
        for($i = 2016;$i<= $year;$i++){
            $allYear[] = $i;
        }
        //默认当年
        if(empty($nowYear)){
            $nowYear = date("Y");
        }
        $where['state'] = ['egt',config('normal_status')];//config('normal_status');
        $where['created_time'] = array('BETWEEN',array(strtotime(($nowYear-1).'-1-1'),strtotime($nowYear.'-12-31 23:59:59')));
        $OrderData = Db::name('OrderGrave')->field('status,created_time')->where($where)->select();
        $subTime = strtotime($nowYear.'-1-1');
        $benYear = $this->month($nowYear);
        $nextYear = $this->month($nowYear - 1);
        $next_total = array();
        $now_total = array();

        $lastData = array();
        foreach($benYear as $key => $timeVal){
            $now_total[$key] = 0;
        }
        foreach($nextYear as $key => $timeVal){
            $next_total[$key] =0;
        }
        foreach($OrderData as $dataVal){
        	if($dataVal['created_time'] > $subTime){
	        	foreach($benYear as $key => $timeVal){
	                if(($timeVal['start']<$dataVal['created_time'])&&($dataVal['created_time']<$timeVal['end'])){
	                    $now_total[$key] +=1;
	                }
	            }
        	}else{
        		foreach($nextYear as $key => $timeVal){
	                if(($timeVal['start']<$dataVal['created_time'])&&($dataVal['created_time']<$timeVal['end'])){
	                    $next_total[$key] +=1;
	                }
	            }
        	}
        }

       
        unset($where['created_time']);
        $where['status'] =  config('order_status.success');
        $where['payfor_us_time'] = array('BETWEEN',array(strtotime(($nowYear-1).'-1-1'),strtotime($nowYear.'-12-31 23:59:59')));
        $successOrderData = Db::name('OrderGrave')->field('success_time')->where($where)->select();
        $now_success_total = array();
        $next_success_total = array();
        foreach($benYear as $key => $timeVal){
            $now_success_total[$key] = 0;
        }
        foreach($nextYear as $key => $timeVal){
            $next_success_total[$key] = 0;
        }
        foreach($successOrderData as $dataVal){
        	if($dataVal['success_time'] > $subTime){
	        	foreach($benYear as $key => $timeVal){
	                if(($timeVal['start']<$dataVal['success_time'])&&($dataVal['success_time']<$timeVal['end'])){
	                    $now_success_total[$key] +=1;
	                }
	            }
        	}else{
        		foreach($nextYear as $key => $timeVal){
	                if(($timeVal['start']<$dataVal['success_time'])&&($dataVal['success_time']<$timeVal['end'])){
	                    $next_success_total[$key] +=1;
	                }
	            }
        	}
        }

        $this->assign('now_success_total',$now_success_total);//今年完成总订单量
        $this->assign('next_success_total',$next_success_total);//今年完成总订单量
        $this->assign('now_total',$now_total);//今年总订单量
        $this->assign('next_total',$next_total);
        $this->assign('allYear',$allYear);
        $this->assign('nowYear',$nowYear);
        return $this->fetch();
    }

     /**
     * 本月完成订单核心方法
     * @param  input|void;
     * @return  output|data;
     */
    public function _instantorder(){

        $where = array();

        //封装完成时间
        $where = $this->ordertime('success_time');
        //封装搜索地区
        $search_province_id = input('get.regionData/a');
        if(!empty($search_province_id)){
            $where['province_id'] = array('in',$search_province_id); 
            
        }
        $orderGraveModal = Db::name('OrderGrave');
        //完成订单
        $where['state'] = ['egt',config('normal_status')];//config('normal_status');
        $OrderData = $orderGraveModal->field('id,province_id,status,paid_in_amount')->order('province_id asc')->where($where)->select();
        $lastData = array(); 
        foreach($OrderData as $lastDatas){  
            if(array_key_exists($lastDatas['province_id'], $lastData)){
                $lastData[$lastDatas['province_id']]['success'] +=1; //完成订单 
                $lastData[$lastDatas['province_id']]['success_id'] .= ','.$lastDatas['id']; //订单ID
                $lastData[$lastDatas['province_id']]['success_money'] += $lastDatas['paid_in_amount']; //佣金
            }else{
                $lastData[$lastDatas['province_id']]['success'] =1; //完成订单
                $lastData[$lastDatas['province_id']]['success_id'] = $lastDatas['id']; //订单ID
                $lastData[$lastDatas['province_id']]['success_money'] = $lastDatas['paid_in_amount']; //佣金
            }
        }
        //已交订金+
        $depositWhere = $this->ordertime('created_time');
        if(!empty($search_province_id)){
            $depositWhere['province_id'] = array('in',$search_province_id);
        }
        $depositWhere['state'] = ['egt',config('normal_status')];//config('normal_status');
        $depositWhere['status'] = config('order_status.deposit');
        $depositData = $orderGraveModal->field('id,province_id,deposit,province_id')->order('province_id ASC')->where($depositWhere)->select();

        //初始化数据
        foreach($depositData as $lastDatas){
        	$lastData[$lastDatas['province_id']]['deposit'] = 0;
            //已交订金订单 
            $lastData[$lastDatas['province_id']]['deposit_id']=0; //订单ID
        }

        foreach($depositData as $lastDatas){
            $lastData[$lastDatas['province_id']]['deposit'] +=1; //已交订金订单 
            $lastData[$lastDatas['province_id']]['deposit_id'] .= ','.$lastDatas['id']; //订单ID
        }
        //查找地区
        $regionData = $this->getRegionData(array('status'=>config('normal_status'),'level'=>1),array('id,name'),'',true);
        foreach ($lastData as $key => $value) {
                $lastData[$key]['regionname'] = $regionData[$key];
        }
        $this->assign('regionData',$regionData);
        return $lastData;
    }


    /**
     * 本月完成订单
     * @param input|void
     * @param outinput|void
     */
    public function instantorder(){
        $lastData = $this->_instantorder();
        //查找地区
        $search_province_id = input('get.regionData/a');
      
        $this->assign('search_province_id',$search_province_id);
        $this->assign('lastData',$lastData);
        return $this->fetch();
    }


    /**本月完成订单数据下载**/
    public function instantorderdown(){

        $downData = $this->_instantorder();
        $count = count($downData);
        //dump($lastDataTmp);die;
        //引入PHPExcel
        vendor('PHPExcel.PHPExcel');
        $phpExcel = new \PHPExcel();
        $phpExcel->getProperties()->setCreator('hgy')
            ->setLastModifiedBy('hgy')
            ->setTitle('Office 2007 XLSX Test Document')
            ->setSubject('Office 2007 XLSX Test Document')
            ->setDescription('Test document for Office 2007 XLSX, generated using PHP classes.')
            ->setKeywords('office 2007 openxml php')
            ->setCategory('Test result file');
        // 设置行宽度
        $phpExcel->getActiveSheet()->getColumnDimension('A')->setWidth(25);
        $phpExcel->getActiveSheet()->getColumnDimension('B')->setWidth(14);
        $phpExcel->getActiveSheet()->getColumnDimension('C')->setWidth(14);
        $phpExcel->getActiveSheet()->getColumnDimension('D')->setWidth(14);
        $phpExcel->getActiveSheet()->getColumnDimension('E')->setWidth(14);
        $phpExcel->getActiveSheet()->getColumnDimension('F')->setWidth(14);
       
        // 设置水平居中
        $phpExcel->getActiveSheet()->getStyle('A')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $phpExcel->getActiveSheet()->getStyle('B')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $phpExcel->getActiveSheet()->getStyle('C')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $phpExcel->getActiveSheet()->getStyle('D')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $phpExcel->getActiveSheet()->getStyle('E')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $phpExcel->getActiveSheet()->getStyle('F')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        // 设置表格标题
        $phpExcel->setActiveSheetIndex()->setCellValue('A1','地区名称')->setCellValue('B1','完成订单')->setCellValue('C1','佣金')->setCellValue('D1','交订金')->setCellValue('E1','开始时间')->setCellValue('F1','结束时间');
        $i=2;
        
        foreach ($downData as $key => $value) {
            if(empty($downData[$key]['deposit'])){
                    $downData[$key]['deposit'] = '';
            }
            $phpExcel->getActiveSheet()->setCellValue('A'.$i,$downData[$key]['regionname']);
            $phpExcel->getActiveSheet()->setCellValue('B'.$i,$downData[$key]['success']);
            $phpExcel->getActiveSheet()->setCellValue('C'.$i,$downData[$key]['success_money']);
            $phpExcel->getActiveSheet()->setCellValue('D'.$i,$downData[$key]['deposit']);
            $phpExcel->getActiveSheet()->setCellValue('E'.$i,$downData[$key]['start_time']);
            $phpExcel->getActiveSheet()->setCellValue('F'.$i,$downData[$key]['end_time']);
            $i++;
        }
        
        // excel头参数
        header('Content-Type: application/vnd.ms-excel'); // 生成xls/xlsx文件
        // header('Content-Type: text/csv'); // 生成csv文件
        $start_time = input('start_time');
        $end_time = input('end_time');
        header('Content-Disposition: attachment;filename="本月完成订单数据下载('.$start_time.'--'.$end_time.').xls"'); // 后缀名:xls/xlsx/csv
        header('Cache-Control: max-age=0');
        $phpWriter = \PHPExcel_IOFactory::createWriter($phpExcel, 'Excel5'); // Excel5:xls; Excel2007:xlsx; CSV:csv;
        $phpWriter->save('php://output');
    }

    /**
     * 访客增降百分比统计
     */
    public function growth(){
        $data = input('get.');
        $map = array();
        $type = array();
        $map = $this->ordertime('end_time');
        if($data){
            $map['type'] = $data['type'];
            $type['type'] = $data['type'];
        }
        $growth_data = Db::name('BaiduVisitorNum')->where($map)->select();
        
        foreach ($growth_data as $key => $value) {
            $growth_data[$key]['percent'] = floor(($value['ontime_visitors']/($value['total_visitors']))*100);
            $growth_data[$key]['refact_percent'] = floor(($value['refact_visitors']/($value['total_visitors']))*100);
        }
        $this->assign('growth',$growth_data);
        $this->assign('type',$type);
        return $this->fetch();
    }



    /**
     * 本月完成订单详情
     * @param   input   void;
     * @return  output  void;
     */
    public function instantorderdetails(){
        //接收数据(一个用于分页，一个用于查询条件)
        $input = $inputtmp = input('get.');
        //处理时间
        $where = $this->ordertime($inputtmp['field']);
        //删除不需要的条件
        unset($inputtmp['field']);
        unset($inputtmp['start_time']);
        unset($inputtmp['end_time']);
        unset($inputtmp['page']);
        $where['status'] = array('in',$input['status']);
        $where['state'] = ['egt',config('normal_status')];//config('normal_status');
        $where['province_id'] = $input['province_id'];
        $field = 'order_grave_sn,store_name,reservation_person,reservation_phone,buyer,mobile,order_flow_id,success_time,appoint_time,brokerage_percent,paid_in_amount';
        $pageSize = Config('page_size');
        $orderData = OrderGrave::with(array('flowman'))->field($field)->order('id DESC')->where($where)->paginate($pageSize,false,['query'=>$input]);
        //分页
        $page = $orderData->render();
        $this->assign('orderData',$orderData);
        $this->assign('page',$page);
        $this->assign('input',$input);
        return $this->fetch();
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
     * @param  $year|eg;2017; 年份
     * @return  $result|array;
     */
    private function month($year){
        $result = array();
        if(!empty($year)){
            $result = array(
               '1'=> array('start'=>strtotime($year.'-1-1'),'end'=>strtotime($year.'-1-31 23:59:59')),
               '2'=> array('start'=>strtotime($year.'-2-1'),'end'=>strtotime($year.'-2-28 23:59:59')),
               '3'=> array('start'=>strtotime($year.'-3-1'),'end'=>strtotime($year.'-3-31 23:59:59')),
               '4'=> array('start'=>strtotime($year.'-4-1'),'end'=>strtotime($year.'-4-30 23:59:59')),
               '5'=> array('start'=>strtotime($year.'-5-1'),'end'=>strtotime($year.'-5-31 23:59:59')),
               '6'=> array('start'=>strtotime($year.'-6-1'),'end'=>strtotime($year.'-6-30 23:59:59')),
               '7'=> array('start'=>strtotime($year.'-7-1'),'end'=>strtotime($year.'-7-31 23:59:59')),
               '8'=> array('start'=>strtotime($year.'-8-1'),'end'=>strtotime($year.'-8-31 23:59:59')),
               '9'=> array('start'=>strtotime($year.'-9-1'),'end'=>strtotime($year.'-9-30 23:59:59')),
               '10'=> array('start'=>strtotime($year.'-10-1'),'end'=>strtotime($year.'-10-31 23:59:59')),
               '11'=> array('start'=>strtotime($year.'-11-1'),'end'=>strtotime($year.'-11-30 23:59:59')),
               '12'=> array('start'=>strtotime($year.'-12-1'),'end'=>strtotime($year.'-12-31 23:59:59'))
            );
        }
        return $result;
    }

    /**
     * 搜索词列表
     * @return void
     */
    public function searchWdList(){
        $input = input('get.');
        $where = [];
        $where = $this->ordertime('created_time');
        if(!empty($input['search_type'])){
            $where['category_id'] = $input['search_type'];
        }
        $wdList = SearchKeywords::with('province')->where($where)->order('created_time desc')->paginate(config('page_size'),false,['query' => $input]);
        $page = $wdList->render();
        $this->assign([
            'page' => $page,
            'list' => $wdList,
        ]);
        return $this->fetch();
    }
}