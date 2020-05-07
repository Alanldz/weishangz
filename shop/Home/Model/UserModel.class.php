<?php

namespace Home\Model;

use Common\Util\Constants;
use Think\Exception;

/**
 * 用户模型
 */
class UserModel extends \Common\Model\UserModel
{

    public $user_id;

    public $url;

    const one_box_money = 20;//推荐奖一盒金额

    const shareholder_bonus = 10; //股东分红一盒金额

    const shareholder_profit = 5; //股东收益一盒金额

    /**
     * 自动验证规则
     *
     */
    protected $_validate = array(
        // self::EXISTS_VALIDATE 或者0 存在字段就验证（默认）
        // self::MUST_VALIDATE 或者1 必须验证
        // self::VALUE_VALIDATE或者2 值不为空的时候验证

        //我的交易密码
        // array('mypwdtwo', 'require', '‘我的交易密码’不能为空', self::EXISTS_VALIDATE, 'regex', self::MODEL_INSERT),
        // array('mypwdtwo', 'check_pwd_two', '‘我的交易密码’错误', self::EXISTS_VALIDATE, 'callback', self::MODEL_INSERT),

        //激活码
        // array('activatecode', 'require', '激活码不能为空', self::EXISTS_VALIDATE, 'regex', self::MODEL_INSERT),

        //推荐人
//        array('pid', 'require', '推荐人手机不能为空', self::MUST_VALIDATE, 'regex', self::MODEL_INSERT),
//        array('pid', '/^1[3456798]\d{9}$/', '推荐人手机错误', self::MUST_VALIDATE, 'regex', self::MODEL_INSERT),
//        array('pid', 'check_parent', '推荐人手机错误或不存在', self::MUST_VALIDATE, 'callback', self::MODEL_INSERT),
        //接点人
//        array('junction_id', 'check_parent', '接点人ID错误或不存在', self::MUST_VALIDATE, 'callback', self::MODEL_INSERT),
        array('username', 'require', '姓名不能为空', self::MUST_VALIDATE, 'regex', self::MODEL_BOTH),
//		array('username', '2,8', '姓名长度为2-8个字', self::MUST_VALIDATE, 'length', self::MODEL_INSERT),

        array('identity_card', 'require', '身份证不能为空', self::MUST_VALIDATE, 'regex', self::MODEL_BOTH),
//        array('identity_card', '', '身份证已存在', self::MUST_VALIDATE, 'unique', self::MODEL_INSERT),

        //验证手机号码
        array('mobile', 'require', '手机号码不能为空', self::MUST_VALIDATE, 'regex', self::MODEL_BOTH),
        array('mobile', '/^1[3456798]\d{9}$/', '手机号码格式不正确', self::MUST_VALIDATE, 'regex', self::MODEL_BOTH),
        array('mobile', '', '手机号码已存在', self::MUST_VALIDATE, 'unique', self::MODEL_BOTH),

        array('wx_no', 'require', '微信号不能为空', self::MUST_VALIDATE, 'regex', self::MODEL_BOTH),

        //验证用户名
//        array('account', '', '该账号已被使用', self::MUST_VALIDATE, 'unique', self::MODEL_BOTH),
//        array('account', '/^[A-Za-z0-9]+$/', '用户名只可含有数字、字母、下划线且不以下划线开头结尾，不以数字开头！', self::MUST_VALIDATE, 'regex', self::MODEL_BOTH),

        //验证登录密码
        array('login_pwd', 'require', '登录密码不能为空', self::MUST_VALIDATE, 'regex', self::MODEL_INSERT),
        array('relogin_pwd', 'require', '确认登录密码不能为空', self::MUST_VALIDATE, 'regex', self::MODEL_INSERT),
        array('login_pwd', '6,20', '登录密码长度为6-20位', self::MUST_VALIDATE, 'length', self::MODEL_INSERT),
        array('relogin_pwd', 'login_pwd', '两次输入登录密码不一致', self::MUST_VALIDATE, 'confirm', self::MODEL_INSERT),

        //验证交易密码
        array('safety_pwd', 'require', '交易密码不能为空', self::MUST_VALIDATE, 'regex', self::MODEL_INSERT),
//		array('resafety_pwd', 'require', '确认交易密码不能为空', self::MUST_VALIDATE, 'regex', self::MODEL_INSERT),
        array('safety_pwd', '6,20', '交易密码长度为6-20位', self::MUST_VALIDATE, 'length', self::MODEL_INSERT),
//		array('resafety_pwd', 'safety_pwd', '两次输入交易密码不一致', self::MUST_VALIDATE, 'confirm', self::MODEL_INSERT),

        // array('email', 'require', '邮箱不能为空', self::MUST_VALIDATE, 'regex', self::MODEL_INSERT),
        // array('email', '/^([a-zA-Z0-9_\.\-])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/', '邮箱格式不正确', self::EXISTS_VALIDATE, 'regex', self::MODEL_INSERT),
        // array('email', '', '该邮箱已被使用', self::MUST_VALIDATE, 'unique', self::MODEL_INSERT),
    );

