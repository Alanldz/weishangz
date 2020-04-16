<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/24
 * Time: 15:43
 */

namespace Shop\Model;

use Common\Model\StoreRecordModel;
use Common\Model\UcoinsModel;
use Common\Model\UserModel;
use Common\Util\Constants;
use Think\Exception;

class StoreModel extends \Common\Model\StoreModel
{

    /**
     * 判断消费区消费通证是否还能购买
     * @param int $user_id 用户id
     * @param double $amount 购买金额
     * @return bool
     */
    public static function is_can_bao_dan_buy($user_id, $amount)
    {
        $bao_dan_buy_amount = M('store')->where(['uid' => $user_id])->getField('bao_dan_buy_amount');
        $total_amount = $bao_dan_buy_amount + $amount;
        if ($total_amount > Constants::bao_dan_buy_amount) {
            return false;
        }
        return true;
    }

    /**
     * 释放用户冻结的金额
     *
     * @param $userid
     * @return bool
     */
    public static function releaseFreezeMoney($userid)
    {
        $storeModel = M('store');
        $store = $storeModel->where(['uid' => $userid])->find();
        $results_integral = $store['results_integral'];
        $freeze_red_results = 0;
        if ($store['freeze_red_results'] > 0) {//释放冻结红包金额
            if ($results_integral > $store['freeze_red_results']) {
                $freeze_red_results = $store['freeze_red_results'];
            } else {
                $freeze_red_results = $results_integral;
            }
            $results_integral -= $freeze_red_results;
            if ($freeze_red_results > 0) {
                //写入记录
                $redRecord = [
                    'uid' => $userid,
                    'amount' => $freeze_red_results,
                    'type' => 2,
                    'create_time' => time()
                ];
                $res = M('red_envelope_record')->add($redRecord);
                if (!$res) {
                    return false;
                }
            }
        }
        $freeze_gift_results = 0;
        if ($store['freeze_gift_results'] > 0) {//释放冻结礼品券金额
            if ($results_integral > $store['freeze_gift_results']) {
                $freeze_gift_results = $store['freeze_gift_results'];
            } else {
                $freeze_gift_results = $results_integral;
            }
            $results_integral -= $freeze_gift_results;

            if ($freeze_gift_results) {
                //写入记录
                $gifRecord = [
                    'uid' => $userid,
                    'amount' => $freeze_gift_results,
                    'type' => 2,
                    'create_time' => time()
                ];
                $res = M('gift_certificate_record')->add($gifRecord);
                if (!$res) {
                    return false;
                }
            }
        }
        $service_fee = 0;
        if ($store['freeze_service_money'] > 0) {
            if ($results_integral > $store['freeze_service_money']) {
                $service_fee = $store['freeze_service_money'];
            } else {
                $service_fee = $results_integral;
            }
            $results_integral -= $service_fee;
        }

        if ($freeze_red_results > 0 || $freeze_gift_results > 0 || $service_fee > 0) {
            $data = [
                'red_envelope' => $store['red_envelope'] + $freeze_red_results,
                'gift_certificate' => $store['gift_certificate'] + $freeze_gift_results,
                'freeze_red_results' => $store['freeze_red_results'] - $freeze_red_results,
                'freeze_gift_results' => $store['freeze_gift_results'] - $freeze_gift_results,
                'freeze_service_money' => $store['freeze_service_money'] - $service_fee,
                'results_integral' => $results_integral
            ];
            $res = $storeModel->where(['uid' => $userid])->save($data);
            if (!$res) {
                return false;
            }

            $deduct_share_points = $store['results_integral'] - $results_integral;
            //分享积分记录
            $shared_points_record = [
                'uid' => $userid,
                'amount' => -$deduct_share_points,
                'type' => 3,
                'create_time' => time()
            ];
            $res = M('shared_points_record')->add($shared_points_record);
            if (!$res) {
                return false;
            }
        }

        return true;
    }

