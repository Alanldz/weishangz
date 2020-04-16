<?php
/**
 * Created by PhpStorm.
 * User: ldz
 * Date: 2020/2/23
 * Time: 15:11
 */

namespace Home\Model;

use Common\Model\UcoinsModel;
use Common\Util\Constants;
use Think\Exception;

class LockWarehousePassModel extends \Common\Model\LockWarehousePassModel
{

    /**
     * 修改都没有选择时间的锁仓通证，且修改第一单
     * @author ldz
     * @time 2020/2/23 17:48
     */
    public static function changeFirstLockWarehouse()
    {
        $multipleList = ConfigModel::getLockWareHouseInfo();
        $day = 1;

        $release_user = M('lock_warehouse_pass')->where(['status' => ['gt', Constants::LOCK_WAREHOUSE_NOT_SELECT]])->group('uid')->getField('uid', true);
        $where = [];
        if ($release_user) {
            $where['userid'] = ['not in', $release_user];
        }

        $arrUser = M('user')->where($where)->getField('userid', true);
        foreach ($arrUser as $user_id) {
            $lock_warehouse = M('lock_warehouse_pass')->where(['uid' => $user_id])->field('id,status,amount,create_time')->order('create_time asc,id asc')->find();
            if (empty($lock_warehouse)) {
                continue;
            }
            $multiple = $multipleList[$day];
            $data['growth_amount'] = $data['release_amount'] = $multiple * $lock_warehouse['amount'];
            $data['multiple'] = $multiple;
            $data['day'] = $day;
            $data['status'] = Constants::LOCK_WAREHOUSE_GROWTH;
            $data['start_time'] = $lock_warehouse['create_time'];
            M('lock_warehouse_pass')->where(['id' => $lock_warehouse['id']])->save($data);
        }
    }

    /**
     * 释放锁仓通证
     * @return bool
     * @throws \Exception
     * @author ldz
     * @time 2020/2/24 17:10
     */
    public function releaseLockWarehouse()
    {
        $status_growth = Constants::LOCK_WAREHOUSE_GROWTH;
        $status_release = Constants::LOCK_WAREHOUSE_RELEASE;

        $where['status'] = ['in', [$status_growth, $status_release]];
        $where['is_release'] = Constants::YesNo_No;
        $list = $this->where($where)->select();
        $time = strtotime(date('Y-m-d')); //当前时间
        $one_day_time = 60 * 60 * 24;
        $release_ratio = D('config')->getValue('lock_warehouse_release_ratio'); //锁仓通证释放比例
        $coin_price = UcoinsModel::getCoinPrice(); //生态通证当前价格
        M()->startTrans();
        try {
            foreach ($list as $item) {
                $start_time = strtotime(date('Y-m-d', $item['start_time']));
                $day = ($time - $start_time) / $one_day_time;
                if ($day < $item['day']) {
                    continue;
                }

                //第一次释放,添加一条流动可用流动通证记录
                if ($item['status'] == Constants::LOCK_WAREHOUSE_GROWTH) {
                    StoreRecordModel::addRecord($item['uid'], 'can_flow_amount', $item['growth_amount'], Constants::STORE_TYPE_CAN_FLOW, 0);
                }

                $amount = formatNum2($item['release_amount'] * $release_ratio);//数量=当前释放总数量 * 比例
                if (($item['release_amount'] - $amount) > $item['amount']) {
                    $release_amount = $amount;
                    $is_release = 0;
                } else {
                    $release_amount = $item['release_amount'] - $item['amount'];
                    $is_release = 1;
                }

                if ($release_amount <= 0) {
                    continue;
                }

                //增加生态通证
                UcoinsModel::addRecord($item['uid'], $release_amount, 1, $coin_price);

                //添加可用流动通证记录
                $storeRecord = [
                    'uid' => $item['uid'],
                    'amount' => -$release_amount,
                    'store_type' => Constants::STORE_TYPE_CAN_FLOW,
                    'type' => 1,
                    'create_time' => time(),
                ];
                $res = M('store_record')->add($storeRecord);
                if (!$res) {
                    throw new Exception('新增流动通证记录失败');
                }

                //已全部释放，剩余的释放到百宝箱中的流动通证
                $flow_amount = 0;
                if ($is_release) {
                    $flow_amount = formatNum2($item['amount'] * ($item['multiple_ratio'] - 1));

                    StoreRecordModel::addRecord($item['uid'], 'flow_amount', $flow_amount, Constants::STORE_TYPE_FLOW, 0);

                    if ($flow_amount != 0) {
                        //添加可用流动通证记录
                        $storeRecord = [
                            'uid' => $item['uid'],
                            'amount' => -$flow_amount,
                            'store_type' => Constants::STORE_TYPE_CAN_FLOW,
                            'type' => 2,
                            'create_time' => time(),
                        ];
                        $res = M('store_record')->add($storeRecord);
                        if (!$res) {
                            throw new Exception('新增流动通证记录失败');
                        }
                    }
                    //额外赠送，到可用流动通证的增值额
                    $this->increment($item['uid'], $item['multiple_ratio'], $item['amount']);

                    $release_amount += $flow_amount;
                }

                //扣除流动通证
                $res = M('store')->where(['uid' => $item['uid']])->setDec('can_flow_amount', $release_amount);
                if (!$res) {
                    throw new Exception('释放流动通证失败');
                }

                //修改锁仓通证列表信息
                $updateData['release_amount'] = $item['release_amount'] - $release_amount;
                $updateData['is_release'] = $is_release;
                $updateData['status'] = $status_release;
                $updateData['flow_amount'] = $flow_amount;
                $res = $this->where(['id' => $item['id']])->save($updateData);
                if (!$res) {
                    throw new Exception('扣除释放总数量');
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
     * 增值额
     * @param $uid
     * @param $multiple_ratio
     * @param $amount
     * @throws Exception
     */
    private function increment($uid, $multiple_ratio, $amount)
    {
        switch ($amount) {
            case 2000:
                $exchange = UserModel::two_thousand_exchange;
                break;
            case 5000:
                $exchange = UserModel::five_thousand_exchange;
                break;
            case 10000:
                $exchange = UserModel::ten_thousand_exchange;
                break;
            default :
                $exchange = 0;
        }
        if ($exchange) {
            $can_flow_amount = formatNum2($amount * ($exchange - $multiple_ratio));
            if ($can_flow_amount > 0) {
                StoreRecordModel::addRecord($uid, 'can_flow_amount', $can_flow_amount, Constants::STORE_TYPE_CAN_FLOW, 10);
                //将增加的可用流动通证添加到最新的锁仓中
                self::addReleaseAccount($uid, $can_flow_amount);
            }
        }
    }

}