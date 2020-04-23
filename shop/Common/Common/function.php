<?php

function p($data)
{
    echo "<pre>";
    print_r($data);
    echo "</pre>";
}

//检查网站是否关闭
function is_close_site()
{
    $where['name'] = 'TOGGLE_WEB_SITE';
    $info = M('Config', 'ysk_')->where($where)->field('value,tip')->find();
    return $info;
}

//检查商城是否关闭
function is_close_mall()
{
    $where['name'] = 'TOGGLE_MALL_SITE';
    $info = M('Config', 'ysk_')->where($where)->field('value,tip')->find();
    return $info;
}

function sp_dir_create($path, $mode = 0777)
{
    if (is_dir($path)) return true;
    $ftp_enable = 0;
    $path = sp_dir_path($path);
    $temp = explode('/', $path);
    $cur_dir = '';
    $max = count($temp) - 1;
    for ($i = 0; $i < $max; $i++) {
        $cur_dir .= $temp[$i] . '/';
        if (@is_dir($cur_dir))
            continue;
        @mkdir($cur_dir, 0777, true);
        @chmod($cur_dir, 0777);
    }
    return is_dir($path);
}

function sp_dir_path($path)
{
    $path = str_replace('\\', '/', $path);
    if (substr($path, -1) != '/')
        $path = $path . '/';
    return $path;
}

/**
 * [get_car_no 生成矿车编号]
 * @return int|string
 */
function get_car_no()
{
    $no = M('nzusfarm')->max('no');
    $no = intval($no);
    $no = $no + 1;
    $len = strlen($no);
    if ($len < 6) {
        $arr[1] = '00000';
        $arr[2] = '0000';
        $arr[3] = '000';
        $arr[4] = '00';
        $arr[5] = '0';
        $no = $arr[$len] . $no;
    }
    return $no;
}

/**
 *添加公共上传方法
 *获取上传路径
 *$picture
 */
function get_cover_url($picture)
{
    if ($picture) {
        $url = __ROOT__ . '/Uploads/' . $picture;
    } else {
        $url = __ROOT__ . '/Uploads/photo.jpg';
    }
    return $url;
}

/**
 * 用于生成插入datetime类型字段用的字符串
 * @param string $str 支持偏移字符串
 * @return false|string
 */
function datetime($str = 'now')
{
    return @date("Y-m-d H:i:s", strtotime($str));
}

/**
 * 时间戳格式化
 * @param null $time
 * @param string $format 完整的时间显示
 * @return false|string
 */
function time_format($time = null, $format = 'Y-m-d H:i')
{
    $time = $time === null ? time() : intval($time);
    return date($format, $time);
}

/**
 * 时间转换  1559549872  =》 2019-06-03 16:17:52
 * @param $time
 * @param string $format
 * @return false|string
 */
function toDate($time, $format = 'Y-m-d H:i:s')
{
    return empty($time) ? '' : date($format, $time);
}

/**
 * 系统非常规MD5加密方法
 * @param  string $str 要加密的字符串
 * @return string
 * @author jry <598821125@qq.com>
 */
function user_md5($str, $auth_key = null)
{
    if (!$auth_key) {
        $auth_key = C('AUTH_KEY') ?: '0755web';
    }
    return '' === $str ? '' : md5(sha1($str) . $auth_key);
}

/**
 * [user_salt 用户密码加密链接串]
 * @param null $time
 * @return bool|string
 */
function user_salt($time = null)
{
    if (isset($time) || empty($time)) {
        $time = time();
    }
    return substr(md5($time), 0, 3);
}

/**
 * 获取上传文件路径
 * @param  int $id 文件ID
 * @return string
 * @author jry <598821125@qq.com>
 */
function get_cover($id = null, $type = null)
{
    return D('Admin/Upload')->getCover($id, $type);
}

/**
 * 检测是否使用手机访问
 * @access public
 * @return bool
 */
