<?php

namespace SAdmin\Model;

use Common\Util\Constants;
use Think\Model;

class GoodsModel extends Model
{

    /**
     * 按商城获取分类
     * @param int $shop_type
     * @return mixed
     * @author ldz
     * @time 2020/2/21 11:27
     */
    public static function getGoodsCategoryByShopType($shop_type = Constants::SHOP_TYPE_BAO_DAN)
    {
        $where['status'] = Constants::YesNo_Yes;
        $where['shop_type'] = ['like', '%' . $shop_type . '%'];
        $cateList = M('product_cate')->where($where)->select();

        return $cateList;
    }

    function copy_goods($goods_id)
    {
        $query = M()->query("SELECT DISTINCT * FROM " . C('DB_PREFIX') . "goods p LEFT JOIN " . C('DB_PREFIX') . "goods_description pd ON (p.goods_id = pd.goods_id) WHERE p.goods_id =" . (int)$goods_id);

        if ($query) {
            $data = $query[0];
            $data['viewed'] = '0';
            $data['image'] = '';
            $data['goods_description'] = M('goods_description')->where(array('goods_id' => $goods_id))->find();
            $data['goods_description']['name'] = $data['name'];
            $data['goods_discount'] = M('goods_discount')->where(array('goods_id' => $goods_id))->select();
            $category = M('goods_to_category')->where(array('goods_id' => $goods_id))->select();
            foreach ($category as $k => $v) {
                $data['goods_category'][] = $v['category_id'];
            }
            $this->add_Goods($data);
        }
    }

    /**
     * 删除商品
     * @return array
     */
    public function del_Goods()
    {
        $action = trim(I('action', 'index'));
        $id = intval(I('id'));
        $res = M("product_detail")->where(array("id" => $id))->delete();
        if ($res === false) {
            return array(
                'status' => 'fail',
                'message' => '删除失败,未知异常',
                'jump' => U('Goods/' . $action)
            );
        }
        return array(
            'status' => 'success',
            'message' => '删除成功',
            'jump' => U('Goods/' . $action)
        );
    }

    public function get_goods_data($id)
    {
        $d = M('Goods')->find($id);
        $d['thumb_image'] = resize($d['image'], 100, 100);
        return $d;
    }

    public function get_goods_image_data($id)
    {
        $d = M('goods_image')->where(array('goods_id' => $id))->select();
        foreach ($d as $k => $v) {
            $d[$k]['thumb'] = resize($v['image'], 100, 100);
        }
        return $d;
    }

    public function get_goods_category_data($id)
    {
        $sql = 'SELECT pc.name,ptc.category_id FROM ' . C('DB_PREFIX') . 'goods_to_category ptc,'
            . C('DB_PREFIX') . 'goods_category pc WHERE pc.id=ptc.category_id AND ptc.goods_id=' . $id;
        $d = M()->query($sql);
        return $d;
    }

    /**
     * 商品列表 搜索
     * @param $search
     * @return array
     * @throws \Think\Exception
     */
    public static function show_goods_page($search)
    {
        $sql = 'SELECT pd.id,pd.type_id,pd.name,pd.price,pd.ecological_total_assets,pd.pic,pd.status,pd.shangjia,pd.stock,pd.ctime,pc.id as cid,pc.name as cname FROM ' . C('DB_PREFIX') . 'product_detail pd,'
            . C('DB_PREFIX') . 'product_cate pc WHERE pd.type_id=pc.id';

        if (isset($search['name'])) {
            $sql .= " and pd.name like '%" . $search['name'] . "%'";
        }
        if (isset($search['category'])) {
            $sql .= " and pc.id=" . $search['category'];
        }
        if (isset($search['status'])) {
            $sql .= " and pd.status=" . $search['status'];
        }
        if (isset($search['shop_type'])) {
            $sql .= " and pd.shop_type=" . $search['shop_type'];
        }

        $count = count(M()->query($sql));
        $Page = new \Think\Page($count, C('BACK_PAGE_NUM'));
        $show = $Page->show();// 分页显示输出

        $sql .= ' order by pd.status desc,pd.ctime desc LIMIT ' . $Page->firstRow . ',' . $Page->listRows;

        $list = M()->query($sql);
        foreach ($list as $key => $value) {
            $list[$key]['status_name'] = Constants::getYesNoItems($value['status']);
            $list[$key]['time'] = toDate($value['ctime']);
        }

        return array(
            'empty' => '<tr><td colspan="20" style="text-align: center">~~暂无商品</td></tr>',
            'list' => $list,
            'page' => $show
        );
    }

