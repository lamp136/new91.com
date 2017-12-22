<?php 
namespace web\extra\controller;
use think\Controller;

class PushMesage extends Controller
{
    private $host;
    private $appKey;
    private $appId;
    private $masterSecret;
    private $cid;//客户端标识
    private $deviceToken;
    private $igt;

    /**
     * 写在前面的话：
     * IOS建议使用透传消息模板来推送消息
     * android可以使用点击通知打开应用模板和透传消息模板
     * */

    public function __construct(){
        //导入个推的SDK文件
        vendor("IGeTui.IGt#Push");
        // vendor("IGeTui.igetui.utils.AppConditions");

        //赋值
        $this -> host = 'http://sdk.open.api.igexin.com/apiex.htm';
        // $this -> appKey = 'PAX8cvVl4a5EEHQr5X4fk3';
        // $this -> appId = 'wl3swnArkgArkKabkC0L59';
        // $this -> masterSecret = 'WXdDb4jWle5V06ggaCe6j';
        $this -> appKey ='1lOtwDwHIP7IYEgo0Zceh6';
        $this -> appId ='CCxHvEuxWp5YZj3FxMWI2A';
        $this -> masterSecret ='UXQmyYgI5o9UdNN2U9bez3';
        $this -> cid = '';
        $this -> deviceToken = '';
        $this -> igt = new \IGeTui($this -> host , $this -> appKey , $this -> masterSecret);
        
    }

    /**
     * 2016-7-29
     * 推送给所有APP的用户（官方给的demo）
     * （这个没什么用，因为要分IOS和Android客户端推送的话，建议使用pushIGtMsgL（））
     * */
    function pushMessageToApp(){
        $template = $this -> IGtNotificationTemplateDemo();
        //基于应用消息体
        $message = new \IGtAppMessage();
        $message -> set_isOffline(true);
        $message -> set_offlineExpireTime(10 * 60 * 1000);//离线时间单位为毫秒，例，两个小时离线为3600*1000*2
        $message -> set_data($template);

        $appIdList=array($this -> appId);
        $message->set_appIdList($appIdList);

        $rep = $this -> igt-> pushMessageToApp($message);

        var_dump($rep);
        echo ("<br><br>");
    }

    //消息模版：
    // 1.TransmissionTemplate:透传功能模板
    // 2.LinkTemplate:通知打开链接功能模板
    // 3.NotificationTemplate：通知透传功能模板
    // 4.NotyPopLoadTemplate：通知弹框下载功能模板

    /**
     * 2016-7-29
     * 3.NotificationTemplate：通知透传功能模板
     * param1  :   ['title' => "通知标题",'content' => "通知内容" , 'payload' => "通知去干嘛这里可以自定义"]
     * */
    function IGtNotificationTemplateDemo($data){
        $template =  new \IGtNotificationTemplate();
        $template -> set_appId($this -> appId);//应用appid
        $template -> set_appkey($this -> appKey);//应用appkey
        $template -> set_transmissionType(1);//透传消息类型
        $template -> set_transmissionContent($data);//透传内容
        $template -> set_title($data['title']);//通知栏标题
        $template -> set_text($data['content']);//通知栏内容
        //$template -> set_logo("http://wwww.igetui.com/logo.png");//通知栏logo
        $template -> set_isRing(true);//是否响铃
        $template -> set_isVibrate(true);//是否震动
        $template -> set_isClearable(true);//通知栏是否可清除
        //$template->set_duration(BEGINTIME,ENDTIME); //设置ANDROID客户端在此时间区间内展示消息
        return $template;
    }

    /**
     * 2016-8-1
     * 1.TransmissionTemplate:透传功能模板
     * param1  :   ['title' => "通知标题",'content' => "通知内容" , 'payload' => "通知去干嘛这里可以自定义"]
     * 注意  第二个参数必须是这种格式 否则android客户端收不到
     * */
    function IGtTransmissionTemplateDemo($data){
        $template =  new \IGtTransmissionTemplate();
        $template -> set_appId($this -> appId);//应用appid
        $template -> set_appkey($this -> appKey);//应用appkey
        $template -> set_transmissionType(2);//透传消息类型
        $template -> set_transmissionContent($data);//透传内容
        //$template->set_duration(BEGINTIME,ENDTIME); //设置ANDROID客户端在此时间区间内展示消息
        return $template;
    }

