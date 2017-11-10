<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

return [
    // +----------------------------------------------------------------------
    // | 应用设置
    // +----------------------------------------------------------------------
    // 应用命名空间
    // 'app_namespace'          => 'back',
    // 应用调试模式
    'app_debug'              => true,
    // 应用Trace
    'app_trace'              => false,
    // 应用模式状态
    'app_status'             => '',
    // 是否支持多模块
    'app_multi_module'       => true,
    // 入口自动绑定模块
    'auto_bind_module'       => false,
    // 注册的根命名空间
    'root_namespace'         => [],
    // 扩展函数文件
    'extra_file_list'        => [THINK_PATH . 'helper' . EXT],
    // 默认输出类型
    'default_return_type'    => 'html',
    // 默认AJAX 数据返回格式,可选json xml ...
    'default_ajax_return'    => 'json',
    // 默认JSONP格式返回的处理方法
    'default_jsonp_handler'  => 'jsonpReturn',
    // 默认JSONP处理方法
    'var_jsonp_handler'      => 'callback',
    // 默认时区
    'default_timezone'       => 'PRC',
    // 是否开启多语言
    'lang_switch_on'         => false,
    // 默认全局过滤方法 用逗号分隔多个

    'default_filter'         => 'trim',

    // 默认语言
    'default_lang'           => 'zh-cn',
    'default_charset'        => 'utf8',
    // 应用类库后缀
    'class_suffix'           => false,
    // 控制器类后缀
    'controller_suffix'      => false,


    // +----------------------------------------------------------------------
    // | 模块设置
    // +----------------------------------------------------------------------

    // 默认模块名
    'default_module'         => 'index',
    // 禁止访问模块
    'deny_module_list'       => ['common'],
    // 默认控制器名
    'default_controller'     => 'Index',
    // 默认操作名
    'default_action'         => 'index',
    // 默认验证器
    'default_validate'       => '',
    // 默认的空控制器名
    'empty_controller'       => 'Error',
    // 操作方法后缀
    'action_suffix'          => '',
    // 自动搜索控制器
    'controller_auto_search' => false,

    // +----------------------------------------------------------------------
    // | URL设置
    // +----------------------------------------------------------------------

    // PATHINFO变量名 用于兼容模式
    'var_pathinfo'           => 's',
    // 兼容PATH_INFO获取
    'pathinfo_fetch'         => ['ORIG_PATH_INFO', 'REDIRECT_PATH_INFO', 'REDIRECT_URL'],
    // pathinfo分隔符
    'pathinfo_depr'          => '/',
    // URL伪静态后缀
    'url_html_suffix'        => 'html',
    // URL普通方式参数 用于自动生成
    'url_common_param'       => true,
    // URL参数方式 0 按名称成对解析 1 按顺序解析
    'url_param_type'         => 0,
    // 是否开启路由
    'url_route_on'           => true,
    // 路由使用完整匹配
    'route_complete_match'   => false,
    // 路由配置文件（支持配置多个）
    'route_config_file'      => ['route'],
    // 是否强制使用路由
    'url_route_must'         => false,
    // 域名部署
    'url_domain_deploy'      => false,
    // 域名根，如thinkphp.cn
    'url_domain_root'        => '',
    // 是否自动转换URL中的控制器和操作名
    'url_convert'            => false,
    // 默认的访问控制器层
    'url_controller_layer'   => 'controller',
    // 表单请求类型伪装变量
    'var_method'             => '_method',
    // 表单ajax伪装变量
    'var_ajax'               => '_ajax',
    // 表单pjax伪装变量
    'var_pjax'               => '_pjax',
    // 是否开启请求缓存 true自动缓存 支持设置请求缓存规则
    'request_cache'          => false,
    // 请求缓存有效期
    'request_cache_expire'   => null,

    // +----------------------------------------------------------------------
    // | 模板设置
    // +----------------------------------------------------------------------

    'template'               => [
        // 模板引擎类型 支持 php think 支持扩展
        'type'         => 'Think',
        // 模板路径
        'view_path'    => '',
        // 模板后缀
        'view_suffix'  => 'html',
        // 模板文件名分隔符
        'view_depr'    => DS,
        // 模板引擎普通标签开始标记
        'tpl_begin'    => '{',
        // 模板引擎普通标签结束标记
        'tpl_end'      => '}',
        // 标签库标签开始标记
        'taglib_begin' => '{',
        // 标签库标签结束标记
        'taglib_end'   => '}',
        'tpl_cache'    => false,
    ],

    // 视图输出字符串内容替换
    'view_replace_str'       => [
        '_BACKSTAGE_' => '/public/static/backstage',
    ],
    // 默认跳转页面对应的模板文件
    'dispatch_success_tmpl'  => THINK_PATH . 'tpl' . DS . 'dispatch_jump.tpl',
    'dispatch_error_tmpl'    => THINK_PATH . 'tpl' . DS . 'dispatch_jump.tpl',

    // +----------------------------------------------------------------------
    // | 异常及错误设置
    // +----------------------------------------------------------------------

    // 异常页面的模板文件
    'exception_tmpl'         => THINK_PATH . 'tpl' . DS . 'think_exception.tpl',

    // 错误显示信息,非调试模式有效
    'error_message'          => '页面错误！请稍后再试～',
    // 显示错误信息
    'show_error_msg'         => false,
    // 异常处理handle类 留空使用 \think\exception\Handle
    'exception_handle'       => '',

    // +----------------------------------------------------------------------
    // | 日志设置
    // +----------------------------------------------------------------------

    'log'                    => [
        // 日志记录方式，内置 file socket 支持扩展
        'type'  => 'File',
        // 日志保存目录
        'path'  => LOG_PATH,
        // 日志记录级别
        'level' => [],
    ],

    // +----------------------------------------------------------------------
    // | Trace设置 开启 app_trace 后 有效
    // +----------------------------------------------------------------------
    'trace'                  => [
        // 内置Html Console 支持扩展
        'type' => 'Html',
    ],


    // 评论状态
    'comment_status' =>[
        'unpass' => -1,
        'pass'   => 1,
    ],


    
    // +----------------------------------------------------------------------
    // | 缓存设置
    // +----------------------------------------------------------------------

    'cache'                  => [
        // 驱动方式
        'type'   => 'File',
        // 缓存保存目录
        'path'   => CACHE_PATH,
        // 缓存前缀
        'prefix' => '',
        // 缓存有效期 0表示永久缓存
        'expire' => 10,
    ],

    // +----------------------------------------------------------------------
    // | 会话设置
    // +----------------------------------------------------------------------

    'session'                => [
        'id'             => '',
        // SESSION_ID的提交变量,解决flash上传跨域
        'var_session_id' => '',
        // SESSION 前缀
        'prefix'         => '91yos',
        // 驱动方式 支持redis memcache memcached
        'type'           => '',
        // 是否自动开启 SESSION
        'auto_start'     => true,
    ],

    // +----------------------------------------------------------------------
    // | Cookie设置
    // +----------------------------------------------------------------------
    'cookie'                 => [
        // cookie 名称前缀
        'prefix'    => '',
        // cookie 保存时间
        'expire'    => 0,
        // cookie 保存路径
        'path'      => '/',
        // cookie 有效域名
        'domain'    => '',
        //  cookie 启用安全传输
        'secure'    => false,
        // httponly设置
        'httponly'  => '',
        // 是否使用 setcookie
        'setcookie' => true,
    ],

    //分页配置
    'paginate'               => [
        'type'      => 'laypage',
        'var_page'  => 'page',
        'list_rows' => 10,
    ],

    // 发送给商家的状态
    'send_to_store' =>[      
        'default' => 0,//未推送
        'success' => 1,//已推送
    ], 

    //推送给财务的状态
    'send_finance_status' => [
        'default'         => 0, //默认推送给财务
        'send_finance'    => 1, //推送给财务
        'success_finance' => 2, //完成订单后后期申请返现推送给财务状态
    ],

   //来电类型
    'call_type' => [
        1 => '购墓', 
        2 => '价格',
        3 => '路线',
        4 => '班车',
        5 => '扫墓',
        6 => '电话',
        7 => '其它',
    ], 
    //安葬人状态
    'tomb_user_status'=>[
        1=>'寿穴',
        2=>'刚过世',
        3=>'迁坟',
        4=>'寄存中',
        5=>'其他',

    ],

    //事情紧急程度
    'degree'=>[
        1=>'高',
        2=>'中',
        3=>'低',
    ],

    //回访紧急程度
    'is_alive'=>[
        1=>'高',
        2=>'中',
        3=>'低',
    ],

    //财务票据类型
    'bill_type' => [
        'brokerage' => 1,//佣金   
        'cash'      => 2,//返现
        'back'      => 3,//退单
        '1'         =>'佣金票据',
        '2'         =>'返现票据',
        '3'         =>'退单票据',
    ],

    //订单来源
    'order_type'=>[
        1=>'400电话',
        2=>'商桥',
        3=>'手机',
        4=>'微信',
        5=>'自己预约',
        6=>'朋友推荐',

    ],
    
    //有意向程度
    'purpose'=>[
        0=>'有意向',
        1=>'无意向',
    ],


    //性别
    'tomb_sex'=>[
        0=>'男',
        1=>'女',
        2=>'保密',
    ],


      //订单状态
    'order_status' => [
        'fail'          => -1, //删除
        'default'       => 0,  //订单生成成功
        'ok'            => 1,  //购墓成功
        'interesting'   => 2,  //有意向
        'check_success' => 3,  //审核成功待收佣金
        'get_money'     => 4,  //收到佣金待返现
        'return_success'=> 5,  //返现成功
        'deposit'       => 6,  //有订金订单  
        'other'         => 7,  //其它咨询列表  
        'success'       => 10, //达成交易
        'stop'          => 11, //不允许退单
        'notvip'        => 12, //非会员订单
        'apply_back'    => 21, //申请退单待审核
        'allow'         => 22, //申请退单审核通过
        'back_success'  => 30, //退单完成
        'no_relation'   => 31, //购墓成功与平台无关
    ],

    //订单转换汉字
    'order_status_change'=>[
        -1 => '删除',
         0 =>'预约',
         1 =>'购墓成功',
         2 =>'有意向',
         3 =>'待收佣金',
         4 =>'待返现',
         5 =>'返现成功',
         6 =>'有订金订单',
         7 => '其他订单', 
         10=>'订单完成',
         11=>'不允许退单',
         12=>'非会员订单',
         21=>'待审核',
         22=>'审核通过',
         30=>'退单完成',
         31=>'平台无关',
    ],



    //订单中发送给财务的状态
    'send_to_finance'=>array(
        'default'         => 0, //默认状态
        'success'         => 1, //发送给财务
        'success_finance' => 2, //完成订单后后期申请返现推送给财务状态
    ),

    //订单中的返现状态
    'apply_return_status' => array(
        'default_status'         => 0, //0默认不需要，
        'need_return_status'     => 1, //1需要申请
        'ok_check_return_status' => 2, //2审核通过
        'stop'                   => 6, //审核不通过
    ),
    
    'department_type'      => 0, //role表中部门的标识
    'role_type'            => 1, //role表中角色（职位）的标识
    'operate_id'           => array(19), //运营部门ID
    'business_id'          => 9,   //商务部ID
    
    'a_prefix' => 'sm91',//前端密码前缀
    'a_suffix' => 'hgy',//后端密码后缀

    'page_size'            => 10,  //分页数
	
	'admin_name'           =>'admin',//超级管理员的账号

    'h_prefix'          => 's91m',   //前端密码前缀
    'h_suffix'          => 'hgysm',  //前端密码后缀

    //不需要验证权限的方法
    'no_auth_path' => array(
        'index/Index/shouye',
    ),

    //商家相册,相册类型
    'store_img_scenery' => 2,  //景观
    'store_img_qualification' => 5, //资质
    
    'store_image'       => 'Store',    //商家上传图片的路径
    'profiles_image'    => 'Profiles', //合同档案上传图片路径
    'celebrity_image'   => 'CelebrityCemetery', //名人墓地图片途径
    'tombs_image'       => 'Tombs',    //墓地图片路径
    'plugin_image'      =>  'Plugin',  //车辆管理图片
    'order_bill'        => 'OrderBill', //订单票据
    'combo_image'       => 'Combo',// 礼仪套餐图片
    'news_image'        =>  'News',     //新闻图片地址
    'banner_image'      =>   'Banner',  //广告图片地址     
    'ecology_image'      =>   'Ecology',  //生态葬葬式地址     
    
    /************ 图片相关的配置 *********************/
    'max_size'   =>  '3M',
    'ext'       =>  "jpg,jpeg,pjpeg,gif,png,bmp",
    'root_path'  => '/public/uploads/',
    'public_path' => '/public',
    'img_host'   =>  '/uploads',
    'ctrack_excel' => 'CTrack',//评论导入数据


    //图片水印参数
    'water_southeast' => 8, //水印位置
    'water_pic_path'  => './public/water_logo_small_n.png', //水印图片
    'water_pic_thumb_path'  => './public/water_logo_thumb.png', //缩略图水印图片

    /**
     * 1左上      2上居中   3右上位置
     * 4左居中    5居中     6右居中
     * 7左下      8下居中   9右下
     */
    // 商家图片尺寸
    'store_image_size' => [
        'original' => '546 * 296',// 原图
        'thumb' => [
            ['294','158','6'],
            ['250','132','6']
        ],
    ],

    // 套餐图片尺寸
    'combo_image_size' => [
        'original' => '400 * 238',// 原图
        'thumb' => [
            ['264','242','6'],
            ['80','74','6']
        ],
    ],

    // 礼仪公司图片尺寸
    'etiquette_image_size' => [
        'original' => '380 * 268',// 原图
        'thumb' => [
            ['250','176','6'],
            ['150','150','6']
        ],
    ],

    // 套餐图片尺寸
    'tombs_image_size' => [
        'original' => '400 * 238',
        'thumb' => [
            ['264','242','6']
        ],
    ],

    //文件上传相关配置
    'exts'       =>  "xls, xlsx",


    //地区相关
    'china_num'  => 2,
   // 'china_MSG'  => '全国',
    //'china_abbr' => 'ALL',
    
    // 直辖市
    'municipalities' => ['3','22','41','61'],
    
    'normal_status'       => 1,  //正常状态
    'default_status'      => 0,  //默认状态
    'delete_status'       => -1, //删除数据

    //陵园殡仪馆分类ID
    'category_cemetery_id' => 37,  //陵园分类ID
    'category_funeral_id'  => 38,  //殡仪馆分类ID
    'category_etiquette_id' => 43,// 礼仪公司分类id
    'category_group_id'    => 71,  //集团分类ID
    'category_store_id'    => 27,  //店铺分类ID

    //商家会员标识
    'store_member'            => 20, //商家会员优惠
    'store_member_v'          => 19, //商家会员无优惠只显示会员V
    'store_member_person'     => 16, //个人会员
    'store_member_ad'         => 14, //广告会员
    'store_member_msg'        => '商家',
    'store_member_v_msg'      => '会员V',
    'store_member_person_msg' => '个人',
    'store_member_ad_msg'     => '广告',
    
    'profiles_electron_image' => 1,//电子合同 
    'profiles_price_image' => 2,//价格图片


    'category_tombs' => 40, //墓位分类ID
    'ecology_tombs'  => 126,// 生态葬分类id

    //墓位单位
    'tombs_unit' => [
        0 => '万/平米',
        1 => '万'
    ],

    //殡仪馆等级
    'funeral_level' => [
        1 => '一级',
        2 => '二级',
        3 => '三级',
        4 => '四级',
        5 => '五级',
        6 => '六级'
    ],

    // 是否有车
    'have_car' => [
        1 => '91班车',
        2 => '陵园班车',
        3 => '自驾',
        4 => '公交地铁'
    ],

    //车辆管理车辆分类
    'car_type' =>[
        1 => '自购',
        2 => '客户'
    ],

    
    //看墓记录客户意向
    'view_tomb_intention' =>[
        1 => '一般',
        2 => '非常好',
        3 => '很好',
        4 => '不错',
        5 => '不太好',
        6 => '不好',
        7 => '非常不好',
    ],
    
    //看墓交通工具
    'view_tomb_vehicle' =>[
        1 => '91班车',
        2 => '自驾',
        3 => '陵园班车',
        4 => '公交地铁',
    ],
    //看墓记录中是否成交
    'view_tomb_result' =>[
        1 => '成交',
        2 => '未成交'
    ],
    'view_tomb_result_msg' =>[
        'success' => 1,
        'fail'    =>2
    ],

    //cookie生存时间
    'life_time' => 12*3600,

    // 约车状态
    'appoint_car_status' => [
        'wait'     => 0,
        'underway' => 1,
        'finish'   => 10,
    ],

     //前台订单状态
    'appoint_status' =>array(
        'waiting' => 0,  //待处理
        'on_load' => 1,  //处理中
        'refuse'  => 2,  //拒绝
        'success' => 5,  //成功
        'del'     => -1, //删除
    ),


    //平台短信类型（注册、预约、发送给平台的配置在前台）
    'msg_customer'        => 1, //订单短信发送给客户的短信
    'msg_cemetery'        => 2, //订单发给给陵园的短信
    'msg_return_success'  => 5, //返现成功发送给客户的短信
    'msg_send_status'     => 1, //短信发送成功的标识，写入短息记录中

    // 支付方式
    'pay_type' => [
        '1'  =>'工商银行',
        '2'  =>'农业银行',
        '3'  =>'中国银行',
        '4'  =>'建设银行',
        '5'  =>'交通银行',
        '6'  =>'邮政储蓄银行',
        '7'  =>'中信银行',
        '8'  =>'光大银行',
        '9'  =>'华夏银行',
        '10' =>'民生银行',
        '11' =>'广发银行',
        '12' =>'深圳发展银行',
        '13' =>'招商银行',
        '14' =>'兴业银行',
        '15' =>'浦发银行',
        '16' =>'平安银行',
        '17' =>'恒丰银行',
        '18' =>'渤海银行',
        '19' =>'浙商银行',
        '20' =>'北京银行',
        '21' =>'上海银行',
        '22' =>'支付宝',
        '23' =>'微信钱包',
    ],


    //客户银卡卡默认状态
    'bank_status' => [
        '1'  =>'是',
        '0'  =>'否',
    ],

    
    //订单票据类型
    'order_bill_type' =>[
        1 => '佣金票据',
        2 => '返现票据',
        3 => '退单票据',
        4 => '购墓票据'
    ],
    
    'bill_type' => [
        'brokerage' => 1,//佣金   
        'cash'      => 2,//返现
        'back'      => 3,//退单
        'buy'       => 4,//购墓
    ],

    //票据状态
    'audit_status' => [
        'default'    => 0,  //默认状态
        'wait_check' => 20, //待审核
        'success'    => 21, //成功
        'fail'       => 22, //失败
    ],
 
    //殡葬百科
    'article'                            => 134,//殡葬百科
    'article_91_information'             => 135, //91资讯   一级分类
    'article_91_headline'                => 136, //91头条
    'article_com_culture'                => 137, //企业软文
    'article_industry_information'       => 138, //业界动态  一级分类
    'article_industry_dynamic'           => 139, //行业新闻
    'article_laws_regulations'           => 140, //政策法规
    'article_fengshui_culture'           => 141, //风水文化  一级分类
    'article_sense'                      => 142, //白事常识  一级分类
    'article_folk_culture'               => 143, //民俗文化  一级分类
    'article_traditional_festival'       => 144, //传统节日
    'article_sacrifice_custom'           => 145, //祭祀习俗  
    'article_burial_custom'              => 146, //丧葬习俗  
    'article_life_story'                 => 147, //生命礼赞  一级分类
    'article_life_sentiment'             => 148, //人生感悟
    'article_cemetry_story'              => 149, //陵园故事  
    'article_lucky_celebrity'            => 150, //福地名人  
    'article_kg_culture'                 => 151, //考古文化  一级分类
    //帮助中心
    'helpcenter'                         => 152, //帮助中心
    'article_noticeoftomb'               => 153, //购墓须知
    'article_flower'                     => 154, //鲜花祭品
    'article_zomb'                       => 155, //三大鬼节
    'article_qianfen'                    => 156, //迁坟讲究
    
    
    
    //banner类型
    'banner_type' => [
        '1' => 'PC',
        '2' => '手机端',
        '3' => '手机端购墓须知',
        '4' => '手机端墓地风水',
    ],
    //商家推荐(位置)设置
    'recommend_location' =>[
        1 => '风景优美',
        2 => '大家都在看',
        3 => '风水优越',
    ],

    //SEO 类型
    'seo_type' =>[
        'cemetery_home' => 1, //陵园首页 
        'cemetery_list' => 2, //陵园列表页
        'news_home'     => 4, //新闻首页
        'news_class'    => 5, //新闻分类
        'funeral_list'  => 8, //殡仪馆列表
        'funeral_liyi'  => 10, //殡仪礼仪
    ],

    'finance_seo' =>[
        10 => '首页',
        11 => '找项目',
    ],

    'save_log_open'    => 1, //开启后台日志记录


      //email标记信息
    'business_email_msg' =>[
        '1' => '所有邮件（超管）',
        '4' => '91乐融留言',
        '5' => '陵园合作申请',
        '6' => '预约看墓',
        '7' => '预约服务商',
    ],

     // 邮箱配置
    'mail_smtp'     => true,  
    'mail_host'     => 'smtp.exmail.qq.com',//smtp服务器的名称
    'mail_smtpauth' => true, //启用smtp认证
    'mail_username' => 'Notice@huigeyuan.com', //发件人邮箱名
    'mail_password' => 'Hgy123',//发件人邮箱密码
    'mail_secure'   => 'tls',
    'mail_charset'  => 'utf-8',//设置邮件编码
    'mail_ishtml'   => true, // 是否HTML格式邮件
    'mail_fromname' => '卉格苑(北京)科技有限公司',//发件人姓名


     //热门陵园分数
    'hot_score' => [
        'appoint'    => 1,  //预约、有意向订单
        'wait_money' => 5, //交订金、待审核、待收佣金
        'success'    => 30, //已收佣金、已经返现、完成
    ],

    //热门陵园分数
    'ecology_type' => [
        '1' => '花坛葬',
        '2' => '草坪葬',
        '3' => '树葬',
        '4' => '壁葬',
        '5' => '海葬',
    ],

    //用户注册类型
    'member_types'=>[
        '1' => '前台注册',
        '2' => '平台注册',
        '3' => 'WAP',
        '4' => '微信',
        '9' => '找资金申请人',

    ],

      //服务套餐分类
    'combo_type' =>[
        1 =>  '殡仪服务',
        2 =>  '葬仪服务',
        3 =>  '祭祀服务',
    ],
   


      //跟踪信息邮件发送配置
    'order_follow_email'=>[
        '649106883@qq.com',
        '1343467494@qq.com',
    ],

    'money_user_category_id' => 90, //资金用途分类ID


    //前台搜索框type类型
    'search_type'=>[
        1  => '全局搜索',
        37 => '陵园',
        38 => '殡仪馆',
        43 => '殡仪服务',
        // 134 => '新闻',
    ],

    'category_web_guide' => 106, //网址导航分类ID  
    'web_guide_image'    => 'WebGuide', //专题图片上传路径


     //碑文挽联
    'wanlian'=>[
        '1'=>'祖父母',
        '2'=>'外祖父母',
        '3'=>'父母',
        '4'=>'岳父母',
        '5'=>'叔伯',
        '6'=>'娘舅姨妈',
        '7'=>'丈夫妻子',
        '8'=>'朋友同事',
        '9'=>'子侄外甥',
        '10'=>'恩师同窗',
    ],


    //我的工作任务
    'mywork'=>[
        '1'=>'购墓预约订单',
        '2'=>'前台购墓留言订单',
        '3'=>'前台服务留言订单',
        '4'=>'陵园合作申请',
        '5'=>'服务预约订单',
    ],

     //资金注入记录审批人
    'approver_name' =>[ 
        4  => '翟洪亮',
        17 => '刘鹏',
    ],

    'xml_url' => [
        0 => 'http://www.91soumu.com/help/history.html',
        1 => 'http://www.91soumu.com',
        2 => 'http://www.91soumu.com/article.html',
        3 => 'http://www.91soumu.com/sitemap.html',
    ],

    //商务数据跟踪意向类型
    'intention_type' => [
        1 => '意向',
        2 => '潜在',
        3 => '未知',
        4 => '无意向',
    ],
    // 系统情况
    'is_system' => [
        1 => '没有',
        2 => '有',
        3 => '不确定',
    ],

    // 商家类型
    'business_type' => [
        37 => '陵园',
        38 => '殡仪馆',
    ],

    'business_depart'    => 16, //商务部ID
    'bj_software_depart' => 30,// 北京软件部

    'track_excel' => 'BTrack',//商务数据导入跟踪
];