    /**
     * 用户购物-上级得到奖励
     *
     * @param int $userid 购买商品的用户
     * @param double $order_result 用户购买商品得到的总业绩
     * @return bool
     */
    public static function sendReward($userid, $order_result)
    {
        $userInfo = M('user')->where(['userid' => $userid])->field('pid,gid')->find();
        $pid = $userInfo['pid'];
        $gid = $userInfo['gid'];

        /**一级代数奖发放*/
        if (empty($pid) || empty($order_result)) {
            return true;
        }

        $res = self::sendPidReward($userid, $pid, $order_result);
        if (!$res) {
            return false;
        }

        /**二级代数奖发放*/
        if (empty($gid)) {
            return true;
        }

        $res = self::sendGidReward($userid, $gid, $order_result);
        if (!$res) {
            return false;
        }

        return true;
    }

    /**
     * 一级代数奖励
     *
     * @param $userid
     * @param $order_result
     * @return bool
     */
    public function sendPidReward($userid, $pid, $order_result)
    {
        //一级代数奖比例
        $algebra_award_first = M('config')->where(['name' => 'algebra_award_first'])->getField('value') / 100;
        //一级奖励
        $first_red_money = $order_result * $algebra_award_first * 0.8;    //红包金额
        $first_gift_money = $order_result * $algebra_award_first * 0.1;   //礼品券金额
        $first_service_fee = $order_result * $algebra_award_first * 0.1;  //服务费

        $first_store = M('store')->where(['uid' => $pid])->field('red_envelope,gift_certificate,results_integral,freeze_red_results,freeze_gift_results,freeze_service_money')->find(); //pid的总业绩积分
        $results_integral = $first_store['results_integral'];

        if ($first_red_money > 0) {//添加可用积分
            if ($results_integral >= $first_red_money) {
                $first_get_red_money = $first_red_money;
            } else {
                $first_get_red_money = $results_integral;
            }
            $freeze_red_results = $first_red_money - $first_get_red_money; //冻结金额
            if ($freeze_red_results > 0) {
                $first_store_data['freeze_red_results'] = $first_store['freeze_red_results'] + $freeze_red_results;
            }
            $first_store_data['red_envelope'] = $first_store['red_envelope'] + $first_get_red_money;
            $results_integral -= $first_get_red_money;

            if ($first_get_red_money > 0) {
                //写入记录
                $redRecord = [
                    'uid' => $pid,
                    'amount' => $first_get_red_money,
                    'type' => 3,
                    'remark' => $userid,
                    'create_time' => time()
                ];
                $res = M('red_envelope_record')->add($redRecord);
                if (!$res) {
                    return false;
                }
            }
        }

        if ($first_gift_money > 0) {//添加礼品券
            if ($results_integral >= $first_gift_money) {
                $first_get_gift_money = $first_gift_money;
            } else {
                $first_get_gift_money = $results_integral;
            }
            $freeze_gift_results = $first_gift_money - $first_get_gift_money; //冻结金额
            if ($freeze_gift_results > 0) {
                $first_store_data['freeze_gift_results'] = $first_store['freeze_gift_results'] + $freeze_gift_results;
            }
            $first_store_data['gift_certificate'] = $first_store['gift_certificate'] + $first_get_gift_money;
            $results_integral -= $first_get_gift_money;
            if ($first_get_gift_money > 0) {
                //写入记录
                $gifRecord = [
                    'uid' => $pid,
                    'amount' => $first_get_gift_money,
                    'type' => 3,
                    'remark' => $userid,
                    'create_time' => time()
                ];
                $res = M('gift_certificate_record')->add($gifRecord);
                if (!$res) {
                    return false;
                }
            }
        }

        if ($first_service_fee > 0) {//扣除服务费
            if ($results_integral >= $first_service_fee) {
                $first_service_money = $first_service_fee;
            } else {
                $first_service_money = $results_integral;
            }
            $freeze_service_money = $first_service_fee - $first_service_money; //冻结服务费金额
            if ($freeze_service_money > 0) {
                $first_store_data['freeze_service_money'] = $first_store['freeze_service_money'] + $freeze_service_money;
            }
            $results_integral -= $first_service_money;
        }

        if (!empty($first_store_data)) {
            $first_store_data['results_integral'] = $results_integral;
            $res = M('store')->where(array('uid' => $pid))->save($first_store_data);
            if (!$res) {
                return false;
            }
        }

        $deduct_share_points = $first_store['results_integral'] - $results_integral;
        if ($deduct_share_points > 0) {
            //分享积分记录
            $shared_points_record = [
                'uid' => $pid,
                'amount' => -$deduct_share_points,
                'type' => 1,
                'remark' => $userid,
                'create_time' => time()
            ];
            $res = M('shared_points_record')->add($shared_points_record);
            if (!$res) {
                return false;
            }
        }

        return true;
    }

