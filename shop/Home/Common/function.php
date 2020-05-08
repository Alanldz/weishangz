<?php

/**
 * TODO 基础分页的相同代码封装，使前台的代码更少
 * @param $m 模型，引用传递
 * @param $where 查询条件
 * @param int $pagesize 每页查询条数
 * @return \Think\Page
 */
//h后台分页
function getpage(&$m, $where, $pagesize = 10)
{
    $m1 = clone $m;//浅复制一个模型
    $count = $m->where($where)->count();//连惯操作后会对join等操作进行重置
    $m = $m1;//为保持在为定的连惯操作，浅复制一个模型
    $p = new Think\Page($count, $pagesize);
    $p->lastSuffix = false;
    $p->setConfig('theme', '%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END% %HEADER%');
    $p->setConfig('prev', '上一页');
    $p->setConfig('next', '下一页');
    $p->setConfig('last', '末页');
    $p->setConfig('first', '首页');
    $p->parameter = I('get.');

    $m->limit($p->firstRow, $p->listRows);
    return $p;
}

//前台分页
function Fgetpage(&$m, $where, $pagesize = 10)
{
    $m1 = clone $m;//浅复制一个模型
    $count = $m->where($where)->count();//连惯操作后会对join等操作进行重置
    $m = $m1;//为保持在为定的连惯操作，浅复制一个模型
    $p = new Think\Page($count, $pagesize);
    $p->lastSuffix = false;
//    $p->setConfig('theme','%FIRST% %UP_PAGE% %LINK_PAGE% %DOWN_PAGE% %END% %HEADER%');
    $p->setConfig('theme', '%FIRST% %UP_PAGE%  %DOWN_PAGE% %END% %HEADER%');
    $p->setConfig('prev', '上一页');
    $p->setConfig('next', '下一页');
    $p->setConfig('last', '末页');
    $p->setConfig('first', '首页');
    $p->parameter = I('get.');

    $m->limit($p->firstRow, $p->listRows);
    return $p;
}

//生成唯一订单
function build_order_no()
{
    $no = 'PAY' . date('Ymd') . substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);
    //检测是否存在
    $db = M('trans', 'ysk_');
    $count = $db->where(array('pay_no' => $no))->count(1);
    ($count > 0) && $no = build_order_no();
    return $no;
}

//生成唯一订单
function build_wallet_add()
{
    // 密码字符集，可任意添加你需要的字符
    $chars = 'abcdefghijklmuvwxyzABCDNOPQRSTUVWXYZ0123456';
    $password = "";
    for ($i = 0; $i < 34; $i++) {
        $password .= $chars[mt_rand(0, strlen($chars) - 1)];
    }
    //检测是否存在
    $db = M('user', 'ysk_');
    $count = $db->where(array('wallet_add' => $password))->count(1);
    ($count > 0) && $no = build_wallet_add();
    return $password;
}

/**
 * 检测用户是否登录
 * @return integer 0-未登录，大于0-当前登录用户ID
 * @author jry <598821125@qq.com>
 */
function user_login()
{
    return D('Home/user')->user_login();
}

function get_userid()
{
    $userid = session('userid');
    return $userid;
}

//交易状态
function SellStatus($value, $id)
{
    $arr = array(
        0 => "交易中...<a href='javascript:' onclick='quitsell(this)' data='" . $id . "'   url='" . U('Trading/QuitSell') . "' style='color:#de1414;'>取消交易</a>",
        1 => "<a href='javascript:' onclick='suresell(this)' data='" . $id . "'   url='" . U('Trading/SureSell') . "' style='color:#0000aa;'>确认收款</a>",
        2 => '交易成功',
        3 => '已取消交易',
    );
    return $arr[$value];
}

function BuyStauts($value, $id)
{
    //0-出售成功 1-买家确认  2-买家确认 3-取消交易
    $arr = array(
        0 => "<a href='javascript:' onclick='surebuy(this)' data='" . $id . "'   url='" . U('Trading/SureBuy') . "' style='color:#0000aa'>确认购买</a> ",
        1 => '等待卖家确认收款',
        2 => '交易成功',
        3 => '已取消交易',
    );
    return $arr[$value];
}

/**
 * [caimin_state 判断能否采矿]
 * @param int $fid [好友ID]
 * @param null $type
 * @return bool|string
 */
