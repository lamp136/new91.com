<?php
namespace back\store\controller;
use think\Controller;
use back\extra\controller\Base;
use think\Session;
use think\Request;
use think\Db;
use back\extra\model\Store;

class Business extends Base
{
    /**
     * 商家默认查询页面
     * 
     * @return void
     */
    public function index() {
        $input = input('get.');
        $where = [
            'status' => ['in',['1','2']],
            'category_id' => config('category_cemetery_id')
        ];
        /**
         * [$searchStoreList 列表页陵园下拉框数据]
         */
        $searchStoreList = Db::name('store')->where($where)->field('id,name,member_status,status')->select();
        /**
         * [$regions 所有地区]
         * @var [type]
         */
        $regions = Db::name('region')->where(['status' => config('normal_status')])->field('id,name,pid')->select();
        $province = [];
        $city = [];
        if($regions){
            foreach ($regions as $val) {
                if($val['pid'] == config('china_num')){
                    // 省份
                    $province[] = $val;
                }else if(isset($input['province']) && !empty($input['province'])){
                    if($val['pid'] == $input['province']){
                        // 市区
                        $city[] = $val;
                    }
                }
            }
        }
        $fields = 'id,store_sn,profiles_id,name,category_id,category_pid,province_id,city_id,status,member_status,min_price,max_price,business_type,sort,distance,pick_up_address,remarks,created_time,updated_time,address';
        $map = [];
        $storeInfo = [];
        /**
         * [$with 关联数据]
         */
        $with = ['province','city','storecontact','storeprofile','priceimage'];
        if(!empty($input['store_id']) && isset($input['store_id'])){
            $map['id'] = $input['store_id'];
            $storeData = Store::with($with)->where($map)->field($fields)->find();
            if($storeData){
                $provinceId = '';
                /**
                 * 判断省份是否是直辖市
                 */
                if(in_array($storeData['province_id'],config('municipalities'))){
                    $provinceId = $storeData['province_id'];
                }
                if(!empty($provinceId)){
                    $other['province_id'] = $provinceId;
                }else{
                    $other['city_id'] = $storeData['city_id'];
                }
                $other['category_id'] = config('category_cemetery_id');
                $other['member_status'] = ['gt',0];
                $other['status'] = config('normal_status');
                $other['id'] = ['neq',$input['store_id']];
                $storeList = Store::with($with)->where($other)->field($fields)->order('created_time desc')->select();

                $storeInfo[] = $storeData;
                $this->assign('otherlist',$storeList);
            }
        }else{
            if(!empty($input['member_status']) || !empty($input['province']) || !empty($input['city'])){
                if(isset($input['member_status']) && $input['member_status'] != 0){
                    $map['member_status'] = $input['member_status'];
                }
                if(isset($input['province']) && $input['province'] != 0){
                    $map['province_id'] = $input['province'];
                    if(isset($input['city']) && $input['city'] != 0){
                        $map['city_id'] = $input['city'];
                    }
                }
                $map['category_id'] = config('category_cemetery_id');
                $map['status'] = ['in',['1','2']];
                $storeInfo = Store::with($with)->where($map)->field($fields)->order('created_time desc')->paginate(config('page_size'),false,['query' => $input]);
                // 分页
                $page = $storeInfo->render();
                $this->assign('page',$page);
            }
        }
        $this->assign('storeInfo',$storeInfo);
        $this->assign('province',$province);
        $this->assign('city',$city);
        $this->assign('storeMember',getStoreMember());
        $this->assign('store',$searchStoreList);

        return $this->fetch();
    }

    /**
     * 获取市区列表
     * @return json
     */
    public function getCityList(){
        $pid = input('get.pid');
        $result = ['code' => 0,'data' => []];
        if($pid){
            $cityData = $this->cityList($pid);
            if($cityData){
                $result = ['code' => 1,'data' => $cityData];
            }
        }

        echo json_encode($result);
    }