    /**
     * 二级代数奖励
     *
     * @param $userid
     * @param $gid
     * @param $order_result
     * @return bool
     */
    public function sendGidReward($userid, $gid, $order_result)
    {
        //二级代数奖比例及满足要求(二级用户直推人数达到系统设置人数且每个人的总业绩也需要达标)
        $algebra_award_second = M('config')->where(['name' => 'algebra_award_second'])->getField('value') / 100;                   //比例
        $algebra_award_second_num = M('config')->where(['name' => 'algebra_award_second_num'])->getField('value');           //达标直推人数
        $algebra_award_second_account = M('config')->where(['name' => 'algebra_award_second_account'])->getField('value');   //达标业绩

        $sql = 'SELECT COUNT(*) as num FROM ysk_user as us LEFT JOIN ysk_store as st ON st.uid = us.userid WHERE us.pid = ' . $gid . ' AND st.total_results >=' . $algebra_award_second_account;
        $count = M()->query($sql);
        if ($count[0]['num'] >= $algebra_award_second_num) {
            $second_red_money = $order_result * $algebra_award_second * 0.8;      //红包金额
            $second_gift_money = $order_result * $algebra_award_second * 0.1;     //礼品券金额
            $second_service_fee = $order_result * $algebra_award_second * 0.1;  //服务费

            $gid_store = M('store')->where(['uid' => $gid])->field('red_envelope,gift_certificate,results_integral,freeze_red_results,freeze_gift_results,freeze_service_money')->find(); //gid的总业绩积分
            $results_integral = $gid_store['results_integral'];

            if ($second_red_money > 0) {//添加可用积分
                if ($results_integral >= $second_red_money) {
                    $second_get_red_money = $second_red_money;
                } else {
                    $second_get_red_money = $results_integral;
                }
                $freeze_red_results = $second_red_money - $second_get_red_money; //冻结金额
                if ($freeze_red_results > 0) {
                    $second_store_data['freeze_red_results'] = $gid_store['freeze_red_results'] + $freeze_red_results;
                }
                $second_store_data['red_envelope'] = $gid_store['red_envelope'] + $second_get_red_money;
                $results_integral -= $second_get_red_money;

                if ($second_get_red_money > 0) {
                    //写入记录
                    $redRecord = [
                        'uid' => $gid,
                        'amount' => $second_get_red_money,
                        'type' => 3,
                        'remark' => $userid,
                        'create_time' => time()
                    ];
                    $res = M('red_envelope_record')->add($redRecord);
                    if (!$res) {
                        return false;
                    }
                }
            }
            if ($second_gift_money > 0) {//添加礼品券
                if ($results_integral >= $second_gift_money) {
                    $second_get_gift_money = $second_gift_money;
                } else {
                    $second_get_gift_money = $results_integral;
                }
                $freeze_gift_results = $second_gift_money - $second_get_gift_money; //冻结金额
                if ($freeze_gift_results > 0) {
                    $second_store_data['freeze_gift_results'] = $gid_store['freeze_gift_results'] + $freeze_gift_results;
                }
                $second_store_data['gift_certificate'] = $gid_store['gift_certificate'] + $second_get_gift_money;
                $results_integral -= $second_get_gift_money;

                if ($second_get_gift_money > 0) {
                    //写入记录
                    $gifRecord = [
                        'uid' => $gid,
                        'amount' => $second_get_gift_money,
                        'type' => 3,
                        'remark' => $userid,
                        'create_time' => time()
                    ];
                    $res = M('gift_certificate_record')->add($gifRecord);
                    if (!$res) {
                        return false;
                    }
                }
            }
            if ($second_service_fee > 0) {//扣除服务费
                if ($results_integral >= $second_service_fee) {
                    $second_service_money = $second_service_fee;
                } else {
                    $second_service_money = $results_integral;
                }
                $freeze_service_money = $second_service_fee - $second_service_money; //冻结服务费金额
                if ($freeze_service_money > 0) {
                    $second_store_data['freeze_service_money'] = $gid_store['freeze_service_money'] + $freeze_service_money;
                }
                $results_integral -= $second_service_money;
            }

            if (!empty($second_store_data)) {
                $second_store_data['results_integral'] = $results_integral;
                $res = M('store')->where(array('uid' => $gid))->save($second_store_data);
                if (!$res) {
                    return false;
                }
            }

            $deduct_share_points = $gid_store['results_integral'] - $results_integral;
            if ($deduct_share_points > 0) {
                //分享积分记录
                $shared_points_record = [
                    'uid' => $gid,
                    'amount' => -$deduct_share_points,
                    'type' => 1,
                    'remark' => $userid,
                    'create_time' => time()
                ];
                $res = M('shared_points_record')->add($shared_points_record);
                if (!$res) {
                    return false;
                }
            }

        }

        return true;
    }

