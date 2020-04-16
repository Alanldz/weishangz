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
        array('pid', 'require', '推荐人手机不能为空', self::MUST_VALIDATE, 'regex', self::MODEL_INSERT),
        array('pid', '/^1[3456798]\d{9}$/', '推荐人手机错误', self::MUST_VALIDATE, 'regex', self::MODEL_INSERT),
        array('pid', 'check_parent', '推荐人手机错误或不存在', self::MUST_VALIDATE, 'callback', self::MODEL_INSERT),
        //接点人
//        array('junction_id', 'check_parent', '接点人ID错误或不存在', self::MUST_VALIDATE, 'callback', self::MODEL_INSERT),

        //验证手机号码
        array('mobile', 'require', '手机号码不能为空', self::MUST_VALIDATE, 'regex', self::MODEL_INSERT),
        array('mobile', '/^1[3456798]\d{9}$/', '手机号码格式不正确', self::MUST_VALIDATE, 'regex', self::MODEL_INSERT),
        array('mobile', '', '手机号码已存在', self::MUST_VALIDATE, 'unique', self::MODEL_INSERT),

        array('identity_card', '', '身份证已存在', self::MUST_VALIDATE, 'unique', self::MODEL_INSERT),
        //验证用户名
        array('account', '', '该账号已被使用', self::MUST_VALIDATE, 'unique', self::MODEL_BOTH),
        array('account', '/^[A-Za-z0-9]+$/', '用户名只可含有数字、字母、下划线且不以下划线开头结尾，不以数字开头！', self::MUST_VALIDATE, 'regex', self::MODEL_BOTH),
        array('username', 'require', '姓名不能为空', self::MUST_VALIDATE, 'regex', self::MODEL_INSERT),