function is_wap()
{
    if (isset($_SERVER['HTTP_VIA']) && stristr($_SERVER['HTTP_VIA'], "wap")) {
        return true;
    } elseif (isset($_SERVER['HTTP_ACCEPT']) && strpos(strtoupper($_SERVER['HTTP_ACCEPT']), "VND.WAP.WML")) {
        return true;
    } elseif (isset($_SERVER['HTTP_X_WAP_PROFILE']) || isset($_SERVER['HTTP_PROFILE'])) {
        return true;
    } elseif (isset($_SERVER['HTTP_USER_AGENT']) && preg_match('/(blackberry|configuration\/cldc|hp |hp-|htc |htc_|htc-|iemobile|kindle|midp|mmp|motorola|mobile|nokia|opera mini|opera |Googlebot-Mobile|YahooSeeker\/M1A1-R2D2|android|iphone|ipod|mobi|palm|palmos|pocket|portalmmm|ppc;|smartphone|sonyericsson|sqh|spv|symbian|treo|up.browser|up.link|vodafone|windows ce|xda |xda_)/i', $_SERVER['HTTP_USER_AGENT'])) {
        return true;
    } else {
        return false;
    }
}

/**
 * 是否微信访问
 * @return bool
 * @author jry <598821125@qq.com>
 */
function is_weixin()
{
    if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false) {
        return true;
    } else {
        return false;
    }
}

/**
 * [get_verify 生成验证码]
 */
function get_verify()
{
    ob_clean();
    $config = array(
        'codeSet' => '0123456789',
        'fontSize' => 50,    // 验证码字体大小
        'length' => 4,     // 验证码位数
        'fontttf' => '5.ttf',
        'useCurve' => false,
        'bg' => array(229, 237, 240),
    );
    $Verify = new \Think\Verify($config);
    $Verify->entry();
}

/**
 * [ajaxReturn ajax提示款]
 * @param  [type]  $message [提示文字]
 * @param  integer $status [1=成功 0=失败]
 * @param  string $url [跳转地址]
 * @param  string $extra [回传数据]
 * 返回[json数据]
 */
function ajaxReturn($message, $status = 0, $url = '', $extra = '')
{
    // 返回JSON数据格式到客户端 包含状态信息
    header('Content-Type:application/json; charset=utf-8');
    $result = array(
        'message' => $message,
        'status' => $status,
        'url' => $url,
        'result' => $extra
    );

    exit(json_encode($result));
}

//js消息提示框===
function error_alert($mes)
{
    echo "<meta charset=\"utf-8\"/><script>alert('" . $mes . "');javascript:history.back(-1);</script>";
    exit;
}

function success_alert($mes, $url = '')
{
    if ($url != '') {
        echo "<meta charset=\"utf-8\"/><script>alert('" . $mes . "');location.href='" . $url . "';</script>";
    } else {
        echo "<meta charset=\"utf-8\"/><script>alert('" . $mes . "');location.href='" . $jumpUrl . "';</script>";
    }
    exit;
}

//js消息提示框===

/**
 * 防注入，字符串处理，禁止构造数组提交
 * 字符过滤
 * @param $string
 * @return mixed|string
 */
function safe_replace($string)
{
    if (is_array($string)) {
        $string = implode('，', $string);
        $string = htmlspecialchars(str_shuffle($string));
    } else {
        $string = htmlspecialchars($string);
    }
    $string = str_replace('%20', '', $string);
    $string = str_replace('%27', '', $string);
    $string = str_replace('%2527', '', $string);
    $string = str_replace('*', '', $string);
    $string = str_replace("select", "", $string);
    $string = str_replace("join", "", $string);
    $string = str_replace("union", "", $string);
    $string = str_replace("where", "", $string);
    $string = str_replace("insert", "", $string);
    $string = str_replace("delete", "", $string);
    $string = str_replace("update", "", $string);
    $string = str_replace("like", "", $string);
    $string = str_replace("drop", "", $string);
    $string = str_replace("create", "", $string);
    $string = str_replace("modify", "", $string);
    $string = str_replace("rename", "", $string);
    $string = str_replace("alter", "", $string);
    $string = str_replace("cas", "", $string);
    $string = str_replace("or", "", $string);
    $string = str_replace("=", "", $string);
    $string = str_replace('"', '&quot;', $string);
    $string = str_replace("'", '', $string);
    $string = str_replace('"', '', $string);
    $string = str_replace(';', '', $string);
    $string = str_replace('<', '&lt;', $string);
    $string = str_replace('>', '&gt;', $string);
    $string = str_replace("{", '', $string);
    $string = str_replace('}', '', $string);
    $string = str_replace('--', '', $string);
    $string = str_replace('(', '', $string);
    $string = str_replace(')', '', $string);

    return $string;
}

