/*
Powered by ly200.com		http://www.ly200.com
广州联雅网络科技有限公司		020-83226791
*/

var shop_obj = {
    page_init: function () {
        //分类
        $('#category .close, #cover_layer').click(function () {
            $('html, body').removeAttr('style');
            $('#cover_layer').hide();
            $('#category').animate({ left: '-100%' }, 500);
            $('#shop_page_contents').animate({ margin: '0' }, 500);
        });

        //案例
        $('#category2 .close, #cover_layer').click(function () {
            $('html, body').removeAttr('style');
            $('#cover_layer').hide();
            $('#category2').animate({ left: '-100%' }, 500);
            $('#shop_page_contents').animate({ margin: '0' }, 500);
        });

        if ($('#support').size()) {
            $('#support').css('bottom', 0);
            $('#footer').css('bottom', 16);
            $('#footer_points').height(57);
        }

        $('footer .category a').click(function () {
            if ($('#category').height() > $(window).height()) {
                $('html, body, #cover_layer').css({
                    height: $('#category').height(),
                    width: $(window).width(),
                    overflow: 'hidden'
                });
            } else {
                $('#category, #cover_layer').css('height', $(window).height());
                $('html, body').css({
                    height: $(window).height(),
                    overflow: 'hidden'
                });
            }

            $('#cover_layer').show().html('');
            $('#category').animate({ left: '0%' }, 500);
            $('#shop_page_contents').animate({ margin: '0 -60% 0 60%' }, 500);
            window.scrollTo(0);

            return false;
        });

        //案例
        $('footer .category2 a').click(function () {
       
            if ($('#category2').height() > $(window).height()) {
                $('html, body, #cover_layer').css({
                    height: $('#category2').height(),
                    width: $(window).width(),
                    overflow: 'hidden'
                });
            } else {
                $('#category2, #cover_layer').css('height', $(window).height());
                $('html, body').css({
                    height: $(window).height(),
                    overflow: 'hidden'
                });
            }

            $('#cover_layer').show().html('');
            $('#category2').animate({ left: '0%' }, 500);
            $('#shop_page_contents').animate({ margin: '0 -60% 0 60%' }, 500);
            window.scrollTo(0);

            return false;
        });

        
    },

    detail_init: function () {
        if (proimg_count > 0) {
            (function (window, $, PhotoSwipe) {
                $('.touchslider-viewport .list a[rel]').photoSwipe({});
            }(window, window.jQuery, window.Code.PhotoSwipe));

            $('.touchslider').touchSlider({
                mouseTouch: false,
                autoplay: true,
                delay: 2000
            });
        }
        $('.touchslider-nav-item').click(function () {

            if (this.className.split(" ").length < 3) {
                if (this.id == "aPic1") {
                    $("#dPic1").css("left", " 8200px");
                    $("#dPic2").css("left", " -10000px");
                    $("#dPic3").css("left", " -10000px");
                    this.className = "touchslider-nav-item touchslider-nav-item-current";

                    $('.touchslider-nav-item')[1].className = "touchslider-nav-item";
                    $('.touchslider-nav-item')[2].className = "touchslider-nav-item";

                } else
                    if (this.id == "aPic2") {
                        $("#dPic2").css("left", " 8200px");
                        $("#dPic1").css("left", " -10000px");
                        $("#dPic3").css("left", " -10000px");
                        this.className = "touchslider-nav-item touchslider-nav-item-current";
                        $('.touchslider-nav-item')[0].className = "touchslider-nav-item";
                        $('.touchslider-nav-item')[2].className = "touchslider-nav-item";
                    } else
                        if (this.id == "aPic3") {
                            $("#dPic3").css("left", " 8200px");
                            $("#dPic1").css("left", " -10000px");
                            $("#dPic2").css("left", " -10000px");
                            this.className = "touchslider-nav-item touchslider-nav-item-current";
                            $('.touchslider-nav-item')[0].className = "touchslider-nav-item";
                            $('.touchslider-nav-item')[1].className = "touchslider-nav-item";
                        }
            }

        });

        $('#detail .property span').click(function () {
            var Pid = $(this).attr('Pid');
            $('#detail .property span[Pid=' + Pid + ']').removeClass();
            $(this).addClass('cur');
            $('#detail #PId_' + Pid).val($(this).attr('LId'));
        });

        $('#addtocart_tips .close').click(function () {
            $(this).parent().hide();
        });
        $('#addtocart_form a[name=add]').click(function () { //添加商品数量
            var a = $("#Qty").val();
            a++;
            $("#Qty").val(a);

        });
        $('#addtocart_form a[name=minus]').click(function () {//减少商品数量
            var a = $("#Qty").val();
            if (a > 1) {
                a--;
            }
            $("#Qty").val(a);

        });

        $('#addtocart_form').submit(function () { return false; });
        $('#addtocart_form .cart .add, #addtocart_form .cart .buy').click(function () {
            if (!confirm('是否购买该产品?')) {
                return;
            }
            $('#addtocart_tips').hide();
         

            var buyNum = $("#Qty").val();
            var strRest = $("#restNum").text();
            var reg = /[0-9]+/;
            var restNum = reg.exec(strRest);
            if (parseInt(buyNum) > parseInt(restNum)) {
                global_obj.win_alert('购买数量不能大于剩余参团人数！')
                //alert('购买数量大于库存数量！');
                return false;
            }


            //yax新增部分  end
            var this_btn = $(this);
            this_btn.attr('disabled', true);

            $.post($('#addtocart_form').attr('action') , $('#addtocart_form').serialize(), function (data) {
                this_btn.attr('disabled', false);
                if (data.status == 1) {
                    window.location.href = "/member/orderitem.aspx?type=1&id=" + data.Goodsqty;
                } else if (data.status == 2) {
                    global_obj.win_alert('购买数量大于库存数量！')
                    //alert('购买数量大于库存数量！');
                    $("#restNum").text(data.Goodsqty+'人');
                  
                } else if (data.status == 3) {
                    global_obj.win_alert("您当前积分还差" + data.total +"分才能够购买，请您<a href='/PayMoney.aspx?money="+data.total+"'>【充值】</a>或者去【积分寄售】列表购买积分！")
                    //alert('购买数量大于库存数量！');

                } else {
                    global_obj.win_alert('系统繁忙，购买失败，请重新访问本页面再尝试购买。');
                }
            }, 'json');
        });

        $('#addtocart_form .wx_buybtn .add, #addtocart_form .wx_buybtn .buy').click(function () {
            $('#addtocart_tips').hide();
            //yax新增部分 begin
            var chkSizes = document.getElementsByName("SizeN");
            if (chkSizes.length > 0) {
                var isChoose = false;
                for (var i = 0; i < chkSizes.length; i++) {
                    if (chkSizes[i].checked == document.getElementById("haha2").checked) {
                        isChoose = true;
                    }
                }
                if (!isChoose) {
                    global_obj.win_alert('请选择属性')
                    //alert('请选择尺寸');
                    return false;
                }
            }


            var chkColors = document.getElementsByName("ColorN");
            if (chkColors.length > 0) {
                var isChoose = false;
                for (var i = 0; i < chkColors.length; i++) {
                    if (chkColors[i].checked == document.getElementById("haha2").checked)  //haha2表示已选
                    {
                        isChoose = true;
                    }
                }
                if (!isChoose) {
                    global_obj.win_alert('请选择属性')
                    //alert('请选择颜色');
                    return false;
                }
            }

            var buyNum = $("#Qty").val();
            var strRest = $("#restNum").text();
            var reg = /[0-9]+/;
            var restNum = reg.exec(strRest);
            if (parseInt(buyNum) > parseInt(restNum)) {
                global_obj.win_alert('购买数量大于库存数量！')
                //alert('购买数量大于库存数量！');
                return false;
            }


            //yax新增部分  end
            var this_btn = $(this);
            this_btn.attr('disabled', true);

            $.post($('#addtocart_form').attr('action') + 'cart', $('#addtocart_form').serialize(), function (data) {
                this_btn.attr('disabled', false);
                if (data.status == 1) {
                    if (this_btn.attr('class') == 'buy') {
                        window.location.href = data.url;
                    } else {
                        //$('#addtocart_tips .Goodsqty').html(data.num);
                        $('#addtocart_tips .qty').html(data.qty);
                        $('#addtocart_tips .total').html('￥' + data.total);

                        $('#addtocart_tips .Goodsqty').html(data.Goodsqty);
                        $('#addtocart_tips .num').html('￥' + data.num);

                        $('#addtocart_tips').css({
                            left: $(window).width() / 2 - 125,
                            top: $(window).height() / 2 - 60
                        }).show();
                    }
                } else if (data.status == 2) {
                    global_obj.win_alert('购买数量大于库存数量！')
                    //alert('购买数量大于库存数量！');

                }
            }, 'json');
        });

        $('#promo_form   .buy').click(function () {
            var this_btn = $(this);
            this_btn.attr('disabled', true);

            $.post($('#promo_form').attr('action') + 'buy', $('#promo_form').serialize(), function (data) {
                this_btn.attr('disabled', false);
                if (data.status == 1) {
                    if (this_btn.attr('class') == 'buy') {
                        window.location = data.url;
                    } else {

                    }
                }
            }, 'json');
        });
    },

    review_init: function () {
        $('#review_form').submit(function () { return false; });
        $('#review_form :submit').click(function () {
            var this_btn = $(this);
            if (global_obj.check_form($('*[notnull]'))) { return false };
            $.post($('#review_form').attr('action') + 'ajax/', $('#review_form').serialize(), function (data) {
                this_btn.attr('disabled', true);
                //alert(data);
                if (data.status > 0) {
                    alert('评论已经成功提交');
                    //alert(data);
                    if (data.status == 2) {
                        var html = '<div class="items"><div class="lbar"><div class="face"><img src="' + data.face + '" /></div><div class="name">' + data.Name + '</div></div><div class="rbar">' + data.Contents + '</div><div class="clear"></div></div>';
                        $('.review_list').prepend(html);
                    }
                }
            }, 'json');
        });
    },

    cart_init: function () {
         
        var price_detail = function () {
            var total_price = 0;
            $('#cart_form .sub_total span span').each(function () {
                var price = parseFloat($(this).parent().parent().siblings('.price').children('span').html().replace('￥', ''));
                var qty = parseInt($(this).parent().siblings('input[name=Qty\\[\\]]').val());
                isNaN(qty) && (qty = 1);
                var sub_total = price * qty;
                sub_total = sub_total.toFixed(2);
                $(this).html('￥' + sub_total);
                total_price += price * qty;
            });

            $('#cart_form .total span').html('￥' + total_price.toFixed(2));

        }

        var closeprice_detail = function () {
            var total_price = 0;
            $('#cart_form .sub_total span span').each(function () {
                var price = parseFloat($(this).parent().parent().siblings('.price').children('span').html().replace('￥', ''));
                var qty = parseInt($(this).parent().siblings('input[name=Qty\\[\\]]').val());
                isNaN(qty) && (qty = 1);
                var sub_total = price * qty;
                sub_total = sub_total.toFixed(2);
                $(this).html('￥' + sub_total);
                total_price += price * qty;
            });

            $('#cart_form .total span').html('￥' + total_price.toFixed(2));

        }


        $('#cart_form .sub_total input[name=Qty\\[\\]]').blur(function () {
            var obj = $(this);
            var qty = $(this).val();
            //$(this).val(qty);
            var _ProId = $(this).parent().attr('ProId');
            var _CId = $(this).parent().children('input[name=CId\\[\\]]').val();
            $.get($('#cart_form').attr('action') + '&', 'd=list&gotype=update&id=' + _CId + '&Qty=' + qty + '&Price=' + $('#cart_form .info .price span').attr('id'), function (data) {
                if (data.status == 1) {
                    // obj.parent().siblings('.price').children('span').html('￥' + data.price);
                    price_detail();
                };
                if (data.status == 2) {
                    obj.val(data.qty);
                    alert('购买数量大于库存数量');

                }
            }, 'json');
        });
        // + '&id=' + $('#cart_form .del div').attr('id')
        price_detail();
        $('#cart_form input[name=Qty\\[\\]]').keyup(function () {
            //var qty=parseInt($(this).val().replace(/[^\d]/g, ''));
            var obj = $(this);
            var qty = $(this).val();
            qty >= 1000 && (qty = 999);
            $(this).val(qty);

            var _ProId = $(this).parent().attr('ProId');
            var _CId = $(this).parent().children('input[name=CId\\[\\]]').val();
            $.post($('#cart_form').attr('action') + 'ajax/', $('#cart_form').serialize() + 'd=list&atype=update&_ProId=' + _ProId + '&_CId=' + _CId, function (data) {
                if (data.status == 1) {
                    obj.parent().siblings('.price').children('span').html('￥' + data.price);
                    price_detail();
                } else {
                    global_obj.win_alert('出现未知错误！');
                }
            }, 'json');
        });

        //添加了category
        $('#cart_form .del div').click(function () {
            var obj = $(this);
            //'d=list&atype=del&CId=' + $(this).attr('CId') + '&id=' + $(this).attr('id')
            $.post('/AjaxCs/Shop.ashx?sid=' + $('#hfsid').val() + '&gotype=cartDel&id=' + $(this).attr('id') + '&category=' + $('#category').val(), function (data) {
                if (data.status == 1) {
                    $('#cart_form .total span').html('￥' + data.total);
                    obj.parent().parent().remove();
                    price_detail();
                };
            }, 'json');

 
            //$.get($('#cart_form').attr('action') + '&', '&gotype=del&id=' + $(this).attr('id'), function (data) {
            //    if (data.status == 1) {
            //	    $('#cart_form .total span').html('￥' + data.total);
            //	    obj.parent().parent().remove();
            //	    price_detail();
            //	} ;
            //}, 'json'); 
        });

        $('#cart_form').submit(function () { return false; });
        $('#cart_form .checkout input').click(function () {
            //yax  新增  购物车  cart.aspx  页面



            //yax  新增
            $(this).attr('disabled', true);
            $.post($('#cart_form').attr('action'), $('#cart_form').serialize(), function (data) {
                $('#cart_form .checkout input').attr('disabled', false);
                if (data.status == 1) {
                    window.location = data.url;
                }
                else {
                    window.location = data.url;
                }
            }, 'json');
        });
    },

    checkout_init: function () {
        var address_display = function () {
            var AId = parseInt($('#checkout_form input[name=AId]:checked').val());
            if (AId == 0 || isNaN(AId)) {
                $('#checkout .address dl').css('display', 'block');
            } else {
                $('#Name').val($('#adName-' + AId).val());
                $('#MobilePhone').val($('#adPhone-' + AId).val());
                $('#Address').val($('#adAddress-' + AId).val());

                $.cascadeDropDownBind.bind({
                    sourceID: 'Province', selectValue: $('#adProvince-' + AId).val(), parentValue: '0', removeFirst: true,

                    child: {
                        sourceID: 'City', selectValue: $('#adCity-' + AId).val(), removeFirst: true,

                        child: {
                            sourceID: 'Area', selectValue: $('#adArea-' + AId).val(), removeFirst: true
                        }
                    }

                });

                ///    $('#Province').val($('#adProvince-' + AId).val());
                //  $('#City').val($('#adCity-' + AId).val());
                //  $('#Area').val($('#adArea-' + AId).val());
                //$('#checkout .address dl').css('display', 'none');
            }
        }
        var total_price_display = function () {
            var shipping_price = parseFloat($('#checkout_form input[name=SId]:checked').attr('Price'));
            isNaN(shipping_price) && (shipping_price = 0);
            var total_price = parseFloat($('#checkout_form input[name=total_price]').val()) + shipping_price;
            $('#checkout_form .total_price span').html('￥' + total_price.toFixed(2));
        }

        $('#checkout_form input[name=AId]').click(address_display);
        $('#checkout_form input[name=SId]').click(total_price_display);
        address_display();
        total_price_display();

        $('#checkout_form').submit(function () { return false; });
        $('#checkout_form .checkout input').click(function () {
            var AId = parseInt($('#checkout_form input[name=AId]:checked').val());
            if (AId == 0 || isNaN(AId)) {
                if (global_obj.check_form($('*[notnull]'))) { return false };
                if ($("#Area").val() == "" || $("#Area").val() == "999999") {
                    $('#Province').focus();
                    return false;
                }
            }


            $(this).attr('disabled', true);
            $.post($('#checkout_form').attr('action') + 'ajax/', $('#checkout_form').serialize(), function (data) {

                if (data.status == 1) {
                    //	window.location=$('#checkout_form').attr('action')+'?d=payment&OrderId='+data.OrderId;
                    window.location = data.url;
                } else if
                 (data.status == "4") {       // 4： 库存不足
                    alert('该商品已经被其他用户买完啦。');
                    $(this).attr('disabled', false);
                    window.location = data.url;
                }

            }, 'json');
        });
    },

    payment_init: function () {
        var PaymentMethod = $('#payment_form input[name=PaymentMethod]');
        if (PaymentMethod.size()) {
            var change_payment_method = function () {
                if (PaymentMethod.filter(':checked').val() == '线下支付') {
                    $('#payment_form .payment_info').show();
                } else {
                    $('#payment_form .payment_info').hide();
                }
            }
            PaymentMethod.click(change_payment_method);
            PaymentMethod.filter('[value=' + $('#payment_form input[name=DefautlPaymentMethod]').val() + ']').click();
            change_payment_method();
        } else {
            $('#payment_form').hide();
        }

        $('#payment_form').submit(function () { return false; });
        $('#payment_form .payment input').click(function () {
            $(this).attr('disabled', true);
            $.post($('#payment_form').attr('action') + 'ajax/', $('#payment_form').serialize(), function (data) {
                $('#payment_form .payment input').attr('disabled', false);
                if (data.status == 1) {
                    window.location = data.url
                }
            }, 'json');
        });
    },

    bankcards_init: function () {
        $('#bankcards_form .payment input').click(function () {
            if (global_obj.check_form($('*[notnull]'))) { return false };

            $(this).attr('disabled', true);
            var sid = $("#sid").val();
            $.post('?action=save', $('#bankcards_form').serialize(), function (data) {


                if (data.status == 1) {
                    window.location.href = data.url;
                } else if (data.status == 1) {

                    global_obj.win_alert('服务器繁忙，请刷新页面重新操作！', function () {
                        $('#user_form .submit input').attr('disabled', false)
                        $('#user_form input[name=Password]').val('');
                    });
                }
            }, 'json');



            //$.post('?', $('#user_form').serialize(), function(data){
            //	if(data.status==1){
            //		window.location=data.jumbbp_url;
            //	}else{
            //		global_obj.win_alert('错误的用户名或密码，请重新登录！', function(){
            //			$('#user_form .submit input').attr('disabled', false)
            //			$('#user_form input[name=Password]').val('');
            //		});
            //	};
            //}, 'json');
        });
    },
    //yax  新增
    comment_init: function () {
        $('#bankcards_form .comment input').click(function () {
            var radios = document.getElementsByName("type");
            var isCheck = false;
            for (var i = 0; i < radios.length; i++) {
                if (radios[i].checked) {
                    isCheck = true;
                }
            }
            if (!isCheck) {
                alert('亲！请对每一项评分做出评价');
                return false;
            }
            if ($("#Description").val() == "") {
                alert('亲！请对每一项评分做出评价');
                return false;
            }
            if ($("#Service").val() == "") {
                alert('亲！请对每一项评分做出评价');
                return false;
            }
            if ($("#Delivery").val() == "") {
                alert('亲！请对每一项评分做出评价');
                return false;
            }
            if ($("#Logistics").val() == "") {
                alert('亲！请对每一项评分做出评价');
                return false;
            }
        });
    },
    //yax 新增End

    drawmoney_init: function () {
        $('#drawmoney_form .payment input').click(function () {
            if (global_obj.check_form($('*[notnull]'))) { return false };
            var price = parseFloat($("#Price").val());
            var uprice = parseFloat($("#uPrice").val());
            if (price % 100 != 0) {
                global_obj.win_alert('提现失败!<br>提现金额必须整百的金额');
                return;
            }

            var money = price * 0.01;  //手续费1%
            var yue = uprice - price;   //余额
            if (money < 1) {            //手续费最低是1元，最多是25元
                money = 1;
            } else if (money > 25) {
                money = 25;
            }

            if (yue < money) {          //余额不能小于手续费
                global_obj.win_alert('提现失败!<br>剩下余额不够抵扣手续费');
                return
            }



            if (price > uprice) {
                global_obj.win_alert('提现失败!<br>提现金额不能大于余额');
                return;
            }
            $(this).attr('disabled', true);
            var sid = $("#sid").val();
            $.post('?action=save', $('#drawmoney_form').serialize(), function (data) {


                if (data.status == 1) {
                    global_obj.win_alert('提现成功!<br>扣除手续费:' + data.total, function () {

                        window.location.href = data.url;
                    });

                } else if (data.status == 0) {

                    global_obj.win_alert('提现失败!<br>提现金额不能大于余额！', function () {
                        $('#user_form .submit input').attr('disabled', false)
                        $('#user_form input[name=Password]').val('');
                    });
                }
            }, 'json');







            //$.post('?', $('#user_form').serialize(), function(data){
            //	if(data.status==1){
            //		window.location=data.jumbbp_url;
            //	}else{
            //		global_obj.win_alert('错误的用户名或密码，请重新登录！', function(){
            //			$('#user_form .submit input').attr('disabled', false)
            //			$('#user_form input[name=Password]').val('');
            //		});
            //	};
            //}, 'json');
        });
    },

    ziliao_init: function () {
        $('#drawmoney_form .payment input').click(function () {
            if (global_obj.check_form($('*[notnull]'))) { return false };
            var MobilePhone = $('input[name=Phone]').val();
            if (MobilePhone == '' || MobilePhone.length != 11) {
                global_obj.win_alert('请正确填写手机号码！', function () {
                    $('input[name=Phone]').focus();
                  
                });
                return;
            }
          //  var sid = $("#sid").val();
            $.post('?action=save', $('#drawmoney_form').serialize(), function (data) {


                if (data.status == 1) {
                    global_obj.win_alert('修改成功', function () {

                        window.location.href = data.url;
                    });

                } else if (data.status == 0) {

                    global_obj.win_alert('修改失败！', function () {
                        $('#user_form .submit input').attr('disabled', false)
                      
                    });
                }
            }, 'json');







            //$.post('?', $('#user_form').serialize(), function(data){
            //	if(data.status==1){
            //		window.location=data.jumbbp_url;
            //	}else{
            //		global_obj.win_alert('错误的用户名或密码，请重新登录！', function(){
            //			$('#user_form .submit input').attr('disabled', false)
            //			$('#user_form input[name=Password]').val('');
            //		});
            //	};
            //}, 'json');
        });
    },
    myshop_init: function () {
        $('#myshop_form .payment input').click(function () {
            if (global_obj.check_form($('*[notnull]'))) { return false };
           
            //$.post($('#myshop_form').attr('action') + '', $('#myshop_form').serialize(), function (data) {


            //    if (data.status == 1) {
            //        global_obj.win_alert('保存成功!');

            //    } else {

            //        global_obj.win_alert('保存失败');
                     
            //    }
            //}, 'json');
         

 
        });


    },

    user_address_init: function () {
        $('#address_form .back').click(function () {
            window.location = './?d=address';
        });

        $('#address_form').submit(function () { return false; });
        $('#address_form .submit').click(function () {
            if (global_obj.check_form($('*[notnull]'))) { return false };

            $(this).attr('disabled', true);
            $.post($('#address_form').attr('action') + 'ajax/', $('#address_form').serialize(), function (data) {
                if (data.status == 1) {
                    window.location = $('#address_form').attr('action') + '?d=address';
                }
            }, 'json');
        });
    }

}