    /**
     * 发放达标奖
     *
     * @param $userid
     * @param $order_result
     * @return bool
     */
    public static function sendStandardAward($userid, $order_result)
    {
        $path = M('user')->where(['userid' => $userid])->getField('path');
        if (empty($path) || empty($order_result)) {
            return true;
        }

        $pathList = explode('-', $path);
        array_shift($pathList);//去除数组第一个值
        array_pop($pathList);//去除数组最后一个值
        $pathList = array_reverse($pathList);

        foreach ($pathList as $key => $id) {
            $oldGrand = M('user')->where(['userid' => $id])->getField('use_grade');
            $pidList = M('user')->where(['pid' => $id])->getField('userid', true);
            if (count($pidList) < 2) {
                continue;
            }
            $data = [];
            foreach ($pidList as $item) {
                $userResults = M('store')->where(['uid' => $item])->getField('total_results');
                $data[] = UserModel::userTeamPerformance($item) + $userResults;
            }
            asort($data);//正序排序
            $user_regional_performance = array_pop($data); //最后一个值出栈
            $user_cell_performance = array_sum($data); //数组值相加
            $newGrand = GrandModel::getUserLevel($user_regional_performance, $user_cell_performance);

            if ($oldGrand != $newGrand) {//修改用户等级
                $res = M('user')->where(['userid' => $id])->save(['use_grade' => $newGrand]);
                if ($res === false) {
                    return false;
                }
            }

            if ($newGrand == 0) {
                continue;
            }
            $grand_ratio = M('grand')->where(['id' => $newGrand])->getField('ratio');
            $poor_ration = $grand_ratio;
            if ($key != 0) {
                $last_user_grand = M('user')->where(['userid' => $pathList[$key - 1]])->getField('use_grade');
                $last_user_ration = M('grand')->where(['id' => $last_user_grand])->getField('ratio');
                $poor_ration = $grand_ratio - $last_user_ration;
            }

            if ($poor_ration <= 0) {
                continue;
            }

            $red_money = $order_result * ($poor_ration / 100) * 0.8;
            $gift_money = $order_result * ($poor_ration / 100) * 0.1;
            $service_fee = $order_result * ($poor_ration / 100) * 0.1;  //服务费

            $user_store = M('store')->where(['uid' => $id])->field('red_envelope,gift_certificate,results_integral,freeze_red_results,freeze_gift_results,freeze_service_money')->find(); //gid的总业绩积分
            $results_integral = $user_store['results_integral'];

            if ($red_money > 0) {//添加可用积分
                if ($results_integral >= $red_money) {
                    $get_red_money = $red_money;
                } else {
                    $get_red_money = $results_integral;
                }
                $freeze_red_results = $red_money - $get_red_money; //冻结金额
                if ($freeze_red_results > 0) {
                    $user_store_data['freeze_red_results'] = $user_store['freeze_red_results'] + $freeze_red_results;
                }
                $user_store_data['red_envelope'] = $user_store['red_envelope'] + $get_red_money;
                $results_integral -= $get_red_money;

                if ($get_red_money > 0) {
                    //写入记录
                    $redRecord = [
                        'uid' => $id,
                        'amount' => $get_red_money,
                        'type' => 4,
                        'remark' => $userid,
                        'create_time' => time()
                    ];
                    $res = M('red_envelope_record')->add($redRecord);
                    if (!$res) {
                        return false;
                    }
                }
            }
            if ($gift_money > 0) {//添加礼品券
                if ($results_integral >= $gift_money) {
                    $get_gift_money = $gift_money;
                } else {
                    $get_gift_money = $results_integral;
                }
                $freeze_gift_results = $gift_money - $get_gift_money; //冻结金额
                if ($freeze_gift_results > 0) {
                    $user_store_data['freeze_gift_results'] = $user_store['freeze_gift_results'] + $freeze_gift_results;
                }
                $user_store_data['gift_certificate'] = $user_store['gift_certificate'] + $get_gift_money;
                $results_integral -= $get_gift_money;

                if ($get_gift_money > 0) {
                    //写入记录
                    $gifRecord = [
                        'uid' => $id,
                        'amount' => $get_gift_money,
                        'type' => 4,
                        'remark' => $userid,
                        'create_time' => time()
                    ];
                    $res = M('gift_certificate_record')->add($gifRecord);
                    if (!$res) {
                        return false;
                    }
                }
            }
            if ($service_fee > 0) {//扣除服务费
                if ($results_integral >= $service_fee) {
                    $service_money = $service_fee;
                } else {
                    $service_money = $results_integral;
                }
                $freeze_service_money = $service_fee - $service_money; //冻结服务费金额
                if ($freeze_service_money > 0) {
                    $user_store_data['freeze_service_money'] = $user_store['freeze_service_money'] + $freeze_service_money;
                }
                $results_integral -= $service_money;
            }

            if (!empty($user_store_data)) {
                $user_store_data['results_integral'] = $results_integral;
                $res = M('store')->where(array('uid' => $id))->save($user_store_data);
                if (!$res) {
                    return false;
                }
            }

            $deduct_share_points = $user_store['results_integral'] - $results_integral;
            if ($deduct_share_points > 0) {
                //分享积分记录
                $shared_points_record = [
                    'uid' => $id,
                    'amount' => -$deduct_share_points,
                    'type' => 2,
                    'remark' => $userid,
                    'create_time' => time()
                ];
                $res = M('shared_points_record')->add($shared_points_record);
                if (!$res) {
                    return false;
                }
            }

        }
        return true;
    }