function payway($value)
{
    $arr = array('支付宝', '微信');
    return $arr[$value];
}

/**
 * 获取父级账号
 */
function get_parent_account($pid)
{
    $account = D('User')->where('userid =' . $pid)->getField('account');
    if ($account)
        return $account;
    else
        return '无';
}

function get_user_name($uid)
{
    $where['userid'] = $uid;
    $info = M('user')->where($where)->field('account,username')->find();
    return $info['username'] . "(" . $info['account'] . ")";
}

//验证码
function set_verify()
{
    ob_clean();
    $config = array(
        'codeSet' => '0123456789',
        'fontSize' => 30,    // 验证码字体大小
        'length' => 4,     // 验证码位数
        'fontttf' => '5.ttf',
        'useCurve' => false,
        'expire' => 1800,//过期时间
    );
    $Verify = new \Think\Verify($config);
    $Verify->entry();
}

//验证验证码
function check_verify($code)
{
    $verify = new \Think\Verify();
    return $verify->check($code);
}

//获取以及好友人数
function get_children_count($uid)
{
    $where['pid'] = $uid;
    return M('user')->where($where)->count(1);
}

/**
 * [trading 添加用户交易记录明细]
 * @param  [type] $data [添加的数据]
 * @return bool|mixed
 */
function add_trading($data)
{
    if (empty($data))
        return false;

    $trading = M('fruitdetail');

    if (empty($data['uid'])) {
        $userid = session('userid');
        $data['uid'] = $userid;
    }
    $data['add_time'] = time();
    $res = $trading->add($data);
    return $res;
}

/**
 * 手机充值
 * @param int $mobile 手机号
 * @param int $price 充值面额
 * @return array
 * @time
 */
function juhepay($mobile, $price)
{
    header('Content-type:text/html;charset=utf-8');
    import('Vendor.juhe.recharge');
    $uid = session('userid');
    $appkey = C('appkey');
    $openid = C('openid');
    $recharge = new recharge($appkey, $openid);
    $telCheckRes = $recharge->telcheck($mobile, $price);

    if (!$telCheckRes) {
        //暂不支持充值
        return ['对不起，该面额暂不支持充值', 0, false];
    }

    $telQueryRes = $recharge->telquery($mobile, $price); #可以选择的面额5、10、20、30、50、100、300
    if ($telQueryRes['error_code'] == '0') {
        //正常获取到话费商品信息
        $proinfo = $telQueryRes['result'];
        $monile_info = [
//            'product_id' =>$proinfo['cardid'],            //商品ID
            'mobile' => $mobile,                            //手机号
            'price' => $price,                              //充值金额
            'cardname' => $proinfo['cardname'],               //商品名称
            'inprice' => $proinfo['inprice'],                //进价
            'game_area' => $proinfo['game_area'],            //手机归属地
        ];
    } else {
        //查询失败，可能维护、不支持面额等情况
        return [$telQueryRes["error_code"] . ":" . $telQueryRes['reason'], 0, false];
    }

    /*四：提交话费充值*/
    $orderid = rand(pow(10, (9 - 1)), pow(10, 33) - 1); //自己定义一个订单号，需要保证唯一
    $monile_info['merchants_order'] = $orderid;
    $telRechargeRes = $recharge->telcz($mobile, $price, $orderid); #可以选择的面额5、10、20、30、50、100、300
    if ($telRechargeRes['error_code'] == '0') {
        $monile_info['order_id'] = $telRechargeRes['result']['sporder_id'];
        $monile_info['status'] = 1;
        $monile_info['message'] = '充值成功';
        $monile_info['uid'] = $uid;
        $monile_info['create_time'] = date('YmdHis', time());
        M('recharge_record')->add($monile_info);
        return ['充值成功:' . $telRechargeRes['result']['sporder_id'], 200, true];
    } else {
        $monile_info['status'] = 0;
        $monile_info['message'] = $telRechargeRes['reason'];
        $monile_info['uid'] = $uid;
        $monile_info['create_time'] = date('YmdHis', time());
        M('recharge_record')->add($monile_info);
        return [$telRechargeRes['reason'], 0, false];
    }
}