    /**
     * 自动完成规则
     */
    protected $_auto = array(
        array('reg_ip', 'get_client_ip', self::MODEL_INSERT, 'function'),
//        array('pid', 'check_parent', self::MODEL_BOTH, 'callback'),
//        array('junction_id', 'check_parent', self::MODEL_BOTH, 'callback'),
        array('reg_date', 'time', self::MODEL_INSERT, 'function'),
        array('status', '1', self::MODEL_INSERT),
        array('activate', '0', self::MODEL_INSERT),
    );

    //激活用户时，所得购股比率
    static $PURCHASE_RATE = [
        '1' => 0.47,
        '2' => 0.48,
        '3' => 0.49,
        '4' => 0.5
    ];

    //周封金额
    static $WEEKSEAL_PRICE = [
        '1' => 400,
        '2' => 1200,
        '3' => 2000,
        '4' => 4000
    ];

    /**
     * 验证手机号
     */
    protected function check_parent($value)
    {
        $where['mobile'] = $value;
        $pid = $this->where($where)->getField('userid');
        if ($pid > 0)
            return $pid;
        else
            return false;
    }

    /**
     * [getdeep 层级]
     * @return [type] [description]
     */
    protected function getdeep()
    {
        $userid = $this->user_login();
        $where['userid'] = $userid;
        $deep = $this->where($where)->getField('deep');
        return $deep + 1;
    }

    /**
     * [getpath 路径]
     * @return [type] [description]
     */
    protected function getpath()
    {
        $userid = $this->user_login();
        $where['userid'] = $userid;
        $path = $this->where($where)->getField('path');
        if (empty($path))
            $path = '-' . $userid . '-';
        else
            $path .= $userid . '-';
        return $path;
    }

    /**
     * 用户登录
     */
    public function Quicklogin($account)
    {
        //去除前后空格
        $account = trim($account);
        if (!isset($account) || empty($account)) {
            $this->error = '账号不能为空';
            return false;
        }

        //匹配登录方式
        // if (preg_match("/^([a-zA-Z0-9_\.\-])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/", $account)) {
        //     $map['email'] = array('eq', $account); // 邮箱登陆
        // if (preg_match("/^1\d{10}$/", $account)) {
        $map['mobile|account|userid'] = array('eq', $account); // 手机号登陆
        // } else {
        //     $map['account'] = array('eq', $account); // 用户名登陆
        // }
        $user_info = $this->where($map)->find(); //查找用户
        if ($user_info['status'] <= 0) {
            $this->error = '您的账号已锁定，请联系管理员!';
            return false;
        } else {
            $session_id = session_id();
            $this->where($map)->setField('session_id', $session_id);
            return $user_info;
        }
        return false;
    }

    /**
     * 用户登录
     */
    public function login($account, $password, $map = null)
    {
        //去除前后空格
        $account = trim($account);
        if (!isset($account) || empty($account)) {
            $this->error = '账户不能为空';
            return false;
        }
        if (!isset($password) || empty($password)) {
            $this->error = '密码不能为空';
            return false;
        }

        //匹配登录方式
        // if (preg_match("/^([a-zA-Z0-9_\.\-])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/", $account)) {
        //     $map['email'] = array('eq', $account); // 邮箱登陆
        // if (preg_match("/^1\d{10}$/", $account)) {
        $map['account|mobile'] = array('eq', $account); // 用户ID登录
        // } else {
        //     $map['account'] = array('eq', $account); // 用户名登陆
        // }
        $user_info = $this->where($map)->find(); //查找用户
        if (!$user_info) {
            $this->error = '账号或密码错误!';
            return false;
        } elseif ($user_info['status'] <= 0) {
            $this->error = '您的账号已锁定，请联系管理员!';
            return false;
        }
//		elseif ($user_info['level'] == 0){
//            $this->error = '您还未激活不能登录';
//            return false;
//        }
        else {
            if ($this->pwdMd5($password, $user_info['login_salt']) !== $user_info['login_pwd']) {
                $this->error = '账号或密码错误！';
                return false;
            } else {
                $session_id = session_id();
                $this->where($map)->setField('session_id', $session_id);
                return $user_info;
            }
        }
        return false;
    }

    /**
     * 用户交易
     */
    public function Trans($account, $password, $map = null)
    {
        $user_id = session('userid');
        $map['userid'] = array('eq', $user_id);   // 用户ID号登陆
        $user_info = $this->where($map)->find(); //查找用户
        if (!$user_info) {
            ajaxReturn('用户不存在', 0);
        } elseif ($user_info['status'] <= 0) {
            ajaxReturn('您的账号已锁定', 0);
        } else {
            $ispwd = $this->pwdMd5($password, $user_info['safety_salt']);
            if ($ispwd == $user_info['safety_pwd']) {
            } else {
                ajaxReturn('交易密码错误', 0);
            }
        }
        return false;
    }

