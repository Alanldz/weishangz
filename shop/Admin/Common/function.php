<?php

/**
 * 检测用户是否登录
 * @return integer 0-未登录，大于0-当前登录用户ID
 * @author jry <598821125@qq.com>
 */
function is_login()
{
    return D('Admin/Manage')->is_login();
}

/**
 * [status_name 表状态配置]
 * @param  [type] $moble [数据表]
 * @param  [type] $value [状态值]
 * @return [type]        [description]
 */
function status_name($moble, $value)
{
    $arr = array();
    switch ($moble) {
        case 'traing':
            $arr = array(0 => "<span style='color:#2699ed' >出售成功</span>", 1 => "<span style='color:#3c763d' >购买者已确认</span>", 2 => "<span style='color:#ff7826' >交易完成</span>", 3 => "<span style='color:#ef2a2a' >交易取消</span>");
            break;

        default:
            # code...
            break;
    }

    return $arr[$value];
}


/**
 * 字节格式化
 * @access public
 * @param string $size 字节
 * @return string
 */
function byte_Format($size)
{
    $kb = 1024;          // Kilobyte
    $mb = 1024 * $kb;    // Megabyte
    $gb = 1024 * $mb;    // Gigabyte
    $tb = 1024 * $gb;    // Terabyte

    if ($size < $kb)
        return $size . 'B';

    else if ($size < $mb)
        return round($size / $kb, 2) . 'KB';

    else if ($size < $gb)
        return round($size / $mb, 2) . 'MB';

    else if ($size < $tb)
        return round($size / $gb, 2) . 'GB';
    else
        return round($size / $tb, 2) . 'TB';
}

/**
 * TODO 基础分页的相同代码封装，使前台的代码更少
 * @param $m 模型，引用传递
 * @param $where 查询条件
 * @param int $pagesize 每页查询条数
 * @return \Think\Page
 */
function getpage(&$m, $where, $pagesize = 10)
{
    $m1 = clone $m;//浅复制一个模型
    $count = $m->where($where)->count();//连惯操作后会对join等操作进行重置
    $m = $m1;//为保持在为定的连惯操作，浅复制一个模型
    $p = new Think\PageAdmin($count, $pagesize);
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

//按日期搜索
function date_query($field)
{

    $date_start = I('date_start');
    $date_end = I('date_end');
    if (!empty($date_start) && !empty($date_end) && ($date_start == $date_end)) {
        $map["FROM_UNIXTIME(" . $field . ",'%Y-%m-%d')"] = $date_end;
    } else if ($date_start != '' && $date_end != '' && $date_start != $date_end) {
        $map[$field] = array('between', array(strtotime($date_start), strtotime($date_end) + 86400));
    } else if ($date_start != '' && empty($date_end)) {
        $map[$field] = array('gt', strtotime($date_start) + 86400);
    } else if (empty($date_start) && $date_end != '') {
        $map[$field] = array('lt', strtotime($date_end) + 86400);
    }
    if ($map)
        return $map;
}


function date_search($field)
{

    $date_start = I('date_start');
    $date_end = I('date_end');
    if ($date_start != '' && $date_end != '') {
        $map[$field] = array('between', array(date('YmdHis', strtotime($date_start)), date('YmdHis', strtotime($date_end) + 86400)));
    } else if ($date_start != '' && empty($date_end)) {
        $map[$field] = array('gt', date('YmdHis', strtotime($date_start)));
    } else if (empty($date_start) && $date_end != '') {
        $map[$field] = array('lt', date('YmdHis', strtotime($date_end) + 86400));
    }
    if ($map)
        return $map;
}

/**
 * 短信发送
 */
function sellSendMsg($mobile, $tpl_id = '')
{
    $mobile = safe_replace($mobile);
    if (!empty($mobile)) {
        if (time() > session('set_time' . $mobile) + 60 || session('set_time' . $mobile) == '') {
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

function getCode()
{
    return rand(100000, 999999);
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