function caimin_state($fid, $type = null)
{
    if (empty($fid)) {
        return false;
    }

    $level = I('type');//二代
    if ($level == 'two') {
        $count = D('User')->where(array('pid' => get_userid()))->count(1);
        if ($count < 10) {
            $type != null ? $str = '<span>不可采矿</span>' : $str = false;
            return $str;
        }
    }

    $store = M('store');
    $where['uid'] = $fid;
    $where['sign_time'] = date('Ymd');
    $userid = get_userid();
    $where["caimi_fids"] = array('NOTLIKE', '%,' . $userid . ',%');
    $count = $store->where($where)->count(1);
    if ($count == 1) {
        $type != null ? $str = '<span class="red" data="' . $fid . '"  >采矿</span>' : $str = true;
    } else {
        $type != null ? $str = '<span>不可采矿</span>' : $str = false;
    }
    return $str;
}

//用户等级
function user_level($level)
{
    $arr = array(
        0 => '普通用户',
        1 => '青铜农主',
        2 => '白金农主',
        3 => '水晶农主',
        4 => '宝石农主',
        5 => '钻石农主',
        6 => '皇冠农主',
        7 => '金牌代理',
        8 => '平台站长',
        9 => '领袖站长',
    );
    return $arr[$level];
}

function AddUserLevel($uid)
{
    $where['uid'] = $uid;
    //分享人数
    $table = M('user_level');
    $info = $table->where($where)->field('children_num,land_num,user_level')->find();
    $children_count = $info['children_num'];
    $land_count = $info['land_num'];

    if ($land_count >= 1) {
        $level = 1;
    }
    if ($land_count >= 10 && $children_count >= 10) {
        $level = 2;
    }
    if ($land_count >= 15 && $children_count >= 20) {
        $level = 3;
    }
    if ($land_count >= 15 && $children_count >= 30) {
        $level = 4;
    }
    if ($land_count >= 15 && $children_count >= 40) {
        $level = 5;
    }
    if ($land_count >= 15 && $children_count >= 60) {
        $level = 5;
    }
    //低等级才修改
    if ($level && $info['user_level'] < $level) {
        $table->where($where)->setField('user_level', $level);
    }
}

/**
 * [SearchDate 获取上周的还是时间和结束时间]
 */
function SearchDate()
{
    $date = date('Y-m-d');  //当前日期
    $first = 1; //$first =1 表示每周星期一为开始日期 0表示每周日为开始日期
    $w = date('w', strtotime($date));  //获取当前周的第几天 周日是 0 周一到周六是 1 - 6
    $now_start = date('Y-m-d', strtotime("$date -" . ($w ? $w - $first : 6) . ' days')); //获取本周开始日期，如果$w是0，则表示周日，减去 6 天
    $last_start = strtotime("$now_start - 7 days");  //上周开始时间
    $last_end = strtotime("$now_start - 1 days");  //上周结束时间
    //获取上周起始日期
    $arr['week_start'] = $last_start;
    $arr['week_end'] = strtotime($now_start);//本周开始时间,即上周最后时间
    return $arr;
}

function img_uploading($path_old = null)
{
    $images_path = './Uploads/';
    if (!is_dir($images_path)) {
        mkdir($images_path);
    }

    $upload = new \Think\Upload();// 实例化上传类
    $upload->maxSize = 3145728;// 设置附件上传大小
    $upload->exts = array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型
    $upload->rootPath = $images_path; // 设置上传根目录    // 上传文件
    $upload->savePath = ''; // 设置上传子目录    // 上传文件
    $info = $upload->upload();
    dump($info);
    echo 132;
    exit;
    if (!$info) {// 上传错误提示错误信息
        $res['status'] = 0;
        $res['res'] = $upload->getError();
    } else {// 上传成功
        foreach ($info as $file) {
            $img_path = $file['savepath'] . $file['savename'];
        }
        //上传成功后删除原来的图片
        if ($path_old && $img_path) {
            unlink('.' . $path_old);
            // echo '删除成功';
        }
        $res['status'] = 1;
        $res['res'] = '/Uploads/' . $img_path;
    }
    return $res;
}

function getCode()
{
    return rand(100000, 999999);
}

function check_code($value, $send_email)
{
    $time = session('set_time');
    $email = session('user_email');
    $code = session('sms_code');
    if (time() - $time > 600 || $code != $value || $code == '' || $email != $send_email) {
        return false;
    }
    return true;
}