/**
 * 邮箱发送验证码
 * @param $to
 * @param $name
 * @param string $subject 发送者名称
 * @param string $body
 * @param null $attachment
 * @return bool|string
 * @throws phpmailerException
 */
function think_send_mail($to, $name, $subject = '', $body = '', $attachment = null)
{
    $email_server = D('config')->where("name='email_server'")->getField("value");
    $email_server_port = D('config')->where("name='email_server_port'")->getField("value");
    $email_address = D('config')->where("name='email_address'")->getField("value");
    $email_account_name = D('config')->where("name='email_account_name'")->getField("value");
    $email_account_pwd = D('config')->where("name='email_account_pwd'")->getField("value");

    vendor('PHPMailer.class#phpmailer'); //从PHPMailer目录导class.phpmailer.php类文件
    vendor('SMTP');

    $mail = new PHPMailer(); //PHPMailer对象
    $mail->CharSet = 'UTF-8'; //设定邮件编码，默认ISO-8859-1，如果发中文此项必须设置，否则乱码
    $mail->IsSMTP(); // 设定使用SMTP服务
    $mail->SMTPDebug = 0; // 关闭SMTP调试功能
    $mail->SMTPAuth = true; // 启用 SMTP 验证功能
    $mail->SMTPSecure = 'ssl'; // 使用安全协议
    $mail->Host = $email_server; // SMTP 服务器
    $mail->Port = $email_server_port; // SMTP服务器的端口号
    $mail->Username = $email_account_name; // SMTP服务器用户名
    $mail->Password = $email_account_pwd; // SMTP服务器密码(授权码)
    $mail->SetFrom($email_address, $email_account_name);
    $replyEmail = $email_address;
    $replyName = $email_account_name;
    $mail->AddReplyTo($replyEmail, $replyName);
    $mail->Subject = $subject;
    $mail->AltBody = "为了查看该邮件，请切换到支持 HTML 的邮件客户端";
    $mail->MsgHTML($body);
    $mail->AddAddress($to, $name);

    if (is_array($attachment)) { // 添加附件
        foreach ($attachment as $file) {
            is_file($file) && $mail->AddAttachment($file);
        }
    }

    return $mail->Send() ? true : $mail->ErrorInfo;
}

/**
 * 返回银行卡尾号
 * @param $card_number
 * @return string
 * @author lzp
 * @time 2020/1/10 10:08
 */
function cardNumber($card_number)
{
    return '****' . substr($card_number, -4, 4);
}

/**
 * 上传二维码
 * @param $path
 * @param $save_name
 * @return array
 * @time 2019-9-10 11:52:58
 */
