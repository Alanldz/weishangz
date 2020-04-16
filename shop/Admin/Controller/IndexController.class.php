<?php

namespace Admin\Controller;

use Common\Model\DealsRecordModel;
use Common\Model\UcoinsModel;
use Common\Model\UserModel;

class IndexController extends AdminController
{

    public function index()
    {
        //会员统计
        $this->getUserCount();
        //交易量
        $this->TraingCount();
        $this->assign('meta_title', "首页");
        $this->display();
    }

    public function getUserCount()
    {
        $user = D('User');
        $user_total = $user->count(1);
        $start = strtotime(date('Y-m-d'));
        $end = $start + 86400;
        $where = "reg_date BETWEEN {$start} AND {$end}";
        $user_count = $user->where($where)->count(1);
        $this->assign('user_total', $user_total);
        $this->assign('user_count', $user_count);
    }

    public function TraingCount()
    {
        $traing = M('trading');
        $trading_free = M('trading_free');

        $start = strtotime(date('Y-m-d'));
        $end = $start + 86400;
        $where = "create_time BETWEEN {$start} AND {$end}";

        $traing_count = $traing->where($where)->count(1);
        $traing_total = $traing->count(1);

        $traing_count += $trading_free->where($where)->count(1);
        $traing_total += $trading_free->count(1);

        //不可用QBB总数
        $total_can_not_qbb_num = UserModel::getAllUserCanNotUseQbb();
        //可用QBB总数
        $total_qbb_num = UcoinsModel::getAllUserAmountByCid();
        //交易信息
        $dealInfo = DealsRecordModel::getTodayInfo();

        $can_not_deal_num = M('deal')->where(['status'=>0])->count();
        $deal_transaction_num = M('deals')->where(['status'=>['in',[1,2]]])->count();
        $completed_num = M('deals')->where(['status'=>3])->count();

        $qbb_sell_complete_num = M('config')->where(['name'=>'qbb_sell_complete_num'])->getField('value');

        $this->assign('traing_count', $traing_count);
        $this->assign('traing_total', $traing_total);
        $this->assign('total_qbb_num', $total_qbb_num);
        $this->assign('total_can_not_qbb_num', $total_can_not_qbb_num);
        $this->assign('dealInfo', $dealInfo);
        $this->assign('can_not_deal_num', $can_not_deal_num);
        $this->assign('deal_transaction_num', $deal_transaction_num);
        $this->assign('qbb_sell_complete_num', $qbb_sell_complete_num);
        $this->assign('completed_num', $completed_num);
    }

    /**
     * 删除缓存
     * @author jry <598821125@qq.com>
     */
    public function removeRuntime()
    {
        $file = new \Util\File();
        $result = $file->del_dir(RUNTIME_PATH);
        if ($result) {
            $this->success("缓存清理成功1");
        } else {
            $this->error("缓存清理失败1");
        }
    }
}