#+++++++判断是否开满地++++++#
function is_all_land($uid)
{
    if (empty($uid))
        $uid = session('userid');

    $nzusfarm = M('nzusfarm');
    $where['uid'] = $uid;
    $where['status'] = 0;
    $count = $nzusfarm->where($where)->count(1);
    // 已开满
    if ($count == 0) {
        return true;
    } else { // 如果未开满地
        return false;
    }
}

#++++++判断是否已经开打+++++++
function no_land()
{
    $uid = session('userid');
    $nzusfarm = M('nzusfarm');
    $where['uid'] = $uid;
    $where['farm_type'] = array('neq', 4);
    $where['status'] = array('neq', 0);
    $count = $nzusfarm->where($where)->count(1);
    // 未开地
    if ($count == 0) {
        return true;
    } else { //已开地
        return false;
    }
}

function get_huafei()
{
    $userid = session('userid');
    $where['uid'] = $userid;
    $huafei_num = M('user_huafei')->where($where)->getField('huafei_num');
    return $huafei_num;
}

function Loginmsg($mobile)
{
    $mobile = safe_replace($mobile);
    if (empty($mobile)) {
        $mes['status'] = 0;
        $mes['message'] = '手机号码不能为空';
    }

    if (time() > session('set_time') + 60 || session('set_time') == '') {
        session('set_time', time());
        $code = getCode();
        $sms_code = sha1(md5(trim($code) . trim($mobile)));
        session('sms_code', $sms_code);
        //发送短信
//        $content="您本次的验证码为".$code."，请在5分钟内完成验证，验证码打死也不要告诉别人哦！";//要发送的短信内容
        $where['mobile|userid'] = $mobile;
        $user_mobile = M('user')->where($where)->getField('mobile');
        $res = newMsg($user_mobile, $code);
        if ($res == 0) {
            $mes['status'] = 1;
            $mes['message'] = '短信发送成功';
            return $mes;
        } else {
            $mes['status'] = 0;
            $mes['message'] = '短信发送失败';
            return $mes;
        }
    } else {
        $msgtime = session('set_time') + 60 - time();
        $data = $msgtime . '秒之后再试';
        $mes['status'] = 0;
        $mes['message'] = $data;
        return $mes;
    }
}

// 发送短信验证
function sendMsg($mobile)
{
    $mobile = safe_replace($mobile);
    if (empty($mobile)) {
        $mes['status'] = 0;
        $mes['message'] = '手机号码不能为空';
    }

    if (time() > session('set_time') + 60 || session('set_time') == '') {
        session('set_time', time());
        $user_mobile = $mobile;
        $code = getCode();
        $sms_code = sha1(md5(trim($code) . trim($mobile)));
        session('sms_code', $sms_code);
        //发送短信
//        $content="您本次的验证码为".$code."，请在5分钟内完成验证，验证码打死也不要告诉别人哦！";//要发送的短信内容
        $res = newMsg($user_mobile, $code);
        // var_dump($res);die;
        if ($res == 0) {
            $mes['status'] = 1;
            $mes['message'] = '短信发送成功';
            return $mes;
        } else {
            $mes['status'] = 0;
            $mes['message'] = '短信发送失败';
            return $mes;
        }
    } else {
        $msgtime = session('set_time') + 60 - time();
        $data = $msgtime . '秒之后再试';
        $mes['status'] = 0;
        $mes['message'] = $data;
        return $mes;
    }
}

/**
 * 卖出短信提示
 */
function sellSendMsg($mobile, $tpl_id = '')
{
    $mobile = safe_replace($mobile);
    if (!empty($mobile)) {
        if (time() > session('set_time' . $mobile) + 600 || session('set_time' . $mobile) == '') {
            session('set_time' . $mobile, time());
            $user_mobile = $mobile;
            $code = getCode();
            $sms_code = sha1(md5(trim($code) . trim($mobile)));
            session('sms_code', $sms_code);
            //发送短信
//        $content="您本次的验证码为".$code."，请在5分钟内完成验证，验证码打死也不要告诉别人哦！";//要发送的短信内容
            $res = newMsg($user_mobile, $code, $tpl_id);
            if ($res == 0) {
                $mes['status'] = 1;
                $mes['message'] = '短信发送成功';
                return $mes;
            } else {
                $mes['status'] = 0;
                $mes['message'] = '短信发送失败';
                return $mes;
            }
        }
    }
}