    /**
     * 2016-8-4
     * 2.LinkTemplate:通知打开链接功能模板
     * */   
    function IGtLinkTemplateDemo($data){
        $template =  new \IGtLinkTemplate();
        $template -> set_appId($this -> appId);//应用appid
        $template -> set_appkey($this -> appKey);//应用appkey
        $template -> set_title($data['title']);//通知栏标题
        $template -> set_text($data['content']);//通知栏内容
        $template -> set_isRing(true);//是否响铃
        $template -> set_isVibrate(true);//是否震动
        $template -> set_isClearable(true);//通知栏是否可清除
        $template -> set_url($data['url']);
        //$template->set_duration(BEGINTIME,ENDTIME); //设置ANDROID客户端在此时间区间内展示消息
        return $template;
    }

    /**
     * 2016-7-29
     * 别名绑定
     * */
    function aliasBind(){
        $rep = $this -> igt -> bindAlias($this -> appId,'','');
        dump($rep);
    }

    /**
     * 2016-8-2
     * IOS推送消息的方式  IOS只用透传消息 APN推送
     * param1(推送消息)  :   ['title' => "通知标题",'content' => "通知内容" , 'payload' => "通知去干嘛这里可以自定义"]
     * param2(是否是群推消息)  :   bool
     * */
    function getIOSMsg($data , $isList = false){
        $template = new \IGtAPNTemplate();
        $apn = new \IGtAPNPayload();
        $alertmsg = new \DictionaryAlertMsg();
        $alertmsg -> body = $data['content'];
        //$alertmsg -> actionLocKey="测试测试2";
        //$alertmsg -> locKey="333333";
        //$alertmsg -> locArgs = array("locargs");
        $alertmsg -> launchImage="launchimage";
        //IOS8.2 支持
        $alertmsg -> title = $data['title'];
        //$alertmsg -> titleLocKey = $data['title'];
        //$alertmsg -> titleLocArgs = array("TitleLocArg");

        $apn -> alertMsg = $alertmsg;
        $apn -> badge = 1;
        $apn -> add_customMsg("payload",$data['payload']);
        $apn -> contentAvailable=1;
        $apn -> category="ACTIONABLE";
        $template -> set_apnInfo($apn);

        if($isList){
            $message = new \IGtListMessage();
            $message -> set_data($template);
        }else{
            $message = new \IGtSingleMessage();
            $message -> set_isOffline(true);//是否离线
            $message -> set_offlineExpireTime(3600*12*1000);//离线时间
            $message -> set_data($template);//设置推送消息类型

        }       
        //$ret = $this -> igt -> pushAPNMessageToSingle($this -> appId, $this -> deviceToken, $message);
        //var_dump($ret);
        return $message;
    }

    /**
     * 2016-8-2
     * android推送消息方式
     * param1(推送消息)  :   ['title' => "通知标题",'content' => "通知内容" , 'payload' => "通知去干嘛这里可以自定义"]
     * param2(是否群推消息) :  bool
     * param3(消息模板)  :
     *   1.TransmissionTemplate:透传功能模板
     *   2.LinkTemplate:通知打开链接功能模板
     *   3.NotificationTemplate：通知透传功能模板
     *   4.NotyPopLoadTemplate：通知弹框下载功能模板
     * */
    function getAndroidMsg($data , $isList = false , $type = 1){
        switch($type){
            case 1 :
                $template = $this -> IGtTransmissionTemplateDemo($data);
                break;
            case 2 :
                $template = $this -> IGtLinkTemplateDemo($data);
                break;
            case 3 :
                $template = $this -> IGtNotificationTemplateDemo($data);
                break;
        }
        //个推信息体
        if($isList){
            $message = new \IGtListMessage();
            $message -> set_isOffline(true);
            $message -> set_offlineExpireTime(3600*12*1000);
            $message -> set_data($template);
        }else{
            $message = new \IGtSingleMessage();
            $message -> set_isOffline(true);//是否离线
            $message -> set_offlineExpireTime(3600*12*1000);//离线时间
            $message -> set_data($template);//设置推送消息类型
        }
        return $message;
    }