    public function show_shangpin($search)
    {
        $sql = 'SELECT pd.id,pd.type_id,pd.count_price,pd.jifen_nums,pd.name,pd.price,pd.pic,pd.status,pd.stock,pd.ctime,pc.id as cid,pc.name as cname FROM ' . C('DB_PREFIX') . 'product_detail pd,'
            . C('DB_PREFIX') . 'product_cate pc WHERE pd.type_id=pc.id';

        if (isset($search['name'])) {
            $sql .= " and pd.name like '%" . $search['name'] . "%'";
        }
        if (isset($search['category'])) {
            $sql .= " and pc.id=" . $search['category'];
        }
        if (isset($search['status'])) {
            $sql .= " and pd.status=" . $search['status'];
        }

        $duobao = 1;
        $sql .= " and pd.is_duobao=" . $duobao;
        $count = count(M()->query($sql));

        $Page = new \Think\Page($count, C('BACK_PAGE_NUM'));
        $show = $Page->show();// 分页显示输出

        $sql .= ' order by pd.status desc,pd.ctime desc LIMIT ' . $Page->firstRow . ',' . $Page->listRows;
        $goodsdet = M()->query($sql);
        foreach ($goodsdet as $key => $value) {
            $goodsdet[$key]['pic'] = $value['pic'];
            $goodsdet[$key]['time'] = date("Y-m-d H:i:s", $value['ctime']);
        }
        return array(
            'empty' => '<tr><td colspan="20">~~暂无数据</td></tr>',
            'goodsdet' => $goodsdet,
            'page' => $show
        );
    }

    /*一元购*/
    public function show_goods_pageyiyuangou($search)
    {
        $sql = 'SELECT pd.id,pd.type_id,pd.name,pd.price,pd.pic,pd.status,pd.stock,pd.ctime,pc.id as cid,pc.name as cname FROM ' . C('DB_PREFIX') . 'product_detail pd,'
            . C('DB_PREFIX') . 'product_cate pc WHERE pd.type_id=pc.id';

        if (isset($search['name'])) {
            $sql .= " and pd.name like '%" . $search['name'] . "%'";
        }
        if (isset($search['category'])) {
            $sql .= " and pc.id=" . $search['category'];
        }
        if (isset($search['status'])) {
            $sql .= " and pd.status=" . $search['status'];
        }
        $duobao = 2;
        $sql .= " and pd.is_duobao=" . $duobao;
        $count = count(M()->query($sql));

        $Page = new \Think\Page($count, C('BACK_PAGE_NUM'));
        $show = $Page->show();// 分页显示输出
        $sql .= ' order by pd.status desc,pd.ctime desc LIMIT ' . $Page->firstRow . ',' . $Page->listRows;
        $list = M()->query($sql);
        foreach ($list as $key => $value) {
            $list[$key]['pic'] = $value['pic'];
            $list[$key]['time'] = date("Y-m-d H:i:s", $value['ctime']);
        }

        return array(
            'empty' => '<tr><td colspan="20">~~暂无数据</td></tr>',
            'list' => $list,
            'page' => $show
        );
    }

    /**
     * 添加商品
     * @param $data
     * @return array
     */
    public function add_Goods($data)
    {
        $error = $this->validate($data);
        if ($error) {
            return $error;
        }

        $goods['name'] = $data['name'];
        $goods['type_id'] = $data['category'];
        $goods['price'] = $data['price'];
        $goods['pic'] = $data['pic'];
        $goods['pic1'] = $data['pic1'];
        $goods['pic2'] = $data['pic2'];
        $goods['pic3'] = $data['pic3'];
        $goods['pic4'] = $data['pic4'];
        $goods['pic5'] = $data['pic5'];
        $goods['stock'] = $data['stock'];
        $goods['content'] = $data['content'];
        $goods['shop_type'] = Constants::SHOP_TYPE_BAO_DAN;
        $goods['status'] = $data['status'];
        $goods['ctime'] = time();

        $goods_id = M('product_detail')->add($goods);
        if ($goods_id) {
            return array(
                'status' => 'success',
                'message' => '新增成功',
                'jump' => U('Goods/index')
            );
        } else {
            return array(
                'status' => 'fail',
                'message' => '新增失败',
                'jump' => U('Goods/index')
            );
        }
    }

