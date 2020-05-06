<?php

namespace Home\Model;

use Common\Util\Constants;
use Think\Exception;

/**
 * 用户模型
 */
class StoreModel extends \Common\Model\StoreModel
{

    //月销量达标值
    const month_amount_f_one = 100000;
    const month_amount_f_two = 200000;
    const month_amount_f_three = 300000;
    const month_amount_f_four = 400000;
    const month_amount_f_five = 500000;
    const month_amount_f_six = 1000000;
    const month_amount_f_seven = 2000000;
    const month_amount_f_eight = 3000000;
    const month_amount_f_nine = 5000000;
    //月销量达标值比例
    const month_amount_f_one_ratio = 0.05;
    const month_amount_f_two_ratio = 0.06;
    const month_amount_f_three_ratio = 0.07;
    const month_amount_f_four_ratio = 0.08;
    const month_amount_f_five_ratio = 0.09;
    const month_amount_f_six_ratio = 0.12;
    const month_amount_f_seven_ratio = 0.14;
    const month_amount_f_eight_ratio = 0.16;
    const month_amount_f_nine_ratio = 0.18;

    /**
     * 获取伞下用户数量、业绩
     * @param $user_id
     * @return array
     * @author ldz
     * @time 2020/1/18 12:00
     */
    public static function getTeamInfo($user_id)
    {
        $where['path'] = ['like', '%-' . $user_id . '-%'];
        $teamUser = M('user u')->where($where)
            ->join('ysk_store ys on ys.uid = u.userid')
            ->field('u.userid,ys.total_release')
            ->select();

        $teamNum = count($teamUser);//伞下用户数量
        $teamAchievement = array_sum(array_column($teamUser, 'total_release')); //伞下业绩

        return [$teamNum, $teamAchievement];
    }

    /**
     * 获取销毁总QBB数，交易超时付款扣除QBB数量的总和
     * @param bool $is_admin 是否加上后台设置数量
     * @return int|mixed
     * @author ldz
     * @time 2020/1/18 13:49
     */
    public static function getOverTimeQbbNum($is_admin = true)
    {
        $over_time_qbb_num = 0;
        if ($is_admin) {
            $over_time_qbb_num += D('config')->getValue('over_time_qbb_num');
        }

        $over_time_qbb_num += M('store')->sum('huafei_total');

        return $over_time_qbb_num;
    }

    public function CangkuNum($where)
    {
        if (empty($where)) {
            $userid = get_userid();
            $where['uid'] = $userid;
        }
        return $this->where($where)->getField('cangku_num');
    }

    //扣减仓库数量
    public function DesNum($num)
    {
        if (empty($num)) {
            $this->error = "参数错误";
            return false;
        }
        $userid = session('userid');
        $where['uid'] = $userid;
        $cangku_num = $this->where($where)->getField('cangku_num');
        if ($cangku_num < $num) {
            $this->error = "仓库果子不足";
            return false;
        }
        $res = $this->where($where)->setDec('cangku_num', $num);
        if ($res) {
            return true;
        } else {
            $this->error = "操作失败";
            return false;
        }
    }

    //增加仓库数量
    public function IncNum($num, $where = null)
    {
        if (empty($num))
            return false;

        if ($where == null) {
            $userid = get_userid();
            $where['uid'] = $userid;
        }
        $res = $this->where($where)->setInc('cangku_num', $num);
        return $res;
    }

    //增加拆分累计
    public function IncHuaFei($num, $where = null)
    {
        if (empty($num))
            return false;
        if ($where == null) {
            $userid = get_userid();
            $where['uid'] = $userid;
        }
        $res = $this->where($where)->setInc('huafei_num', $num);
        if ($res)
            return $this->where($where)->getField('huafei_num');
        else
            return false;
    }

    //创建仓库
    public function CreateCangku($num, $uid)
    {
        if (empty($uid))
            return false;
        $data['cangku_num'] = $num;
        $data['uid'] = $uid;
        $res = $this->add($data);
        if ($res === false)
            return false;
        //创建15块地
        for ($i = 1; $i <= 15; $i++) {
            $seed = 0;
            $status = 0;
            if ($i <= 5) {
                $farm_type = 1;
            } else if (5 < $i && $i <= 10) {
                $farm_type = 2;
            } else {
                $farm_type = 3;
            }
            $datalist[] = array('uid' => $uid, 'f_id' => $i, 'farm_type' => $farm_type, 'status' => $status, 'seeds' => $seed);
        }
        D('nzusfarm')->addAll($datalist);
        //添加等级表
        M('user_level')->add(array('uid' => $uid));
        //添加施肥表
        M('user_huafei')->add(array('uid' => $uid));
        return true;
    }

