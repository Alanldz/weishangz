<?php

namespace Home\Controller;

use Home\Model\UserModel;
use Think\Controller;

class LoginController extends Controller
{
    /**
     * 登录页
     */
    public function login()
    {
        //判断网站是否关闭
        $close = is_close_site();
        if (session('userid')) {
            $this->redirect('/Shop/Index/index');
        }
        if ($close['value'] == 0) {
            $this->assign('message', $close['tip'])->display('closesite');
        } else {
            $this->display();
        }
    }

    /**
     * 前台登录
     * @time 2020/2/18 11:31
     */
    public function checkLogin()
    {
        if (IS_AJAX) {
            $account = I('account');
            $password = I('password');
            // 验证用户名密码是否正确
            $user_object = D('Home/User');
            $user_info = $user_object->login($account, $password);
            if (!$user_info) {
                ajaxReturn($user_object->getError(), 0);
            }
            session('account', $user_info['account']);
            $user_info = $user_object->Quicklogin($account);
            if (!$user_info) {
                ajaxReturn($user_object->getError(), 0);
            }
            // 设置登录状态
            $uid = $user_object->auto_login($user_info);
            // 跳转
            if (0 < $uid && $user_info['userid'] === $uid) {
                M('config')->where(['name' => 'online_number'])->setInc('value');
                session('in_time', time());
                ajaxReturn('登录成功', 1, U('shop/index/index'));
            }
        }
    }

    /**
     * 注销
     */
    public function logout()
    {
        cookie('msg', null);
        session(null);
        M('config')->where(['name' => 'online_number'])->setDec('value');
        $this->redirect('Login/login');
    }

    /**
     * 注册用户
     * @time 2020/2/9 17:17
     */
    public function register()
    {
        if (IS_AJAX) {
            $user = new UserModel();
            $res = $user->register();
            if (!$res) {
                ajaxReturn($user->getError(), 0);
            }
            ajaxReturn('注册成功', 1, U('Login/login'));
        }

        $mobile = trim(I('mobile'));
        $this->assign('mobile', $mobile);
        $this->display();
    }

    //快速登录
    public function quickLogin()
    {
        if (IS_AJAX) {
            $account = I('account');
            $code = I('code');

            // 验证验证码是否正确
            $user_object = D('Home/User');
            if (!check_sms($code, $account)) {
                ajaxReturn('验证码错误或已过期');
            }
            $user_info = $user_object->Quicklogin($account);
            if (!$user_info) {
                ajaxReturn($user_object->getError(), 0);
            }
            // 设置登录状态
            $uid = $user_object->auto_login($user_info);
            // 跳转
            if (0 < $uid && $user_info['userid'] === $uid) {
                session('in_time', time());
                ajaxReturn('登录成功', 1, U('shop/index/index'));
            } else {
                ajaxReturn('签名错误', 0);
            }
        }
    }

    /**
     * 图片验证码生成，用于登录和注册
     */
    public function verify()
    {
        set_verify();
    }

    //找回密码
    public function getpsw()
    {
        $this->display();
    }

    /**
     * 找回密码-提交
     */
    public function setpsw()
    {
        if (!IS_AJAX) return;
        $userid = I('post.userid');
        $mobile = trim(I('post.mobile'));
        $code = I('post.code');
        $password = I('post.password');
        $reppassword = I('post.passwordmin');
        if (empty($userid)) {
            ajaxReturn('会员ID不能为空');
        }
//        if (empty($email)) {
//            ajaxReturn('邮箱不能为空');
//        }
//        if (!verifyEmail($email)) {
//            ajaxReturn('请输入正确的邮箱');
//        }

        if (empty($code)) {
            ajaxReturn('验证码不能为空');
        }
        if (empty($password)) {
            ajaxReturn('密码不能为空');
        }
        if ($password != $reppassword) {
            ajaxReturn('两次输入的密码不一致');
        }

        if (!check_sms($code, $mobile)) {
            ajaxReturn('验证码错误或已过期');
        }
//        if (!check_email($code, $email, 'getpsw_code_' . $email)) {
//            ajaxReturn('验证码错误或已过期');
//        }

        $user = D('User');
        $userMobile = $user->where(['userid' => $userid])->getField('mobile');
        if (empty($userMobile)) {
            ajaxReturn('会员不存在，请重新输入');
        }
        if ($mobile != $userMobile) {
            ajaxReturn('该邮箱和会员邮箱不一致，请重新输入');
        }

        $where['userid'] = $userid;
        //密码加密
        $salt = user_salt();
        $data['login_pwd'] = $user->pwdMd5($password, $salt);
        $data['login_salt'] = $salt;
        $res = $user->field('login_pwd,login_salt')->where($where)->save($data);
        if ($res) {
            session('sms_code', null);
            ajaxReturn('修改成功', 1, U('Login/logout'));
        } else {
            ajaxReturn('修改失败');
        }

    }