    /**
     * 新增或编辑消费区商品
     * @return array
     */
    public function edit_Goods()
    {
        $data = I('post.');
        $error = $this->bao_dan_validate($data);
        if ($error) {
            return $error;
        }
        if ($data['id']) {
            $goods['id'] = $data['id'];
        } else {
            $goods['ctime'] = time();
            $goods['shop_type'] = Constants::SHOP_TYPE_BAO_DAN;
        }

        $goods['id'] = $data['id'];
        $goods['name'] = $data['name'];
        $goods['type_id'] = $data['category'];
        $goods['price'] = $data['price'];
        $goods['ecological_total_assets'] = $data['ecological_total_assets'];
        $business_id = M('user')->where(['mobile' => $data['business_phone']])->getField('userid');
        $goods['shangjia'] = $business_id;
        $goods['pic'] = $data['pic'];
        $goods['pic1'] = $data['pic1'];
        $goods['pic2'] = $data['pic2'];
        $goods['pic3'] = $data['pic3'];
        $goods['pic4'] = $data['pic4'];
        $goods['pic5'] = $data['pic5'];
        $goods['is_sort'] = $data['is_sort'];
        $goods['stock'] = $data['stock'];
        $goods['content'] = $data['content'];
        $goods['status'] = $data['status'];
        if ($goods['id']) {
            $res = M('product_detail')->save($goods);
            $operation = '修改';
        } else {
            $res = M('product_detail')->add($goods);
            $operation = '新增';
        }
        if ($res === false) {
            return array(
                'status' => 'fail',
                'message' => $operation . '失败',
                'jump' => U('Goods/index')
            );
        } else {
            return array(
                'status' => 'success',
                'message' => $operation . '成功',
                'jump' => U('Goods/index')
            );
        }
    }

    /**
     * 新增或编辑再生商城商品
     * @return array
     * @author ldz
     * @time 2020/3/2 14:05
     */
    public function edit_regenerate_shop_goods()
    {
        $data = I('post.');
        $error = $this->bao_dan_validate($data);
        if ($error) {
            return $error;
        }

        if ($data['id']) {
            $goods['id'] = $data['id'];
        } else {
            $goods['ctime'] = time();
            $goods['shop_type'] = Constants::SHOP_TYPE_REGENERATE;
        }

        $goods['name'] = $data['name'];
        $goods['type_id'] = $data['category'];
        $goods['price'] = $data['price'];
        $goods['ecological_total_assets'] = $data['ecological_total_assets'];
        $business_id = M('user')->where(['mobile' => $data['business_phone']])->getField('userid');
        $goods['shangjia'] = $business_id;
        $goods['pic'] = $data['pic'];
        $goods['pic1'] = $data['pic1'];
        $goods['pic2'] = $data['pic2'];
        $goods['pic3'] = $data['pic3'];
        $goods['pic4'] = $data['pic4'];
        $goods['pic5'] = $data['pic5'];
        $goods['stock'] = $data['stock'];
        $goods['is_sort'] = $data['is_sort'];
        $goods['content'] = $data['content'];
        $goods['status'] = $data['status'];
        if ($goods['id']) {
            $res = M('product_detail')->save($goods);
            $operation = '修改';
        } else {
            $res = M('product_detail')->add($goods);
            $operation = '新增';
        }
        if ($res === false) {
            return array(
                'status' => 'fail',
                'message' => $operation . '失败',
            );
        } else {
            return array(
                'status' => 'success',
                'message' => $operation . '成功',
                'jump' => U('Goods/regenerateShop')
            );
        }
    }

