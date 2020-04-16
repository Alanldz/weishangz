 

 

var posturl = "http://www.ypj889911.com/AjaxCs/jsonserver.aspx";
//var posturl = "http://localhost:53431/AjaxCs/jsonserver.aspx";

var member_obj = {
    member_index_init: function () {
      

        $.get(posturl + '?action=GetUser&eid=' + encodeURI(localStorage.eid), function (data) {
            var su = data.items[0];
         
          
            $('.ft_eid').html("EID:"+su.ID);
            $('.ft_xinyong').html("信用："+GetXing(su.Xing)); 

            $('#Money').html(su.Money);
            $('#MoneyJy').html(su.MoneyJy);
            $('#MoneyFt').html(su.MoneyFt);
            $('#MoneyDs').html(su.MoneyDs);
            var headimg = "img/img.jpg";
            if (su.HeadImg != "") {
                headimg = su.HeadImg;
            }
            $('.divheadimg').attr('src', headimg);

            //$('#ft_eid').html(GetZhType(1));
        }, 'json');
       
   
        $('.sc_login').click(function () {
            $.post(posturl + '?action=sc_login&eid=' + encodeURI(localStorage.eid), $('#DrawCz_form').serialize(), function (data) {

                if (data.status == 1) {

                    // alert(openid);
                    //   mainView.router.reloadPage("member/DrawCz.html")
                    window.location.href = data.msg;

                } else {

                    alert('商城即将开放，敬请期待');
                }
            }, 'json');
        });
        $.get(posturl + '?action=MsgCount&eid=' + encodeURI(localStorage.eid), function (data) {
            var msgcount1 = data.msg;
            var askcount1 = data.msg2;

            if (msgcount1 > 0) {
                $('.msg_caiwu').html("资金<span class='MsgCont'>" + msgcount1 + "</span>");
            }
            if (askcount1 > 0) {
                $('.msg_member').html("我的<span class='MsgCont'>" + askcount1 + "</span>");
            }



            //$('.msg_caiwu').html("资金<span class='MsgCont'>" + msgcount1 + "</span>");
            //$('.msg_member').html("我的<span class='MsgCont'>" + askcount1 + "</span>");

            //$('#ft_eid').html(GetZhType(1));
        }, 'json');

        $.get(posturl + '?action=GetUserDayR&eid=' + encodeURI(localStorage.eid), function (data) {
            var dayR = data.msg;

            if (dayR>0) {
                $('.dayR').html(dayR);

                $('.qd').show();  $('.cover_layer').show();
                
            }
           


            //$('#ft_eid').html(GetZhType(1));
        }, 'json');
        //事件
        $('.index_qd').click(function () {
            //    alert(1);

            $.post(posturl + '?action=index_qd&eid=' + encodeURI(localStorage.eid), $('#DrawPpItem_form').serialize(), function (data) {

                if (data.status == 1) {
                    global_obj.win_alert(data.msg, function () {
                        //window.location.href = "member/DrawCz.html";
                        mainView.router.reloadPage("index.html");
                    });

                } else {
                    global_obj.win_alert(data.msg, function () {

                    });
                }
            }, 'json');

        });
   
    },

    //SyListMoney2

 

    card_init: function () {
        $('#card .sign').click(function () {
            $(this).html('签到中...');
            $.get('?d=sign', function (data) {
                if (data.ret == 1) {
                    $('#card .sign').html('今天已签到');
                    $('#card .sign').off();
                    $('#card .intergral').html('我的原动力：' + data.msg);
                } else {
                    $('#card .sign').html('签到失败');
                };
            }, 'json');
        });

        $('#card .benefits_btn').click(function () {
            $('#card .benefits').slideToggle();
            $('#card .benefits_btn span:last').removeClass().addClass($('#card .benefits').is(':hidden') ? 'jt_up' : 'jt_down');
        });
    },

    my_init: function () {
        $('.modify_password').click(function () {
            var o = $(this);
            global_obj.div_mask();
            $('#modify_password_div').show();
            $('#modify_password_div .cancel').off().click(function () {
                global_obj.div_mask(1);
                $('#modify_password_div').hide();
            });

            $('#modify_password_form .submit').off().click(function () {
                if (global_obj.check_form($('#modify_password_form input[notnull]'))) { return false };
                if ($('#modify_password_form input[name=Password]').val() != $('#modify_password_form input[name=ConfirmPassword]').val()) {
                    global_obj.win_alert('登录密码与确认密码不匹配，请重新输入！', function () {
                        $('#modify_password_form input[name=Password]').focus();
                    });
                    return false;
                }

                $(this).attr('disabled', true);
                $.post('?d=modify_password', $('#modify_password_form').serialize(), function (data) {
                    if (data.ret == 1) {
                        global_obj.win_alert(data.msg, function () {
                            global_obj.div_mask(1);
                            $('#modify_password_div').hide();
                            $('#modify_password_form .submit').attr('disabled', false);
                            $('#modify_password_form input[name=Password], #modify_password_form input[name=ConfirmPassword]').val('');
                        });
                    } else {
                        global_obj.win_alert(data.msg, function () {
                            $('#modify_password_form .submit').attr('disabled', false);
                        });
                    };
                }, 'json');
            });
        });

        $('.modify_mobile').click(function () {
            var o = $(this);
            global_obj.div_mask();
            $('#modify_mobile_div').show();
            $('#modify_mobile_div .cancel').off().click(function () {
                global_obj.div_mask(1);
                $('#modify_mobile_div').hide();
            });
            $('#modify_mobile_form .submit').off().click(function () {
                if (global_obj.check_form($('#modify_mobile_form input[notnull]'))) { return false };
                $(this).attr('disabled', true);
                $.post('?d=modify_mobile', $('#modify_mobile_form').serialize(), function (data) {
                    if (data.ret == 1) {
                        global_obj.win_alert(data.msg, function () {
                            global_obj.div_mask(1);
                            $('#modify_mobile_div').hide();
                            $('#modify_mobile_form .submit').attr('disabled', false);
                            $('#modify_mobile_form input[name=MobilePhoneCheck]').val('');
                        });
                    } else {
                        global_obj.win_alert(data.msg, function () {
                            $('#modify_mobile_form .submit').attr('disabled', false);
                        });
                    };
                }, 'json');
            });

            $('#modify_mobile_form .sms_button').off().click(function () {
                var MobilePhone = $('input[name=MobilePhone]').val();
                if (MobilePhone == '' || MobilePhone.length != 11) {
                    global_obj.win_alert('请正确填写手机号码！', function () {
                        $('input[name=MobilePhone]').focus();
                    });
                } else {
                    $(this).attr('disabled', true);
                    var time = 0;
                    time_obj = function () {
                        if (time >= 30) {
                            $('#modify_mobile_form .sms_button').val('获取验证码').attr('disabled', false);
                            time = 0;
                            clearInterval(timer);
                        } else {
                            $('#modify_mobile_form .sms_button').val('重新获取(' + (30 - time) + ')');
                            time++;
                        }
                    }
                    var timer = setInterval('time_obj()', 1000);
                    $.get('?d=get_sms&MobilePhone=' + MobilePhone);
                }
            });
        });
    },

    my_address_init: function () {
        $('#user_form .submit_btn').click(function () {
            if (global_obj.check_form($('*[notnull]'))) { return false };
            $(this).attr('disabled', true);
            $.post('?', $('#user_form').serialize(), function (data) {
                if (data.ret == 1) {
                    window.location = '../my_address/';
                } else {
                    global_obj.win_alert(data.msg, function () {
                        $('#user_form .submit input').attr('disabled', false);
                    });
                }
            }, 'json');
        });
        $('#footer_user, #footer_user_points').hide();
    },

    integral_init: function () {
        $('#integral_header .sign').click(function () {
            $(this).html('签到中');
            $.get('?d=sign', function (data) {
                if (data.ret == 1) {
                    $('#integral_header .sign').html('已签到').off().removeClass().addClass('sign_ok');
                    $('#integral_header .l span').html(data.msg);
                    $('#integral_header .r span').html(parseInt($('#integral_header .r span').html()) + 1);
                } else {
                    $('#integral_header .sign').html('签到失败');
                };
            }, 'json');
        });

        $('#integral_get_use div').click(function () {
            var o = $(this);
            global_obj.div_mask();
            $('.pop_form').show();
            $('.pop_form .cancel').off().click(function () {
                global_obj.div_mask(1);
                $('.pop_form').hide();
            });
            $('.pop_form h1').html(o.html());
            $('.pop_form input:text').attr('placeholder', o.html());
            $('.pop_form input[name=opt]').val(o.html());

            $('#integral_form .submit').off().click(function () {
                if (global_obj.check_form($('*[notnull]'))) { return false };
                $(this).attr('disabled', true);
                $.post('?d=opt_integral', $('#integral_form').serialize(), function (data) {
                    if (data.ret == 1) {
                        global_obj.win_alert(data.msg, function () {
                            window.location.reload();
                        });
                    } else {
                        global_obj.win_alert(data.msg, function () {
                            $('#integral_form .submit').attr('disabled', false);
                        });
                    };
                }, 'json');
            });
        });
    },

    message_init: function () {
        $('#message .list').click(function () {
            var o = $(this);
            if (o.attr('Display') == 0) {
                o.attr('Display', 1);
                $.get('?d=get_message_contents&MId=' + o.attr('MId'), function (data) {
                    o.after('<div class="contents">' + data + '</div>');
                    o.removeClass().addClass('list is_read').find('div').addClass('up').html('');
                    o.next().slideToggle();
                    var not_read = $('#message .not_read').size();
                    if (not_read <= 0) {
                        $('#footer_user font').remove();
                    } else {
                        $('#footer_user font').html(not_read);
                    }
                }, 'text');
            } else {
                $(this).attr('Display', 0);
                o.next().slideToggle(function () {
                    o.next().remove();
                    o.find('div').removeClass();
                });
            }
        });
    },


    coupon_init: function () {
        $('#coupon .use').click(function () {
            var o = $(this);
            global_obj.div_mask();
            $('.pop_form').show();
            $('.pop_form .cancel').off().click(function () {
                global_obj.div_mask(1);
                $('.pop_form').hide();
            });

            $('#coupon_use_form .submit').off().click(function () {
                if (global_obj.check_form($('*[notnull]'))) { return false };
                $(this).attr('disabled', true);
                $.post('?d=use_coupon', $('#coupon_use_form').serialize() + '&LId=' + o.attr('LId'), function (data) {
                    if (data.ret == 1) {
                        global_obj.win_alert(data.msg, function () {
                            window.location = $('#coupon .t_list a:first').attr('href');
                        });
                    } else {
                        global_obj.win_alert(data.msg, function () {
                            $('#coupon_use_form .submit').attr('disabled', false);
                        });
                    };
                }, 'json');
            });
        });

        $('#coupon .p img').click(function () {
            $(this).parent().parent().find('h3').slideToggle();
        });

        $('#coupon .get').click(function () {
            var o = $(this);
            o.html('领取中...');
            $.get('?', 'd=get_coupon&LId=' + o.attr('LId'), function (data) {
                o.html('领取');
                if (data.ret == 1) {
                    global_obj.win_alert(data.msg, function () {
                        window.location = $('#coupon .t_list a:first').attr('href');
                    });
                } else {
                    global_obj.win_alert(data.msg);
                }
            }, 'json');
        });
    }
}



