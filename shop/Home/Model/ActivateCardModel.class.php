<?php
/**
 * Created by PhpStorm.
 * Date: 2018/12/15
 * Time: 10:43
 */

namespace Home\Model;

class ActivateCardModel extends \Common\Model\ActivateCardModel
{

    /**
     * 通过id获取激活卡信息
     * @param int $id
     * @param string $field
     * @return mixed
     * @time 2018-12-16 11:44:57
     */
    public static function getCardInfoById($id, $field = '*')
    {
        return M('activate_card')->where(['id' => $id])->field($field)->find();
    }

    /**
     * 搜索列表
     * @return array
     * @time 2018-12-15 12:39:07
     */
    public static function search()
    {
        $uid = session('userid');
        $models = M('activate_card');
        //分页
        $where['uid'] = $uid;
        $where['status'] = intval(I('step', 0));
        $where['delete_time'] = '';
        $p = Fgetpage($models, $where);
        $page = $p->show();
        $list = $models->where($where)->order('id desc')->select();
        $data = ['list' => $list, 'page' => $page];

        return $data;
    }

    /**
     * 制作激活卡
     * @return bool
     * @time 2018-12-16 10:48:59
     */
    public function mark_card()
    {
        $uid = session('userid');
        $level = intval(I('level'));
        $safety_salt = trim(I('safety_salt'));
        if (empty($level)) {
            $this->error = '请选择生成级别';
            return false;
        }
        if (empty($safety_salt)) {
            $this->error = '请输入您的交易密码';
            return false;
        }

        $arrLevel = [1, 2, 3, 4];
        if (!in_array($level, $arrLevel)) {
            $this->error = '该生成级别不存在';
            return false;
        }
        $userModel = D('Home/User');
        $userInfo = $userModel->where(array('userid' => $uid))->field('account,level,empty_order')->find();
        if ($userInfo['empty_order']) {
            $this->error = '您暂时未能制作激活卡，有疑问请留言！';
            return false;
        }
        if ($userInfo['level'] == 0) {
            $this->error = '制作失败，您还未激活';
            return false;
        }

        $expend_ep = UserModel::$INVESTMENT_GRADE[$level];
        $cangku_num = M('store')->where(['uid' => $uid])->getField('cangku_num');
        if ($expend_ep > $cangku_num) {
            $this->error = '您的EP金额不足';
            return false;
        }

        //验证交易密码
        $userModel->Trans($userInfo['account'], $safety_salt);

        try {
            M()->startTrans();   //开启事务

            //扣除用户ep
            $userData['cangku_num'] = array('exp', 'cangku_num -' . $expend_ep);
            $res = M('store')->where(array('uid' => $uid))->save($userData);
            if (!$res) {
                throw new \Exception('扣除EP值失败');
            }
            //添加ep记录
            $now_cangku_num = $cangku_num - $expend_ep;
            $data['pay_id'] = $uid;
            $data['get_id'] = $uid;
            $data['get_nums'] = $expend_ep;
            $data['now_nums_get'] = $now_cangku_num;
            $data['now_nums'] = $now_cangku_num;
            $data['is_release'] = 1;
            $data['get_time'] = time();
            $data['get_type'] = 42;
            $data['remark'] = '';
            $res = M('tranmoney')->add($data);
            if (!$res) {
                throw new \Exception('ep流水添加失败');
            }
            //添加激活卡
            $addCard = [
                'activation_code' => $this->create_randomstr_s(),
                'uid' => $uid,
                'level' => $level,
                'create_time' => date('YmdHis')
            ];
            $res = M('activate_card')->add($addCard);
            if (!$res) {
                throw new \Exception('生成激活码失败');
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
     * 赠送激活卡
     * @return bool
     * @time 2018-12-16 13:42:58
     */
    public function gift_card()
    {
        M()->startTrans();   //开启事务
        try {
            $uid = session('userid');
            $card_id = intval(I('card_id', 0));
            $give_user_id = intval(trim(I('give_user_id', 0)));
            $mobile = trim(I('mobile', ''));
            $safety_salt = trim(I('safety_salt', ''));
            if (empty($card_id)) {
                throw new \Exception('参数错误');
            }
            if (empty($give_user_id)) {
                throw new \Exception('请输入会员ID');
            }
            if (empty($mobile)) {
                throw new \Exception('请输入手机后四位');
            }
            if (empty($safety_salt)) {
                throw new \Exception('请输入您的交易密码');
            }
            if ($give_user_id == $uid) {
                throw new \Exception('不能赠送给自己');
            }
            //验证手机号码后四位
            $give_user_info = M('user')->where(['userid' => $give_user_id])->field('mobile,level,service_center_account')->find();
            if (empty($give_user_info)) {
                throw new \Exception('赠送的会员ID不存在,请重新输入');
            }
            if ($give_user_info['level'] == 0) {
                throw new \Exception('赠送的会员ID未激活,不能赠送');
            }
            $tmobile = $give_user_info['mobile'];

            $tmobile = substr($tmobile, -4);
            if ($tmobile != $mobile) {
                throw new \Exception('您输入的手机号码后四位有误');
            }

            //验证交易密码
            $userModel = D('Home/User');
            $userInfo = $userModel->where(array('userid' => $uid))->Field('account,empty_order,service_center_account,service_center')->find();
            $userModel->Trans($userInfo['account'], $safety_salt);
            if ($userInfo['empty_order']) {
                throw new \Exception('不可赠送，请留言给后台');
            }

            $cardInfo = M('activate_card')->where(['id' => $card_id, 'uid' => $uid, 'delete_time' => ''])->find();
            if (empty($cardInfo)) {
                throw new \Exception('该激活卡不存在，请从新选择');
            }

            //判断赠送的人跟自己是不是同一个服务中心，或者服务中心为自己
            if ($userInfo['service_center']) {
                if ($give_user_info['service_center_account'] != $uid && $give_user_info['service_center_account'] != $userInfo['service_center_account']) {
                    throw new \Exception('赠送失败，非同一个服务中心');
                }
            } else {
                if ($give_user_info['service_center_account'] != $userInfo['service_center_account']) {
                    throw new \Exception('赠送失败，非同一个服务中心');
                }
                $countCard = M('activate_card')->where(['activation_code' => $cardInfo['activation_code']])->count();
                if ($countCard > 1) {
                    throw new \Exception('该激活卡是赠送所得，不可赠送他人');
                }
            }

            $changeData['delete_time'] = date('YmdHis');
            $res = M('activate_card')->where(['id' => $card_id, 'uid' => $uid])->save($changeData);
            if (!$res) {
                throw new \Exception('删除激活卡失败');
            }
            //赠送激活卡
            $addCard = [
                'activation_code' => $cardInfo['activation_code'],
                'uid' => $give_user_id,
                'level' => $cardInfo['level'],
                'create_time' => date('YmdHis')
            ];
            $res = M('activate_card')->add($addCard);
            if (!$res) {
                throw new \Exception('赠送失败');
            }
            //添加激活卡记录
            $cardRecard = [
                'uid' => $uid,
                'activate_card_id' => $card_id,
                'userid' => $give_user_id,
                'level' => $cardInfo['level'],
                'type' => 2,
                'create_time' => date('YmdHis')
            ];
            $res = M('activate_card_record')->add($cardRecard);
            if (!$res) {
                M()->rollback();
                ajaxReturn('添加赠送记录失败', 0);
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
     * 生成随机字符串（数字字母大小写）
     * @param int $lenth 长度
     * @return string 字符串
     */
    function create_randomstr_s($lenth = 32)
    {
        $str = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
        $activation_code = "";
        for ($i = 0; $i < $lenth; $i++) {
            $activation_code .= $str{mt_rand(0, 32)};    //生成php随机数
        }
        $count = M('activate_card')->where(['activation_code' => $activation_code, 'delete_time' => ''])->count();
        if ($count > 0) {
            return $this->create_randomstr_s();
        }
        return $activation_code;
    }

}