    public function Savepwd($account, $password, $type)
    {
        $map['mobile|account'] = array('eq', $account); // 手机号登陆
        $user_info = $this->where($map)->find(); //查找用户

        if (!$user_info) {
            ajaxReturn('您输入的账号有误', 0);
        } elseif ($user_info['status'] <= 0) {
            ajaxReturn('您的账号已锁定', 0);
        } else {
            if ($type == 1) {
                $ispwd = $this->pwdMd5($password, $user_info['login_salt']);
                if ($ispwd == $user_info['login_pwd']) {
                } else {
                    ajaxReturn('登录密码错误', 0);
                }
            } else {
                $ispwd = $this->pwdMd5($password, $user_info['safety_salt']);
                if ($ispwd == $user_info['safety_pwd']) {
                } else {
                    ajaxReturn('交易密码错误', 0);
                }
            }
        }
    }

    //验证交易密码是否正确
    public function check_pwd_two($value)
    {
        $where['userid'] = session('userid');
        $u_info = $this->where($where)->field('safety_pwd,safety_salt')->find();
        $salt = $u_info['safety_salt'];
        $pwd = $u_info['safety_pwd'];
        if ($pwd == $this->pwdMd5($value, $salt)) {
            return true;
        } else {
            return false;
        }
    }

    //验证登录密码是否正确
    public function check_pwd_one($value)
    {
        $where['userid'] = session('userid');
        $u_info = $this->where($where)->field('login_pwd,login_salt')->find();
        $salt = $u_info['login_salt'];
        $pwd = $u_info['login_pwd'];
        if ($pwd == $this->pwdMd5($value, $salt)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 设置登录状态
     */
    public function auto_login($user)
    {
        // 记录登录SESSION和COOKIES
        $auth = array(
            'userid' => $user['userid'],
            'account' => $user['account'],
            'mobile' => $user['mobile'],
            'username' => $user['username'],
        );
        session('userid', $user['userid'], 43200);

        session('user_login', $auth, 43200);
        session('user_login_sign', $this->data_auth_sign($auth), 43200);
        return $this->user_login();
    }

    /**
     * 数据签名认证
     * @param  array $data 被认证的数据
     * @return string       签名
     *
     */
    public function data_auth_sign($data)
    {
        // 数据类型检测
        if (!is_array($data)) {
            $data = (array)$data;
        }
        ksort($data); //排序
        $code = http_build_query($data); // url编码并生成query字符串
        $sign = sha1($code); // 生成签名
        return $sign;
    }

    /**
     * 检测用户是否登录
     * @return integer 0-未登录，大于0-当前登录用户ID
     *
     */
    public function user_login()
    {
        $user = session('user_login');
        if (empty($user)) {
            return 0;
        } else {
            if (session('user_login_sign') == $this->data_auth_sign($user)) {
                return $user['userid'];
            } else {
                return 0;
            }
        }
    }


    public function UserField($field)
    {
        $userid = session('userid');
        $where['userid'] = $userid;
        return $this->where($where)->getField($field);
    }

    /**
     * 获取用户信息
     * @param null $where
     * @param $field
     * @return array|mixed
     */
    public function UserInfo($where = null, $field)
    {
        if (empty($where))
            $where['userid'] = $this->user_login();
        $info = $this->where($where)->field($field)->find();

        $pid = $info['pid'];
        if (!$field && $pid > 0) {
            //读取父级信息
            $p_info = $this->where('userid = ' . $pid)->field('account as p_account,username as p_username,mobile as p_mobile')->find();
            if ($p_info)
                $info = array_merge($info, $p_info);
        }

        return $info;
    }

    protected function is_password($value)
    {
        $len = strlen($value);
        if ($len < 6 || $len > 20) {
            $this->error = '密码长度为6-20位';
            return false;
        }
        // $meth='/(?!^(\d+|[a-zA-Z]+|[~!@#$%^&*()_+{}:"<>?\-=[\];\',.\/]+)$)^[\w~!@#$%^&*()_+{}:"<>?\-=[\];\',.\/]+$/';
        // if(!preg_match($match,$value)){
        //     $this->error='密码至少由数字、字符组成';
        //     return false;
        // }
        return true;
    }

    public function CangkuNum($field)
    {
        $userid = $this->user_login();
        $where['uid'] = $userid;
        return D('store')->field($field)->where($where)->find();
    }

    /**
     * [ChildrenNum 直推人数]
     */
    public function ChildrenNum()
    {
        $where['pid'] = $this->user_login();
        return $this->where($where)->count(1);
    }

    protected function getUserTool($userid)
    {
        $where['uid'] = $userid;
        $info = M('user_level')->where($where)->find();
        return $info;
    }

    /**
     * 获取伞下所有人信息
     * @param $user_id
     * @param string $fields
     * @return mixed
     * @author ldz
     * @time 2020/2/2 20:12
     */
    public static function getTeamUser($user_id, $fields = "*")
    {
        $path = '-' . $user_id . '-';
        $where['path'] = array('like', '%' . $path . '%');
        $teamUser = M('user')->where($where)->field($fields)->select();
        return $teamUser;
    }

    /**
     * 修改未激活用户信息
     * @return bool
     */
    public function changeUserInfo()
    {
        return true;
    }

    /**
     * 注册
     * @return bool
     */
    public function register()
    {
        M()->startTrans();//开启事务
        try {
            $user_id = session('userid');
            if (empty($user_id)) {
                $this->url = U('Home/Login/login');
                throw new Exception('您还未登录，请先登录');
            }
            //接收数据
            $data = $this->create();
            if (!$data) {
                throw new Exception($this->getError());
            }

            //验证码
            $code = trim(I('code'));
//            if (!check_sms($code, $data['mobile'])) {
//                throw new Exception('验证码错误或已过期');
//            }

            //验证
            list($productInfo, $address) = $this->validateAddData();

            //密码加密
            $salt = $this->getSalt();
            $data['login_pwd'] = $this->pwdMd5($data['login_pwd'], $salt);
            $data['login_salt'] = $salt;

            $data['safety_pwd'] = $this->pwdMd5($data['safety_pwd'], $salt);
            $data['safety_salt'] = $salt;

            //推荐人
            $pid = $data['pid'] = $user_id;
            $p_info = M('user')->where(array('userid' => $pid))->field('pid,gid,path,deep,level')->find();
            if (empty($p_info)) {
                throw new Exception('推荐人不存在');
            }
            if ($p_info['level'] < Constants::USER_LEVEL_A_ONE) {
                throw new Exception('推荐人必须是社区站或社区站以上才能注册');
            }

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
            $data['account'] = $data['mobile'];
            $data['deep'] = $deep + 1;
            $data['is_reward'] = 1;
            $data['junction_path'] = '';
            $data['activation_time'] = 0;
            $data['get_touch_layer'] = '';
            $data['investment_grade'] = $productInfo['level'];
//            $data['level'] = $productInfo['level'];
//            $data['activate'] = 1;
//            $data['activation_time'] = time();
            $data['is_share'] = 1;
            $uid = $this->add($data);
            if (!$uid) {
                throw new Exception('注册失败');
            }
            $this->user_id = $uid;

            //创建钱包
            $store['uid'] = $uid;
            $res = M("store")->add($store);
            if (!$res) {
                throw new Exception('创建用户钱包失败');
            }

            $ucoins_qbb = [
                'cid' => 1,
                'c_nums' => 0,
                'c_uid' => $uid,
                'djie_nums' => 0
            ];
            $res = M('ucoins')->add($ucoins_qbb);
            if (!$res) {
                throw new Exception('创建用户钱包失败');
            }

            if ($address) {
                $this->createOrder($productInfo, $address);
            }

//            $user_id = session('userid');
//            $storeInfo = M('store')->where(['uid' => $user_id])->field('cangku_num,cloud_library')->find();
//            if ($productInfo['activate_buy_num'] > $storeInfo['cloud_library']) {
//                throw new Exception('您的云库不足');
//            }
//            //扣除用户的云库数量
//            StoreRecordModel::addRecord($user_id, 'cloud_library', -$productInfo['activate_buy_num'], Constants::STORE_TYPE_CLOUD_LIBRARY, 0, $this->user_id);
//
//            $arrayPath = array_reverse(getArray($data['path']));
//            //奖励
//            $this->award($user_id, $arrayPath, $productInfo['activate_buy_num'], $address);
//            if (empty($address)) {//云库
//                StoreRecordModel::addRecord($this->user_id, 'cloud_library', $productInfo['activate_buy_num'], Constants::STORE_TYPE_CLOUD_LIBRARY, 1);
//            } else {//邮寄，创建订单
//                $this->createOrder($productInfo, $address);
//            }

            M()->commit();
            return true;
        } catch (Exception $ex) {
            M()->rollback();
            $this->error = $ex->getMessage();
            return false;
        }
    }

    /**
     * 奖励
     * @param $user_id
     * @param $arrayPath
     * @param $activate_buy_num
     * @param $address
     * @param $activate_user_id
     * @throws Exception
     */
    public function award($user_id, $arrayPath, $activate_buy_num, $address)
    {
        //增加业绩
        $this->achievement($arrayPath, $activate_buy_num, $this->user_id);
        //推荐奖
        $this->recommendAward($user_id, $activate_buy_num);
        //用户升级
        $this->upgradeUserLevel($arrayPath);
        //股东分红和股东收益
        $this->shareholderBonus($arrayPath, $activate_buy_num, $address);
    }

    /**
     * 验证
     * @return array
     * @throws Exception
     */
    private function validateAddData()
    {
        $product_id = intval(I('product_id'));
        if ($product_id == -1) {
            throw new Exception('请选择级别');
        }

        $productInfo = M('product_detail')->where(['id' => $product_id])->field('id,level,price,activate_buy_num,name,pic')->find();
        if (empty($productInfo)) {
            throw new Exception('您选择的级别不存在');
        }

        $delivery_type = I('delivery_type', 1);
        if ($delivery_type == -1) {
            throw new Exception('请选择货运方式');
        }
        $address = [];
        if ($delivery_type == 2) {
            $address = I('address');
            if (empty($address['name'])) {
                throw new Exception('请输入收货人姓名');
            }
            if (empty($address['telephone'])) {
                throw new Exception('请输入收货人手机号');
            }
            if (!isMobile($address['telephone'])) {
                throw new Exception('收货人手机号有误');
            }

            if ($address['province_id'] == '' || $address['province_id'] == '--请选择省份--') {
                throw new Exception('请选择省份');
            }

            if ($address['city_id'] == '' || $address['city_id'] == '--请选择市--') {
                throw new Exception('请选择市');
            }

            if ($address['country_id'] == '' || $address['country_id'] == '--请选择区--') {
                throw new Exception('请选择区');
            }
            if (empty($address['address'])) {
                throw new Exception('请输入详细地址');
            }
        }

        return [$productInfo, $address];
    }

    /**
     * 邮寄，创建订单
     * @param $productInfo
     * @param $address
     * @throws Exception
     */
    private function createOrder($productInfo, $address)
    {
        //创建收货地址
        $address['member_id'] = $this->user_id;
        $res = M('address')->add($address);
        if (!$res) {
            throw new Exception('创建收货地址失败');
        }

        //创建订单
        $order = array();
        $order_no = "M" . date("YmdHis") . rand(100000, 999999);
        $order['order_no'] = $order_no;
        $order['uid'] = $this->user_id;
        $order['buy_price'] = $productInfo['price'] * $productInfo['activate_buy_num'];
        $order['buy_name'] = $address['name'];
        $order['buy_phone'] = $address['telephone'];
        $order['buy_address'] = $address['province_id'] . $address['city_id'] . $address['country_id'] . $address['address'];
        $order['province_id'] = $address['province_id'];
        $order['city_id'] = $address['city_id'];
        $order['country_id'] = $address['country_id'];
        $order['address'] = $address['address'];
        $order['status'] = 0;
        $order['time'] = time();
        $order_id = M("order")->add($order);

        if (!$order_id) {
            throw new Exception('订单创建失败');
        }

        //添加订单明细
        $detail = array();
        $detail["order_id"] = $order_id;
        $detail["com_id"] = $productInfo['id'];
        $detail["com_name"] = $productInfo['name'];
        $detail["com_price"] = $productInfo['price'];
        $detail["com_num"] = $productInfo['activate_buy_num'];
        $detail["com_img"] = $productInfo['pic'];
        $detail["uid"] = $this->user_id;
        $res = M("order_detail")->add($detail);
        if (!$res) {
            throw new Exception('订单明细创建失败');
        }
    }

    /**
     * 增加上级的业绩和销量
     * @param $arrPath
     * @param $activate_buy_num
     * @param $activate_user_id
     * @throws Exception
     */
    private function achievement($arrPath, $activate_buy_num, $activate_user_id)
    {
        $level = M('user')->where(['userid' => $activate_user_id])->getField('level');
        if ($level == Constants::USER_LEVEL_A_FOUR) {
            $level = Constants::USER_LEVEL_A_THREE;
        }

        $price = M('product_detail')->where(['level' => $level])->getField('price');
        $total_amount = $activate_buy_num * $price; //业绩

        foreach ($arrPath as $pid) {
            $update['total_amount'] = array('exp', 'total_amount + ' . $total_amount);
            $update['total_month_amount'] = array('exp', 'total_month_amount + ' . $total_amount);
            $update['total_month_amount_two'] = array('exp', 'total_month_amount_two + ' . $total_amount);
            $update['total_num'] = array('exp', 'total_num + ' . $activate_buy_num);
            $update['month_num'] = array('exp', 'month_num + ' . $activate_buy_num);
            $res = M('store')->where(['uid' => $pid])->save($update);
            if (!$res) {
                throw new Exception('业绩新增失败');
            }
        }
    }

    /**
     * 推荐奖
     * @param $user_id
     * @param $activate_buy_num
     * @throws Exception
     */
    private function recommendAward($user_id, $activate_buy_num)
    {
        $awardMoney = self::one_box_money * $activate_buy_num;
        $userInfo = M('user')->where(['userid' => $user_id])->field('level,pid')->find();
        $new_user_level = M('user')->where(['userid' => $this->user_id])->getField('level');
        if ($userInfo['level'] <= $new_user_level) {
            //推荐人获得
            StoreModel::changStore($user_id, 'cangku_num', $awardMoney, 4, 1, $this->user_id);
        }

        $pid_info = M('user')->where(['userid' => $userInfo['pid']])->field('level')->find();
        $child_info = M('user')->where(['userid' => $this->user_id])->field('level')->find();
        if ($pid_info && $pid_info['level'] >= Constants::USER_LEVEL_A_THREE && $child_info['level'] >= Constants::USER_LEVEL_A_THREE) {
            //上上级获得间推奖
            StoreModel::changStore($userInfo['pid'], 'cangku_num', $awardMoney, 6, 1, $this->user_id);
        }
    }

    /**
     * 股东分红和股东收益
     * @param $path
     * @param $activate_buy_num
     * @param $address
     * @throws Exception
     */
    private function shareholderBonus($path, $activate_buy_num, $address)
    {
        $bonus = self::shareholder_bonus * $activate_buy_num;//分红
        $profit = self::shareholder_profit * $activate_buy_num;//收益
        foreach ($path as $pid) {
            $level = M('user')->where(['userid' => $pid])->getField('level');
            if ($level == Constants::USER_LEVEL_A_FOUR) {
                //股东分红
                StoreModel::changStore($pid, 'cangku_num', $bonus, 8, 1, $this->user_id);
                //股东收益
                $verify_list = M('verify_list')->where(['uid' => $pid, 'status' => Constants::YesNo_Yes])->field('province_id,city_id')->find();
                if ($address && $verify_list && ($verify_list['province_id'] == $address['province_id']) && ($verify_list['city_id'] == $address['city_id'])) {
                    StoreModel::changStore($pid, 'cangku_num', $profit, 10, 1, $this->user_id);
                }
            }
        }
    }

    /**
     * 用户升级
     * @param $path
     * @throws Exception
     */
    private function upgradeUserLevel($path)
    {
        foreach ($path as $pid) {
            $old_level = M('user')->where(['userid' => $pid])->getField('level');
            if ($this->shareholderLevel($pid)) {
                $level = Constants::USER_LEVEL_A_FOUR;
            } elseif ($this->branchOfficeLevel($pid)) {
                $level = Constants::USER_LEVEL_A_THREE;
            } elseif ($this->directorLevel($pid)) {
                $level = Constants::USER_LEVEL_A_TWO;
            } else {
                $level = $old_level;
            }

            if ($level > $old_level) {
                $res = M('user')->where(['userid' => $pid])->save(['level' => $level]);
                if ($res === false) {
                    throw new Exception('用户升级失败');
                }
            }
        }

    }

    /**
     * 判断用户是否达到运营中心级别
     * @param $uid
     * @return bool
     * @author ldz
     * @time 2020/4/23 20:52
     */
    private function directorLevel($uid)
    {
        $num = M('user')->where(['pid' => $uid, 'level' => Constants::USER_LEVEL_A_ONE])->count();
        if (intval($num) < 6) {
            return false;
        }

        return true;
    }


    /**
     * 判断用户是否达到分公司级别
     * @param $uid
     * @return bool
     * @author ldz
     * @time 2020/4/23 20:52
     */
    private function branchOfficeLevel($uid)
    {
        $num = M('user')->where(['pid' => $uid, 'level' => Constants::USER_LEVEL_A_TWO])->count();
        if (intval($num) < 4) {
            return false;
        }

        return true;
    }

    /**
     * 判断用户是否达到股东级别
     * @param $uid
     * @return bool
     */
    private function shareholderLevel($uid)
    {
        $arrID = M('user')->where(['pid' => $uid, 'level' => Constants::USER_LEVEL_A_THREE])->getField('userid',true);
        if (count($arrID) < 3) {
            return false;
        }

        $arrData = [];
        foreach ($arrID as $pid) {
            $arrData[] = [
                'uid' => $pid,
                'total_month_amount' => M('store')->where(['uid' => $pid])->getField('total_month_amount')
            ];
        }
        $arrData = arraySort($arrData, 'total_month_amount');//按月销量从大到小排序

        if ($arrData[0]['total_month_amount'] < 1000000) {
            return false;
        }
        array_shift($arrData);//移除最大
        $sum = array_sum(array_column($arrData, 'total_month_amount'));
        if ($sum < 1000000) {
            return false;
        }

        return true;
    }


    /**
     * 激活二维码
     * @return bool
     * @author ldz
     * @time 2020/2/11 16:58
     */
    public function openSharePower()
    {
        $uid = session('userid');
        $trans_pwd = trim(I('pwd', ''));
        $user_info = M('user')->where(['userid' => $uid])->field('pid,gid,is_share')->find();
        if ($user_info['is_share']) {
            $this->error = '您已经开通分享权限，无须再次开通';
            return false;
        }

        $money = 2;

        $cangku_num = M('store')->where(['uid' => $uid])->getField('cangku_num');
        if ($cangku_num < $money) {
            $this->error = '您的可用余额不足，不能激活';
            return false;
        }

        //验证交易密码
        $this->Trans('', $trans_pwd);

        M()->startTrans();
        try {
            //扣除用户可用余额
            StoreModel::changStore($uid, 'cangku_num', -$money, 6);

            if ($user_info['pid']) {//给上级
                //添加金额记录
                $tranMoney = [
                    'pay_id' => $user_info['pid'],
                    'get_id' => $user_info['pid'],
                    'get_nums' => 1,
                    'get_type' => 6,
                    'is_release' => 0,
                    'remark' => $uid,
                    'get_time' => time()
                ];

                $res = M('tranmoney')->add($tranMoney);
                if (!$res) {
                    throw new Exception('添加记录失败');
                }
            }

            if ($user_info['gid']) {//给上上级
                //添加金额记录
                $tranMoney = [
                    'pay_id' => $user_info['gid'],
                    'get_id' => $user_info['gid'],
                    'get_nums' => 0.5,
                    'get_type' => 6,
                    'is_release' => 0,
                    'remark' => $uid,
                    'get_time' => time()
                ];

                $res = M('tranmoney')->add($tranMoney);
                if (!$res) {
                    throw new Exception('添加记录失败');
                }
            }

            $res = M('user')->where(['userid' => $uid])->save(['is_share' => 1]);
            if ($res === false) {
                throw new Exception('开通分享二维码权限失败');
            }

            M()->commit();
            return true;
        } catch (Exception $ex) {
            M()->rollback();
            $this->error = $ex->getMessage();
            return false;
        }
    }

    /**
     * 签到
     * @return bool
     * @author ldz
     * @time 2020/2/12 9:51
     */
    public function signIn()
    {
        $uid = session('userid');

        if (today_sign_num($uid) >= 1) {
            $this->error = '今天已经签到过了';
            return false;
        }

        M()->startTrans();
        try {
            $purchase_integral = D('config')->getValue('sign_in');

            if ($purchase_integral) {
                $res = M('store')->where(['uid' => $uid])->setInc('purchase_integral', $purchase_integral);
                if (!$res) {
                    throw new Exception('增加签到账户失败');
                }

                $res = D('purchase_record')->addData($uid, $purchase_integral, 1);
                if (!$res) {
                    throw new Exception('添加签到账户记录失败');
                }
            }

            M()->commit();
            return true;
        } catch (Exception $ex) {
            M()->rollback();
            $this->error = $ex->getMessage();
            return false;
        }
    }

    /**
     * 修改用户信息
     * @return bool
     * @author ldz
     * @time 2020/3/30 13:57
     */
    public function updateUserInfo()
    {
        $user_name = trim(I('user_name'));
        if (empty($user_name)) {
            $this->error = '请填写真实姓名';
            return false;
        }
        $user_id = session('userid');

        $res = $this->where(['userid' => $user_id])->save(['username' => $user_name]);
        if ($res === false) {
            $this->error = '修改失败，请重试';
            return false;
        }

        return true;
    }

    /**
     * 未激活用户列表
     * @param $user_id
     * @return array
     * @author ldz
     * @time 2020/5/5 15:51
     */
    public static function getUnActivateUser($user_id)
    {
        $userInfo = M('user')->where(['userid' => $user_id])->field('mobile,level,service_center,username')->find();
        $field = 'userid,pid,mobile,investment_grade,reg_date,username';
        $list_one = M('user')->where(['pid' => $user_id, 'level' => Constants::USER_LEVEL_NOT_ACTIVATE])->order('reg_date asc')->field($field)->select();

        $where['pid'] = ['neq', $user_id];
        $where['level'] = Constants::USER_LEVEL_NOT_ACTIVATE;
        $where['path'] = ['like', '%' . $user_id . '%'];
        $list_two = M('user')->where($where)->field($field)->order('userid asc')->select();
        $arrUser = array_merge($list_one, $list_two);
        $userList = [];
        foreach ($arrUser as $key => $item) {
            $data = $item;
            $data['is_can_deal'] = 0;
            $data['is_can_del'] = 0;
            $data['pid_username'] = $userInfo['username'];
            $data['delivery_type'] = '邮寄';
            if ($item['pid'] == $user_id) {//直推下级
                $data['pid_mobile'] = $userInfo['mobile'];
                if ($item['investment_grade'] == Constants::USER_LEVEL_A_THREE) {
                    if ($userInfo['level'] >= Constants::USER_LEVEL_A_THREE && $userInfo['service_center'] == 1) {
                        $data['is_can_deal'] = 1;
                    }
                } else {
                    if ($userInfo['level'] > $item['investment_grade']) {
                        $data['is_can_deal'] = 1;
                    }
                }

                if ($userInfo['level'] >= Constants::USER_LEVEL_A_THREE && $userInfo['service_center'] == 1) {
                    $data['is_can_del'] = 1;
                }
            } else {
                $data['pid_mobile'] = M('user')->where(['userid' => $item['pid']])->getField('mobile');
                if ($item['investment_grade'] == Constants::USER_LEVEL_A_THREE) {
                    if ($userInfo['level'] >= Constants::USER_LEVEL_A_THREE && $userInfo['service_center'] == 1) {
                        $data['is_can_deal'] = 1;
                    } else {
                        continue;
                    }
                } else {
                    if ($userInfo['level'] > $item['investment_grade']) {
                        $data['is_can_deal'] = 1;
                    } else {
                        continue;
                    }
                }
                if ($userInfo['level'] >= Constants::USER_LEVEL_A_THREE && $userInfo['service_center'] == 1) {
                    $data['is_can_del'] = 1;
                }
            }
            $data['activate_buy_num'] = M('product_detail')->where(['level' => $item['investment_grade']])->getField('activate_buy_num');
            $order = M('order')->where(['uid' => $item['userid']])->count();
            if (empty($order)) {
                $data['delivery_type'] = '云库';
            }

            $userList[] = $data;
        }

        return $userList;
    }

    /**
     * 激活用户
     * @return bool
     * @author ldz
     * @time 2020/2/12 9:51
     */
    public function activateUser()
    {
        M()->startTrans();
        try {
            $uid = session('userid');
            $activate_user_id = intval(I('user_id'));
            $trans_pwd = trim(I('pwd', ''));
            if (empty($trans_pwd)) {
                throw new Exception('请输入交易密码');
            }
            //验证交易密码
            $this->Trans('', $trans_pwd);

            $activate_user_info = M('user')->where(['userid' => $activate_user_id, 'level' => Constants::USER_LEVEL_NOT_ACTIVATE])->field('pid,path,investment_grade')->find();
            if (empty($activate_user_info)) {
                throw new Exception('您要激活的用户不存在，请重试');
            }

            $path = '-' . $uid . '-';
            if (strpos($activate_user_info['path'], $path) === false) {
                throw new Exception('该用户不是您的团队，请重新选择');
            }

            $user_info = M('user')->where(['userid' => $uid])->field('level,service_center')->find();
            $is_can_deal = false;
            if ($activate_user_info['investment_grade'] == Constants::USER_LEVEL_A_THREE) {
                if ($user_info['level'] >= Constants::USER_LEVEL_A_THREE && $user_info['service_center'] == 1) {
                    $is_can_deal = true;
                }
            } else {
                if ($user_info['level'] > $activate_user_info['investment_grade']) {
                    $is_can_deal = true;
                }
            }

            if (!$is_can_deal) {
                throw new Exception('您没有权限激活');
            }

            $productInfo = M('product_detail')->where(['level' => $activate_user_info['investment_grade']])->field('id,level,price,activate_buy_num,name,pic')->find();
            if (empty($productInfo)) {
                throw new Exception('激活失败，请联系管理员');
            }

            $storeInfo = M('store')->where(['uid' => $uid])->field('cloud_library')->find();
            if ($productInfo['activate_buy_num'] > $storeInfo['cloud_library']) {
                throw new Exception('您的云库不足');
            }

            //扣除用户的云库数量
            StoreRecordModel::addRecord($uid, 'cloud_library', -$productInfo['activate_buy_num'], Constants::STORE_TYPE_CLOUD_LIBRARY, 0, $activate_user_id);
            $order = M('order')->where(['uid' => $activate_user_id, 'status' => 0])->order('order_id asc')->find();

            if (empty($order)) {//云库
                $address = [];
                $type = 2;
                StoreRecordModel::addRecord($activate_user_id, 'cloud_library', $productInfo['activate_buy_num'], Constants::STORE_TYPE_CLOUD_LIBRARY, 1);
            } else {//邮寄
                $res = M('order')->where(['order_id' => $order['order_id']])->save(['status' => 1, 'pay_time' => time()]);
                if ($res === false) {
                    throw new Exception('修改订单状态失败');
                }
                $type = 1;
                $address['province_id'] = $order['province_id'];
                $address['city_id'] = $order['city_id'];
            }

            //修改用户状态
            $update['level'] = $activate_user_info['investment_grade'];
            $update['activate'] = 1;
            $update['activation_time'] = time();
            $res = M('user')->where(['userid' => $activate_user_id])->save($update);
            if (!$res) {
                throw new Exception('激活用户失败');
            }

            //奖励
            $arrayPath = array_reverse(getArray($activate_user_info['path']));
            $this->user_id = $activate_user_id;
            $this->award($activate_user_info['pid'], $arrayPath, $productInfo['activate_buy_num'], $address);

            //激活记录
            $addData = [
                'uid' => $uid,
                'activate_user_id' => $activate_user_id,
                'level' => $activate_user_info['investment_grade'],
                'num' => $productInfo['activate_buy_num'],
                'type' => $type,
                'create_time' => $update['activation_time']
            ];
            $res = M('activate_record')->add($addData);
            if (!$res) {
                throw new Exception('新增激活用户记录失败');
            }

            M()->commit();
            return true;
        } catch (Exception $ex) {
            M()->rollback();
            $this->error = $ex->getMessage();
            return false;
        }
    }

    /**
     * 删除用户
     * @return bool
     * @author ldz
     * @time 2020/2/12 9:51
     */
    public function deleteUser()
    {
        M()->startTrans();
        try {
            $uid = session('userid');
            $del_user_id = intval(I('user_id'));
            $trans_pwd = trim(I('pwd', ''));
            if (empty($trans_pwd)) {
                throw new Exception('请输入交易密码');
            }
            //验证交易密码
            $this->Trans('', $trans_pwd);

            $del_user_info = M('user')->where(['userid' => $del_user_id])->field('path')->find();
            if (empty($del_user_info)) {
                throw new Exception('您要删除的用户不存在，请重试');
            }

            $path = '-' . $uid . '-';
            if (strpos($del_user_info['path'], $path) === false) {
                throw new Exception('该用户不是您的团队，请重新选择');
            }

            $user_info = M('user')->where(['userid' => $uid])->field('level,service_center')->find();
            if ($user_info['level'] < Constants::USER_LEVEL_A_THREE || $user_info['service_center'] == 0) {
                throw new Exception('您没有删除用户的权限');
            }

            $res = M('ucoins')->where(['c_uid' => $del_user_id])->delete();
            if ($res === false) {
                throw new Exception('删除用户失败');
            }

            $res = M('store')->where(['uid' => $del_user_id])->delete();
            if ($res === false) {
                throw new Exception('删除用户失败');
            }

            $res = M('user')->where(['userid' => $del_user_id])->delete();
            if ($res === false) {
                throw new Exception('删除用户失败');
            }

            M()->commit();
            return true;
        } catch (Exception $ex) {
            M()->rollback();
            $this->error = $ex->getMessage();
            return false;
        }
    }


}