// 短信发送接口
function newMsg($mobile, $code, $tpl_id = '')
{
    /*
    ***聚合数据（JUHE.CN）短信API服务接口PHP请求示例源码
    ***DATE:2015-05-25
*/
    header('content-type:text/html;charset=utf-8');

    $sendUrl = 'http://v.juhe.cn/sms/send'; //短信接口的URL
    // $content=M('config','nc')->where(array('name'=>'MSG'))->getField('value');
    $key = M('config', 'nc')->where(array('name' => 'MSG_account'))->getField('value');
    if ($tpl_id == '') {
        $tpl_id = M('config', 'nc')->where(array('name' => 'MSG_password'))->getField('value');
    }
    $contents = '#code#=' . $code;//要发送的短信内容
    $smsConf = array(
        'key' => $key, //您申请的APPKEY
        'mobile' => $mobile, //接受短信的用户手机号码
        'tpl_id' => $tpl_id, //您申请的短信模板ID，根据实际情况修改
        'tpl_value' => urldecode($contents), //您设置的模板变量，根据实际情况修改
    );

    $content = juhecurl($sendUrl, $smsConf, 1); //请求发送短信

    if ($content) {
        $result = json_decode($content, true);
        $error_code = $result['error_code'];
        if ($error_code == 0) {
            //状态为0，说明短信发送成功
            // echo "短信发送成功,短信ID：".$result['result']['sid'];
            $mes = 0;
        } else {
            //状态非0，说明失败
            $mes = 1;
            // $msg = $result['reason'];
            // echo "短信发送失败(".$error_code.")：".$msg;
        }
    } else {
        //返回内容异常，以下可根据业务逻辑自行修改
        // echo "请求发送短信失败";
        $mes = 1;
    }
    return $mes;
//     $url='http://smssh1.253.com/msg/send/json';
//     // $content=M('config','nc')->where(array('name'=>'MSG'))->getField('value');
//     $account=M('config','nc')->where(array('name'=>'MSG_account'))->getField('value');
//     $password=M('config','nc')->where(array('name'=>'MSG_password'))->getField('value');
//     $content="你的验证码是".$code."，如非本人操作，请忽略本短信";//要发送的短信内容

//     //创蓝接口参数
//     $postArr = array (
//         'account'  =>  $account,
//         'password' => $password,
//         //百星接口
// //        'account'  =>  'N541103_N4745204',
// //        'password' => 'dYA7mCh18s4041',
//         'msg' => urlencode($content),
//         'phone' => $mobile,
//         'report' => 'true'
//     );
//     $result = curlPost($url, $postArr);
//     if(!is_null(json_decode($result))){
//         $output=json_decode($result,true);
//         if(isset($output['code'])  && $output['code']=='0'){
//             $mes=0 ;
//         }else{
//             $mes=1;
//         }
//     }else{
//         $mes=1;
//     }
//     return $mes;
}

/**
 * 请求接口返回内容
 * @param  string $url [请求的URL地址]
 * @param  string $params [请求的参数]
 * @param  int $ipost [是否采用POST形式]
 * @return  string
 */
function juhecurl($url, $params = false, $ispost = 0)
{
    $httpInfo = array();
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 5.1) AppleWebKit/537.22 (KHTML, like Gecko) Chrome/25.0.1364.172 Safari/537.22');
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    if ($ispost) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_URL, $url);
    } else {
        if ($params) {
            curl_setopt($ch, CURLOPT_URL, $url . '?' . $params);
        } else {
            curl_setopt($ch, CURLOPT_URL, $url);
        }
    }
    $response = curl_exec($ch);
    if ($response === FALSE) {
        //echo "cURL Error: " . curl_error($ch);
        return false;
    }
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $httpInfo = array_merge($httpInfo, curl_getinfo($ch));
    curl_close($ch);
    return $response;
}

function setmyCode($mobile, $msg)
{
    $url = "http://service.winic.org:8009/sys_port/gateway/index.asp?";
    $data = "id=%s&pwd=%s&to=%s&content=%s&time=";
    $id = 'yxnongchang';
    $pwd = '123456web';
    $to = $mobile;
    $content = iconv("UTF-8", "GB2312", $msg);
    $rdata = sprintf($data, $id, $pwd, $to, $content);


    $ch = curl_init();
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $rdata);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $result = curl_exec($ch);
    curl_close($ch);
    $result = substr($result, 0, 3);
    if ($result == '000') {
        return true;
    } else {
        return false;
    }
}


//验证短信验证码
function check_sms($code, $mobile)
{
    $md5_code = sha1(md5(trim($code) . trim($mobile)));
    $set_time = session('set_time');
    $sms_code = session('sms_code');
    if (time() - $set_time <= 300 && $code != '' && $md5_code == $sms_code) {
        $res = true;
    } else {
        $res = false;
    }
    return $res;
}