function uploading($path = 'common', $save_name = ['uniqid', ''])
{
    $upload = new \Think\Upload();
    $upload->maxSize = 3145728;
    $upload->rootPath = __ROOT__ . './Uploads/';
    $upload->savePath = $path . '/';
    $upload->replace = true;   //同文件名覆盖
    $upload->saveExt = 'png';   //设置文件后缀
    $upload->saveName = $save_name;
    $upload->exts = array('jpg', 'gif', 'png', 'jpeg');
    $upload->autoSub = false;
    if (!is_dir(__ROOT__ . '/Uploads/' . $path)) {
        mkdir(__ROOT__ . '/Uploads/' . $path);
    }
    if ($info = $upload->upload()) {
        $origin = '/Uploads/' . $upload->savePath . $info['image']['savename'];
        $corpicurl = '/' . $path . '/' . $info['image']['savename'];
        return array(
            'status' => 1,
            'path' => array(
                'filename' => $info['image']['savename'],
                'filepath' => $origin,
                'filepicurl' => $corpicurl,
            ),
        );
    } else {
        return array('status' => 0, 'msg' => $upload->getError());
    }
}

/**
 * 验证数字是否是某个值的整数倍
 *
 * @param int $multiple 倍数
 * @param double $num 数值
 * @return bool
 */
function isNumberByMultiple($multiple, $num)
{
    if ($multiple == 0) {
        return false;
    }
    if (!is_numeric($num) || $num % $multiple != 0) {
        return false;
    }
    return true;
}

/**
 * 限定 一周某个时间段
 * @param string $weekStart 限制周几开始
 * @param string $weekEnd 限制周几结束
 * @param string $hourStart 每天几点开始
 * @param string $hourEnd 每天几点结束
 * @return bool
 */
function dateLimit($weekStart = '', $weekEnd = '', $hourStart = '', $hourEnd = '')
{
    $time = time();
    $w = intval(date('w', $time));
    if (!empty($weekStart)) {
        if ($w < $weekStart) {
            return false;
        }
    }
    if (!empty($weekEnd)) {
        if ($w > $weekEnd) {
            return false;
        }
    }
    if (!empty($hourStart)) {
        $hourStart = mktime($hourStart, 0, 0, date('m'), date('d'), date('Y'));
        if ($time < $hourStart) {
            return false;
        }
    }
    if (!empty($hourEnd)) {
        $hourEnd = mktime($hourEnd, 0, 0, date('m'), date('d'), date('Y'));
        if ($time > $hourEnd) {
            return false;
        }
    }
    return true;
}

/**
 * 保留两位小数且不四色五入
 * @param $num
 * @param $retention_digit
 * @return bool|string
 */
function formatNum2($num, $retention_digit = 2)
{
    $retention_digit += 1;
    return substr(sprintf("%." . $retention_digit . "f", $num), 0, -1);
}

/**
 * 字符串转数组
 * @param $string
 * @param string $type
 * @return array
 */
function getArray($string, $type = '-')
{
    if (!is_string($string)) {
        return $string;
    }
    $arr = explode($type, $string);
    if (!empty($arr) && is_array($arr)) {
        $first = reset($arr);
        if ($first == '') {
            array_shift($arr);
        }
        $end = end($arr);
        if ($end == '') {
            array_pop($arr);
        }
        return $arr;
    } else {
        return [];
    }
}

/**
 * 今天签到次数
 * @param $uid
 * @return mixed
 * @author ldz
 * @time 2020/2/12 10:19
 */
function today_sign_num($uid)
{
    $today_time = date('Ymd');
    $where['uid'] = $uid;
    $where['type'] = 1;
    $where['create_time'] = ['like', $today_time . '%'];
    $num = M('purchase_record')->where($where)->count();
    return $num ? $num : 0;
}

/**
 * 获取用户等级
 * @param null $level
 * @return mixed|string
 * @throws \Think\Exception
 */
function getUserLevelItems($level = null)
{
    $levelList = \Common\Util\Constants::getUserLevelItems($level);
    return $levelList;
}

/**
 * 获取订单支付方式
 * @param null $type
 * @return mixed|string
 * @throws \Think\Exception
 */
function getPayTypeItems($type = null)
{
    $type = \Common\Util\Constants::getPayWayItems($type);
    return $type;
}

