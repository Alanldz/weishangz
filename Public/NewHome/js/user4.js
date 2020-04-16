var posturl = "http://www.ypj889911.com/AjaxCs/jsonserver.aspx";
 //var posturl = "http://localhost:53431/AjaxCs/jsonserver.aspx";
function postCode() {
    //  if (global_obj.check_form($('*[notnull]'))) { return false };
    var MobilePhone = $('#user_form input[name=Phone]').val();
    if (!MobilePhone.match(/^1[3-9]\d{9}$/)) {
        global_obj.win_alert('請正確填寫手機號碼！', function () {
            $('#user_form input[name=Phone]').focus();
        });
        return false;
    }

    var dfurl = $('#dfUrl').val();
    var hfurl = $('#hfUrl').val();
    var hfsid = $('#hfsid').val();
    var RealName = $('#user_form input[name=Name]').val();
    $("#btncode").attr('disabled', true);

    $.post('?action=PostMsg', $('#user_form').serialize(), function (data) {

        var type = parseInt(data);
        if (type == 1) {
            global_obj.win_alert("驗證碼已經發出，請您耐心等待，如果5分鐘內，未收到短信提醒，請重新申請發送。", function () {
                // $('#user_form .lostpwd input').attr('disabled', false);


                var time = 0;
                time_obj = function () {
                    if (time >= 30) {
                        $('#btncode').val('獲取驗證碼').attr('disabled', false);
                        time = 0;
                        clearInterval(timer);
                    } else {
                        $('#btncode').val('重新獲取(' + (60 - time) + ')');
                        time++;
                    }
                }
                var timer = setInterval('time_obj()', 1000);
            });
        } else if (type == 2) {
            global_obj.win_alert("該手機已被其他賬號使用無法綁定。", function () {
                $('#btncode').val('獲取驗證碼').attr('disabled', false);
            });
        } else {
            global_obj.win_alert("驗證碼已經發出，請您耐心等待，如果5分鐘內，未收到短信提醒，請重新申請發送。", function () {
                $('#btncode').val('獲取驗證碼').attr('disabled', false);
            });
        }
    }, 'json');

}
var user_obj = {
    user_login_init: function () {

        if (localStorage.eid != null &&localStorage.eid != "" ) {
            window.location.href = "/index.html";
        }
        $('#logbtn').click(function () {
          
            if (global_obj.check_form($('*[notnull]'))) { return false };

            $(this).attr('disabled', true);

            $.post(posturl+'?action=Login', $('#user_form').serialize(), function (data) {
                $('#logbtn').attr('disabled', false)

              
                var type = parseInt(data.status);
                if (type == 1) {
                    localStorage.eid = data.msg;
                  
                    window.location.href ="/index.html";

                }  else {
                    global_obj.win_alert('错误的用户名或密码，请重新登录！', function () {

                        $('#user_form input[name=Password]').val('');
                    });
                }
            }, 'json');


             
        });
    },

    user_create_init: function () {
        function regSelName() {
            //  alert($('#zjsp').html())
            $.post(posturl +'?action=regSelName', $('#user_form').serialize(), function (data) {
                //$('.red-jh ').html("  您还有<span>" + data.data + "</span>次机会");
                //$('.red-tc').css('display', 'block');
                //alert(data.remark);
                if (data.status == 0) {//
                    //$('#zj2').css('display', 'block');//
                    // $("#RePhone2").val('该用戶不存在');
                }
                else if (data.status == 1) {//奖品
                    $("#RePhone2").val(data.remark);
                } else { //没次數
                    //    $("#RePhone2").val('');
                }


            }, 'json');
        }
        //註冊
        $('#subbtn').click(function () {
            if (global_obj.check_form($('*[notnull]'))) { return false };
 

            var Phone = $('input[name=Phone]').val();
            if (!Phone.match(/^1[3-9]\d{9}$/)) {
                global_obj.win_alert('請正確填寫手機號碼！', function () {
                    $('input[name=Phone]').focus();
                });
                return false;
            }
 





            //if ($('#user_form input[name=Password]').size()) {
            //    if ($('#user_form input[name=Password]').val() != $('#user_form input[name=ConfirmPassword]').val()) {
            //        global_obj.win_alert('兩次輸入的密碼不壹致，請重新輸入登錄密碼！', function () {
            //            $('#user_form input[name=Password]').val('').focus();
            //            $('#user_form input[name=ConfirmPassword]').val('');
            //        });
            //        return false;
            //    }
            //}


            //if ($('#user_form input[name=MbPwd]').size()) {
            //    if ($('#user_form input[name=MbPwd]').val() != $('#user_form input[name=MbPwd2]').val()) {
            //        global_obj.win_alert('兩次輸入的密碼不壹致，請重新輸入登錄密碼！', function () {
            //            $('#user_form input[name=MbPwd]').val('').focus();
            //            $('#user_form input[name=MbPwd2]').val('');
            //        });
            //        return false;
            //    }
            //}

            var dfurl = $('#dfUrl').val();
            var hfurl = $('#hfUrl').val();
            var hfsid = $('#hfsid').val();
          
            $(this).attr('disabled', true);

            $.post(posturl + '?action=Reg&AppRegCode=' + localStorage.AppRegCode, $('#user_form').serialize(), function (data) {
                $('#subbtn').attr('disabled', false);
       
                var type = parseInt(data.status);

                if (type == 1) {
                    global_obj.win_alert("註冊成功。", function () {
                     //   $('#user_form input[name=Name]').val('');
                        window.location.href = "login.html?name=" + Phone;
                        //$('#subbtn').attr('disabled', false);
                    });
                    //   window.location = "member/member.aspx";
                } else {

                    global_obj.win_alert(data.msg + "", function () {
                        $("#subbtn").attr('disabled', false);
                    });
                }
               
            }, 'json');
        });

        //註冊
        $('#subbtn2').click(function () {
            if (global_obj.check_form($('*[notnull]'))) { return false };
            var Name = $('input[name=Name]').val();
         

            var Phone = $('input[name=Phone]').val();
            if (!Phone.match(/^1[3-9]\d{9}$/)) {
                global_obj.win_alert('請正確填寫手機號碼！', function () {
                    $('input[name=Phone]').focus();
                });
                return false;
            }

            if ($('#user_form input[name=Password]').size()) {
                if ($('#user_form input[name=Password]').val() != $('#user_form input[name=ConfirmPassword]').val()) {
                    global_obj.win_alert('兩次輸入的密碼不壹致，請重新輸入登錄密碼！', function () {
                        $('#user_form input[name=Password]').val('').focus();
                        $('#user_form input[name=ConfirmPassword]').val('');
                    });
                    return false;
                }
            }


            if ($('#user_form input[name=MbPwd]').size()) {
                if ($('#user_form input[name=MbPwd]').val() != $('#user_form input[name=MbPwd2]').val()) {
                    global_obj.win_alert('兩次輸入的密碼不壹致，請重新輸入登錄密碼！', function () {
                        $('#user_form input[name=MbPwd]').val('').focus();
                        $('#user_form input[name=MbPwd2]').val('');
                    });
                    return false;
                }
            }

            var dfurl = $('#dfUrl').val();
            var hfurl = $('#hfUrl').val();
            var hfsid = $('#hfsid').val();
            var RealName = $('#user_form input[name=Name]').val();
            $(this).attr('disabled', true);
        
            $.post('?action=Reg&hfsid=' + hfsid, $('#user_form').serialize(), function (data) {
                $('#subbtn2').attr('disabled', false);
                var type = parseInt(data);

                if (type == 1) {
                    global_obj.win_alert("註冊成功。", function () {
                        $('#user_form input[name=Name]').val('');
                        window.location.href = "/member/tsulist.aspx";
                        $('#subbtn2').attr('disabled', false);
                    });
                    //   window.location = "member/member.aspx";
                } else if (type == 2) {
                    global_obj.win_alert("驗證碼錯誤。", function () {
                        $("#subbtn2").attr('disabled', false);
                    });
                } else if (type == 4) {
                    global_obj.win_alert("推薦人賬號不存在。", function () {
                        $("#subbtn2").attr('disabled', false);
                    });
                } else if (type == 41) {
                    global_obj.win_alert("安置人賬號不存在。", function () {
                        $("#subbtn2").attr('disabled', false);
                    });
                }
                else if (type == 42) {
                    global_obj.win_alert("安置人賬號左區有人了", function () {
                        $("#subbtn2").attr('disabled', false);
                    });
                } else if (type == 43) {
                    global_obj.win_alert("安置人賬號右區有人了", function () {
                        $("#subbtn2").attr('disabled', false);
                    });
                }
                else if (type == 5) {
                    global_obj.win_alert("注册失败，您所剩余原动力不足", function () {
                        $("#subbtn2").attr('disabled', false);
                    });
                } else if (type == 6) {
                    global_obj.win_alert("报单中心錯誤", function () {
                        $("#subbtn2").attr('disabled', false);
                    });
                } else if (type == 61) {
                    global_obj.win_alert("該用戶不是报单中心", function () {
                        $("#subbtn2").attr('disabled', false);
                    });
                } else if (type == 7) {
                    global_obj.win_alert("激活码错误。", function () {
                        $("#subbtn2").attr('disabled', false);
                    });
                } else if (type == 8) {
                    global_obj.win_alert("所需货币不足。", function () {
                        $("#subbtn2").attr('disabled', false);
                    });
                } else if (type == 0) {
                    global_obj.win_alert("該用戶名已被註冊。", function () {
                        $("#subbtn2").attr('disabled', false);
                    });
                } else {

                    global_obj.win_alert("服務器繁忙，註冊失敗!請稍後註冊。", function () {
                        $("#subbtn2").attr('disabled', false);
                    });
                }
            }, 'json');
        });

        $('#user_form .sms_button').click(function () {
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
                        $('#user_form .sms_button').val('获取验证码').attr('disabled', false);
                        time = 0;
                        clearInterval(timer);
                    } else {
                        $('#user_form .sms_button').val('重新获取(' + (30 - time) + ')');
                        time++;
                    }
                }
                var timer = setInterval('time_obj()', 1000);
                $.get('?d=get_sms&MobilePhone=' + MobilePhone);
            }
        });
    },

    user_lostpwd_init: function () {
        $('#user_form .lostpwd input').click(function () {
          //  if (global_obj.check_form($('*[notnull]'))) { return false };
            var MobilePhone = $('input[name=MobilePhone]').val();
            if (!MobilePhone.match(/^1[3-9]\d{9}$/)) {
                global_obj.win_alert('请正确填写手机号码！', function () {
                    $('input[name=MobilePhone]').focus();
                });
                return false;
            }
         
            var dfurl = $('#dfUrl').val();
            var hfurl = $('#hfUrl').val();
            var hfsid = $('#hfsid').val();
            var RealName = $('#user_form input[name=Name]').val();
            $(this).attr('disabled', true);

            $.post('?action=PostMsg' , $('#user_form').serialize(), function (data) {

                var type = parseInt(data);
                if (type == 1) {
                    global_obj.win_alert("验证码已经发出，请您耐心等待，如果5分钟内，未收到短信提醒，请重新申请发送。", function () {
                       // $('#user_form .lostpwd input').attr('disabled', false);

                        $(this).attr('disabled', true);
                        var time = 0;
                        time_obj = function () {
                            if (time >= 30) {
                                $('#user_form .lostpwd input').val('获取验证码').attr('disabled', false);
                                time = 0;
                                clearInterval(timer);
                            } else {
                                $('#user_form .lostpwd input').val('重新获取(' + (60 - time) + ')');
                                time++;
                            }
                        }
                        var timer = setInterval('time_obj()', 1000);


                    });
                } else if  (type == 2) {
                    global_obj.win_alert("该用户不存在。", function () {
                        $('#user_form .lostpwd input').attr('disabled', false);
                    });
                } else {
                    global_obj.win_alert("验证码已经发出，请您耐心等待，如果5分钟内，未收到短信提醒，请重新申请发送。", function () {
                        $('#user_form .lostpwd input').attr('disabled', false);
                    });
                }
            }, 'json');
        });
        //修改密码
        $('#user_form .submit input').click(function () {
              if (global_obj.check_form($('*[notnull]'))) { return false };
            var MobilePhone = $('input[name=MobilePhone]').val();
            if (!MobilePhone.match(/^1[3-9]\d{9}$/)) {
                global_obj.win_alert('请正确填写手机号码！', function () {
                    $('input[name=MobilePhone]').focus();
                });
                return false;
            }
            if ($('#user_form input[name=Password]').size()) {	//微信认证号没有密码这一项
                if ($('#user_form input[name=Password]').val() != $('#user_form input[name=ConfirmPassword]').val()) {
                    global_obj.win_alert('两次输入的密码不一致，请重新输入登录密码！', function () {
                        $('#user_form input[name=Password]').val('').focus();
                        $('#user_form input[name=ConfirmPassword]').val('');
                    });
                    return false;
                }
            }
         
            $(this).attr('disabled', true);

            $.post('?action=UpdatePwd', $('#user_form').serialize(), function (data) {

                var type = parseInt(data);
                if (type == 1) {
                    global_obj.win_alert("密码重置成功。", function () {
                        $('#user_form .submit input').attr('disabled', false);
                    });
                } else {
                    global_obj.win_alert("验证码不正确。", function () {
                        $('#user_form .submit input').attr('disabled', false);
                    });
                }
                return false;
            }, 'json');
        });
       
    } 
 



    






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
                $("#sp1").html("*请输入正确的手机号码" );
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