function  GetZhType(type)
{
    var   strs = new Array( "注册积分[XT]", "充值[XT]", "原动力[XT]", "EP充值", "兑换分[后台]", "收派", "消费", "兑换积分[E派]", "消费赠分", "收派赠分", "兑换积分[原动力]" );

    if (type <= 10) {
        return strs[type];
    }
    else {
        if (type == 11) {
            return "慈善分";
        }
        else if (type == 12) {
            return "兑换商品[兑换分]";
        }
        else if (type == 13) {
            return "押金[购入]";
        }
        else if (type == 14) {
            return "卖出E派";
        }
        else if (type == 15) {
            return " 15";
        }
        else if (type == 16) {
            return "转账";
        }
        else if (type == 17) {
            return "撤销寄售";
        }
        else if (type == 18) {
            return "18";
        }
        else if (type == 19) {
            return "买入E派";
        }

        else if (type == 21) {
            return "21";
        }
        else if (type == 22) {
            return "22";
        }
        else if (type == 23) {
            return "23";
        }
        else if (type == 24) {
            return "24";
        }
        else if (type == 25) {
            return "25";
        }
        else if (type == 26) {
            return "26";
        }
        return type + "";
    }
}

function  GetXing( xing)
{
    var strHtml = "";
    var i = 0;
    while (i < xing) {
        i++;
        strHtml += "<i class='icon iconfont icon-star'></i>";
    }
    return strHtml;
}
function GetVipXing(xing) {
    var strHtml = "";
    var i = 0;
    while (i < xing) {
        i++;
        strHtml += "<i class='icon iconfont icon-star'></i>";
    }
    return strHtml;
}



