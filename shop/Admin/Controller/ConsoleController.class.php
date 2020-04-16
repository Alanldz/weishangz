<?php
/**
 * 计划任务控制器
 */
namespace Admin\Controller;
use Think\Controller;
class ConsoleController extends Controller
{

    /**
     * 如果sp配送中断，有两条未交易的订单就发送短信提示
     */
    public function auto(){
        //卖方列表
        $seller_list = M('deal')->where(['status'=>0,'cid'=>1])->count();
        if($seller_list >= 2){
            $config = D('config')->where("name='sellmobile'")->getField("value");
            $arrMobile = explode(';', $config);
            foreach ($arrMobile as $mobile) {
                 sellSendMsg($mobile,'115710');
            }
        }

        file_put_contents("aaaa.txt",var_export(date('Y-m-d H:i:s',time()),true)."\r\n",FILE_APPEND);
        $this->display();
    }

}