    //找回密码
    public function getpswpay()
    {
        $this->display();
    }

    /**
     * 设置密码
     */
    public function setpswpay()
    {
        if (!IS_AJAX)
            return;
        $mobile = I('post.mobile');
        $code = I('post.code');
        $password = I('post.password');
        $reppassword = I('post.passwordmin');
        if (empty($mobile)) {
            ajaxReturn('手机号码不能为空');
        }
        if (empty($code)) {
            ajaxReturn('验证码不能为空');
        }
        if (empty($password)) {
            ajaxReturn('密码不能为空');
        }
        if ($password != $reppassword) {
            ajaxReturn('两次输入的密码不一致');
        }

        if (!check_sms($code, $mobile)) {
            ajaxReturn('验证码错误或已过期');
        }

        $user = D('User');
        $mwhere['mobile'] = $mobile;
        $userid = $user->where($mwhere)->getField('userid');
        if (empty($userid)) {
            ajaxReturn('手机号码错误或不在系统中');
        }

        $where['userid'] = $userid;
        //密码加密

        $salt = user_salt();
        $data['safety_pwd'] = $user->pwdMd5($password, $salt);
        $data['safety_salt'] = $salt;
        $res = $user->field('safety_pwd,safety_salt')->where($where)->save($data);
        if ($res) {
            session('sms_code', null);
            ajaxReturn('支付密码修改成功', 1, U('Index/index'));
        } else {
            ajaxReturn('支付密码修改失败');
        }
    }

    /**
     * 验证码生成
     */
    public function verify_c()
    {
        $Verify = new \Think\Verify();
        $Verify->fontSize = 18;
        $Verify->length = 4;
        $Verify->useNoise = false;
        $Verify->codeSet = '0123456789';
        $Verify->imageW = 130;
        $Verify->imageH = 50;
        //$Verify->expire = 600;
        $Verify->entry();
    }

    /* 验证码校验 */
    public function check_verify($code, $id = '')
    {
        $verify = new \Think\Verify();
        $res = $verify->check($code, $id);
        $this->ajaxReturn($res, 'json');
    }

    public function sendCodelogin()
    {
        $mobile = I('post.mobile');
        if (empty($mobile)) {
            $mes['status'] = 0;
            $mes['message'] = '手机号码不能为空';
            $this->ajaxReturn($mes);
        }
        $where['mobile|userid'] = $mobile;
        $isset = M('user')->where($where)->count(1);
        if ($isset < 1) {
            $mes['status'] = 0;
            $mes['message'] = '手机号码不在系统中';
            $this->ajaxReturn($mes);
        }
        $this->ajaxReturn(Loginmsg($mobile));
    }

    public function adduser()
    {
        //判断是否开启交易功能
        $return = IsTrading(32);
        if ($return['value'] == 0) {
            $this->assign('content', $return['tip'])->display('Close/index');
            exit();
        }

        $mobile = I('get.mobile');
        $this->assign('mobile', $mobile);
        $this->display();
    }

