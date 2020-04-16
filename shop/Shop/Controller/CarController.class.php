<?php

namespace Shop\Controller;

use Shop\Model\OrderModel;

class CarController extends CommonController
{
    /**
     * 支付成功页
     */
    public function ment()
    {
        $this->display();
    }

    /**
     * 购物车
     */
    public function shopping()
    {
        $carList = session("car");
        $this->assign("carList", $carList);
        $this->display();
    }

    /**
     * 提交订单
     */
    public function addOrder()
    {
        $model = new OrderModel();
        $order_id = $model->createOrder();
        if (!$order_id) {
            error_alert($model->getError());
            return;
        }
        session("car", null);
        session("selCar", null);
        $this->redirect("/Shop/Pay/index", array("oid" => $order_id, 'formtype' => 99));
    }

    public function Buynow()
    {
        $uid = session('userid');
        $pid = intval(I("p"));
        $num = intval(I("n"));
        $size = I("s");
        $color = I("c");
        $jiFenType = I("jtype");
        if (!$pid) {
            $this->error('参数不正确。');
        }
        if (!$num) {
            $this->error('数量不正确。');
        }

        //查询商品名以及价格
        $product_detail = M("product_detail")->where(array("id" => $pid))->find();
        if (!$product_detail) {
            $this->error('该商品不存在');
        }

        $address_id = I('addid');
        if ($address_id == '') {
            $addressInfo = M('address')->where(array('member_id' => $uid, 'zt_' => 1))->find();
        } else {
            $addressInfo = M('address')->where(array('address_id' => $address_id))->find();
        }
        if ($addressInfo == '') {
            $addressInfo = M('address')->where(array('member_id' => $uid))->order('address_id desc')->find();
        }
        if ($product_detail['shangjia'] == $uid) {
            $this->error('不能购买自己店铺的商品');
        }

        //查询出要购买的商品
        $product['pid'] = $pid;
        $product['size'] = $size;
        $product['color'] = $color;
        $product['num'] = $num;
        $product['name'] = $product_detail['name'];
        $product['price'] = $product_detail['price'];
        $product['ecological_total_assets'] = $product_detail['ecological_total_assets'];
        $product['ecological_assets'] = $product_detail['ecological_assets'];
        $product['flow_pass_card'] = $product_detail['flow_pass_card'];
        $product['pic'] = $product_detail['pic'];
        $product['jtype'] = $jiFenType;
        $product['isduobao'] = $product_detail['is_duobao'];   //是否夺宝
        $list[$pid][0] = $product;

        if ($list) {
            session("selCar", $list);
        } else {
            $list = session("selCar");
        }

        $this->assign("selProductList", $list);
        $this->assign("addinfp", $addressInfo);
        message("购买成功", "1");
    }

    /*购物车修改数量*/
    public function editCar()
    {
        $uid = session('userid');
        $pid = I("p");
        $num = I("n");
        $size = I("s");
        $color = I("c");
        $jifentype = I("jtype");
        if (!$uid) message("请登录后进行操作。");
        if (!$pid) message("参数不正确。");
        if (!$num) message("数量不正确。");
//          if(!$size) message("尺寸不为空。");
//          if(!$color) message("颜色不为空。");
//          if(!$jifentype) message("积分类型不能为空。");


        //查询商品名以及价格
        $pdetail = M("product_detail")->where(array("id" => $pid))->find();
        if (!$pdetail) message("该商品不存在");
        $name = $pdetail['name'];
        $price = $pdetail['price'];
        $pic = $pdetail['pic'];
        $list = array();
        $car = session("car");
        //购物车是否有商品
        if ($car) {
            $product = $car[$pid];

            //是否存在该商品
            if ($product) {
                $tag = "no";
                foreach ($product as $key => $prod) {
                    if ($prod['pid'] == $pid && $prod['size'] == $size && $prod['color'] == $color) {
                        $product[$key]['num'] = $num;
                        $tag = "ok";
                    }
                }

                $car[$pid] = $product;
                if ($tag == "no") {
                    $pp['pid'] = $pid;
                    $pp['size'] = $size;
                    $pp['color'] = $color;
                    $pp['num'] = $num;
                    $pp['name'] = $name;
                    $pp['price'] = $price;
                    $pp['pic'] = $pic;
                    $pp['jtype'] = $jifentype;
                    $car[$pid][] = $pp;
                }
                session("car", $car);
            } else {
                $product['pid'] = $pid;
                $product['size'] = $size;
                $product['color'] = $color;
                $product['num'] = $num;
                $product['name'] = $name;
                $product['price'] = $price;
                $product['pic'] = $pic;
                $product['jtype'] = $jifentype;
                $car[$pid] = array($product);
                session("car", $car);
            }
        } else {
            $product['pid'] = $pid;
            $product['size'] = $size;
            $product['color'] = $color;
            $product['num'] = $num;
            $product['name'] = $name;
            $product['price'] = $price;
            $product['pic'] = $pic;
            $product['jtype'] = $jifentype;
            $prod[$pid] = array($product);
            session("car", $prod);
        }
        message("修改数量成功", "1");
    }

    /*购物车删除*/
    public function delcar()
    {
        $uid = session('userid');
        $pid = I("p");
        $gid = I("g");
        if (!$pid) message("参数不正确。");

        $carList = session("car");
        unset($carList[$pid][$gid]);
        session("car", $carList);
        message("删除商品成功", "1");
    }

}