    /**
     * 添加商家联系人
     */
    public function addContact(){
        if(request()->isPost()){
            $inputPost = input('post.');
            $result = ['code' => 0,'msg' => '添加失败'];
            $data = $inputPost;
            $data['created_time'] = $data['updated_time'] = time();
            $addInfo = Db::name('StoreContact')->data($data)->insert();
            if($addInfo){
                $result = ['code' => 1,'msg' => '添加成功'];
            }
        }else{
            $storeId = input('get.store_id');
            $result = ['code' => 0];
            if($storeId){
                $contact = Db::name('StoreContact')->where(['store_id' => $storeId,'default_person' => config('normal_status'),'status' => config('normal_status')])->count();
                if($contact > 0){
                    $result = ['code' => 1];
                }
            }
        }
        echo json_encode($result);
    }

    /**
     * 查看商家联系人
     */
    public function showContact(){
        $id = input('get.id');
        $result = ['code' => 0,'data' => []];
        if($id){
            $storeContact = Db::name('StoreContact')->where('store_id',$id)->select();
            if($storeContact){
                $result = ['code' => 1,'data' => $storeContact];
            }
        }
        echo json_encode($result);
    }

    /**
     * 编辑商家联系人
     */
    public function editContact(){
        if(request()->isPost()){
            $result = ['code' => 0,'msg' => '修改失败'];
            $input = input('post.');
            $data = $input;
            $data['updated_time'] = time();
            $info = Db::name('StoreContact')->data($data)->update();
            if($info){
                $result = ['code' => 1,'msg' => '修改成功'];
            }
        }else{
            $id = input('get.id');
            $result = ['code' => 0,'data' => []];
            if($id){
                $contactInfo = Db::name('StoreContact')->where('id',$id)->field('id,contact_name,mobile,tel,default_person,remark')->find();
                if($contactInfo){
                    $result = ['code' => 1,'data' => $contactInfo];
                }
            }
        }
        echo json_encode($result);
    }

    /**
     * 删除商家联系人
     */
    public function delContact(){
        $id = input('post.id');
        $result = ['code' => 0];
        if($id){
            $ret = Db::name('StoreContact')->where('id',$id)->setField('status',config('delete_status'));
            if($ret){
                $result = ['code' => 1];
            }
        }

        echo json_encode($result);
    }

    /**
     * 编辑商家信息
     */
    public function editInfo(){
        if(request()->isPost()){
            $inputData = input('post.');
            $inputData['updated_time'] = time();
            $result = ['code' => 1,'msg' => '修改失败'];
            $storeInfo = Store::with('storeprofile')->where('id',$inputData['id'])->field('profiles_id')->find();
            $ret = Db::name('store')->data($inputData)->update();
            if($ret){
                /**
                 * 判断是否关联合同
                 */
                if(!empty($storeInfo['storeprofile'])){
                    $profiles = [
                        'id'           => $storeInfo['profiles_id'],
                        'updated_time' => time(),
                        'min_price'    => $inputData['min_price'],
                        'max_price'    => $inputData['max_price']
                    ];
                    $saveProfile = Db::name('StoreProfiles')->data($profiles)->update();
                    if($saveProfile){
                        $result = ['code' => 1,'msg' => '修改成功'];
                    }
                }else{
                    $result = ['code' => 1,'msg' => '修改成功'];
                }
            }
        }else{
            $id = input('get.id');
            $result = ['code' => 0];
            if($id){
                $data = Store::with(['province','city'])->where('id',$id)->field('id,name,province_id,city_id,address,member_status,min_price,max_price,pick_up_address,distance')->find();
                $storeMember = getStoreMember();
                if($data){
                    $result = ['code' => 1,'data' => $data,'storeMember' => $storeMember];
                }
            }
        }
        echo json_encode($result);
    }

    /**
     * 获取合同价格图片
     */
    public function getPriceImage(){
        $profileId = input('get.profiles_id');
        $result = ['code' => 0,'data' => []];
        if($profileId){
            $priceImag = Db::name('StoreProfilesImage')->where(['profiles_id' => $profileId,'type' => config('profiles_price_image')])->field('id,image_name,image,thumb_image')->select();
            if($priceImag){
                $result = ['code' => 1,'data' => $priceImag];
            }
        }
        echo json_encode($result);
    }

