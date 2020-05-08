<?php

namespace Shop\Controller;

use Common\Model\ConfigModel;
use Common\Util\Constants;
use Shop\Model\GoodsModel;
use Shop\Model\LockWarehousePassModel;
use Shop\Model\VerifyListModel;

class IndexController extends CommonController
{

    protected function _initialize()
    {
        //判断网站是否关闭
        $close = is_close_site();
        if ($close['value'] == 0) {
            success_alert($close['tip'], U('Home/Login/logout'));
        }

        //判断商城是否关闭
        $close1 = is_close_mall();
        if ($close1['value'] == 0) {
            success_alert($close1['tip'], U('Home/index/index'));
        }
    }

    /**
     * 商城首页
     */
    public function index()
    {
        $banner = M('banner')->where(['status' => 1])->order('sort asc,create_time desc')->limit(3)->select();
        $this->assign('bannerList', $banner);
        //最新新闻公告
        $notice = M("news")->order("create_time desc")->find();
        $this->assign('notice', $notice);

        //消费区商品
        $this->assign("bao_dan_product_list", GoodsModel::getGoodsByShopType());
        //再生商城商品
        $this->assign("regenerate_product_list", GoodsModel::getGoodsByShopType(Constants::SHOP_TYPE_REGENERATE));
        //自营商城商品
        $this->assign("self_support_product_list", GoodsModel::getGoodsByShopType(Constants::SHOP_TYPE_SELF_SUPPORT));
        //淘宝天猫商品
        $taoBaoProduct = M('tao_bao_product')->where(['status' => Constants::YesNo_Yes])->limit(6)->select();
        $this->assign("tao_bao_list", $taoBaoProduct);
        //实体商家
//        $this->assign("business_list", VerifyListModel::getIndexList());
        //爱心商城
        $this->assign("love_product_list", GoodsModel::getGoodsByShopType(Constants::SHOP_TYPE_LOVE));

        $this->assign('methods', 'shopIndex');
        $this->display();
    }

    /**
     * E城E家
     * @author ldz
     * @time 2020/2/15 15:52
     */
    public function nearShop()
    {
        $params = I('');
        if (!isset($params['province_id']) || empty($params['province_id'])) {
            $params['province_id'] = VerifyListModel::getProvinceByIP();
        }
        $list = VerifyListModel::search($params);
        $this->assign('list', $list);
        $this->assign('methods', 'nearShop');
        $this->assign('search', $params);
        $this->display();
    }

    /**
     * 商家详情
     * @param $id
     * @author ldz
     * @time 2020/3/23 17:58
     */
    public function storeDetail($id)
    {
        if (IS_AJAX) {
            $model = new VerifyListModel();
            $res = $model->storePay();
            if (!$res) {
                $this->ajaxReturn(['msg' => $model->getError(), 'status' => 0]);
            }
            $this->ajaxReturn(['msg' => '付款成功', 'status' => 1]);
        }

        $user_id = session('userid');
        $where['id'] = $id;
        $where['status'] = Constants::VERIFY_STATUS_PASS;
        $where['type'] = Constants::VERIFY_PERSON;
        $info = M('verify_list')->where($where)->find();

        $storeInfo = M('store')->where(['uid' => $user_id])->field('cangku_num')->find();

        $this->assign('info', $info);
        $this->assign('storeInfo', $storeInfo);
        $this->display();
    }

    /**
     * 锁仓通证列表页
     * @throws \Think\Exception
     * @author ldz
     * @time 2020/2/22 15:25
     */
    public function lockWarehouse()
    {
        $this->is_user();
        if (IS_AJAX) {
            $model = new LockWarehousePassModel();
            $res = $model->selectDay();
            if (!$res) {
                $this->ajaxReturn(['msg' => $model->getError(), 'status' => 0]);
            }
            $this->ajaxReturn(['msg' => '选择成功', 'status' => 1]);
        }

        $uid = session('userid');
        $list = M('lock_warehouse_pass')->where(['uid' => $uid])->order('create_time desc,id desc')->select();
        $need_repeat_num = 0;
        foreach ($list as $k => $item) {
            $is_again_bug = '';
            if ($item['is_release'] == 1 && $item['flow_amount'] == 0) {
                $is_again_bug = $item['is_again_bug'] ? '（已复购）' : '（未复购）';
                if ($item['is_again_bug'] == 0) {
                    $need_repeat_num += 1;
                }
            }
            $list[$k]['status_name'] = Constants::getLockWarehouseItems($item['status']) . $is_again_bug;
            $list[$k]['start_time'] = $item['start_time'] ? date('Y-m-d', $item['start_time']) : '';
            $list[$k]['shop_type_name'] = Constants::getShopTypeItems($item['shop_type']);
        }

        $multipleList = ConfigModel::getLockWareHouseInfo();
        $this->assign('list', $list);
        $this->assign('multipleList', $multipleList);
        $this->assign('need_repeat_num', $need_repeat_num);
        $this->display();
    }

    /**
     * 百宝箱
     * @author ldz
     * @time 2020/2/24 15:40
     */
    public function treasureChest()
    {
        $this->is_user();
        $user_id = session('userid');
        $storeInfo = M('store')->where(['uid' => $user_id])
            ->field('share_amount,merits_amount,thankful_amount,flow_amount,feedback_amount')
            ->find();
        $userInfo = M('user')->where(['userid' => $user_id])->field('feedback_ratio')->find();

        $this->assign('storeInfo', $storeInfo);
        $this->assign('userInfo', $userInfo);
        $this->display();
    }
}