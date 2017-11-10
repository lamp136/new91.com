<?php
namespace web\extra\model;
use think\Model;
use think\Db;

class Store extends Model
{
    /**
     * 省份
     * @return object
     */
    public function province(){
        return $this->hasOne('Region','id','province_id')->where(['status' => 1,'level' => 1])->field('id,name');
    }

    /**
     * 市区
     * @return object
     */
    public function city(){
        return $this->hasOne('Region','id','city_id')->where(['status' => 1,'level' => 2])->field('id,name');
    }
    /**
     * 合同返现比例
     * @return object
     */
    public function profiles(){
        return $this->hasOne('StoreProfiles','id','profiles_id')->field('id,return_amount');
    }
    /**
     * 分类获取
     * @return object
     */
    public function category(){
        return $this->hasOne('Category','id','category_id')->field('id,name');
    }

    /**
     * 商家墓位
     * @return object
     */
    public function tombs(){
        return $this->hasMany('Tombs','store_id','id')->where('status',config('normal_status'))->field('store_id,tomb_zone_id,tomb_name,material,size,aspect,meridians,unit,maket_price,sales_price,category_id,category_pid,remarks,status,image,thumb_image')->order('id desc');
    }

    /**
     * 名人墓地
     * @return object
     */
    public function celebrity(){
        return $this->hasMany('CelebrityCemetery','store_id','id')->where('status',config('normal_status'))->field('id,store_id,name,life_info,born_in,died_in,summary,content,image_url,thumb_image_url');
    }

    /**
     * 景观图片
     * @return object
     */
    public function landscape(){
        return $this->hasMany('StoreImages','store_id','id')->where(['type' => ['eq',2],'state' => ['eq',1]])->field('id,store_id,image_link,thumb_image,image,type,state,title,tomb_zone_id');
    }
    
      /**查询评论**/
    public function Comment(){
        return $this->HasMany('Comment','store_id','id')->field('store_id');
    }

    /**
     * 评论列表
     * @return object
     */
    public function comments(){
        return $this->HasMany('Comment','store_id','id')->where('comment_status',config('normal_status'))->field('id,mobile,content,environment,service,price,traffic,store_id,comment_time,replay_time,created_time')->order('comment_time desc');
    }

    /**查询预约**/
    public function OrderGrave(){
        return $this->HasMany('OrderGrave','store_id','id')->field('store_id');
    }
    
    /**查询预约**/
    public function OrderService(){
        return $this->HasMany('OrderService','store_id','id')->field('store_id');
    }

    /**
     * 商家联系人信息
     * @return object
     */
    public function contactmsg(){
        return $this->hasMany('StoreContact','store_id','id')->where('status',config('normal_status'))->field('store_id,tel,mobile,contact_name,default_person');
    }
    /**
     * 商家联系人信息
     * @return object
     */
    public function funcontact(){
        return $this->hasOne('StoreContact','store_id','id')->where('status',config('normal_status'))->field('store_id,tel,mobile');
    }
    /**
     * 发送给固定的商务部人员
     *
     * @return bool
     */
    public function sendToBusiness($orderId, $uname, $tel, $storename,$content=null,$phone) {
        if (empty($uname) || empty($tel) || empty($storename)) {
            return false;
        }
        $mobile = implode(",", $phone);
        $msg = '亲,您有一条来自91搜墓网的客户预约信息,客户：'.$uname.'，电话：'.$tel.'，商家名称：'.$storename.'，'.$content.'请及时关注【91搜墓网】';
        $sendMsg = new \common\sendmsg\SendMsg;
        $mobiles = explode(",", $mobile);
        $count = count($mobiles);

        for($i = 0; $i < $count; $i++) {
            $sendMsg->sendMsg($mobile,$msg);
            $data[] = [
                'order_id'     => $orderId,
                'classify'     => config('platform_num'),
                'msg'          => $msg,
                'mobile'       => $mobiles[$i],
                'status'       => config('msg_send_status'),
                'created_time' => time(),
                'send_time'    => time()
            ];
        }
        Db::name('MsgLog')->insertAll($data);
    }
}