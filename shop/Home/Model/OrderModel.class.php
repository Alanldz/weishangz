<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/24
 * Time: 13:34
 */

namespace Home\Model;

use Common\Util\Constants;
use Think\Exception;
use Think\Model;

class OrderModel extends Model
{
    protected $tableName = 'order';

    /**
     * 自营订单释放
     * @return bool
     * @author ldz
     * @time 2020/3/5 18:03
     */
    public function selfOrderRelease()
    {
        $where['shop_type'] = Constants::SHOP_TYPE_SELF_SUPPORT;
        $where['status'] = ['gt', 0];
        $where['ecological_total_assets'] = ['gt', 0];
        $where['is_release'] = 0;
        $orderList = $this->where($where)->field('order_id,uid,ecological_total_assets')->select();
        M()->startTrans();
        $ratio = 0.5;
        try {
            foreach ($orderList as $item) {
                $can_flow_amount = 0;
                //修改订单释放状态
                $res = $this->where(['order_id' => $item['order_id']])->save(['is_release' => 1]);
                if (!$res) {
                    throw new Exception('修改订单释放状态失败');
                }

                $release_amount = formatNum2($item['ecological_total_assets'] * $ratio);

                $storeInfo = M('store')->where(['uid' => $item['uid']])->field('purchase_integral,current_assets')->find();
                if ($storeInfo['current_assets'] > $release_amount) {
                    $current_assets = $release_amount;
                } else {
                    $current_assets = $storeInfo['current_assets'];
                }
                //扣除流动资产
                StoreRecordModel::addRecord($item['uid'], 'current_assets', -$current_assets, Constants::STORE_TYPE_CURRENT_ASSETS, 2);
                $can_flow_amount += $current_assets;

                $remain_amount = $release_amount - $current_assets;
                $sign_in_min_amount = Constants::sign_in_min_amount;
                //扣除签到账户
                if (($remain_amount > 0) && ($storeInfo['purchase_integral'] >= $sign_in_min_amount)) {
                    if ($storeInfo['purchase_integral'] > $remain_amount) {
                        $purchase_integral = $remain_amount;
                    } else {
                        $purchase_integral = $storeInfo['purchase_integral'];
                    }

                    //扣除签到账户失败
                    PurchaseRecordModel::addRecord($item['uid'], -$purchase_integral, 5);
                    $can_flow_amount += $purchase_integral;
                }

                //增加流动通证
                if ($can_flow_amount) {
                    StoreRecordModel::addRecord($item['uid'], 'can_flow_amount', $can_flow_amount, Constants::STORE_TYPE_CAN_FLOW, 6);
                }
            }
            M()->commit();
            return true;
        } catch (Exception $ex) {
            $this->error = $ex->getMessage();
            M()->rollback();
            return false;
        }
    }

    /**
     * 删除一个小时未支付的订单
     * @author ldz
     * @time 2020/3/6 17:39
     */
    public function delNotPayOrder()
    {
        $where['status'] = 0;
        $time = time() - 60 * 60;
        $where['time'] = ['LT', $time];
        $arrOrderID = $this->where($where)->getField('order_id', true);
        M()->startTrans();
        try {
            foreach ($arrOrderID as $order_id) {
                $res = M('order')->where(['order_id' => $order_id])->delete();
                if (!$res) {
                    throw new Exception('删除订单失败');
                }
                $arrProductID = M('order_detail')->where(['order_id' => $order_id])->getField('com_id', true);
                foreach ($arrProductID as $productID) {
                    $res = M('product_detail')->where(['id' => $productID])->setInc('stock');
                    if (!$res) {
                        throw new Exception('增加库存失败');
                    }
                }
                $res = M('order_detail')->where(array('order_id' => $order_id))->delete();
                if (!$res) {
                    throw new Exception('删除订单明细失败');
                }
            }

            M()->commit();
            return true;
        } catch (Exception $ex) {
            $this->error = $ex->getMessage();
            M()->rollback();
            return false;
        }
    }


}