function IdentityCodeValid(code) {
    var city = { 11: "北京", 12: "天津", 13: "河北", 14: "山西", 15: "内蒙古", 21: "辽宁", 22: "吉林", 23: "黑龙江 ", 31: "上海", 32: "江苏", 33: "浙江", 34: "安徽", 35: "福建", 36: "江西", 37: "山东", 41: "河南", 42: "湖北 ", 43: "湖南", 44: "广东", 45: "广西", 46: "海南", 50: "重庆", 51: "四川", 52: "贵州", 53: "云南", 54: "西藏 ", 61: "陕西", 62: "甘肃", 63: "青海", 64: "宁夏", 65: "新疆", 71: "台湾", 81: "香港", 82: "澳门", 91: "国外 " };
    var tip = "";
    var pass = true;

    if (!code || !/^\d{6}(18|19|20)?\d{2}(0[1-9]|1[12])(0[1-9]|[12]\d|3[01])\d{3}(\d|X)$/i.test(code)) {
        tip = "身份证号格式错误";
        pass = false;
    }

    else if (!city[code.substr(0, 2)]) {
        tip = "地址编码错误";
        pass = false;
    }
    else {
        //18位身份证需要验证最后一位校验位
        if (code.length == 18) {
            code = code.split('');
            //∑(ai×Wi)(mod 11)
            //加权因子
            var factor = [7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2];
            //校验位
            var parity = [1, 0, 'X', 9, 8, 7, 6, 5, 4, 3, 2];
            var sum = 0;
            var ai = 0;
            var wi = 0;
            for (var i = 0; i < 17; i++) {
                ai = code[i];
                wi = factor[i];
                sum += ai * wi;
            }
            var last = parity[sum % 11];
            if (parity[sum % 11] != code[17]) {
                tip = "校验位错误";
                pass = false;
            }
        }
    }
    //  if (!pass) alert(tip);
    return pass;
}
$(function () {
    $("#MobilePhone").blur(function () {
        var reg = /^1[3-8][0-9]\d{8}$/;
        if (!reg.test($("#MobilePhone").val())) {
            $("#MobilePhone").focus();
            var a = $("#MobilePhone").val();
            if (!$("#MobilePhone").val() == "") {
                $("#sp1").html("*请输入正确的手机号码");
            }
            else {
                $("#sp1").html("");
            }
            $("#MobilePhone").val("");
            $("#MobilePhone").attr("placeholder", a + "手机号码不正确");
        }
        else {
            $("#sp1").html("");
        }
    });
})