    /**
     * 预览
     */
    public function scan() {
        if(request()->isPost()){
            $postInput = input('post.');
            $result = ['code' => 0];
            if($postInput['id']){
                $data = [
                    'id' => $postInput['id'],
                    'preview' => $postInput['preview']
                ];
            }
            $savePreview = Db::name('store')->data($data)->update();
            if($savePreview){
                $result = ['code' => 1];
            }
            echo json_encode($result);
        }else{
            $id = input('id');
            if($id){
                $data = Store::with(['province','city','priceimage','tombs','celebrity'])->where('id',$id)->find();
                if(!empty($data['preview'])){
                    $preview = $data['preview'];
                }else{
                    $tombType = Db::name('category')->where('pid',config('category_tombs'))->order('sort')->column('id,name');
                    $storeMember = getStoreMember();
                    // 其他项目
                    $other = '';
                    if(!empty($data['other_project'])){
                        $other = $data['other_project'];
                    }

                    // 会员状态
                    $memberStatus = '';
                    if(!empty($data['member_status'])){
                        $memberStatus = $storeMember[$data['member_status']];
                    }
                    $businessType = '';
                    if($data['business_type'] == 1){
                        $businessType = '公益性';
                    }else if($data['business_type'] == 2){
                        $businessType = '经营性';
                    }

                    // 联系人
                    $contact = '';
                    $mobile = '';
                    $tel = '';
                    if(!empty($data['storecontact'])){
                        foreach($data['storecontact'] as $val){
                            if(!empty($val['mobile'])){
                                $mobile = $val['mobile'].';';
                            }
                            if(!empty($val['tel'])){
                                $tel = $val['tel'];
                            }
                            $contact .= '<p>姓名：'.$val['contact_name'].' 电话：'.$mobile.$tel.'</p>';
                        }
                    }
                    
                    // 墓位
                    $tombs = '';
                    if(!empty($data['tombs'])){
                        foreach ($tombType as $cid => $name) {
                            $tombs .= '<tr><th colspan="4" width="898">'.$name.'</th></tr>';
                            $tombsChild = $this->getTombCategory($cid);
                            $i = 0;
                            $count = $this->getTotalByCpid($cid);
                            if ($count) {
                                foreach ($data['tombs'] as $k => $v) {
                                    if($v['category_pid'] == $cid){
                                        if(array_key_exists($v['category_id'], $tombsChild)){
                                            if ($i%2 == 0) {
                                            $tombs .= '<tr>';
                                        }
                                        $tombs .= '<th>'.$v['tomb_name'].'</th><td width="222">'.'<p>材质：'.$v['material'].'</p><p>'.'价格：'.$v['sales_price'].$v['unit'].'</p><p>'.'朝向：'.$v['aspect'].'</p><p>'.'备注：'.$v['remarks'].'</p><p>类型：'.$tombsChild[$v['category_id']].'</p></td>';
                                            if(($i % 2) == 1 || $i == $count-1){
                                                $tombs .= '</tr>';
                                            }
                                            $i++;
                                        }

                                    }
                                    
                                }
                            }
                            $tombs .= '<tr><th></th><td width="222"></td><th></th><td width="222"></td></tr>';
                        }
                        
                    }

                    // 名人墓地
                    $celebrity = '';
                    if(!empty($data['celebrity'])){
                        foreach($data['celebrity'] as $value){
                            $celebrity .= $value['name'].'，';
                        }
                    }

                    // 价格
                    $price = '';
                    if(!empty($data['min_price'])){
                        $price .= $data['min_price'];
                    }
                    if(!empty($data['max_price'])){
                        $price .= '--'.$data['max_price'];
                    }

                    // 市区距离
                    $distance = '';
                    if(!empty($data['distance'])){
                        $distance = $data['distance'].'公里';
                    }

                    $preview = '
                        <table class="layui-table">
                            <colgroup>
                                <col width="100"/>
                                <col width="200"/>
                                <col width="100"/>
                                <col width="200"/>
                                <col/>
                            </colgroup>
                            <thead>
                                <tr class="firstRow">
                                    <th>地址</th>
                                    <td width="408" style="word-break: break-all;"><!--地址-->'.$data['province']['name'].'/'.$data['city']['name'].'<br/>'.$data['address'].'<!--地址--></td>
                                    <th width="100">
                                        市区距离</th>
                                    <td width="370" style="word-break: break-all;"><!--市区距离-->'.$distance.'<!--市区距离--></td>
                                </tr>
                                <tr>
                                    <th>联系人</th>
                                    <td width="418" style="word-break: break-all;">
                                    <!--联系人-->
                                        '.$contact.'
                                    <!--联系人-->
                                    </td>
                                    <th>商家性质</th>
                                    <td width="408"><!--商家性质-->'.$businessType.'<!--商家性质--></td>
                                </tr>
                                <tr>
                                    <th>价格区间</th>
                                    <td width="408"><!--价格区间-->'.$price.'<!--价格区间--></td>
                                    <th width="100">会员</th>
                                    <td width="370"><!--会员-->'.$memberStatus.'<!--会员--></td>
                                </tr>
                                <tr>
                                    <th>优点</th>
                                    <td width="408" colspan="3"><!--优点-->'.$data['advantage'].'<!--优点--></td>
                                </tr>
                                <tr>
                                    <th width="100">缺点</th>
                                    <td width="370" colspan="3"><!--缺点-->'.$data['disadvantage'].'<!--缺点--></td>
                                </tr>
                                <tr>
                                    <th>描述</th>
                                    <td colspan="3" width="798"><!--描述-->'.$data['content'].'<!--描述--></td>
                                </tr>
                                <tr>
                                    <th>入住名人</th>
                                    <td colspan="3" width="798">
                                        '.$celebrity.'
                                    </td>
                                </tr>
                                <tr>
                                    <th>交通路线</th>
                                    <td colspan="3" width="798"><!--交通路线-->'.$data['pick_up_address'].'<!--交通路线--></td>
                                </tr>
                                <tr>
                                    <th>其他项目</th>
                                    <td colspan="3" width="798">
                                        '.$other.'
                                    </td>
                                </tr>
                                '.$tombs.'
                                <tr>
                                    <th colspan="4" width="898">陵园服务信息</th>
                                </tr>
                                <tr>
                                    <th>市内办事处</th>
                                    <td colspan="3" width="798"><!--市内办事处-->'.$data['urban_office'].'<!--市内办事处--></td>
                                </tr>
                                <tr>
                                    <th>官方网站</th>
                                    <td width="408" style="word-break: break-all;"><!--官方网站-->'.$data['official_website'].'<!--官方网站--></td>
                                    <th width="100">园区电话</th>
                                    <td width="370"><!--园区电话-->'.$data['phone'].'<!--园区电话--></td>
                                </tr>
                            </thead>
                        </table>';
                }
                $this->assign('id',$id);
                $this->assign('preview',$preview);
                $this->assign('store_name',$data['name']);
            }
            return $this->fetch();
        }
    }
    