    //增加总矿石
    public function IncTotal($num)
    {
        if (empty($num))
            return false;

        $userid = get_userid();
        $where['uid'] = $userid;
        $res = $this->where($where)->setInc('total_num', $num);
        return $res;
    }

    /**
     * EP兑换EF
     */
    public function exchange()
    {
        $userid = session('userid');
        $exchangeNum = round(I('exchangeNum', 0));
        $safety_salt = trim(I('pwd'));
        $exchange_type = trim(I('exchange_type', 'qbb_exchange'));
        $userModel = D('Home/User');
        if (empty($exchangeNum)) {
            $this->error = '请输入兑换数量';
            return false;
        }
        if (empty($safety_salt)) {
            $this->error = '请输入交易密码';
            return false;
        }
        $userInfo = $userModel->where(['userid' => $userid])->field('account,level,empty_order')->find();
        //验证交易密码
        $userModel->Trans($userInfo['account'], $safety_salt);

        if ($exchange_type == 'qbb_exchange') {
            $cid = 1;
            $text = 'QBB';
        } else {
            $cid = 2;
            $text = 'KB';
        }

        $ucoins_num = M('ucoins')->where(['cid' => $cid, 'c_uid' => $userid])->getField('c_nums'); //当前QBB/KB数量
        $coindets = M('coindets')->order('coin_addtime desc,cid asc')->where(array("cid" => $cid))->find();
        $price = $coindets['coin_price'];
        $stock_right_price = D('config')->getValue('stock_right_price');//当前股权价格
        $need_qbb_num = formatNum2($exchangeNum * $stock_right_price / $price, 4);

        if ($need_qbb_num > $ucoins_num) {
            $this->error = '兑换数量所需' . $text . '数量大于您的当前QBB数量，请重新输入';
            return false;
        }
        try {
            M()->startTrans();   //开启事务
            //增加股权数量
            $userData['fengmi_num'] = array('exp', 'fengmi_num +' . $exchangeNum);
            $res = M('store')->where(array('uid' => $userid))->save($userData);
            if (!$res) {
                throw new \Exception('兑换失败');
            }
            //添加ep记录
            $storeInfo = M('store')->where(['uid' => $userid])->field('fengmi_num')->find();
            $data['pay_id'] = $userid;
            $data['get_id'] = $userid;
            $data['get_nums'] = $exchangeNum;
            $data['now_nums_get'] = $storeInfo['fengmi_num'];
            $data['now_nums'] = $storeInfo['fengmi_num'] - $exchangeNum;
            $data['is_release'] = 1;
            $data['get_time'] = time();
            $data['get_type'] = 1;
            $res = M('tranmoney')->add($data);
            if (!$res) {
                throw new \Exception('股权流水添加失败');
            }

            //扣除
            $payout['djie_nums'] = array('exp', 'djie_nums + ' . $need_qbb_num);
            $payout['c_nums'] = array('exp', 'c_nums - ' . $need_qbb_num);
            $res2 = M('ucoins')->where(array('c_uid' => $userid, 'cid' => $cid))->save($payout);
            if (!$res2) {
                throw new \Exception('扣除' . $text . '失败');
            }

            //添加记录
            $ucoins_record = [
                'uid' => $userid,
                'cid' => $cid,
                'amount' => -$need_qbb_num,
                'type' => 5,
                'create_time' => date('YmdHis')
            ];
            $res = M('ucoins_record')->add($ucoins_record);
            if (!$res) {
                throw new \Exception($text . '记录新增失败');
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
     * 一键归集
     */
    public function collection()
    {
        $ids = I('ids', []);
        if (count($ids) == 0) {
            $this->error = '请选一条数据';
            return false;
        }
        $safety_salt = trim(I('pwd', ''));
        if (empty($safety_salt)) {
            throw new \Exception('请输入您的交易密码');
        }
        //验证交易密码
        $userModel = D('Home/User');
        $userid = session('userid');
        $userInfo = $userModel->where(['userid' => $userid])->field('account,account_type,mobile')->find();
        $userModel->Trans($userInfo['account'], $safety_salt);
        if ($userInfo['account_type'] == 1) {
            $this->error = '只有主账户才能归集';
            return false;
        }
        try {
            M()->startTrans();   //开启事务
            $multiple = D('config')->where("name='collection_multiple'")->getField("value"); //归集倍数
            $total_num = 0;
            foreach ($ids as $id) {
                $where['us.mobile'] = $userInfo['mobile'];
                $where['us.account_type'] = 1;
                $where['us.userid'] = $id;
                $where['_string'] = 'us.level > 0';
                $child_account = M('user as us')->join('LEFT JOIN  ysk_store as st on st.uid = us.userid')
                    ->where($where)->field('us.userid,us.level,us.activation_time,st.uid,st.cangku_num')
                    ->find();

                if (empty($child_account)) {
                    throw new \Exception('用户ID:' . $id . '不满足归集条件');
                }

                $num = floor($child_account['cangku_num'] / $multiple);

                if ($num == 0) {
                    continue;
                }

                $cangku_num = $num * $multiple;
                $total_num += $cangku_num;
                //扣除用户ep
                $childData['cangku_num'] = array('exp', 'cangku_num -' . $cangku_num);
                $res = M('store')->where(array('uid' => $id))->save($childData);
                if (!$res) {
                    throw new \Exception('扣除EP值失败');
                }
                //添加ep记录
                $now_cangku_num = $child_account['cangku_num'] - $cangku_num;
                $childEpData['pay_id'] = $id;
                $childEpData['get_id'] = $id;
                $childEpData['get_nums'] = $cangku_num;
                $childEpData['now_nums_get'] = $now_cangku_num;
                $childEpData['now_nums'] = $now_cangku_num;
                $childEpData['is_release'] = 1;
                $childEpData['get_time'] = time();
                $childEpData['get_type'] = 44;
                $childEpData['remark'] = '';
                $res = M('tranmoney')->add($childEpData);
                if (!$res) {
                    throw new \Exception('ep流水添加失败');
                }

                //增加EP
                $userData['cangku_num'] = array('exp', 'cangku_num +' . $cangku_num);
                $res = M('store')->where(array('uid' => $userid))->save($userData);
                if (!$res) {
                    throw new \Exception('添加EP值失败');
                }
                //添加ep记录
                $now_cangku_num = M('store')->where(array('uid' => $userid))->getField('cangku_num');
                $data['pay_id'] = $userid;
                $data['get_id'] = $userid;
                $data['get_nums'] = $cangku_num;
                $data['now_nums_get'] = $now_cangku_num;
                $data['now_nums'] = $now_cangku_num;
                $data['is_release'] = 1;
                $data['get_time'] = time();
                $data['get_type'] = 44;
                $data['remark'] = $id;
                $res = M('tranmoney')->add($data);
                if (!$res) {
                    throw new \Exception('ep流水添加失败');
                }
            }
            if ($total_num == 0) {
                throw new \Exception('暂时没有满足的用户可以归集');
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
     * EP转账
     */
    public function Eptransfer()
    {
        $userid = session('userid');
        $intoID = intval(I('intoID', 0));
        $mobile = trim(I('mobile', ''));
        $num = trim(I('num', ''));
        $remark = trim(I('remark'));
        $safety_pwd = trim(I('safety_pwd', ''));
        M()->startTrans();   //开启事务
        try {
            if (empty($intoID)) {
                throw new \Exception('请输入转入ID');
            }
            if (empty($mobile)) {
                throw new \Exception('请输入手机后四位');
            }
            if (empty($num)) {
                throw new \Exception('请输入转出数量');
            }
            if (empty($safety_pwd)) {
                throw new \Exception('请输入交易密码');
            }
            $userModel = D('Home/User');
            $intoUserInfo = $userModel->where(['userid' => $intoID])->field('userid,service_center,service_center_account,junction_path,level')->find();
            if (empty($intoUserInfo)) {
                throw new \Exception('转入ID不存在');
            }
            if (!preg_match("/^[1-9][0-9]*$/", $num) || ($num % 100 !== 0) || $num == 0) {
                throw new \Exception('转出数量必须是100的整数倍');
            }

            $userStore = M('store')->where(['uid' => $userid])->field('cangku_num')->find();
            if ($userStore['cangku_num'] < $num) {
                throw new \Exception('您的EP数量不足');
            }
            if ($intoUserInfo['level'] == 0) {
                throw new \Exception('转账不成功，转入ID未激活');
            }

            //验证交易密码
            $userInfo = $userModel->where(['userid' => $userid])->field('account,service_center,service_center_account')->find();
            $userModel->Trans($userInfo['account'], $safety_pwd);

            if ($userInfo['service_center'] == 0) {
                throw new \Exception('只有服务中心才能转账');
            }

            /**
             * 规则：A转账给B
             * 1.A必须是服务中心
             * 2.B是A伞下的人（也就是接点底下），如果B是服务中心，判断B的服务中心是否是A，不是的话就不能转
             */
            $junction_path = $intoUserInfo['junction_path'] ? explode('-', $intoUserInfo['junction_path']) : [];
            if (!in_array($userid, $junction_path)) {
                throw new \Exception('只能转给自己伞下的会员');
            }

            //扣除用户ep金额
            $userData = ['cangku_num' => ['exp', 'cangku_num - ' . $num]];
            $res = M('store')->where(array('uid' => $userid))->save($userData);
            if (!$res) {
                throw new \Exception('扣除EP数量失败，请重试');
            }

            //转账用户天机Ep数量
            $IntoData = ['cangku_num' => ['exp', 'cangku_num +' . $num]];
            $res = M('store')->where(array('uid' => $intoID))->save($IntoData);
            if (!$res) {
                throw new \Exception('添加EP数量失败，请重试');
            }

            //流水
            $record = [
                'pay_id' => $userid,
                'get_id' => $intoID,
                'get_nums' => $num,
                'get_time' => time(),
                'remark' => $remark,
                'get_type' => 0,
            ];
            $res = M('tranmoney')->add($record);
            if (!$res) {
                throw new \Exception('添加EP流水失败，请重试');
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
     * 百宝箱中流动通证释放
     * @return bool
     * @author ldz
     * @time 2020/2/25 13:40
     */
    public function baiBaoFlowRelease()
    {
        $where['flow_amount'] = ['gt', 0];
        $arrUserID = M('store')->where($where)->getField('uid', true);
        $bai_bao_flow_release_ratio = D('Config')->getValue('bai_bao_flow_release_ratio');
        $bai_bao_flow_release_from = D('Config')->getValue('bai_bao_flow_release_from');

        $list = [];
        if ($arrUserID) {
            $map['uid'] = ['in', $arrUserID];
            $map['is_release'] = 1;
            $map['flow_amount'] = ['gt', 0];
            $list = M('lock_warehouse_pass')->where($map)->select();
        }
        M()->startTrans();
        try {
            $data = [];
            foreach ($arrUserID as $uid) {
                foreach ($list as $item) {
                    if ($uid == $item['uid']) {
                        $data[$uid][] = $item;
                    }
                }
            }

            foreach ($data as $uid => $arrList) {
                $release_amount = 0;
                foreach ($arrList as $item) {
                    $amount = formatNum2($item['amount'] * ($item['multiple_ratio'] - 1) * $bai_bao_flow_release_ratio, 2);
                    if ($item['flow_amount'] > $amount) {
                        $lock_flow_amount = $amount;
                    } else {
                        $lock_flow_amount = $item['flow_amount'];
                    }
                    $release_amount += $lock_flow_amount;

                    $res = M('lock_warehouse_pass')->where(['id' => $item['id']])->setDec('flow_amount', $lock_flow_amount);
                    if (!$res) {
                        throw new Exception('流动通证释放失败');
                    }
                }

                //扣除流动通证
                StoreRecordModel::addRecord($uid, 'flow_amount', -$release_amount, Constants::STORE_TYPE_FLOW, 1);

                if ($bai_bao_flow_release_from == 1) {
                    //增加我的仓库
                    StoreRecordModel::addRecord($uid, 'product_integral', $release_amount, Constants::STORE_TYPE_PRODUCT_INTEGRAL, 0);
                } else {
                    //增加可用余额
                    $this->changStore($uid, 'cangku_num', $release_amount, 8);
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
     * 百宝箱中分享奖释放
     * @return bool
     * @author ldz
     * @time 2020/2/25 13:40
     */
    public function baiBaoShareRelease()
    {
        $where['share_amount'] = ['gt', 0];
        $arrUserID = M('store')->where($where)->getField('uid', true);
        $bai_bao_share_release_ratio = D('Config')->getValue('bai_bao_share_release_ratio');

        $list = [];
        if ($arrUserID) {
            $map['uid'] = ['in', $arrUserID];
            $map['is_release'] = 0;
            $map['release_amount'] = ['gt', 0];
            $map['store_type'] = Constants::STORE_TYPE_SHARE_REWARD;
            $list = M('store_record')->where($map)->field('id,uid,amount,release_amount')->select();
        }
        M()->startTrans();
        try {
            $data = [];
            foreach ($arrUserID as $uid) {
                foreach ($list as $item) {
                    if ($uid == $item['uid']) {
                        $data[$uid][] = $item;
                    }
                }
            }

            foreach ($data as $uid => $arrList) {
                $release_amount = 0;
                foreach ($arrList as $item) {
                    $amount = formatNum2($item['amount'] * $bai_bao_share_release_ratio);
                    if ($item['release_amount'] > $amount) {
                        $share_release_amount = $amount;
                    } else {
                        $share_release_amount = $item['release_amount'];
                    }
                    $release_amount += $share_release_amount;
                    if (($item['release_amount'] - $share_release_amount) <= 0) {
                        $updateData['is_release'] = 1;
                    } else {
                        $updateData['is_release'] = 0;
                    }

                    $updateData['release_amount'] = ['exp', 'release_amount - ' . $share_release_amount];
                    $res = M('store_record')->where(['id' => $item['id']])->save($updateData);
                    if (!$res) {
                        throw new Exception('感恩奖释放失败');
                    }
                }

                //扣除百宝箱分享奖
                StoreRecordModel::addRecord($uid, 'share_amount', -$release_amount, Constants::STORE_TYPE_SHARE_REWARD, 2);

                //增加可用余额
                $this->changStore($uid, 'cangku_num', $release_amount, 22);
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
     * 百宝箱中绩效奖释放
     * @return bool
     * @author ldz
     * @time 2020/3/6 15:56
     */
    public function baiBaoMeritsRelease()
    {
        $where['merits_amount'] = ['gt', 0];
        $arrList = M('store')->where($where)->field('uid,merits_amount')->select();
        $bai_bao_merits_release_ratio = D('Config')->getValue('bai_bao_merits_release_ratio');

        M()->startTrans();
        try {
            foreach ($arrList as $item) {
                $amount = formatNum2($item['merits_amount'] * $bai_bao_merits_release_ratio);
                if ($item['merits_amount'] > $amount) {
                    $release_amount = $amount;
                } else {
                    $release_amount = $item['merits_amount'];
                }

                //扣除百宝箱绩效奖
                StoreRecordModel::addRecord($item['uid'], 'merits_amount', -$release_amount, Constants::STORE_TYPE_MERITS_REWARD, 1);

                //增加可用余额
                $this->changStore($item['uid'], 'cangku_num', $release_amount, 24);
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
     * 百宝箱中感恩奖释放
     * @return bool
     * @author ldz
     * @time 2020/3/6 15:56
     */
    public function baiBaoThankfulRelease()
    {
        $where['thankful_amount'] = ['gt', 0];
        $arrList = M('store')->where($where)->field('uid,thankful_amount')->select();
        $bai_bao_thankful_release_ratio = D('Config')->getValue('bai_bao_thankful_release_ratio');

        M()->startTrans();
        try {
            foreach ($arrList as $item) {
                $amount = formatNum2($item['thankful_amount'] * $bai_bao_thankful_release_ratio);
                if ($item['thankful_amount'] > $amount) {
                    $release_amount = $amount;
                } else {
                    $release_amount = $item['thankful_amount'];
                }

                //扣除百宝箱绩效奖
                StoreRecordModel::addRecord($item['uid'], 'thankful_amount', -$release_amount, Constants::STORE_TYPE_THANKFUL_REWARD, 1);

                //增加可用余额
                $this->changStore($item['uid'], 'cangku_num', $release_amount, 26);
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
     * 百宝箱中回馈释放
     * @return bool
     * @author ldz
     * @time 2020/3/6 15:56
     */
    public function baiBaoFeedbackRelease()
    {
        $where['feedback_amount'] = ['gt', 0];
        $arrList = M('store')->where($where)->field('uid,feedback_amount')->select();
        $bai_bao_feedback_release_ratio = D('Config')->getValue('bai_bao_feedback_release_ratio');

        M()->startTrans();
        try {
            foreach ($arrList as $item) {
                $amount = formatNum2($item['feedback_amount'] * $bai_bao_feedback_release_ratio);
                if ($item['feedback_amount'] > $amount) {
                    $release_amount = $amount;
                } else {
                    $release_amount = $item['feedback_amount'];
                }

                //扣除百宝箱绩效奖
                StoreRecordModel::addRecord($item['uid'], 'feedback_amount', -$release_amount, Constants::STORE_TYPE_FEEDBACK, 1);

                //增加可用流动通证
                StoreRecordModel::addRecord($item['uid'], 'can_flow_amount', $release_amount, Constants::STORE_TYPE_CAN_FLOW, 11);
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
     * 月销售返点
     * @return bool
     * @author ldz
     * @time 2020/4/30 17:02
     */
    public function releaseMonth()
    {
//        $where['u.level'] = ['EGT', Constants::USER_LEVEL_A_THREE];
        $where['ys.total_month_amount'] = ['EGT', self::month_amount_f_one];
        $arrList = M('user u')->where($where)
            ->join('ysk_store ys on ys.uid = u.userid')
            ->field('u.userid user_id,ys.total_month_amount')
            ->select();

        M()->startTrans();
        try {
            foreach ($arrList as $k => $item) {
                $user_ratio = $this->getMonthAmountRatio($item['total_month_amount']);
                $total_num = $item['total_month_amount'] * $user_ratio;
                $directlyUser = M('user u')->where(['pid' => $item['user_id']])
                    ->join('ysk_store ys on ys.uid = u.userid')
                    ->field('ys.total_month_amount')
                    ->select();
                $other_num = 0;
                foreach ($directlyUser as $v) {
                    $ratio = $this->getMonthAmountRatio($v['total_month_amount']);
                    $other_num += $ratio * $v['total_month_amount'];
                }

                $total_release_num = formatNum2($total_num - $other_num);

                if ($total_release_num > 0) {
                    StoreModel::changStore($item['user_id'], 'cangku_num', $total_release_num, 14);
                }
            }

            //清除月销量
            $res = M('store')->where([])->save(['total_month_amount' => 0, 'month_num' => 0]);
            if ($res === false) {
                throw new Exception('清空月销量失败');
            }

            M()->commit();
            return true;
        } catch (Exception $ex) {
            M()->rollback();
            $this->error = $ex->getMessage();
            return false;
        }
    }

    private function getMonthAmountRatio($amount)
    {
        if ($amount >= self::month_amount_f_nine) {//F9
            $ratio = self::month_amount_f_nine_ratio;
        } elseif ($amount >= self::month_amount_f_eight) {//F8
            $ratio = self::month_amount_f_eight_ratio;
        } elseif ($amount >= self::month_amount_f_seven) {//F7
            $ratio = self::month_amount_f_seven_ratio;
        } elseif ($amount >= self::month_amount_f_six) {//F6
            $ratio = self::month_amount_f_six_ratio;
        } elseif ($amount >= self::month_amount_f_five) {//F5
            $ratio = self::month_amount_f_five_ratio;
        } elseif ($amount >= self::month_amount_f_four) {//F4
            $ratio = self::month_amount_f_four_ratio;
        } elseif ($amount >= self::month_amount_f_three) {//F3
            $ratio = self::month_amount_f_three_ratio;
        } elseif ($amount >= self::month_amount_f_two) {//F2
            $ratio = self::month_amount_f_two_ratio;
        } elseif ($amount >= self::month_amount_f_one) {//F1
            $ratio = self::month_amount_f_one_ratio;
        } else {
            $ratio = 0;
        }

        return $ratio;
    }

}