    /**
     * 新增或编辑自营商城商品
     * @return array
     */
    public function edit_self_support_shop_goods()
    {
        $data = I('post.');
        $error = $this->self_support_validate($data);
        if ($error) {
            return $error;
        }
        if ($data['id']) {
            $goods['id'] = $data['id'];
        } else {
            $goods['ctime'] = time();
            $goods['shop_type'] = Constants::SHOP_TYPE_SELF_SUPPORT;
        }

        $goods['name'] = $data['name'];
        $goods['type_id'] = $data['category'];
        $goods['old_price'] = $data['old_price'];
//        $goods['price'] = $data['price'];
        $goods['ecological_total_assets'] = $data['ecological_total_assets'];
        $goods['ecological_total_assets_one'] = $data['ecological_total_assets_one'];
        $goods['flow_pass_card'] = $data['flow_pass_card'];
        $goods['ecological_total_assets_two'] = $data['ecological_total_assets_two'];
        $goods['flow_amount'] = $data['flow_amount'];
        $goods['product_integral'] = $data['product_integral'];
        $business_id = M('user')->where(['mobile' => $data['business_phone']])->getField('userid');
        $goods['shangjia'] = $business_id;
        $goods['pic'] = $data['pic'];
        $goods['pic1'] = $data['pic1'];
        $goods['pic2'] = $data['pic2'];
        $goods['pic3'] = $data['pic3'];
        $goods['pic4'] = $data['pic4'];
        $goods['pic5'] = $data['pic5'];
        $goods['stock'] = $data['stock'];
        $goods['is_sort'] = $data['is_sort'];
        $goods['content'] = $data['content'];
        $goods['status'] = $data['status'];
        if ($goods['id']) {
            $res = M('product_detail')->save($goods);
            $operation = '修改';
        } else {
            $res = M('product_detail')->add($goods);
            $operation = '新增';
        }
        if ($res === false) {
            return array(
                'status' => 'fail',
                'message' => $operation . '失败',
            );
        } else {
            return array(
                'status' => 'success',
                'message' => $operation . '成功',
                'jump' => U('Goods/selfSupportShop')
            );
        }
    }

    /**
     * 新增或编辑爱心商城商品
     * @return array
     * @author ldz
     * @time 2020/3/2 14:05
     */
    public function edit_love_shop_goods()
    {
        $data = I('post.');
        $error = $this->love_validate($data);
        if ($error) {
            return $error;
        }

        if ($data['id']) {
            $goods['id'] = $data['id'];
        } else {
            $goods['ctime'] = time();
            $goods['shop_type'] = Constants::SHOP_TYPE_LOVE;
        }

        $goods['name'] = $data['name'];
        $goods['type_id'] = $data['category'];
        $goods['ecological_total_assets'] = $data['ecological_total_assets'];
        $business_id = M('user')->where(['mobile' => $data['business_phone']])->getField('userid');
        $goods['shangjia'] = $business_id;
        $goods['pic'] = $data['pic'];
        $goods['pic1'] = $data['pic1'];
        $goods['pic2'] = $data['pic2'];
        $goods['pic3'] = $data['pic3'];
        $goods['pic4'] = $data['pic4'];
        $goods['pic5'] = $data['pic5'];
        $goods['stock'] = $data['stock'];
        $goods['is_sort'] = $data['is_sort'];
        $goods['content'] = $data['content'];
        $goods['status'] = $data['status'];
        if ($goods['id']) {
            $res = M('product_detail')->save($goods);
            $operation = '修改';
        } else {
            $res = M('product_detail')->add($goods);
            $operation = '新增';
        }
        if ($res === false) {
            return array(
                'status' => 'fail',
                'message' => $operation . '失败',
            );
        } else {
            return array(
                'status' => 'success',
                'message' => $operation . '成功',
                'jump' => U('Goods/loveShop')
            );
        }
    }

    /**
     * 消费区和再生商城字段验证
     * @param $data
     * @return array
     */
    private function bao_dan_validate($data)
    {
        $error = $this->validate($data);
        if ($error) {
            return $error;
        }

        $error = array('status' => 'back');
        if (empty($data['price'])) {
            $error['message'] = '消费通证必填';
            return $error;
        }
        if ($data['price'] <= 0) {
            $error['message'] = '消费通证必须大于零';
            return $error;
        }

        if (empty($data['ecological_total_assets'])) {
            $error['message'] = '可用余额不能为空';
            return $error;
        }
        if ($data['ecological_total_assets'] <= 0) {
            $error['message'] = '可用余额不能小于零';
            return $error;
        }

        $total_money = $data['price'] + $data['ecological_total_assets'];
        $array_money = [2000, 5000, 10000];
        if (!in_array($total_money, $array_money)) {
            $error['message'] = '总金额必须等于</br>2000或者5000或者10000';
            return $error;
        }

        return [];
    }