    public function saveuser()
    {
        if (IS_AJAX) {
            //接收数据
            $user = D('User');
            $data = $user->create();

            if (!$data) {
                ajaxReturn($user->getError(), 0);
                return;
            }

            //判断仓库
            $store = D('Store');
            $data['account'] = $data['mobile'];
            //密码加密
            $salt = substr(md5(time()), 0, 3);
            $data['login_pwd'] = $user->pwdMd5($data['login_pwd'], $salt);
            $data['login_salt'] = $salt;
            $data['safety_pwd'] = $user->pwdMd5($data['safety_pwd'], $salt);
            $data['safety_salt'] = $salt;

            //推荐人
            $pid = $data['pid'];
            $p_info = $user->field('pid,gid,username,account,mobile,path,deep')->find($pid);
            $gid = $p_info['pid'];//上上级ID
            $ggid = $p_info['gid'];//上上上级ID
            if ($gid) {
                $data['gid'] = $gid;
            }
            if ($ggid) {
                $data['ggid'] = $ggid;
            }

            //拼接路径
            $path = $p_info['path'];
            $deep = $p_info['deep'];
            if (empty($path)) {
                $data['path'] = '-' . $pid . '-';
            } else {
                $data['path'] = $path . $pid . '-';
            }
            $data['deep'] = $deep + 1;


            $user->startTrans();//开启事务
            $uid = $user->add($data);
            if (!$uid) {
                $user->rollback();
                ajaxReturn('注册失败');
            }
            //为新会员创建仓库和土地
            if (!$store->CreateCangku(0, $uid)) {
                $user->rollback();
                ajaxReturn('仓库创建失败，请联系管理员', 0);
            }

            //给上级添加值推人数
            M('user_level')->where(array('uid' => $pid))->setInc('children_num', 1);
            //给用户添加等级
            AddUserLevel($pid);

            if ($uid) {
                $user->commit();
                ajaxReturn('注册成功', 1, U('Login/login'));
            } else {
                $user->rollback();
                ajaxReturn('注册失败', 0);
            }
        }
    }

    //TD每分钟增长任务
    public function Growem()
    {
        $config_object = D('Config');
        $growem = $config_object->where("name='growem'")->getField('value');
        $growem = (float)$growem;
        $suiji = mt_rand() / mt_getrandmax() * $growem;//每十二分钟增长率

        $fp = fopen("open13.txt", "a+");
        fwrite($fp, date("Y-m-d H:i:s") . "增长率：" . $suiji . "\n");

        $ntime = time();
        $zerot = date("Y-m-d");
        $zerotime = strtotime($zerot);
        //获取当天第一个价格
        $TDzprice = D('coindets')->where("coin_addtime>=$zerotime")->order('coin_addtime asc')->getField("coin_price");
        $TDzpricez = $TDzprice * 1.1;

        $TD = D('coindets')->where("cid=1")->order('coin_addtime desc')->find();
        $coin_price = $TD["coin_price"];
        $coin_price = $TD["max"];
        $coin_price = $TD["min"];
        $coin_price = $TD["coin_price"];
        $nowp = $TD["coin_price"] * (1 + $suiji * 0.01); //现在的价格

        //每天涨幅不超过10%
        if ($nowp < $TDzpricez) {

            $coinone['cid'] = 1;
            $coinone['coin_name'] = "Epay";
            $coinone['coin_price'] = $nowp;
            $coinone['max'] = $TD["max"] * (1 + $suiji * 0.01);
            $coinone['min'] = $TD["min"] * (1 + $suiji * 0.01);
            $coinone['coin_addtime'] = $ntime;
            M('coindets')->add($coinone);
            fwrite($fp, "现在价格：" . $nowp . "\n");

        }
        fclose($fp);
        //采集
        $url1 = "http://gateio.io/json_svr/query_push/?u=13&c=472008&type=push_main_rates&symbol=USDT_CNY";
        $ch1 = curl_init();
        curl_setopt($ch1, CURLOPT_URL, $url1);
        curl_setopt($ch1, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch1, CURLOPT_HEADER, 0);
        $output1 = curl_exec($ch1);
        if ($output1 === FALSE) {
            echo "CURL Error:" . curl_error($ch1);
        }

        curl_close($ch1);
        $huilv_s = $this->get_between($output1, "sell_rate\":\"", "\",\"max_rate");

        $url = "http://gateio.io";
        $ch = curl_init();

        // 2. 设置选项，包括URL
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        // 3. 执行并获取HTML文档内容
        $output = curl_exec($ch);
        if ($output === FALSE) {
            echo "CURL Error:" . curl_error($ch);
        }
        // 4. 释放curl句柄
        curl_close($ch);
        //$content=$this->get_between($output,">ETH</span>","day-updn");
        $content1 = $this->get_between($output, "list_tbody", "ody>");
        $content_eth = $this->get_between($content1, ">ETH</span>", "</tb");
        $eth_m = $this->get_between($content_eth, "$", "</td><td>$");
        $eth_m = $huilv_s * $eth_m;


        $content_btc = $this->get_between($content1, ">BTC</span>", "</tb");
        $btc_m = $this->get_between($content_btc, "$", "</td><td>$");
        $btc_m = $huilv_s * $btc_m;

        $content_ltc = file_get_contents("https://gateio.io/trade/LTC_USDT");
        $ltc_m = $this->get_between($content_ltc, "<title>$", " LTC/USDT");
        $ltc_m = $huilv_s * $ltc_m;

        $content_dog = file_get_contents("https://gateio.io/trade/DOGE_USDT");
        $dog_m = $this->get_between($content_dog, "<title>$", " DOGE/USDT");
        $dog_m = $huilv_s * $dog_m;

        if ($btc_m > 0) {
            $coinone['cid'] = 2;
            $coinone['coin_name'] = "比特币";
            $coinone['coin_price'] = $btc_m;
            $coinone['max'] = $btc_m * 1.1;
            $coinone['min'] = $btc_m * 0.9;
            $coinone['coin_addtime'] = $ntime;
            M('coindets')->add($coinone);

        }
        if ($ltc_m > 0) {
            $coinone['cid'] = 3;
            $coinone['coin_name'] = "莱特币";
            $coinone['coin_price'] = $ltc_m;
            $coinone['max'] = $ltc_m * 1.1;
            $coinone['min'] = $ltc_m * 0.9;
            $coinone['coin_addtime'] = $ntime;
            M('coindets')->add($coinone);
        }

        if ($eth_m > 0) {
            $coinone['cid'] = 4;
            $coinone['coin_name'] = "以太坊";
            $coinone['coin_price'] = $eth_m;
            $coinone['max'] = $eth_m * 1.1;
            $coinone['min'] = $eth_m * 0.9;
            $coinone['coin_addtime'] = $ntime;
            M('coindets')->add($coinone);
        }

        if ($dog_m > 0) {
            $coinone['cid'] = 5;
            $coinone['coin_name'] = "狗狗币";
            $coinone['coin_price'] = $dog_m;
            $coinone['max'] = $dog_m * 1.1;
            $coinone['min'] = $dog_m * 0.9;
            $coinone['coin_addtime'] = $ntime;
            M('coindets')->add($coinone);
        }
        file_put_contents("1.txt", $content1);
        file_put_contents("2.txt", date("Y-m-d H:i:s") . " B:" . $btc_m . " L:" . $ltc_m . " E:" . $eth_m . " D:" . $dog_m . " 汇率:" . $huilv_s);
    }

