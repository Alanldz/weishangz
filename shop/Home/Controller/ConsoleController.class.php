<?php

namespace Home\Controller;

use Common\Util\Constants;
use Home\Model\LockWarehousePassModel;
use Home\Model\OrderModel;
use Home\Model\PurchaseRecordModel;
use Home\Model\StoreModel;
use Think\Controller;
use Think\Exception;

class ConsoleController extends Controller
{

    /**
     * 用户激活二维码，上级，上上级获得可用余额释放(每天晚上12点释放)
     * @author ldz
     * @time 2020/2/11 17:47
     */
    public function releaseTrans()
    {
        $where['is_release'] = 0;
        $where['get_type'] = 6;
        $arrTrans = M('tranmoney')->where($where)->select();
        M()->startTrans();
        try {
            foreach ($arrTrans as $tran) {
                $now_nums = M('store')->where(['uid' => $tran['pay_id']])->getField('cangku_num');
                $res = M('store')->where(['uid' => $tran['pay_id']])->setInc('cangku_num', $tran['get_nums']);
                if (!$res) {
                    throw new Exception('扣除可用余额失败');
                }

                $changeData['now_nums'] = $now_nums;
                $changeData['now_nums_get'] = $now_nums + $tran['get_nums'];
                $changeData['is_release'] = 1;
                $changeData['get_time'] = time();
                $res = M('tranmoney')->where(['id' => $tran['id']])->save($changeData);
                if (!$res) {
                    throw new Exception('扣除可用余额失败');
                }
            }
            M()->commit();
            echo 'success';
        } catch (Exception $ex) {
            M()->rollback();
            echo $ex->getMessage();
        }
    }

    /**
     * 三天未签到，流动资产账户清零
     * @author ldz
     * @time 2020/2/12 15:40
     */
    public function clearFloatingAmount()
    {
        $user = M('purchase_record')->where(['type' => 1])->group('uid')->getField('uid', true);

        M()->startTrans();
        try {
            foreach ($user as $uid) {
                $is_over = PurchaseRecordModel::is_over_three($uid);
                if ($is_over) {
                    $purchase_integral = M('store')->where(['uid' => $uid])->getField('purchase_integral');
                    if ($purchase_integral <= 0) {
                        continue;
                    }

                    $res = M('store')->where(['uid' => $uid])->save(['purchase_integral' => 0]);
                    if (!$res) {
                        throw new Exception('失败');
                    }

                    $res = D('purchase_record')->addData($uid, -$purchase_integral, 2);
                    if (!$res) {
                        throw new Exception('添加记录失败');
                    }
                }
            }
            M()->commit();
            echo 'success';
        } catch (Exception $ex) {
            M()->rollback();
            echo $ex->getMessage();
        }
    }

    /**
     * 流动通证释放（可用的流动通证）//零点释放
     * @throws \Exception
     * @author ldz
     * @time 2020/2/24 17:42
     */
    public function releaseLockWarehouse()
    {
        //修改都没有选择时间的锁仓通证
        LockWarehousePassModel::changeFirstLockWarehouse();

        $model = new LockWarehousePassModel();
        $res = $model->releaseLockWarehouse();
        if (!$res) {
            echo $model->getError();
        } else {
            echo 'success';
        }
    }

    /**
     * 百宝箱释放 里面流动通证释放
     * @author ldz
     * @time 2020/2/25 13:38
     */
    public function baiBaoFlowRelease()
    {
        $model = new StoreModel();
        $res = $model->baiBaoFlowRelease();
        if (!$res) {
            echo $model->getError();
        } else {
            echo 'success';
        }
    }

    /**
     * 百宝箱释放 里面分享奖释放
     * @author ldz
     * @time 2020/2/25 13:38
     */
    public function baiBaoShareRelease()
    {
        $model = new StoreModel();
        $res = $model->baiBaoShareRelease();
        if (!$res) {
            echo $model->getError();
        } else {
            echo 'success';
        }
    }

    /**
     * 百宝箱释放 里面绩效奖释放
     * @author ldz
     * @time 2020/2/25 13:38
     */
    public function baiBaoMeritsRelease()
    {
        $model = new StoreModel();
        $res = $model->baiBaoMeritsRelease();
        if (!$res) {
            echo $model->getError();
        } else {
            echo 'success';
        }
    }

    /**
     * 百宝箱释放 里面感恩奖释放
     * @author ldz
     * @time 2020/2/25 13:38
     */
    public function baiBaoThankfulRelease()
    {
        $model = new StoreModel();
        $res = $model->baiBaoThankfulRelease();
        if (!$res) {
            echo $model->getError();
        } else {
            echo 'success';
        }
    }

    /**
     * 百宝箱释放 里面回馈释放
     * @author ldz
     * @time 2020/2/25 13:38
     */
    public function baiBaoFeedbackRelease()
    {
        $model = new StoreModel();
        $res = $model->baiBaoFeedbackRelease();
        if (!$res) {
            echo $model->getError();
        } else {
            echo 'success';
        }
    }

    /**
     * 自营订单释放
     * @author ldz
     * @time 2020/3/5 17:18
     */
    public function selfOrderRelease()
    {
        $model = new OrderModel();
        $res = $model->selfOrderRelease();
        if (!$res) {
            echo $model->getError();
        } else {
            echo 'success';
        }
    }