    /**
     * 生态总资产提现
     * @return bool
     */
    public function withdrawal()
    {
        $uid = session('userid');
        $amount = trim(I('amount'));
        $safety_pwd = trim(I('safety_pwd'));
        $card_id = trim(I('cardid'));
        if (empty($card_id)) {
            $this->error = '银行卡不能为空';
            return false;
        }

        $bankInfo = M('ubanks')->where(['id' => $card_id, 'delete_time' => 0])->getField('id');
        if (!$bankInfo) {
            $this->error = '该银行卡不存在，请从新选择';
            return false;
        }

        if (empty($amount)) {
            $this->error = '请输入提现金额';
            return false;
        }
        if (!isNumberBy100($amount)) {
            $this->error = '请输入100的整数倍';
            return false;
        }
        if (empty($safety_pwd)) {
            $this->error = '请输入交易密码';
            return false;
        }
        //验证交易密码
        $res = Trans($uid, $safety_pwd);
        if (!$res['status']) {
            $this->error = $res['msg'];
            return false;
        }
        $storeInfo = M('store')->where(['uid' => $uid])->field('cangku_num')->find();
        if ($amount > $storeInfo['cangku_num']) {
            $this->error = '您的生态总资产不足提现的金额';
            return false;
        }

        try {
            M()->startTrans();
            //扣除生态总资产
            $this->changStore($uid, 'cangku_num', -$amount, 18);
            $fee_ratio = M('config')->where(['name' => 'withdrawal_ratio'])->getField('value');
            $poundage = formatNum2($fee_ratio * $amount);

            //添加提现记录
            $withdrawalRecord = [
                'uid' => $uid,
                'amount' => $amount,
                'poundage' => $poundage,
                'status' => 0,
                'bank_id' => $card_id,
                'type' => Constants::WITHDRAWAL_TYPE_ECOLOGY,
                'create_time' => date('YmdHis', time())
            ];
            $res = M('withdrawal_record')->add($withdrawalRecord);
            if (!$res) {
                throw new Exception('添加提现记录失败');
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
     * 营业款提现
     * @return bool
     */
    public function withTurnover()
    {
        $uid = session('userid');
        $amount = intval(trim(I('amount')));
        $safety_pwd = trim(I('safety_pwd'));
        $card_id = trim(I('cardid'));
        if (empty($card_id)) {
            $this->error = '银行卡不能为空';
            return false;
        }

        $bankInfo = M('ubanks')->where(['id' => $card_id, 'delete_time' => 0])->getField('id');
        if (!$bankInfo) {
            $this->error = '该银行卡不存在，请从新选择';
            return false;
        }

        if (empty($amount)) {
            $this->error = '请输入提现金额';
            return false;
        }
        if (empty($safety_pwd)) {
            $this->error = '请输入交易密码';
            return false;
        }
        //验证交易密码
        $res = Trans($uid, $safety_pwd);
        if (!$res['status']) {
            $this->error = $res['msg'];
            return false;
        }
        $storeInfo = M('store')->where(['uid' => $uid])->field('turnover')->find();
        if ($amount > $storeInfo['turnover']) {
            $this->error = '您的营业款账户不足提现的金额';
            return false;
        }

        try {
            M()->startTrans();
            //扣除营业款账户
            StoreRecordModel::addRecord($uid, 'turnover', -$amount, Constants::STORE_TYPE_TURNOVER, 1);

            //添加提现记录
            $withdrawalRecord = [
                'uid' => $uid,
                'amount' => $amount,
                'status' => 0,
                'bank_id' => $card_id,
                'type' => Constants::WITHDRAWAL_TYPE_TURNOVER,
                'create_time' => date('YmdHis', time())
            ];
            $res = M('withdrawal_record')->add($withdrawalRecord);
            if (!$res) {
                throw new Exception('添加提现记录失败');
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
     * 兑换(生态总资产兑换消费通证)
     * @return bool
     */
    public function exchange()
    {
        $uid = session('userid');
        $amount = trim(I('amount'));
        $safety_pwd = trim(I('safety_pwd'));

        if (empty($amount)) {
            $this->error = '请输入兑换数量';
            return false;
        }
        if (!isNumberBy100($amount)) {
            $this->error = '请输入100的整数倍';
            return false;
        }
        if (empty($safety_pwd)) {
            $this->error = '请输入交易密码';
            return false;
        }
        //验证交易密码
        $res = Trans($uid, $safety_pwd);
        if (!$res['status']) {
            $this->error = $res['msg'];
            return false;
        }
        $cangku_num = M('store')->where(['uid' => $uid])->getField('cangku_num');
        $fee_ratio = M('config')->where(['name' => 'ecology_to_bao_dan_fee_ratio'])->getField('value');
        $ecology_amount = $amount * (1 + $fee_ratio);
        if ($ecology_amount > $cangku_num) {
            $this->error = '您的生态总资产不足';
            return false;
        }
        try {
            M()->startTrans();
            //扣除生态总资产
            StoreModel::changStore($uid, 'cangku_num', -$ecology_amount, 10);
            //添加消费通证
            StoreModel::changStore($uid, 'fengmi_num', $amount, 9);
            M()->commit();
            return true;
        } catch (Exception $ex) {
            M()->rollback();
            $this->error = $ex->getMessage();
            return false;
        }
    }

    /**
     * 兑换(生态通证兑换生态总资产)
     * @return bool
     */
    public function exchangeTwo()
    {
        $uid = session('userid');
        $amount = intval(I('amount'));
        $safety_pwd = trim(I('safety_pwd'));

        if ($amount <= 0) {
            $this->error = '请输入兑换数量';
            return false;
        }

        if (empty($safety_pwd)) {
            $this->error = '请输入交易密码';
            return false;
        }
        //验证交易密码
        $res = Trans($uid, $safety_pwd);
        if (!$res['status']) {
            $this->error = $res['msg'];
            return false;
        }
        $storeInfo = M('store')->where(['uid' => $uid])->field('cangku_num,exchange_amount')->find();
        $pass_card_amount = UcoinsModel::getAmount($uid); //生态通证数量
        if ($amount > $pass_card_amount) {
            $this->error = '您的生态通证不足兑换数量';
            return false;
        }
        $coin_price = UcoinsModel::getCoinPrice();
        $exchange_num = formatNum2($amount * $coin_price);
        if ($exchange_num > $storeInfo['exchange_amount']) {
            $this->error = '您的兑换额度不足所需兑换额度';
            return false;
        }
        try {
            M()->startTrans();
            //扣减兑换额度
            $res = M('store')->where(['uid' => $uid])->setDec('exchange_amount', $exchange_num);
            if (!$res) {
                throw new Exception('扣除兑换额度失败');
            }
            //扣除生态通证
            UcoinsModel::addRecord($uid, -$amount, 2);
            //添加生态总资产
            StoreModel::changStore($uid, 'cangku_num', $exchange_num, 14);
            M()->commit();
            return true;
        } catch (Exception $ex) {
            M()->rollback();
            $this->error = $ex->getMessage();
            return false;
        }
    }

    /**
     * 转账
     * @return bool
     */
    public function transfer()
    {
        $uid = session('userid');
        $amount = ceil(I('amount'));
        $userAccount = trim(I('account'));
        $type = trim(I('type', 'cangku_num'));
        $safety_pwd = trim(I('safety_pwd'));

        if (empty($userAccount)) {
            $this->error = '对方账户不能为空';
            return false;
        }

        $transfer_user_id = M('user')->where(['mobile' => $userAccount])->getField('userid');
        if (empty($transfer_user_id)) {
            $this->error = '转账用户不存在,请重新输入';
            return false;
        }

        if ($transfer_user_id == $uid) {
            $this->error = '转账用户不能为自己';
            return false;
        }

        if (empty($amount) || $amount <= 0) {
            $this->error = '请输入提现金额';
            return false;
        }

        if (empty($safety_pwd)) {
            $this->error = '请输入交易密码';
            return false;
        }

        //验证交易密码
        $res = Trans($uid, $safety_pwd);
        if (!$res['status']) {
            $this->error = $res['msg'];
            return false;
        }

        $userInfo = M('user')->where(['userid' => $uid])->field('level')->find();
        if ($userInfo['level'] == Constants::USER_LEVEL_NOT_ACTIVATE) {
            $this->error = '您还未激活，请先激活在转账';
            return false;
        }
        $storeInfo = M('store')->where(['uid' => $uid])->field('cangku_num,fengmi_num')->find();
        try {
            M()->startTrans();
            if ($type == 'fengmi_num') {
                if ($amount > $storeInfo['fengmi_num']) {
                    throw new Exception('您的消费通证不足兑换数量，请重新输入');
                }
                //用户扣除消费通证
                StoreModel::changStore($uid, 'fengmi_num', -$amount, 11, 1, $transfer_user_id);
                //对方账户获得消费通证
                StoreModel::changStore($transfer_user_id, 'fengmi_num', $amount, 11, 1, $uid);
            } else {
                $fee_ratio = M('config')->where(['name' => 'transfer_accounts_fee_ratio'])->getField('value');
                $deduct_amount = $amount * (1 + $fee_ratio);
                if ($deduct_amount > $storeInfo['cangku_num']) {
                    throw new Exception('您的生态总资产不足兑换数量，请重新输入');
                }
                //用户扣除生态总资产
                StoreModel::changStore($uid, 'cangku_num', -$deduct_amount, 12, 1, $transfer_user_id);
                //对方账户获得消费通证
                StoreModel::changStore($transfer_user_id, 'cangku_num', $amount, 12, 1, $uid);
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
     * 开通交易
     * @return bool
     */
    public function recharge()
    {
        try {
            $uid = session('userid');
            $picname = $_FILES['uploadfile']['name'];
            $picsize = $_FILES['uploadfile']['size'];
            $amount = Constants::open_power_sell_amount;
            $safety_pwd = trim(I('pwd'));
            if (empty($safety_pwd)) {
                throw new Exception('请输入交易密码');
            }

            //验证交易密码
            $res = Trans($uid, $safety_pwd);
            if (!$res['status']) {
                $this->error = $res['msg'];
                return false;
            }

            $userInfo = M('user')->where(['userid' => $uid])->field('quanxian')->find();
            $power = getArray($userInfo['quanxian']);
            if (!in_array(4, $power)) {
                $this->error = '您已经有卖出的权限，无需再开通';
                return false;
            }

            if ($picname == '' || $picsize == '') {
                throw new Exception('请上传凭证');
            }

            if ($picsize > 5767168) { //限制上传大小（最大只能是5.5M）
                throw new Exception('图片大小不能超过2M');
            }

            $type = strstr($picname, '.'); //限制上传格式
            $arrType = ['.gif', '.GIF', '.jpg', '.JPG', '.png', '.PNG', '.jpeg', '.JPEG'];
            if (!in_array($type, $arrType)) {
                throw new Exception('图片格式不对');
            }
            $pics = uniqid() . $type; //命名图片名称
            //上传路径
            $pic_path = "./Uploads/recharge/" . $pics;
            move_uploaded_file($_FILES['uploadfile']['tmp_name'], $pic_path);
            $size = round($picsize / 1024, 2); //转换成kb
            $pic_path = trim($pic_path, '.');

            if (!$size) {
                throw new Exception('图片上传失败');
            }

            $rechargeData = [
                'uid' => $uid,
                'amount' => $amount,
                'image' => $pic_path,
                'create_time' => date('YmdHis', time())
            ];
            $res = M('recharge')->add($rechargeData);
            if (!$res) {
                throw new Exception('开通失败，请重试');
            }
            return true;
        } catch (Exception $ex) {
            $this->error = $ex->getMessage();
            return false;
        }

    }

    /**
     * 判断用户累计是否达标，达标则流动资产释放到流动通证
     * @param $uid
     * @param $amount
     * @param int $current_assets_type 流动资产类型
     * @param int $flow_type 流动通证类型
     * @throws Exception
     */
    public static function flowReleasePassCard($uid, $amount, $current_assets_type, $flow_type)
    {
        $storeInfo = M('store')->where(['uid' => $uid])->field('total_amount,current_assets')->find();
        $result_amount = $storeInfo['total_amount'] + $amount;
        if ($result_amount >= Constants::total_amount) {
            $res = M('store')->where(['uid' => $uid])->setField('total_amount', 0);
            if (!$res) {
                throw new Exception('修改用户累计金额失败');
            }
            //从流动资产释放到流动通证
            $release_amount = formatNum2($result_amount * 0.5);
            if ($release_amount > $storeInfo['current_assets']) {
                $real_release_amount = $storeInfo['current_assets'];
            } else {
                $real_release_amount = $release_amount;
            }

            //扣除流动资产
            StoreRecordModel::addRecord($uid, 'current_assets', -$real_release_amount, Constants::STORE_TYPE_CURRENT_ASSETS, $current_assets_type);
            //增加流动通证
            StoreRecordModel::addRecord($uid, 'can_flow_amount', $real_release_amount, Constants::STORE_TYPE_CAN_FLOW, $flow_type);
        } else {
            $res = M('store')->where(['uid' => $uid])->setInc('total_amount', $amount);
            if (!$res) {
                throw new Exception('增加用户累计失败');
            }
        }
    }
}