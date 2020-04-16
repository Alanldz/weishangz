<?php

namespace Shop\Model;

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
    public static function getGoodsCategoryByShopType($shop_type)
    {
        if ($shop_type != 0) {
            $where['shop_type'] = ['like', '%' . $shop_type . '%'];
        }
        $where['status'] = Constants::YesNo_Yes;
        $cateList = M('product_cate')->where($where)->select();

        return $cateList;
    }

    public function show_goods_page($search)
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

//        $sql .= ' order by pd.status desc,pd.ctime desc LIMIT ' . $Page->firstRow . ',' . $Page->listRows;

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

    /**
     * 按商城获取商品
     * @param int $shop_type
     * @param int $limit
     * @return mixed
     */
    public static function getGoodsByShopType($shop_type = Constants::SHOP_TYPE_BAO_DAN, $limit = 6)
    {
        $where['status'] = Constants::YesNo_Yes;
        $where['shop_type'] = $shop_type;
        $fields = 'id,name,price,ecological_total_assets,flow_pass_card,pic';
        $product_list = M("product_detail")->field($fields)->where($where)
            ->order("is_sort asc,ctime desc")->limit($limit)->select();

        return $product_list;
    }
}