    /**
     * 2016-8-2
     * 总的单推消息的接口
     * param1(推送消息)  :   ['title' => "通知标题",'content' => "通知内容" , 'payload' => "通知去干嘛这里可以自定义"]
     * param2(接收人)   :   ['cid' => "",'device_token' => "" , system=""]
     * */
    public function pushIGtMsg($msg , $to ){
        //1根据系统平台不同获得不同的推送消息
        if($to['system'] == 1){
            $message = $this -> getIOSMsg($msg);

            $this -> igt-> pushAPNMessageToSingle($this -> appId, $to['device_token'], $message);

        }else if($to['system'] == 2){
            $message = $this -> getAndroidMsg($msg);
            //2接收方
            $target = new \IGtTarget();
            $target->set_appId($this -> appId);

            $target->set_clientId($to['cid']); 
            //执行推送消息动作
            try {
                $this -> igt->pushMessageToSingle($message, $target);

            }catch(RequestException $e){
                $requstId =e.getRequestId();
                $this -> igt->pushMessageToSingle($message, $target,$requstId);
            }
        }
    }

    /**
     * 2016-8-2
     * 总的群推消息接口
     * param1(推送消息)  :   ['title' => "通知标题",'content' => "通知内容" , 'payload' => "通知去干嘛这里可以自定义"]
     * param2(接收人)    :   array(['cid' => "",'device_token' => "" , system=""])
     * */
    public function pushIGtMsgL($msg , $toList ){
        //0设置缓存
        $msgCache = [];
        $iosCache = [];
        $androidCache = [];

        //1获得消息
        foreach($toList as $to){
            if($to['system'] == 1){
                //IOS消息
                if(!$msgCache[$to['system']]){
                    $message = $this -> getIOSMsg($msg , true);
                    $msgCache[$to['system']] = $message;
                }
                $iosCache[] = $to['device_token'];

            }else if($to['system'] == 2){
                if(!$msgCache[$to['system']]){
                    $message = $this -> getAndroidMsg($msg , true);
                    $msgCache[$to['system']] = $message;
                }

                $target = new \IGtTarget();
                $target->set_appId($this -> appId);
                $target->set_clientId($to['cid']);
                $androidCache[] = $target;
            }
        }


        //2执行推动消息动作
        if(count($iosCache) > 0){
            $contentId = $this -> igt -> getAPNContentId($this -> appId , $msgCache[1]);
            $this -> igt -> pushAPNMessageToList($this -> appId , $contentId, $iosCache);
            //dump($rs1);
        }
        if(count($androidCache) > 0){
            $contentId = $this -> igt -> getContentId($msgCache[2]);
            $this -> igt -> pushMessageToList($contentId , $androidCache);
            //dump($rs2);
        }
    }

    /**
     * 2016-8-3
     * 获得用户状态
     * */
    public function getCidStatus($cid){
        $rs = $this -> igt -> getClientIdStatus($this -> appId , $cid);
        if('Online' == $rs['result']){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 2016-8-4
     * 本来想用pushIGtMsg发送LinkTemplate 但是因为IOS不支持发送带连接的消息，这就很尴尬了
     * 只能这样用了 ，类似于打电话那种方式
     * */
    public function pushLinkMsg($url , $to){
        $payload = ['action' => 'send' , 'url' => $url];
        //0获得该用户当前是否在线 （为android查的）
        $rs = $this -> igt -> getClientIdStatus($this -> appId , $to['cid']);
        //1根据系统平台不同获得不同的推送消息
        if($to['system'] == 1){
            //如果是IOS的情况下
            $msg = ['title' => '' , 'content' => '' , 'payload' => json_encode($payload)];
        }else if($to['system'] == 2){
            //如果是android的话，在线则需要发送非标准的透传消息
            if('Online' == $rs['result']){
                $msg = json_encode($payload);
            }else{
                $msg = ['title' => '' , 'content' => '' , 'payload' => json_encode($payload)];
            }
        }
        //2调用单推接口
        $this -> pushIGtMsg($msg , $to);
    }

    public function abc(){
        var_dump(123);
    }
}