/**
 * 获取订单状态
 * @param null $type
 * @return mixed|string
 * @throws \Think\Exception
 */
function getOrderStatusItems($type = null)
{
    $type = \Common\Util\Constants::getOrderStatusItems($type);
    return $type;
}

/**
 * 获取商城类型
 * @param null $type
 * @return mixed|string
 * @throws \Think\Exception
 */
function getShopTypeItems($type = null)
{
    $type = \Common\Util\Constants::getShopTypeItems($type);
    return $type;
}

/**
 * 验证手机号是否正确
 * @param $mobile
 * @return bool
 */
function isMobile($mobile)
{
    if (!is_numeric($mobile)) {
        return false;
    }
    return preg_match('#^1[34578]\d{9}$#', $mobile) ? true : false;
}

/**
 * @param array $list 数据
 * @param array $indexKey 数据key值
 * @param string $filename 文件名称
 * @param array $headArr 头部内容
 * @param bool $excel2007
 * @return bool
 * @throws PHPExcel_Exception
 * @throws PHPExcel_Writer_Exception
 * 比如: $indexKey与$list数组对应关系如下:
 *       $indexKey = array('id','username','sex','age');
 *       $list = array(array('id'=>1,'username'=>'YQJ','sex'=>'男','age'=>24));
 *       $headArr = array('序号', '用户名', '性别','年龄');
 */
function exportExcel($list, $indexKey, $filename = '', $headArr = array(), $excel2007 = true)
{
    //文件引入
    import("Org.Util.PHPExcel");
    import("Org.Util.PHPExcel.Writer.Excel2007");

    if (empty($filename)) $filename = time();
    if (!is_array($indexKey)) return false;

    $header_arr = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z');
    //初始化PHPExcel()
    $objPHPExcel = new \PHPExcel();

    // 设置水平居中
    $objPHPExcel->getActiveSheet()->getDefaultStyle()->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

    //设置保存版本格式
    if ($excel2007) {
        $objWriter = new \PHPExcel_Writer_Excel2007($objPHPExcel);
        $filename = $filename . '.xlsx';
    } else {
        $objWriter = new \PHPExcel_Writer_Excel5($objPHPExcel);
        $filename = $filename . '.xls';
    }
    //接下来就是写数据到表格里面去
    $objActSheet = $objPHPExcel->getActiveSheet();
    if ($headArr) {
        foreach ($headArr as $k => $v) {
            //这里是设置单元格的内容
            $objActSheet->setCellValue($header_arr[$k] . 1, $v);
            $objActSheet->getColumnDimension($header_arr[$k])->setWidth(25);//设置宽度
        }
        $startRow = 2;
    } else {
        $startRow = 1;
    }

    foreach ($list as $row) {
        foreach ($indexKey as $key => $value) {
            //这里是设置单元格的内容
            $objActSheet->setCellValue($header_arr[$key] . $startRow, $row[$value]);
            $objActSheet->getColumnDimension($header_arr[$key])->setWidth(25);//设置宽度
        }
        $startRow++;
    }

    // 下载这个表格，在浏览器输出
    header("Pragma: public");
    header("Expires: 0");
    header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
    header("Content-Type:application/force-download");
    header("Content-Type:application/vnd.ms-execl");
    header("Content-Type:application/octet-stream");
    header("Content-Type:application/download");;
    header('Content-Disposition:attachment;filename=' . $filename . '');
    header("Content-Transfer-Encoding:binary");
    $objWriter->save('php://output');
    exit();

}


/**
 * 二维数组根据某个字段排序
 * @param array $array 要排序的数组
 * @param string $keys 要排序的键字段
 * @param int $sort 排序类型  SORT_ASC     SORT_DESC
 * @return array 排序后的数组
 */
function arraySort($array, $keys, $sort = SORT_DESC)
{
    $keysValue = [];
    foreach ($array as $k => $v) {
        $keysValue[$k] = $v[$keys];
    }
    array_multisort($keysValue, $sort, $array);
    return $array;
}