    /**
     * 自营商城字段验证
     * @param $data
     * @return array
     */
    private function self_support_validate($data)
    {
        $error = $this->validate($data);
        if ($error) {
            return $error;
        }

        $error = array('status' => 'back');
        if (empty($data['old_price'])) {
            $error['message'] = '成本价不能为空';
            return $error;
        }
        if ($data['old_price'] <= 0) {
            $error['message'] = '成本价不能小于零';
            return $error;
        }

        if (empty($data['ecological_total_assets']) || empty($data['ecological_total_assets_one']) || empty($data['ecological_total_assets_two'])) {
            $error['message'] = '可用余额不能为空';
            return $error;
        }
        if ($data['ecological_total_assets'] <= 0 || $data['ecological_total_assets_one'] <= 0 || $data['ecological_total_assets_two'] <= 0) {
            $error['message'] = '可用余额不能小于零';
            return $error;
        }

        if (empty($data['flow_pass_card'])) {
            $error['message'] = '流动通证不能为空';
            return $error;
        }
        if ($data['flow_pass_card'] <= 0) {
            $error['message'] = '流动通证不能小于零';
            return $error;
        }
        if (empty($data['flow_amount'])) {
            $error['message'] = '流动资产不能为空';
            return $error;
        }
        if ($data['flow_amount'] <= 0) {
            $error['message'] = '流动资产不能小于零';
            return $error;
        }
        if (empty($data['product_integral'])) {
            $error['message'] = '我的仓库不能为空';
            return $error;
        }
        if ($data['product_integral'] <= 0) {
            $error['message'] = '我的仓库不能小于零';
            return $error;
        }

        return [];
    }

    /**
     * 爱心商城字段验证
     * @param $data
     * @return array
     */
    private function love_validate($data)
    {
        $error = $this->validate($data);
        if ($error) {
            return $error;
        }

        $error = array('status' => 'back');
        if (empty($data['ecological_total_assets'])) {
            $error['message'] = '可用余额不能为空';
            return $error;
        }
        if ($data['ecological_total_assets'] <= 0) {
            $error['message'] = '可用余额不能小于零';
            return $error;
        }
        return [];
    }

    /**
     * 字段验证
     * @param $data
     * @return array
     */
    private function validate($data)
    {
        $error = array('status' => 'back');
        if (empty($data['name'])) {
            $error['message'] = '商品名称必填';
            return $error;
        }
        if (empty($data['category'])) {
            $error['message'] = '请选择分类';
            return $error;
        }

        if (empty($data['pic'])) {
            $error['message'] = '商品封面图必填';
            return $error;
        }
        if (empty($data['stock'])) {
            $error['message'] = '库存必填';
            return $error;
        }
        if ($data['stock'] <= 0) {
            $error['message'] = '库存必须大于零';
            return $error;
        }
        if (empty($data['content'])) {
            $error['message'] = '商品详情必填';
            return $error;
        }
        if (empty($data['business_phone'])) {
            $error['message'] = '请输入商家手机号';
            return $error;
        }

        $businessInfo = M('user')->where(['mobile' => $data['business_phone']])->field('is_e_verify,is_p_verify')->find();
        if (!$businessInfo) {
            $error['message'] = '该商家不存在，请重新输入';
            return $error;
        }
        if ($businessInfo['is_e_verify'] == Constants::YesNo_No && $businessInfo['is_p_verify'] == Constants::YesNo_No) {
            $error['message'] = '该用户还不是商家，请重新输入';
            return $error;
        }
        return [];
    }

    function add_dianpu($data)
    {
        $error = $this->zongdian($data);
        if ($error) {
            return $error;
        }

        $id['id'] = 1;
        $goods['shop_name'] = $data['shop_name'];
        $goods['shop_logo'] = $data['shop_logo'];
        $goods['shop_vpay'] = $data['shop_vpay'];
        $goods['shop_weixin'] = $data['shop_weixin'];
        $goods['shop_zhifubao'] = $data['shop_zhifubao'];
        $goods['shop_address'] = $data['shop_address'];
        $goods['shop_type'] = $data['shop_type'];
        $goods_id = M('gerenshangpu')->where($id)->save($goods);

        if ($goods_id) {
            return array(
                'status' => 'success',
                'message' => '修改成功',
                'jump' => U('goods/dianpu')
            );
        } else {
            return array(
                'status' => 'fail',
                'message' => '修改失败',
                'jump' => U('goods/dianpu')
            );
        }
    }

