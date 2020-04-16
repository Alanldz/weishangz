<?php

namespace Admin\Model;

use Common\Util\Constants;
use Think\Exception;

/**
 * 用户模型
 *
 */
class UserModel extends \Common\Model\UserModel
{
    /**
     * 自动验证规则
     *
     */
    protected $_validate = array(
        // self::EXISTS_VALIDATE 或者0 存在字段就验证（默认）
        // self::MUST_VALIDATE 或者1 必须验证
        // self::VALUE_VALIDATE或者2 值不为空的时候验证
        //验证用户名
        array('account', 'require', '账号不能为空', self::EXISTS_VALIDATE, 'regex', self::MODEL_BOTH),
//        array('account', '6,32', '账号长度为1-32个字符', self::EXISTS_VALIDATE, 'length', self::MODEL_BOTH),
        array('account', '', '该账号已被使用', self::EXISTS_VALIDATE, 'unique', self::MODEL_BOTH),
        array('account', '/^[A-Za-z0-9]+$/', '用户名只可含有数字、字母、下划线且不以下划线开头结尾，不以数字开头！', self::MUST_VALIDATE, 'regex', self::MODEL_BOTH),
        array('username', 'require', '姓名不能为空', self::MUST_VALIDATE, 'regex', self::MODEL_BOTH),

        //验证登录密码
        array('login_pwd', 'require', '密码不能为空', self::MUST_VALIDATE, 'regex', self::MODEL_INSERT),
        array('login_pwd', '6,20', '密码长度为6-20位', self::VALUE_VALIDATE, 'length', self::MODEL_BOTH),
        // array('login_pwd', '/(?!^(\d+|[a-zA-Z]+|[~!@#$%^&*()_+{}:"<>?\-=[\];\',.\/]+)$)^[\w~!@#$%^&*()_+{}:"<>?\-=[\];\',.\/]+$/', '密码至少由数字、字符、特殊字符三种中的两种组成', self::VALUE_VALIDATE, 'regex', self::MODEL_BOTH),
        array('relogin_pwd', 'login_pwd', '两次输入的密码不一致', self::EXISTS_VALIDATE, 'confirm', self::MODEL_BOTH),

        //验证交易密码
        array('safety_pwd', 'require', '交易密码不能为空', self::MUST_VALIDATE, 'regex', self::MODEL_INSERT),
        array('safety_pwd', '6,20', '交易密码长度为6-20位', self::VALUE_VALIDATE, 'length', self::MODEL_BOTH),
        // array('safety_pwd', '/(?!^(\d+|[a-zA-Z]+|[~!@#$%^&*()_+{}:"<>?\-=[\];\',.\/]+)$)^[\w~!@#$%^&*()_+{}:"<>?\-=[\];\',.\/]+$/', '密码至少由数字、字符、特殊字符三种中的两种组成', self::VALUE_VALIDATE, 'regex', self::MODEL_BOTH),
        array('resafety_pwd', 'safety_pwd', '两次输入的交易密码不一致', self::EXISTS_VALIDATE, 'confirm', self::MODEL_BOTH),

        //验证手机号码
        array('mobile', '/^1[3456798]\d{9}$/', '手机号码格式不正确', self::MUST_VALIDATE, 'regex', self::MODEL_BOTH),
        array('mobile', 'require', '手机号码不能为空', self::MUST_VALIDATE, 'regex', self::MODEL_BOTH),
        array('mobile', '', '该手机号已被使用', self::MUST_VALIDATE, 'unique', self::MODEL_BOTH),

        //推荐人
        array('pid', 'require', '推荐人手机不能为空', self::MUST_VALIDATE, 'regex', self::MODEL_INSERT),
        array('pid', '/^1[3456798]\d{9}$/', '推荐人手机错误', self::MUST_VALIDATE, 'regex', self::MODEL_INSERT),
        array('pid', 'check_parent', '推荐人手机错误或不存在', self::MUST_VALIDATE, 'callback', self::MODEL_INSERT),

        // array('email', 'require', '邮箱不能为空', self::MUST_VALIDATE, 'regex', self::MODEL_BOTH),
        // array('email', '/^([a-zA-Z0-9_\.\-])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/', '邮箱格式不正确', self::EXISTS_VALIDATE, 'regex', self::MODEL_BOTH),
        // array('email', '', '该邮箱已被使用', self::MUST_VALIDATE, 'unique', self::MODEL_BOTH),

//        array('identity_card', 'require', '身份证不能为空', self::MUST_VALIDATE, 'regex', self::MODEL_BOTH),
//        array('identity_card', '', '该身份证已被使用，请重新输入', self::MUST_VALIDATE, 'unique', self::MODEL_BOTH),
    );