//		array('username', '2,8', '姓名长度为2-8个字', self::MUST_VALIDATE, 'length', self::MODEL_INSERT),


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
        array('pid', 'check_parent', self::MODEL_BOTH, 'callback'),
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
            $this->error = '账号不能为空';
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
        $map['userid|account|mobile'] = array('eq', $account); // 用户ID登录
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
        $userid = session('userid');
        $userModel = D('user');
        $data = $this->ChangeInfocheckFiled();
        $changeID = intval(I('userid'));
        $changeUser = $userModel->where(['userid' => $changeID])->find();
        //验证码验证
        $code = I('code');
        $mobile = I('mobile');
        if (!check_sms($code, $mobile)) {
            $this->error = '验证码错误或已过期';
            return false;
        }
        if (empty($changeUser)) {
            $this->error = '修改用户不存在，请重新选择';
            return false;
        }
        if ($changeUser['pid'] != $userid && $changeUser['junction_id'] != $userid && $changeUser['service_center_account'] != $userid) {
            $this->error = '您没有权限修改该用户信息';
            return false;
        }
        if ($changeUser['level'] != 0) {
            $this->error = '该用户已激活，不能修改该用户信息';
            return false;
        }
        $accountID = $userModel->where(['mobile' => $data['mobile'], 'account_type' => 2])->getField('userid');
        if (!empty($accountID) && $accountID != $changeID) {
            $this->error = '该手机号已被其他主账户占用';
            return false;
        }
        $IdentityCard = $userModel->where(['identity_card' => $data['identity_card'], 'account_type' => 2])->getField('userid');
        if (!empty($IdentityCard) && $IdentityCard != $changeID) {
            $this->error = '该身份证已被其他主账户占用';
            return false;
        }

        $arrJunctionPath = explode('-', $changeUser['junction_path']);
        array_shift($arrJunctionPath);   //移除头部
        array_pop($arrJunctionPath);      //移除尾部
        if (!in_array($data['pid'], $arrJunctionPath)) {
            $this->error = '推荐必须是接点人上面的接点人';
            return false;
        }
        try {
            M()->startTrans();   //开启事务
            $data['account'] = $data['mobile'];
            //密码加密
            $salt = substr(md5(time()), 0, 3);
            $data['login_pwd'] = $userModel->pwdMd5($data['login_pwd'], $salt);
            $data['login_salt'] = $salt;

            $data['safety_pwd'] = $userModel->pwdMd5($data['safety_pwd'], $salt);
            $data['safety_salt'] = $salt;

            //推荐人
            $pid = $data['pid'];
            $last['userid'] = $pid;
            $p_info = $userModel->where(array('userid' => $pid))->field('userid,pid,gid,username,account,mobile,path,deep,service_center,service_center_account,account_type')->find();
            if (empty($p_info)) {
                throw new \Exception('推荐人不存在，请重新输入');
            }
            if ($p_info['account_type'] == 1) {
                ajaxReturn('推荐人必须为主账户', 0);
            }
            //服务中心账号
            if ($p_info['service_center']) {
                $service_center_account = $pid;
            } else {
                $service_center_account = $p_info['service_center_account'];
            }
            if (!$service_center_account) {
                ajaxReturn('服务中心不存在，请留言', 0);
            }

            if ($changeUser['service_center_account'] != $service_center_account) {
                ajaxReturn('修改失败：推荐人与原有的不在同一个服务中心', 0);
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
            $data['deep'] = $deep + 1;

            $res = $userModel->where(['userid' => $changeID])->save($data);
            if (!$res) {
                ajaxReturn('修改失败，请重试', 0);
            }

            M()->commit();
            return true;
        } catch (\Exception $e) {
            M()->rollback();
            $this->error = $e->getMessage();
            return false;
        }
    }

    /**
     * 子账号注册-验证字段
     */
    public function checkFiled()
    {
        $data = I('post.');
        if (empty($data['pid'])) {
            ajaxReturn('推荐人不能为空');
        }
        $userInfo = M('user')->where(['userid' => $data['pid']])
            ->field('userid,mobile,username,email,identity_card')->find();
        if (empty($userInfo)) {
            ajaxReturn('推荐人ID错误或者不存在');
        }
        if (empty($data['login_pwd'])) {
            ajaxReturn('请输入登录密码');
        }
        $login_pwd_len = strlen($data['login_pwd']);
        if ($login_pwd_len < 6 || $login_pwd_len > 20) {
            ajaxReturn('登录密码长度为6-20位');
        }
        if ($data['login_pwd'] != $data['relogin_pwd']) {
            ajaxReturn('两次输入登录密码不一致');
        }
        if (empty($data['safety_pwd'])) {
            ajaxReturn('交易密码不能为空');
        }

        $data['mobile'] = $userInfo['mobile'];
        $data['username'] = $userInfo['username'];
        $data['email'] = $userInfo['email'];
        $data['identity_card'] = $userInfo['identity_card'];
        $data['status'] = 1;
        $data['reg_date'] = time();
        return $data;
    }

    /**
     * 修改未激活用户信息-字段验证
     * @return array
     */
    public function ChangeInfocheckFiled()
    {
        $data = I('post.');

        if ($data['username'] == '') {
            ajaxReturn('账户姓名不能为空');
        }
        if ($data['identity_card'] == '') {
            ajaxReturn('身份证不能空');
        }
        $identity_card = trim($data['identity_card']);
        if (strlen($identity_card) < 10) {
            ajaxReturn('请输入正确的身份证号');
        }
        $mobile = trim($data['mobile']);
        if ($mobile == '') {
            ajaxReturn('手机号码不能为空');
        }
        $search = '/^0?1[3|4|5|6|7|8][0-9]\d{8}$/';
        if (!preg_match($search, $mobile)) {
            ajaxReturn('请填写正确的手机号码');
        }
        if ($data['code'] == '') {
            ajaxReturn('验证码不能为空');
        }
        if ($data['login_pwd'] == '') {
            ajaxReturn('登录密码不能为空');
        }
        if (strlen($data['login_pwd']) < 6 || strlen($data['login_pwd']) > 20) {
            ajaxReturn('登录密码长度为6-20位');
        }
        if ($data['safety_pwd'] == '') {
            ajaxReturn('交易密码不能为空');
        }
        if (strlen($data['safety_pwd']) < 6 || strlen($data['safety_pwd']) > 20) {
            ajaxReturn('交易密码长度为6-20位');
        }
        if ($data['pid'] == '') {
            ajaxReturn('推荐人不能为空');
        }

        $param = [
            'username' => trim($data['username']),
            'identity_card' => $identity_card,
            'email' => trim($data['email']),
            'mobile' => $mobile,
            'login_pwd' => trim($data['login_pwd']),
            'safety_pwd' => trim($data['safety_pwd']),
            'pid' => trim($data['pid']),
            'investment_grade' => intval($data['investment_grade'])
        ];
        return $param;
    }

    public function register()
    {
        M()->startTrans();//开启事务
        try {
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

            //密码加密
            $salt = $this->getSalt();
            $data['login_pwd'] = $this->pwdMd5($data['login_pwd'], $salt);
            $data['login_salt'] = $salt;

            $data['safety_pwd'] = $this->pwdMd5($data['safety_pwd'], $salt);
            $data['safety_salt'] = $salt;

            //推荐人
            $pid = $data['pid'];
            $p_info = $this->where(array('userid' => $pid))->field('pid,gid,path,deep,is_share')->find();

            if ($p_info['is_share'] == 0) {
                throw new Exception('该推荐人没有推荐权限');
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
            $data['deep'] = $deep + 1;
            $data['is_reward'] = 1;
            $data['junction_path'] = '';
            $data['activation_time'] = 0;
            $data['get_touch_layer'] = '';
            $data['quanxian'] = 4;
            $data['level'] = Constants::USER_LEVEL_NOT_ACTIVATE;
            $data['two_thousand_multiple_ratio'] = self::two_thousand_multiple_ratio;
            $data['five_thousand_multiple_ratio'] = self::five_thousand_multiple_ratio;
            $data['multiple_ratio'] = self::multiple_ratio;
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
                'djie_nums' => ''
            ];
            $res = M('ucoins')->add($ucoins_qbb);
            if (!$res) {
                throw new Exception('创建用户钱包失败');
            }

            //赠送流动资产
            $reg_send_flow = D('Config')->getValue('reg_send_flow');
            StoreRecordModel::addRecord($pid, 'current_assets', $reg_send_flow, Constants::STORE_TYPE_CURRENT_ASSETS, 4, $uid);

            M()->commit();
            return true;
        } catch (Exception $ex) {
            M()->rollback();
            $this->error = $ex->getMessage();
            return false;
        }
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


}