//验证邮箱验证码
function check_email($code, $email, $sessionName)
{
    $md5_code = sha1(md5(trim($code) . trim($email)));
    $sms_code = session($sessionName);
    if ($code != '' && $md5_code == $sms_code) {
        $res = true;
        session($sessionName, null);
    } else {
        $res = false;
    }
    return $res;
}


function curlPost($url, $postFields)
{
    $postFields = json_encode($postFields);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json; charset=utf-8'
        )
    );
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    $ret = curl_exec($ch);
    if (false == $ret) {
        $result = curl_error($ch);
    } else {
        $rsp = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if (200 != $rsp) {
            $result = "请求状态 " . $rsp . " " . curl_error($ch);
        } else {
            $result = $ret;
        }
    }
    curl_close($ch);
    return $result;
}


//是否开启交易
function IsTrading($id)
{
    return D('config')->getById($id);
}

function add_seed($num, $uid)
{

    $table = M('user_seed');
    $where['uid'] = $uid;
    $count = $table->where($where)->count(1);
    if ($count == 0) {
        $data['uid'] = $uid;
        $data['zhongzi_num'] = $num;
        return $table->where($where)->add($data);
    }

    return $table->where($where)->setInc('zhongzi_num', $num);
}

//获取当前用户的父级
function parent_account()
{
    $userid = session('userid');
    $user = D('User');
    $pid = $user->where(array('userid' => $userid))->getField('pid');
    $account = $user->where(array('userid' => $pid))->getField('account');
    if ($account)
        return $account;
    else
        return '无';
}

//数字只显示两位
function Showtwo($nums)
{
    $nums = floor($nums * 100) / 100;
    return $nums;
}

//判断当日是否有公告
function checkReadeNews()
{
    $starTime = date('Y-m-d');
    $endTime = date("Y-m-d", strtotime("+1 day"));
    $todayStarTime = strtotime($starTime);
    $todayEndTime = strtotime($endTime);
    $news_where['create_time'] = array(array('egt', $todayStarTime), array('lt', $todayEndTime));
    return M('news')->where($news_where)->count();
}

/**
 * 发送短信
 */
function sendMessage($mobile, $tpl_id, $contents = '')
{
    $mobile = safe_replace($mobile);
    if (empty($mobile)) {
        $mes['status'] = 0;
        $mes['message'] = '手机号码不能为空';
    }

    header('content-type:text/html;charset=utf-8');
    $sendUrl = 'http://v.juhe.cn/sms/send'; //短信接口的URL
    $key = M('config', 'nc')->where(array('name' => 'MSG_account'))->getField('value');
    $smsConf = array(
        'key' => $key, //您申请的APPKEY
        'mobile' => $mobile, //接受短信的用户手机号码
        'tpl_id' => $tpl_id, //您申请的短信模板ID，根据实际情况修改
        'tpl_value' => urldecode($contents), //您设置的模板变量，根据实际情况修改
    );

    $content = juhecurl($sendUrl, $smsConf, 1); //请求发送短信

    if ($content) {
        $result = json_decode($content, true);
        $error_code = $result['error_code'];
        if ($error_code == 0) {
            //状态为0，说明短信发送成功
            // echo "短信发送成功,短信ID：".$result['result']['sid'];
            $status = 0;
        } else {
            //状态非0，说明失败
            $status = 1;
            // $msg = $result['reason'];// echo "短信发送失败(".$error_code.")：".$msg;
        }
    } else {
        //返回内容异常，以下可根据业务逻辑自行修改    // echo "请求发送短信失败";
        $status = 1;
    }

    if ($status == 0) {
        $mes['status'] = 1;
        $mes['message'] = '短信发送成功';
        return $mes;
    } else {
        $mes['status'] = 0;
        $mes['message'] = '短信发送失败';
        return $mes;
    }
}

/**
 * 验证是否是邮箱
 * @param $str
 * @return bool
 */
function verifyEmail($str)
{
    //@前面的字符可以是英文字母和._- ，._-不能放在开头和结尾，且不能连续出现
    $pattern = '/^[a-z0-9]+([._-][a-z0-9]+)*@([0-9a-z]+\.[a-z]{2,14}(\.[a-z]{2})?)$/i';
    if (preg_match($pattern, $str)) {
        return true;
    } else {
        return false;
    }
}

