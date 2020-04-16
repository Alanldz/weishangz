<?php
/**
 * Created by PhpStorm.
 * Date: 2019/1/12
 * Time: 10:12
 */

namespace Home\Controller;

use Home\Model\StoreModel;

class StoreController extends CommonController
{
    /**
     * 兑换股权
     */
    public function exchangEf()
    {
        if (IS_AJAX) {
            $models = new StoreModel();
            $res = $models->exchange();
            if (!$res) {
                ajaxReturn($models->getError(), 0);
            }
            ajaxReturn('兑换成功', 1);
        }
        $user_id = session('userid');
        $storeInfo = M('store')->where(array('uid' => $user_id))->field('fengmi_num')->find();
        $qbb_num = M('ucoins')->where(['cid' => 1, 'c_uid' => $user_id])->getField('c_nums'); //当前QBB数量
        $kb_num = M('ucoins')->where(['cid' => 2, 'c_uid' => $user_id])->getField('c_nums'); //当前KB数量
        $coindets = M('coindets')->order('coin_addtime desc,cid asc')->where(array("cid" => 1))->find();
        $stock_right_price = D('config')->getValue('stock_right_price');//当前股权价格
        $kb_coind = M('coindets')->order('coin_addtime desc,cid asc')->where(array("cid" => 2))->find();

        $this->assign('storeInfo', $storeInfo);
        $this->assign('qbb_num', $qbb_num);
        $this->assign('kb_num', $kb_num);
        $this->assign('coindets', $coindets);
        $this->assign('stock_right_price', $stock_right_price);
        $this->assign('kb_coind', $kb_coind);
        $this->display();
    }

    /**
     * 一键归集
     */
    public function collection()
    {
        if (IS_AJAX) {
            $models = new StoreModel();
            $res = $models->collection();
            if (!$res) {
                ajaxReturn($models->getError(), 0);
            }
            ajaxReturn('操作成功', 1);
        }

        $uid = session('userid');
        $list = [];
        //用户信息
        $userInfo = M('user')->where(['userid' => $uid])->field('account_type,mobile')->find();
        if ($userInfo['account_type'] == 2) {
            $where['us.mobile'] = $userInfo['mobile'];
            $where['us.account_type'] = 1;
            $where['_string'] = 'us.level > 0 AND st.cangku_num > 0';
            $list = M('user as us')->join('LEFT JOIN  ysk_store as st on st.uid = us.userid')
                ->where($where)->field('us.userid,us.level,us.activation_time,st.uid,st.cangku_num')
                ->select();
        }
        $multiple = D('config')->where("name='collection_multiple'")->getField("value"); //归集倍数
        $this->assign('list', $list);
        $this->assign('multiple', $multiple);
        $this->display();
    }

    /**
     * EP转账
     */
    public function Eptransfer()
    {
        if (IS_AJAX) {
            $models = new StoreModel();
            $res = $models->Eptransfer();
            if (!$res) {
                ajaxReturn($models->getError(), 0);
            }
            ajaxReturn('操作成功', 1);
        }

        $uid = session('userid');
        $store = M('store')->where(['uid' => $uid])->field('uid,cangku_num')->find();

        $this->assign('store', $store);
        $this->display();
    }

}