    public function get_between($input, $start, $end)
    {
        $substr = substr($input, strlen($start) + strpos($input, $start), (strlen($input) - strpos($input, $end)) * (-1));
        return $substr;
    }

    //积分释放
    public function Relase()
    {
        $fp = fopen("open12.txt", "a+");
        fwrite($fp, date("Y-m-d H:i:s") . " 当前时间可开奖！\n");
        fclose($fp);

        //基础释放率
        $uinfp = M('user');
        $time = date('Y-m-d');
        $todaystime = strtotime($time);
        $where['releas_rate'] = array('gt', 0);
        $where['is_reward'] = 1;
        $where['_logic'] = 'or';
        $map['_complex'] = $where;
        $map['releas_time'] = array('elt', $todaystime);

        //今日基础积分释放率
//        $basi = M('config')->where(array('name' => 'sell_fee'))->getField('value');
        $perinfo = $uinfp->where($map)->order('userid desc')->limit(2000)->field('userid,releas_rate,releas_time,is_reward')->select();

        if (!empty($perinfo)) {
            foreach ($perinfo as $k => $v) {
                $treauid = $v['userid'];
                $data['releas_rate'] = 0.00;
                $data['releas_time'] = time();
                $data['is_reward'] = 0;
                $data['today_releas'] = $v['releas_rate'];

                $res_get = M('user')->where(array('userid' => $treauid))->save($data);//资产转入积分
            }
        } else {
            echo '今日已经执行完';
        }
    }

    public function Appload()
    {
        $this->display();
    }