    /**
     * 自动完成规则
     */
    protected $_auto = array(
        array('reg_ip', 'get_client_ip', self::MODEL_INSERT, 'function', 1),
        array('pid', 'check_parent', self::MODEL_INSERT, 'callback'),
        array('reg_date', 'time', self::MODEL_INSERT, 'function'),
        array('deep', '1', self::MODEL_INSERT),
        array('path', '', self::MODEL_INSERT),
        array('status', '1', self::MODEL_INSERT),
        array('activate', '1', self::MODEL_INSERT),
    );

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
     * 新增用户
     * @return bool
     */
    public function addUser()
    {
        $data = $this->create();
        if (!$data) {
            return false;
        }
        M()->startTrans();
        try {
            $salt = $this->getSalt();
            //登录密码加密
            $data['login_pwd'] = $this->pwdMd5($data['login_pwd'], $salt);
            $data['login_salt'] = $salt;
            //交易密码加密
            $data['safety_pwd'] = $this->pwdMd5($data['safety_pwd'], $salt);
            $data['safety_salt'] = $salt;
            if ($data['identity_card']) {
                $identity_card_count = M('user')->where(['identity_card' => $data['identity_card']])->count();
                if ($identity_card_count) {
                    throw new Exception('该身份证已被使用，请重新输入');
                }
            }

            //推荐人
            $pid = $data['pid'];
            $p_info = $this->where(array('userid' => $pid))->field('pid,gid,path,deep,is_share')->find();

            if ($p_info['is_share'] == 0) {
                throw new Exception('该推荐人没有推荐权限');
            }

            if ($p_info['pid']) {//上上级ID
                $data['gid'] = $p_info['pid'];
            }
            if ($p_info['gid']) {//上上上级ID
                $data['ggid'] = $p_info['gid'];
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
            $data['multiple_ratio'] = self::multiple_ratio;
            $data['is_share'] = 1;
            $user_id = $this->add($data);
            if (!$user_id) {
                throw new Exception('新增失败，请重试');
            }

            //创建钱包
            $store['uid'] = $user_id;
            $res = M("store")->add($store);
            if (!$res) {
                throw new Exception('创建用户钱包失败');
            }

            $ucoins_qbb = [
                'cid' => 1,
                'c_nums' => 0,
                'c_uid' => $user_id,
                'djie_nums' => ''
            ];
            $res = M('ucoins')->add($ucoins_qbb);
            if (!$res) {
                throw new Exception('创建用户钱包失败');
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
     * 编辑用户信息
     * @return bool
     * @author ldz
     * @time 2020/2/28 16:55
     */
    public function editUser()
    {
        if (empty($_POST['login_pwd'])) {
            unset($_POST['relogin_pwd']);
        }
        if (empty($_POST['safety_pwd'])) {
            unset($_POST['resafety_pwd']);
        }
        $data = $this->create();
        if (!$data) {
            return false;
        }
        $data['sell_num'] = intval($data['sell_num']);

        $userInfo = $this->where(['userid' => $data['userid']])->field('pid,account_type')->find();
        if (!$userInfo) {
            $this->error = '该用户不存在，请重试';
            return false;
        }

        if ($data['identity_card']) {
            $identity_card_count = M('user')->where(['identity_card' => $data['identity_card']])->count();
            if ($identity_card_count) {
                throw new Exception('该身份证已被使用，请重新输入');
            }
        }

        //如果没有密码，去掉密码字段
        if (empty($data['login_pwd']) || trim($data['login_pwd']) == '') {
            unset($data['login_pwd']);
        } else {
            $salt = $this->getSalt();
            $data['login_pwd'] = $this->pwdMd5($data['login_pwd'], $salt);
            $data['login_salt'] = $salt;
        }

        if (empty($data['safety_pwd']) || trim($data['safety_pwd']) == '') {
            unset($data['safety_pwd']);
        } else {
            $salt = $this->getSalt();
            $data['safety_pwd'] = $this->pwdMd5($data['safety_pwd'], $salt);
            $data['safety_salt'] = $salt;
        }

        if (empty($data['quanxian'])) {
            $data['quanxian'] = '';
        } else {
            $quanxian = join("-", $data['quanxian']);
            $data['quanxian'] = $quanxian;
        }

        //如果保存状态不为未激活且激活时间为空，则保存激活时间
        if ($data['level'] != Constants::USER_LEVEL_NOT_ACTIVATE && empty(trim($data['activation_time']))) {
            $data['activation_time'] = time();
        }
        $result = $this->save($data);
        if ($result === false) {
            $this->error = '更新失败';
            return false;
        }
        return true;
    }

    /**
     * 充值
     * @param int $id 充值单ID
     * @return bool
     */
    public function recharge($id)
    {
        try {
            M()->startTrans();
            $status = (trim(I('status', 1)) == 1) ? 1 : 2;

            $recharge = M('recharge')->where(['id' => $id, 'delete_time' => ''])->field('uid,amount,status')->find();
            if (empty($recharge)) {
                throw new Exception('该充值订单不存在');
            }
            if ($recharge['status'] == 1) {
                throw new Exception('该充值订单已处理');
            }

            //修改充值单状态
            $res = M('recharge')->where(['id' => $id])->save(['status' => $status, 'recharge_time' => date('YmdHis', time())]);
            if (!$res) {
                throw new Exception('修改充值订单状态失败');
            }

            if ($status == 1) {
                //给用户开通卖出权限
                $userInfo = M('user')->where(['userid' => $recharge['uid']])->field('quanxian')->find();
                $power = getArray($userInfo['quanxian']);
                foreach ($power as $k => $item) {
                    if ($item == 4) {
                        unset($power[$k]);
                    }
                }
                $power = join("-", $power);
                $res = M('user')->where(['userid' => $recharge['uid']])->save(['quanxian' => $power]);
                if ($res === false) {
                    throw new Exception('开通权限失败');
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


}