    //预览
    // public function scan() {
    //     if(request()->isPost()){
    //         $postInput = input('post.');
    //         $result = ['code' => 0];
    //         if($postInput['id']){
    //             $data = [
    //                 'id' => $postInput['id'],
    //                 'preview' => $postInput['preview']
    //             ];
    //         }
    //         $savePreview = Db::name('store')->data($data)->update();
    //         if($savePreview){
    //             $result = ['code' => 1];
    //         }
    //         echo json_encode($result);
    //     }else{
    //         $id = input('id');
    //         if($id){
    //             $data = Store::with(['province','city','priceimage','tombs','celebrity'])->where('id',$id)->find();
    //             if(!empty($data['preview'])){
    //                 $preview = $data['preview'];
    //             }else{
    //                 $preview = '<p style=";text-align:center">
    //                     <span style=";font-family:Calibri;font-size:16px">&nbsp;</span>
    //                 </p>
    //                 <p style="margin-left: 0;text-indent: 28px">
    //                     <strong><span style="font-family: &#39;Times New Roman&#39;"><span style="font-family:宋体">一、地址：</span></span></strong>
    //                 </p>
    //                 <p style="margin-left:0;text-indent:28px">
    //                     <strong><span style="font-family: &#39;Times New Roman&#39;"><span style="font-family:宋体">二、简介：</span></span></strong>
    //                 </p>
    //                 <p style="margin-left:0;text-indent:28px">
    //                     <strong><span style="font-family: &#39;Times New Roman&#39;"><span style="font-family:宋体">三、主朝向：</span></span></strong>
    //                 </p>
    //                 <p style="margin-left:0;text-indent:28px">
    //                     <strong><span style="font-family: &#39;Times New Roman&#39;"><span style="font-family:宋体">四、入驻名人：</span></span></strong>
    //                 </p>
    //                 <p style="margin-left:0;text-indent:28px">
    //                     <strong><span style="font-family: &#39;Times New Roman&#39;"><span style="font-family:宋体">五、关键词描述：</span></span></strong>
    //                 </p>
    //                 <p style="margin-left:0;text-indent:28px">
    //                     <strong><span style="font-family: &#39;Times New Roman&#39;"><span style="font-family:宋体">优点：</span></span></strong>
    //                 </p>
    //                 <p style="margin-left:0;text-indent:28px">
    //                     <strong><span style="font-family: &#39;Times New Roman&#39;"><span style="font-family:宋体">缺点：</span></span></strong>
    //                 </p>
    //                 <p style="margin-left:0;text-indent:28px">
    //                     <span style="font-family:&#39;Times New Roman&#39;;font-weight:bold">六、</span><strong><span style="font-family: &#39;Times New Roman&#39;"><span style="font-family:宋体">详细价格信息：</span></span></strong>
    //                 </p>
    //                 <p style="margin-right:0;margin-left:28px">
    //                     <br/>
    //                 </p>
    //                 <p style="margin-left:0;text-indent:28px">
    //                     <strong><span style="font-family: &#39;Times New Roman&#39;">1<span style="font-family:宋体">、双穴立碑</span></span></strong>
    //                 </p>
    //                 <p style="margin-left:0;text-indent:28px">
    //                     <strong><span style="font-family: &#39;Times New Roman&#39;">2<span style="font-family:宋体">、其它安葬形式价格：</span></span></strong><span style=";font-family:Calibri;font-size:16px">&nbsp;</span>
    //                 </p>
    //                 <p style="margin-left:0;text-indent:28px">
    //                     <br/>
    //                 </p>
    //                 <p style="margin-left:0;text-indent:28px">
    //                     <strong><span style="font-family: &#39;Times New Roman&#39;">3<span style="font-family:宋体">、园区</span><span style="font-family:Times New Roman">&nbsp;&nbsp; &nbsp;&nbsp;&nbsp; &nbsp; &nbsp; &nbsp;&nbsp; </span><span style="font-family:宋体">朝向</span><span style="font-family:Times New Roman">&nbsp;&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; </span><span style="font-family:宋体">价位</span><span style="font-family:Times New Roman">&nbsp;&nbsp; &nbsp;&nbsp;&nbsp;&nbsp;&nbsp; &nbsp; &nbsp;&nbsp; </span><span style="font-family:宋体">几穴 </span><span style="font-family:Times New Roman">&nbsp; &nbsp; </span><span style="font-family:宋体">平米 </span></span></strong>
    //                 </p>
    //                 <p style="margin-left:0;text-indent:28px">
    //                     <br/>
    //                 </p>
    //                 <p style="margin-left:0;text-indent:28px">
    //                     <strong><span style="font-family: &#39;Times New Roman&#39;">4<span style="font-family:宋体">、家族墓 </span><span style="font-family:Times New Roman">&nbsp; &nbsp; &nbsp; &nbsp;</span><span style="font-family:宋体">朝向</span><span style="font-family:Times New Roman">&nbsp;&nbsp; &nbsp; &nbsp;&nbsp; </span><span style="font-family:宋体">价格</span><span style="font-family:Times New Roman">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; &nbsp; &nbsp; &nbsp; &nbsp;&nbsp; </span><span style="font-family:宋体">几穴</span><span style="font-family:Times New Roman">&nbsp;&nbsp;&nbsp; &nbsp;&nbsp; </span><span style="font-family:宋体">平米</span><span style="font-family:Times New Roman">&nbsp;&nbsp;&nbsp; &nbsp; &nbsp;&nbsp; </span><span style="font-family:宋体">是否售罄</span></span></strong><span style=";font-family:Calibri;font-size:16px">&nbsp;</span>
    //                 </p>
    //                 <p style="margin-left:0;text-indent:28px">
    //                     <br/>
    //                 </p>
    //                 <p style="margin-left:0;text-indent:28px">
    //                     <strong><span style="font-family: &#39;Times New Roman&#39;">5<span style="font-family:宋体">、宗教墓价格：</span></span></strong>
    //                 </p>
    //                 <p style="margin-left:0;text-indent:28px">
    //                     <strong><span style="font-family: &#39;Times New Roman&#39;">6<span style="font-family:宋体">、自选地：</span></span></strong>
    //                 </p>
    //                 <p style="margin-left:0;text-indent:28px">
    //                     <br/>
    //                 </p>
    //                 <p style="margin-left:0;text-indent:28px">
    //                     <strong><span style="font-family: &#39;Times New Roman&#39;">7<span style="font-family:宋体">、其它必缴费用</span></span></strong><span style=";font-family:Calibri;font-size:16px">&nbsp;</span>
    //                 </p>
    //                 <p style="margin-left:0;text-indent:28px">
    //                     <span style=";font-family:Calibri;font-size:16px"><span style="font-family:宋体">安葬费：</span></span>
    //                 </p>
    //                 <p style="margin-left:0;text-indent:28px">
    //                     <span style=";font-family:Calibri;font-size:16px"><span style="font-family:宋体">刻字费：</span></span>
    //                 </p>
    //                 <p style="margin-left:0;text-indent:28px">
    //                     <span style=";font-family:Calibri;font-size:16px"><span style="font-family:宋体">备注：</span></span>
    //                 </p>
    //                 <p style="margin-left:0;text-indent:28px">
    //                     <strong><span style="font-family: &#39;Times New Roman&#39;">8<span style="font-family:宋体">、其他可选服务：</span></span></strong><span style=";font-family:Calibri;font-size:16px">&nbsp;</span>
    //                 </p>
    //                 <p style="margin-left:0;text-indent:28px">
    //                     <br/>
    //                 </p>
    //                 <p style="margin-left:0;text-indent:28px">
    //                     <strong><span style="font-family: &#39;Times New Roman&#39;"><span style="font-family:宋体">七、交通</span></span></strong><span style=";font-family:Calibri;font-size:16px">&nbsp;</span>
    //                 </p>
    //                 <p style="margin-left:0;text-indent:28px">
    //                     <strong><span style="font-family: &#39;Times New Roman&#39;">1<span style="font-family:宋体">、自驾车路线：</span></span></strong><span style=";font-family:Calibri;font-size:16px">&nbsp;</span>
    //                 </p>
    //                 <p style="margin-left:0;text-indent:28px">
    //                     <br/>
    //                 </p>
    //                 <p style="margin-left:0;text-indent:28px">
    //                     <strong><span style="font-family: &#39;Times New Roman&#39;">2<span style="font-family:宋体">、公交路线：</span></span></strong><span style=";font-family:Calibri;font-size:16px">&nbsp;</span>
    //                 </p>
    //                 <p style="margin-left:0;text-indent:28px">
    //                     <br/>
    //                 </p>
    //                 <p style="margin-left:0;text-indent:28px">
    //                     <strong><span style="font-family: &#39;Times New Roman&#39;">3<span style="font-family:宋体">、扫墓班车：</span></span></strong><span style=";font-family:Calibri;font-size:16px">&nbsp;</span>
    //                 </p>
    //                 <p style="margin-left:0;text-indent:28px">
    //                     <br/>
    //                 </p>
    //                 <p style="margin-left:0;text-indent:28px">
    //                     <strong><span style="font-family: &#39;Times New Roman&#39;"><span style="font-family:宋体">八、陵园服务信息</span></span></strong><span style=";font-family:Calibri;font-size:16px">&nbsp;</span>
    //                 </p>
    //                 <p style="margin-left:0;text-indent:28px">
    //                     <strong><span style="font-family: &#39;Times New Roman&#39;"><span style="font-family:宋体">市内办事处：</span></span></strong>
    //                 </p>
    //                 <p style="margin-left:0;text-indent:28px">
    //                     <strong><span style="font-family: &#39;Times New Roman&#39;"><span style="font-family:宋体">地</span> <span style="font-family:宋体">址：</span></span></strong>
    //                 </p>
    //                 <p style="margin-bottom:16px;margin-left:0;text-indent:28px">
    //                     <strong><span style="font-family: &#39;Times New Roman&#39;"><span style="font-family:宋体">交通：</span></span></strong>
    //                 </p>
    //                 <p style="margin-left:0;text-indent:28px">
    //                     <strong><span style="font-family: &#39;Times New Roman&#39;"><span style="font-family:宋体">办事处电话：</span></span></strong>
    //                 </p>
    //                 <p style="margin-left:0;text-indent:28px">
    //                     <strong><span style="font-family: &#39;Times New Roman&#39;"><span style="font-family:宋体">地址：</span></span></strong>
    //                 </p>
    //                 <p style="margin-left:0;text-indent:28px">
    //                     <strong><span style="font-family: &#39;Times New Roman&#39;"><span style="font-family:宋体">交通：</span></span></strong>
    //                 </p>
    //                 <p style="margin-left:0;text-indent:28px">
    //                     <strong style="text-align: center;"><span style="font-family: "><span style="font-family:宋体">陵园官方网站</span></span></strong><span style="text-align: center; font-family: Calibri;"><span style="font-family:宋体">：</span></span>
    //                 </p>
    //                 <p style="margin-left:0;text-indent:28px">
    //                     <strong style="text-align: center;"><span style="font-family: "><span style="font-family:宋体">陵园园区电话：</span></span></strong>
    //                 </p>
    //                 <p>
    //                     <span style=";font-family:Calibri;font-size:14px">&nbsp;</span>
    //                 </p>
    //                 <p>
    //                     <br/>
    //                 </p>';
    //             }
    //             $this->assign('id',$id);
    //             $this->assign('preview',$preview);
    //             $this->assign('store_name',$data['name']);
    //         }
    //         return $this->fetch();
    //     }
    // }

    /**
     * 获取墓地分类名称
     * @param  integer $pid 父id
     * @return array
     */
    public function getTombCategory($pid=0){
        $tombs = Db::name('category')->where('pid',$pid)->field('id,pid,name')->select();
        if($tombs){
            foreach ($tombs as  $val) {
                $result[$val['id']] = $val['name'];
            }
        } else {
            $tomb = Db::name('category')->where('id',$pid)->field('id,pid,name')->find();
            $result[$tomb['id']] = $tomb['name'];
        }

        return $result;
    }

    /**
     * 统计墓地分类下的数量
     * @param  integer $pid 父id
     * @return number
     */
    public function getTotalByCpid($pid=0) {
        $count = 0;
        if($pid){
            $count = Db::name('tombs')->where('category_pid', $pid)->count();
        }
        return $count;
    }
} 