    public function Anzhorload()
    {
        //判断是否在微信端
        $url = 'http://www.TDss.com/Public/home/zp/TD.apk';
        //是否为安卓
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'iPhone') || strpos($_SERVER['HTTP_USER_AGENT'], 'iPad')) {
            ajaxReturn('IOS请下载苹果版', 0);
        } else if (strpos($_SERVER['HTTP_USER_AGENT'], 'Android')) {
            if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false) {
                ajaxReturn('安卓端请在浏览器打开下载', 0);
            } else {
                ajaxReturn($url, 1);
            }
        } else {
            ajaxReturn($url, 1);
        }
    }

    public function isReward()
    {
        $res = M("user")->where(['is_reward' => 1])->save(['is_reward' => 0]);
        $this->display();
    }

    //切换账号
    public function switchLogin()
    {
        if (IS_AJAX) {
            //用户登录
            $user = D('Home/User');
            $userid = I('userid', 0);
            $info = $user->find($userid);
            if (empty($info)) {
                ajaxReturn('用户不存在', 0);
            }
            if ($info['level'] == 0) {
                ajaxReturn('该用户未激活，不能登录', 0);
            }

            $login_id = $user->auto_login($info);
            if ($login_id) {
                session('in_time', time());
                session('login_from_admin', 'admin', 10800);
                ajaxReturn('切换成功', 1, '/Index/index');
            } else {
                ajaxReturn('切换失败，请重试', 0);
            }
        }
        $uid = session('userid');

        $mobile = M('user')->where(['userid' => $uid])->getField('mobile');
        $arrUser = M('user')->where(['mobile' => $mobile, 'status' => 1, 'level' => ['neq', 0]])->field('level,account_type,userid,reg_date')->select();

        //当前登录的置顶
        if (count($arrUser) >= 2) {
            $now_user = [];
            foreach ($arrUser as $key => $user) {
                if ($user['userid'] == $uid) {
                    $now_user = $user;
                    unset($arrUser[$key]);
                }
            }
            array_unshift($arrUser, $now_user);
        }

        $this->assign('userid', $uid);
        $this->assign('arrUser', $arrUser);
        $this->display();
    }

    /**
     * 发送短信
     */
    public function sendCode()
    {
        $mobile = I('post.mobile');
        if (empty($mobile)) {
            $mes['status'] = 0;
            $mes['message'] = '手机号码不能为空';
            $this->ajaxReturn($mes);
        }
        if (!empty($_SERVER["HTTP_CLIENT_IP"])) {
            $cip = $_SERVER["HTTP_CLIENT_IP"];
        } else if (!empty($_SERVER["HTTP_X_FORWARDED_FOR"])) {
            $cip = $_SERVER["HTTP_X_FORWARDED_FOR"];
        } else if (!empty($_SERVER["REMOTE_ADDR"])) {
            $cip = $_SERVER["REMOTE_ADDR"];
        }

        $today_where['target'] = $mobile;
        $starTime = strtotime(date('Y-m-d'));
        $endTime = strtotime(date("Y-m-d", strtotime("+1 day")));
        $today_where['time'] = array(array('egt', $starTime), array('lt', $endTime), 'and');
        $today_send_num = M('preventip')->where($today_where)->count();
        if ($today_send_num >= 3) {
            $this->ajaxReturn(['status' => 0, 'message' => '今日已经发送三次，请明日再发送！']);
        }
        $datas = array();
        $datas['ip'] = $cip;
        $datas['target'] = $mobile;
        $datas['time'] = time();
        $res = M('preventip')->where(array('ip' => $cip, 'target' => $mobile))->find();
        if (empty($res)) {
            $result = sendMsg($mobile);
            if ($result['status'] == 1) {
                M('preventip')->add($datas);
            }
            $this->ajaxReturn($result);
        } elseif (!empty($res)) {
            if (time() - $res['time'] <= 60) {
                $mes = array();
                $mes['status'] = 2;
                $mes['message'] = '一分钟分钟内禁止再次操作';
                $this->ajaxReturn($mes);
            } else {
                $result = sendMsg($mobile);
                if ($result['status'] == 1) {
                    M('preventip')->add($datas);
                }
                $this->ajaxReturn($result);
            }
        }
    }

    /**
     * 发送邮箱
     * @time 2019-2-16 16:16:20
     */
    public function sendEmail()
    {
        $email = trim(I('email'));
        $type = trim(I('type'));
        if (empty($email)) {
            $this->ajaxReturn(['message' => '邮箱不能为空', 'status' => 0]);
        }
        if (!verifyEmail($email)) {
            $this->ajaxReturn(['message' => '请输入正确的邮箱', 'status' => 0]);
        }
        $code = getCode();
        $content = 'Your verification code is（您的验证码是）：' . $code;
        $res = think_send_mail($email, 'bat', 'BAT', $content);
        if (strpos($res, 'SMTP') !== false) {
            $this->ajaxReturn(['message' => '发送失败请重试', 'status' => 0]);
        }
        $sms_code = sha1(md5(trim($code) . trim($email)));
        session($type . '_' . $email, $sms_code);
        $this->ajaxReturn(['message' => '验证码已发至邮箱', 'status' => 1]);
    }

}