    public function zongdian($data)
    {

        $error = array();
        if (empty($data['shop_name'])) {
            $error = '店铺名称必填';
        }
        if (empty($data['shop_address'])) {
            $error = '发货地址必填';
        }
        if (empty($data['shop_logo'])) {
            $error = '店铺logo图必填';
        }
//			if(empty($data['csize'])){
//				$error='产品尺寸必填';
//			}
//			if(empty($data['color_cate'])){
//				$error='产品颜色必填';
//			}

        if (empty($data['shop_type'])) {
            $error = '产品分类必填';
        }
        if ($error) {
            return array(
                'status' => 'back',
                'message' => $error
            );
        }
    }


    function get_goods_options($goods_id)
    {
        $goods_option_data = array();
        $goods_option_query = M()->query("SELECT * FROM " . C('DB_PREFIX') . "goods_option po LEFT JOIN "
            . C('DB_PREFIX') . "option o ON po.option_id = o.option_id WHERE po.goods_id =" . (int)$goods_id);

        foreach ($goods_option_query as $goods_option) {
            $goods_option_value_data = array();
            $goods_option_value_query = M()->query("SELECT * FROM " . C('DB_PREFIX')
                . "goods_option_value WHERE goods_option_id = '"
                . (int)$goods_option['goods_option_id'] . "'");

            foreach ($goods_option_value_query as $goods_option_value) {
                $goods_option_value_data[] = array(
                    'goods_option_value_id' => $goods_option_value['goods_option_value_id'],
                    'option_value_id' => $goods_option_value['option_value_id'],
                    'quantity' => $goods_option_value['quantity'],
                    'subtract' => $goods_option_value['subtract'],
                    'price' => $goods_option_value['price'],
                    'price_prefix' => $goods_option_value['price_prefix'],
                    'image' => $goods_option_value['image'],
                    'weight' => $goods_option_value['weight'],
                    'weight_prefix' => $goods_option_value['weight_prefix']
                );
            }

            $goods_option_data[] = array(
                'goods_option_id' => $goods_option['goods_option_id'],
                'option_id' => $goods_option['option_id'],
                'name' => $goods_option['name'],
                'type' => $goods_option['type'],
                'option_value' => $goods_option['name'],
                'required' => $goods_option['required'],
                'goods_option_value' => $goods_option_value_data,
            );
        }

        return $goods_option_data;
    }

    /**
     * 新增或编辑淘宝天猫商城商品
     * @return array
     */
    public function edit_tao_bao_goods()
    {
        $data = I('post.');
        $error = $this->tao_bao_validate($data);
        if ($error) {
            return $error;
        }
        $data['cost_price'] = formatNum2($data['cost_price']);
        $data['discount_price'] = formatNum2($data['discount_price']);
        if (!$data['id']) {
            $data['create_time'] = time();
        }
        if ($data['id']) {
            $res = M('tao_bao_product')->save($data);
            $operation = '修改';
        } else {
            $res = M('tao_bao_product')->add($data);
            $operation = '新增';
        }
        if ($res === false) {
            return ['status' => 'fail', 'message' => $operation . '失败', 'jump' => U('Goods/taoBao')];
        } else {
            return ['status' => 'success', 'message' => $operation . '成功', 'jump' => U('Goods/taoBao')];
        }
    }

    private function tao_bao_validate($data)
    {
        $error = array('status' => 'back');
        if (empty($data['product_name'])) {
            $error['message'] = '商品名称必填';
            return $error;
        }

        $cost_price = formatNum2($data['cost_price']);
        if (empty($cost_price) || $cost_price <= 0) {
            $error['message'] = '请填写原价';
            return $error;
        }
        $discount_price = formatNum2($data['discount_price']);
        if (empty($discount_price) || $discount_price <= 0) {
            $error['message'] = '请填写折后价';
            return $error;
        }
        if (empty($data['product_url'])) {
            $error['message'] = '请填写商品链接';
            return $error;
        }

        if (empty($data['pic_url'])) {
            $error['message'] = '请填写主图链接';
            return $error;
        }
        if (empty($data['coupon_url'])) {
            $error['message'] = '请填写优惠券链接';
            return $error;
        }
        return [];
    }


}