    /**
     * 删除一个小时未付款的订单
     * @author ldz
     * @time 2020/3/6 17:51
     */
    public function delNotPayOrder()
    {
        $model = new OrderModel();
        $res = $model->delNotPayOrder();
        if (!$res) {
            echo $model->getError();
        } else {
            echo 'success';
        }
    }

    /**
     * 处理用户上上级，上上上级数据问题
     * @author ldz
     * @time 2020/3/25 11:01
     */
    public function dealUserPid()
    {
        $startTime = time();
        $userList = M('user')->field('userid,pid,gid,ggid,path')->select();
        foreach ($userList as $user) {
            $gid = M('user')->where(['userid' => $user['pid']])->getField('pid');
            if ($gid) {
                $ggid = M('user')->where(['userid' => $gid])->getField('pid');
                $ggid = $ggid ? $ggid : 0;
            } else {
                $gid = 0;
                $ggid = 0;
            }
            $res = M('user')->where(['userid' => $user['userid']])->save(['gid' => $gid, 'ggid' => $ggid]);
        }

        $endTime = time();
        $difTime = $endTime - $startTime;
        echo '执行成功，用时：' . $difTime . '秒';
    }

    /**
     * 用于处理用户path接点问题
     * @author ldz
     * @time 2020/4/7 14:07
     */
    public function dealUserPath()
    {
        $startTime = time();
        $userList = M('user')->field('userid,pid,gid,ggid,path')->select();
        foreach ($userList as $user) {
            $arrPath = [];
            if ($user['pid']) {
                $arrPath[] = $user['pid'];
            }

            if ($user['gid']) {
                $arrPath[] = $user['gid'];
            }
            if ($user['ggid']) {
                $arrPath[] = $user['ggid'];
                $arrPath = $this->getPid($user['ggid'], $arrPath);
            }

            $path = '';
            if ($arrPath) {
                $path = '-' . implode('-', array_reverse($arrPath)) . '-';
            }
            $res = M('user')->where(['userid' => $user['userid']])->save(['path' => $path]);
        }
        $endTime = time();
        $difTime = $endTime - $startTime;
        echo '执行成功，用时：' . $difTime . '秒';
    }

    private function getPid($user_id, $arrPath)
    {
        $userInfo = M('user')->where(['userid' => $user_id])->field('pid,gid,ggid')->find();
        if ($userInfo['pid']) {
            $arrPath[] = $userInfo['pid'];
        } else {
            return $arrPath;
        }

        if ($userInfo['gid']) {
            $arrPath[] = $userInfo['gid'];
        } else {
            return $arrPath;
        }

        if ($userInfo['ggid']) {
            $arrPath[] = $userInfo['ggid'];
            return $this->getPid($userInfo['ggid'], $arrPath);
        } else {
            return $arrPath;
        }
    }

    /**
     * 处理用户可用流动通证，均摊到各个锁仓中
     * @author ldz
     * @time 2020/4/7 17:54
     */
    public function dealUserCanFlowAccount()
    {
        try {
            $startTime = time();
            M()->startTrans();
            $storeInfo = M('store')->where(['can_flow_amount' => ['gt', 0]])->field('uid,can_flow_amount')->select();
            foreach ($storeInfo as $item) {
                $lock = M('lock_warehouse_pass')->where(['uid' => $item['uid'], 'status' => ['neq', Constants::LOCK_WAREHOUSE_GROWTH], 'is_release' => Constants::YesNo_No])->field('id,release_amount')->select();
                $arrID = array_column($lock, 'id');
                $countID = count($arrID);
                if ($countID > 0) {
                    $total_release_amount = array_sum(array_column($lock, 'release_amount'));
                    if ($item['can_flow_amount'] == $total_release_amount) {
                        continue;
                    }

                    $release_amount = formatNum2($item['can_flow_amount'] / $countID);
                    $last_release_amount = 0;
                    if ($release_amount * $countID != $item['can_flow_amount']) {
                        $last_release_amount = $item['can_flow_amount'] - $release_amount * ($countID - 1);
                    }

                    foreach ($arrID as $key => $lock_id) {
                        if ($key == ($countID - 1) && $last_release_amount > 0) {
                            $res = M('lock_warehouse_pass')->where(['id' => $lock_id])->save(['release_amount'=> $last_release_amount]);
                        } else {
                            $res = M('lock_warehouse_pass')->where(['id' => $lock_id])->save(['release_amount'=>$release_amount]);
                        }
                        if ($res === false) {
                            throw new Exception('用户ID:' . $item['uid'] . '执行锁仓ID：' . $lock_id . '失败');
                        }
                    }
                }
            }
            M()->commit();
            $endTime = time();
            $difTime = $endTime - $startTime;
            echo '执行成功，用时：' . $difTime . '秒';
        } catch (Exception $e) {
            M()->rollback();
            echo '执行失败：' . $e;
        }
    }


    /**
     * 每个月月底释放月销售返点
     * @author ldz
     * @time 2020/4/30 16:40
     */
    public function releaseMonth()
    {
        $model = new StoreModel();
        $res = $model->releaseMonth();
        if (!$res) {
            echo $model->getError();
        } else {
            echo 'success';
        }
    }


}