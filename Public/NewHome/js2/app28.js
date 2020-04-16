// 初始化 app
var myApp = new Framework7();


var posturl = "http://www.ypj889911.com/AjaxCs/jsonserver.aspx";
var postImgurl = "http://www.ypj889911.com/AjaxCs/upload_json.ashx";
//jsonserver2.ashx
 //var posturl = "http://localhost:53431/AjaxCs/jsonserver.aspx";
// var postImgurl = "http://localhost:53431/AjaxCs/upload_json.ashx";
// 为便于使用，自定义DOM库名字为$$
var $$ = Dom7;

// 添加视图
var mainView = myApp.addView('.view-main', {
    // 让这个视图支持动态导航栏
    dynamicNavbar: true
});

// 下面代码是给“关于”页面使用的（关于页面加载完毕后触发）

// 方式1：通过页面回调 (推荐):
myApp.onPageInit('about', function (page) {
    alert('"关于"页面加载完毕1!');
})

// 方式1：通过页面回调 (推荐):

//DrawPpItem
myApp.onPageInit('index', function (page) {

    $.get(posturl + '?action=GetUser&eid=' + encodeURI(localStorage.eid), function (data) {
        var su = data.items[0];

        var headimg = "img/img.jpg";
        if (su.HeadImg != "") {
            headimg = su.HeadImg;
        }
        $('.divheadimg').attr('src', headimg);
        $('.ft_eid').html("EID:" + su.ID);
        $('.ft_xinyong').html("信用：" + GetXing(su.Xing));


        $('#Money').html(su.Money);
        $('#MoneyJy').html(su.MoneyJy);
        $('#MoneyFt').html(su.MoneyFt);
        $('#MoneyDs').html(su.MoneyDs);


        $.get(posturl + '?action=MsgCount&eid=' + encodeURI(localStorage.eid), function (data) {
            var msgcount1 = data.msg;
            var askcount1 = data.msg2;

            if (msgcount1 > 0) {
                $('.msg_caiwu').html("资金<span class='MsgCont'>" + msgcount1 + "</span>");
            }
            if (askcount1 > 0) {
                $('.msg_member').html("我的<span class='MsgCont'>" + askcount1 + "</span>");
            }



            //$('#ft_eid').html(GetZhType(1));
        }, 'json');

        $.get(posturl + '?action=GetUserDayR&eid=' + encodeURI(localStorage.eid), function (data) {
            var dayR = data.msg;


            $('.dayR').html(dayR);


            //$('#ft_eid').html(GetZhType(1));
        }, 'json');
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

});

//DrawPpItem
myApp.onPageInit('Caiwu', function (page) {




    $.get(posturl + '?action=MsgCount&eid=' + encodeURI(localStorage.eid), function (data) {

        var oCount2 = data.msg3;
        var oCount3 = data.msg4;

        if (oCount2 > 0) {
            $('.oCount2').html("<font class='MsgCont'>" + oCount2 + "</font>");
        }
        if (oCount3 > 0) {
            $('.oCount3').html("<font class='MsgCont'>" + oCount3 + "</font>");
        }


        //$('#ft_eid').html(GetZhType(1));
    }, 'json');



});

//DrawPpItem
myApp.onPageInit('FaXian', function (page) {


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


});
myApp.onPageInit('member', function (page) {

    $.get(posturl + '?action=GetUser&eid=' + encodeURI(localStorage.eid), function (data) {
        var su = data.items[0];


        $('.ft_eid').html("EID:" + su.ID);
        $('.ft_xinyong').html("信用：" + GetXing(su.Xing));
        var headimg = "img/img.jpg";
        if (su.HeadImg != "") {
            headimg = su.HeadImg;
        }
        $('.divheadimg').attr('src', headimg);


        //$('#ft_eid').html(GetZhType(1));
    }, 'json');



    $('.logout').click(function () {
        localStorage.eid = "";
        window.location.href = 'login.html';
    });

});

myApp.onPageInit('SyListMoney2', function (page) {

    $.get(posturl + '?action=SyListMoney2&eid=' + encodeURI(localStorage.eid), function (data) {
        var pageList = data.items;

        var pageHtml = "";
        $.each(data.items, function (idx, item) {
            var date = new Date(item.CreateTime);
            pageHtml += " <div class='ls-item yxui-flex'>" +
                " <div class='cell' >" + getMonth(date) + "-" + getDay(date) + "" + "</div >" +
                "<div class='cell' >" + item.Money + "</div> " +
                "  <div class='cell'> " + item.MoneyAf + "</div> " +
                "  <div class='cell' > " + GetZhType(item.Type) + "</div> " +
                " </div >";
        });

        $(".show_main").html(pageHtml);

    }, 'json');

});
//zhlog
myApp.onPageInit('SyListMoneyJy', function (page) {

    $.get(posturl + '?action=SyListMoneyJy&eid=' + encodeURI(localStorage.eid), function (data) {
        var pageList = data.items;

        var pageHtml = "";
        $.each(data.items, function (idx, item) {
            var date = new Date(item.CreateTime);
            pageHtml += " <div class='ls-item yxui-flex'>" +
                " <div class='cell' >" + getMonth(date) + "-" + getDay(date) + "" + "</div >" +
                "<div class='cell' >" + item.Money + "</div> " +
                "  <div class='cell'> " + item.MoneyAf + "</div> " +
                "  <div class='cell' > " + GetZhType(item.Type) + "</div> " +
                " </div >";
        });


        $(".show_main").html(pageHtml);
    }, 'json');

});
//zhlog
myApp.onPageInit('SyListMoneyDs', function (page) {

    $.get(posturl + '?action=SyListMoneyDs&eid=' + encodeURI(localStorage.eid), function (data) {
        var pageList = data.items;

        var pageHtml = "";
        $.each(data.items, function (idx, item) {
            var date = new Date(item.CreateTime);
            pageHtml += " <div class='ls-item yxui-flex'>" +
                " <div class='cell' >" + getMonth(date) + "-" + getDay(date) + "" + "</div >" +
                "<div class='cell' >" + item.Money + "</div> " +
                "  <div class='cell'> " + item.MoneyAf + "</div> " +
                "  <div class='cell' > " + GetZhType(item.Type) + "</div> " +
                " </div >";
        });

        $(".show_main").html(pageHtml);

    }, 'json');

});
//zhlog
myApp.onPageInit('SyListMoneyFt2', function (page) {

    $.get(posturl + '?action=SyListMoneyFt2&eid=' + encodeURI(localStorage.eid), function (data) {
        var pageList = data.items;

        var pageHtml = "";
        $.each(data.items, function (idx, item) {
            var date = new Date(item.CreateTime);
            pageHtml += " <div class='ls-item yxui-flex'>" +
                " <div class='cell' >" + getMonth(date) + "-" + getDay(date) + "" + "</div >" +
                "<div class='cell' >" + item.Money + "</div> " +
                "  <div class='cell'> " + item.MoneyAf + "</div> " +
                "  <div class='cell' > " + GetZhType(item.Type) + "</div> " +
                " </div >";
        });

        $(".show_main").html(pageHtml);

    }, 'json');

});


//rlog
myApp.onPageInit('SyListMoney', function (page) {

    $.get(posturl + '?action=SyListMoney&eid=' + encodeURI(localStorage.eid), function (data) {
        var pageList = data.items;

        var pageHtml = "";
        $.each(data.items, function (idx, item) {

            var date = new Date(item.CreateTime);
            pageHtml += " <div class='ls-item yxui-flex'>" +
                " <div class='cell' >" + getMonth(date) + "-" + getDay(date) + "" + "</div >" +
                "<div class='cell' >" + item.Money + "</div> " +
                "  <div class='cell1'> " + item.MoneyDs + "</div> " +
                "  <div class='cell1'> " + item.MoneySc + "</div> " +
                "  <div class='cell' > " + getrLogType(item.Type) + "</div> " +
                " </div >";
        });
        $(".show_main").html(pageHtml);


    }, 'json');

});


//WebList
myApp.onPageInit('WebList', function (page) {

    $.get(posturl + '?action=WebList&eid=' + encodeURI(localStorage.eid), function (data) {
        var pageList = data.items;

        var pageHtml = "";
        $.each(data.items, function (idx, item) {

            var date = new Date(item.CreateTime);
            pageHtml += " <div class='gg-item yxui-flex'>" +
                //    "  <a href='/webnews.aspx?id="+item.ID+"'>" + item.Title +"  </a> "
                "<div class='cell' >  <a style=' font-size: 0.25rem;' href='member/webnews.html?id=" + item.ID + "'>" + item.Title + "  </a></div> " +
                " <div class='cell' >" + getMonth(date) + "-" + getDay(date) + " " + getHours(date) + ":" + getMinutes(date) + "" + "</div >" +
                " </div >";
        });
        $(".show_main").html(pageHtml);


    }, 'json');

});
//webnews
myApp.onPageInit('webnews', function (page) {

    var mid = (page.query.id);

    $.get(posturl + '?action=webnews&eid=' + encodeURI(localStorage.eid) + "&id=" + mid, function (data) {

        $('.webnewsItem').html("" + data + "");
    });


    ////加载
    //$.get(posturl + '?action=webnews&eid=' + encodeURI(localStorage.eid) + "&id=" + mid, function (data) {
    //    var pageList = data.items;


    //    var pageHtml = "";
    //    $.each(data.items, function (idx, item) {


    //    //   $(".img_qr").src = item.State;


    //        $('.webnewsItem').html("" + item.Content + "");


    //    });


    //}, 'json');
});
myApp.onPageInit('SyListMoneyFt', function (page) {

    $.get(posturl + '?action=SyListMoneyFt&eid=' + encodeURI(localStorage.eid), function (data) {
        var pageList = data.items;

        var pageHtml = "";
        $.each(data.items, function (idx, item) {
            var date = new Date(item.CreateTime);
            pageHtml += " <div class='ls-item yxui-flex'>" +
                " <div class='cell' >" + getMonth(date) + "-" + getDay(date) + "" + "</div >" +
                "<div class='cell' >" + item.MoneyFt + "</div> " +
                "  <div class='cell1'> " + item.GoodsTotal + "</div> " +
                "  <div class='cell' > " + getrLogType(item.Type) + "</div> " +
                " </div >";
        });

        $(".show_main").html(pageHtml);

    }, 'json');

});
//DrawCzbtn
myApp.onPageInit('DrawCz', function (page) {


    //加载
    $.get(posturl + '?action=DrawCz&eid=' + encodeURI(localStorage.eid), function (data) {
        var pageList = data.items;

        var pageHtml = "";
        $.each(data.items, function (idx, item) {
            var date = new Date(item.CreateTime);
            pageHtml += " <div class='cwlic-item yxui-flex' style='padding-left: 0;'>" +
                " <div class='cell' >" + getMonth(date) + "-" + getDay(date) + "" + "</div >" +
                "<div class='cell' >" + item.Price + "</div> " +
                "  <div class='cell2' ><span> " + GetMoneyLogState(item.State, item.ID) + "</span></div> " +
                " </div >";
        });

        $(".showlist").html(pageHtml);


    }, 'json');
    //事件
    $('#DrawCzbtn').click(function () {

        if (global_obj.check_form($('*[notnull]'))) { return false };


        $('#DrawCzbtn').attr('disabled', true)
        $.post(posturl + '?action=DrawCzAdd&eid=' + encodeURI(localStorage.eid), $('#DrawCz_form').serialize(), function (data) {

            if (data.status == 1) {

                // alert(openid);

                global_obj.win_alert(data.msg, function () {
                    //window.location.href = "member/DrawCz.html";

                    mainView.router.reloadPage("member/DrawCz.html")
                });


                $('#DrawCzbtn').attr('disabled', false);
            } else {

                global_obj.win_alert(data.msg, function () {
                    $('#DrawCzbtn').attr('disabled', false);
                });
            }
        }, 'json');





    });

});


//DrawCz2
myApp.onPageInit('DrawCz2', function (page) {
    //图片上传控件
    'use strict';
    // Change this to the location of your server-side upload handler:
    var url = postImgurl + '?action=upImg';
    $('#fileupload').fileupload({
        url: url,
        dataType: 'json',
        done: function (e, data) {
            //  alert(data.result.url);
            $('[name="hfShopAd1"]').val(data.result.url);
            //$.each(data.result.files, function (index, file) {
            //    $('<p/>').text(file.name).appendTo('#files');
            //});
        },
        progressall: function (e, data) {
            //alert(data.result.url);
            var progress = parseInt(data.loaded / data.total * 100, 10);
            $('#progress .progress-bar').css(
                'width',
                progress + '%'
            );
        }
    }).prop('disabled', !$.support.fileInput)
        .parent().addClass($.support.fileInput ? undefined : 'disabled');
    //加载
    $.get(posturl + '?action=DrawCz&eid=' + encodeURI(localStorage.eid), function (data) {
        var pageList = data.items;

        var pageHtml = "";
        $.each(data.items, function (idx, item) {
            var date = new Date(item.CreateTime);
            pageHtml += " <div class='cwlic-item yxui-flex' style='padding-left: 0;'>" +
                " <div class='cell' >" + getMonth(date) + "-" + getDay(date) + "" + "</div >" +
                "<div class='cell' >" + item.Price + "</div> " +
                "  <div class='cell2' ><span> " + GetMoneyLogState(item.State, item.ID) + "</span></div> " +
                " </div >";
        });

        $(".showlist").html(pageHtml);

    }, 'json');

    var mid = (page.query.id);
    //加载实体
    $.get(posturl + '?action=DrawCz2Model&eid=' + encodeURI(localStorage.eid) + '&id=' + mid, function (data) {
        var pageList = data.items;
        var istrue = false;
        var pageHtml = "";
        $.each(data.items, function (idx, item) {
            istrue = true;

            //   var date = new Date(item.CreateTime);
            $('[name="num"]').val(item.Price); //$('#num').val(item.Price);
            //  alert(item.Price);
            $('[name="id"]').val(item.ID);
        });
        if (istrue == false) {
            mainView.router.reloadPage("member/DrawCz.html")
        }


    }, 'json');
    //事件
    $('.DrawCzbtn2').click(function () {

        //  if (global_obj.check_form($('*[notnull]'))) { return false };


        $('.DrawCzbtn2').attr('disabled', true);

        $.post(posturl + '?action=DrawCzAdd2&eid=' + encodeURI(localStorage.eid) + '&id=' + mid, $('#DrawCz_form2').serialize(), function (data) {

            if (data.status == 1) {

                // alert(openid);

                global_obj.win_alert(data.msg, function () {
                    //window.location.href = "member/DrawCz.html";

                    mainView.router.reloadPage("member/DrawCz.html");


                });


                $('.DrawCzbtn2').attr('disabled', false);
            } else {

                global_obj.win_alert(data.msg, function () {
                    $('.DrawCzbtn2').attr('disabled', false);

                });
            }
        }, 'json');





    });

});

//Zc
myApp.onPageInit('Zc', function (page) {




    //事件
    $('#zcbtn').click(function () {
        if (global_obj.check_form($('*[notnull]'))) { return false };


        $('#zcbtn').attr('disabled', true)
        $.post(posturl + '?action=zcbtn&eid=' + encodeURI(localStorage.eid), $('#Zc_form').serialize(), function (data) {

            if (data.status == 1) {

                // alert(openid);

                //window.location.href = "member/DrawCz.html";

                mainView.router.reloadPage("member/ZcItem.html?id=" + data.msg)



                $('#zcbtn').attr('disabled', false);
            } else {

                global_obj.win_alert(data.msg, function () {
                    $('#zcbtn').attr('disabled', false);
                });
            }
        }, 'json');





    });

    appcan.button("#nav-left", "btn-act",
        function () { });
    appcan.button("#nav-right", "btn-act",
        function () { });

    appcan.ready(function () {

    })

    $(".icon-saoma").click(function () {

        uexScanner.open(callback);
    })


    var callback = function (error, data) {
        if (!error) {

            window.location.href = data.code;
            // alert("data:" + data.code);
        } else {
            //  alert("failed!");
        }
    };

    function OpenScanner2() {
        uexScanner.open(callback);
    }
});


//ZcItem
myApp.onPageInit('ZcItem', function (page) {


    //加载
    var mid = (page.query.id);
    //加载实体
    $.get(posturl + '?action=ZcItemModel&eid=' + encodeURI(localStorage.eid) + '&id=' + mid, function (data) {
        var pageList = data.items;
        var istrue = false;
        var pageHtml = "";
        $.each(data.items, function (idx, item) {
            istrue = true;
            //$('#num').val(item.Price);
            //   var date = new Date(item.CreateTime);
            $('[name="Name2"]').val(item.Name);
            //   alert(item.Name);
            $('[name="RealName"]').val(item.RealName);
            $('[name="Phone"]').val(item.Phone);
            $('[name="myMoney"]').val(item.Money);
            //  alert(item.Price);
            $('[name="id"]').val(item.ID);
            var isxi = item.IsXi;
            if (isxi == 1) {

                var selHtml = "   <option value='6'>&nbsp;&nbsp;&nbsp;&nbsp;消&nbsp;&nbsp;费&nbsp;&nbsp;&nbsp;&nbsp;</option>" +
                    "   <option value = '16' >&nbsp;&nbsp;&nbsp;&nbsp; 转 &nbsp;&nbsp; 账 &nbsp;&nbsp;&nbsp;&nbsp;</option >";
                $('[name="type"]').html(selHtml);

            }
        });
        if (istrue == false) {
            mainView.router.reloadPage("member/DrawCz.html")
        }

    }, 'json');
    //事件
    $('.ZcItembtn').click(function () {

        if (global_obj.check_form($('*[notnull]'))) { return false };


        $('.ZcItembtn').attr('disabled', true)
        $.post(posturl + '?action=ZcItembtn&eid=' + encodeURI(localStorage.eid) + "&id=" + mid, $('#Zc_form').serialize(), function (data) {

            if (data.status == 1) {

                // alert(openid);

                global_obj.win_alert(data.msg, function () {
                    //window.location.href = "member/DrawCz.html";

                    mainView.router.reloadPage("member/zcList.html");
                });

                $('.ZcItembtn').attr('disabled', false);
            } else {

                global_obj.win_alert(data.msg, function () {
                    $('.ZcItembtn').attr('disabled', false);
                });
            }
        }, 'json');





    });

});

//ZcItem
myApp.onPageInit('Zdh', function (page) {


    //加载
    var mid = (page.query.id);
    $.get(posturl + '?action=GetUser&eid=' + encodeURI(localStorage.eid), function (data) {
        var su = data.items[0];


        $('[name="Money"]').val(su.Money);
        $('[name="MoneyJy"]').val(su.MoneyJy);



        //$('#ft_eid').html(GetZhType(1));
    }, 'json');


    //事件
    $('.Zdhbtn').click(function () {



        $('.Zdhbtn').attr('disabled', true)
        $.post(posturl + '?action=Zdhbtn&eid=' + encodeURI(localStorage.eid), $('#Zdh_form').serialize(), function (data) {

            if (data.status == 1) {

                // alert(openid);

                global_obj.win_alert(data.msg, function () {
                    //window.location.href = "member/DrawCz.html";

                    mainView.router.reloadPage("member/Zdh.html");
                });

                $('.Zdhbtn').attr('disabled', false);
            } else {

                global_obj.win_alert(data.msg, function () {
                    $('.Zdhbtn').attr('disabled', false);
                });
            }
        }, 'json');





    });

});



//ZcItem
myApp.onPageInit('Draw', function (page) {



    //加载
    var mid = (page.query.id);

    //加载列表
    $.get(posturl + '?action=Draw&eid=' + encodeURI(localStorage.eid), function (data) {
        var isxi = 0;
        $.get(posturl + '?action=GetUser&eid=' + encodeURI(localStorage.eid), function (data2) {
            var su = data2.items[0];
            isxi = su.IsXi;

            var pageList = data.items;

            var pageHtml = "";

            $.each(data.items, function (idx, item) {

                var date = new Date(item.CreateTime);

                if (isxi == 1) {


                    pageHtml += " <div class='cwlic-item yxui-flex' style='padding-left: 0;'>" +
                        " <div class='cell' >C" + item.ID + "" + "</div >" +
                        "<div class='cell' >" + item.GoodsTotal + "/<font>" + item.PayPrice + "</font></div> " +

                        "  <div class='cell' >  <a style='color:blue; font-size:0.28rem' href='member/Draw.html?id=" + item.ID + "'>购入</a></div> " +
                        " <div class='cell' >" + item.Remark + "" + "</div >" +
                        " </div >";
                } else {
                    $('.fuwu').hide();
                    pageHtml += " <div class='cwlic-item yxui-flex' style='padding-left: 0;'>" +
                        " <div class='cell' >C" + item.ID + "" + "</div >" +
                        "<div class='cell' >" + item.GoodsTotal + "/<font>" + item.PayPrice + "</font></div> " +

                        "  <div class='cell' >  <a style='color:blue; font-size:0.28rem' href='member/Draw.html?id=" + item.ID + "'>购入</a></div> " +
                        " </div >";


                }

            });


            $(".show_main").html(pageHtml);
            //$('#ft_eid').html(GetZhType(1));
        }, 'json');


    }, 'json');
    if (mid > 0) {
        //加载实体
        $.get(posturl + '?action=DrawModel&eid=' + encodeURI(localStorage.eid) + '&id=' + mid, function (data) {
            var pageList = data.items;
            var istrue = false;
            var pageHtml = "";



            $.each(data.items, function (idx, item) {
                istrue = true;
                //$('#num').val(item.Price);
                //   var date = new Date(item.CreateTime);
                //   alert(item.Name);

                $('[name="num"]').val(item.GoodsTotal);
                $('[name="Money"]').val(item.PayPrice);
                //  alert(item.Price);
                $('[name="id"]').val(item.ID);
            });

            ;
        }, 'json');
    }
    //事件
    $('[name="Drawbtn"]').click(function () {

        if (global_obj.check_form($('*[notnull]'))) { return false };


        $('#Drawbtn').attr('disabled', true)
        $.post(posturl + '?action=Drawbtn&eid=' + encodeURI(localStorage.eid) + "&id=" + mid, $('#Draw_Form').serialize(), function (data) {

            if (data.status == 1) {

                // alert(openid);

                global_obj.win_alert(data.msg, function () {
                    //window.location.href = "member/DrawCz.html";

                    mainView.router.reloadPage("member/DrawListpp.html");
                });


                $('#Drawbtn').attr('disabled', false);
            } else {

                global_obj.win_alert(data.msg, function () {
                    $('#Drawbtn').attr('disabled', false);
                });
            }
        }, 'json');





    });

});

//DrawMoney
myApp.onPageInit('DrawMoney', function (page) {


    $.get(posturl + '?action=GetUser&eid=' + encodeURI(localStorage.eid), function (data) {
        var su = data.items[0];


        $('[name="Money"] ').val(su.Money);


        //$('#ft_eid').html(GetZhType(1));
    }, 'json');

    //加载银行列表
    $.get(posturl + '?action=BankCardList&eid=' + encodeURI(localStorage.eid), function (data) {
        var pageList = data.items;

        var pageHtml = "";
        var bankCount = 0;
        $.each(data.items, function (idx, item) {
            bankCount++;

            var date = new Date(item.CreateTime);
            pageHtml += "<option value='" + item.ID + "'> " + item.Bank + "尾号:" + item.Card + "</option>";
        });

        $('[name="Bank"]').html(pageHtml);
        if (bankCount == 0) {
            global_obj.win_alert("请您先绑定银行卡", function () {
                //window.location.href = "member/DrawCz.html";
                mainView.router.reloadPage("member/BankCardList.html");
            });
        }
        //  $("#show_main").html(pageHtml);

    }, 'json');
    //加载
    var mid = (page.query.id);

    //加载列表
    $.get(posturl + '?action=DrawMoney&eid=' + encodeURI(localStorage.eid), function (data) {
        var pageList = data.items;

        var pageHtml = "";
        $.each(data.items, function (idx, item) {
            var strState = GetOrderJyJsState(item.State, item.ID, item.PayPrice);// "待匹配";
            //if (item.State != 0) {
            //    strState = "  已完成"
            //}
            var date = new Date(item.CreateTime);
            pageHtml += " <div class='cwlic-item yxui-flex' style='padding-left: 0;'>" +
                " <div class='cell' >C" + item.ID + "" + "</div >" +
                "<div class='cell' >" + item.GoodsTotal + "/<font>" + item.PayPrice + "</font></div> " +
                "  <div class='cell' >" + strState + "  </div> " +
                " </div >";
        });


        $(".show_main").html(pageHtml);

    }, 'json');
    //事件
    $(".show_main").on("click", ".DrawMoneyCs", function () {
        //   $('.s_img').click(function () {
        //   bigimg

        var id = $(this).attr('data-id');

        $(this).attr('disabled', true);
        $.post(posturl + '?action=DrawMoneyCs&eid=' + encodeURI(localStorage.eid) + "&id=" + id, $('#DrawMoney_Form').serialize(), function (data) {

            if (data.status == 1) {
                // alert(openid);
                global_obj.win_alert(data.msg, function () {
                    //window.location.href = "member/DrawCz.html";
                    mainView.router.reloadPage("member/DrawMoney.html");
                });

                $(this).attr('disabled', false)
            } else {

                global_obj.win_alert(data.msg, function () {
                    $(this).attr('disabled', false)
                });
            }
        }, 'json');

    });
    //事件
    $('[name="DrawMoneybtn"]').click(function () {

        if (global_obj.check_form($('*[notnull]'))) { return false };


        var num = parseInt($('[name="num"]').val());

        if (num < 100 || num % 100 != 0) {
            global_obj.win_alert('卖出失败!<br>卖出金额必须满百整百');
            return;
        }
        $('[name="DrawMoneybtn"]').attr('disabled', true);
        $.post(posturl + '?action=DrawMoneybtn&eid=' + encodeURI(localStorage.eid) + "&id=" + mid, $('#DrawMoney_Form').serialize(), function (data) {

            if (data.status == 1) {
                // alert(openid);
                global_obj.win_alert(data.msg, function () {
                    //window.location.href = "member/DrawCz.html";
                    mainView.router.reloadPage("member/DrawMoney.html");
                });

                $('[name="DrawMoneybtn"]').attr('disabled', false)
            } else {

                global_obj.win_alert(data.msg, function () {
                    $('[name="DrawMoneybtn"]').attr('disabled', false)
                });
            }
        }, 'json');





    });



});

//DrawMoney
myApp.onPageInit('DrawMoneyList', function (page) {

    //加载列表
    $.get(posturl + '?action=DrawMoney&eid=' + encodeURI(localStorage.eid), function (data) {
        var pageList = data.items;

        var pageHtml = "";
        $.each(data.items, function (idx, item) {
            var strState = "待匹配";
            if (item.State != 0) {
                strState = "  已完成"
            }
            var date = new Date(item.CreateTime);
            pageHtml += " <div class='cwlic-item yxui-flex' style='padding-left: 0;'>" +
                " <div class='cell' >C" + item.ID + "" + "</div >" +
                "<div class='cell' >" + item.GoodsTotal + "/<font>" + item.PayPrice + "</font></div> " +
                "  <div class='cell' >" + strState + "  </div> " +
                " </div >";
        });


        $(".show_main").html(pageHtml);

    }, 'json');

    //事件
    $('[name="DrawMoneybtn"]').click(function () {

        if (global_obj.check_form($('*[notnull]'))) { return false };


        var num = parseInt($('[name="num"]').val());

        if (num < 100 || num % 100 != 0) {
            global_obj.win_alert('卖出失败!<br>卖出金额必须满百整百');
            return;
        }
        $('[name="DrawMoneybtn"]').attr('disabled', true);
        $.post(posturl + '?action=DrawMoneybtn&eid=' + encodeURI(localStorage.eid) + "&id=" + mid, $('#DrawMoney_Form').serialize(), function (data) {

            if (data.status == 1) {
                // alert(openid);
                global_obj.win_alert(data.msg, function () {
                    //window.location.href = "member/DrawCz.html";
                    mainView.router.reloadPage("member/DrawMoney.html");
                });

                $('[name="DrawMoneybtn"]').attr('disabled', false)
            } else {

                global_obj.win_alert(data.msg, function () {
                    $('[name="DrawMoneybtn"]').attr('disabled', false)
                });
            }
        }, 'json');





    });

});

//DrawListpp
myApp.onPageInit('DrawListpp', function (page) {


    //加载
    var mid = (page.query.id);

    //加载列表
    $.get(posturl + '?action=DrawListpp&eid=' + encodeURI(localStorage.eid), function (data) {
        var pageList = data.items;

        var pageHtml = "";
        $.each(data.items, function (idx, item) {

            var date = new Date(item.CreateTime);
            pageHtml += " <div class='ls-item yxui-flex'  >" +
                " <div class='cell' >C" + item.ID + "" + "</div >" +
                "<div class='cell' >" + getMonth(date) + "-" + getDay(date) + " </div> " +
                "<div class='cell' >" + item.GoodsTotal + " </div> " +
                "<div class='cell' >" + item.Name + " </div> " +
                //  "<div class='cell' >" + item.GoodsTotal + " </div> " +
                "  <div class='cell' > " + GetOrderJyppState(item.State, item.ID) + " </div> " +
                " </div >";
        });


        $(".show_main").html(pageHtml);
        //  $("#show_main").html(pageHtml);
        //$.get(posturl + '?action=GetPageUrl&url=member/DrawListpp.html&eid=' + encodeURI(localStorage.eid), function (data) {
        //    $(".turn_page").html(data.msg);
        //    alert(data.msg);
        //}, 'json');
    }, 'json');



});
//DrawListpp2
myApp.onPageInit('DrawListpp2', function (page) {


    //加载
    var mid = (page.query.id);

    //加载列表
    $.get(posturl + '?action=DrawListpp2&eid=' + encodeURI(localStorage.eid), function (data) {
        var pageList = data.items;

        var pageHtml = "";
        $.each(data.items, function (idx, item) {

            var date = new Date(item.CreateTime);
            pageHtml += " <div class='ls-item yxui-flex'  >" +
                " <div class='cell' >C" + item.ID + "" + "</div >" +
                "<div class='cell' >" + item.GoodsTotal + " </div> " +
                "<div class='cell' >" + item.RealName + " </div> " +
                //  "<div class='cell' >" + item.GoodsTotal + " </div> " +
                "  <div class='cell' > " + GetOrderJyppState2(item.State, item.ID) + " </div> " +
                " </div >";
        });


        $(".show_main").html(pageHtml);
        //  $("#show_main").html(pageHtml);

    }, 'json');



});

//DrawListpp
myApp.onPageInit('qr', function (page) {


    //加载
    var mid = (page.query.id);

    //加载列表
    $.get(posturl + '?action=qr&eid=' + encodeURI(localStorage.eid), function (data) {


        var pageHtml = data.msg;
        if (data.status == 1) {
            // $(".img_qr").src = data.msg;
            $('.img_qr').attr('src', data.msg);
        }



        //  $("#show_main").html(pageHtml);

    }, 'json');

    appcan.button("#nav-left", "btn-act",
        function () { });
    appcan.button("#nav-right", "btn-act",
        function () { });

    appcan.ready(function () {

    })

    $("#open").click(function () {
        uexScanner.open(callback);
    })


    var callback = function (error, data) {
        if (!error) {

            window.location.href = data.code;
            // alert("data:" + data.code);
        } else {
            //  alert("failed!");
        }
    };

    function OpenScanner2() {
        uexScanner.open(callback);
    }

});



//DrawPpItem
myApp.onPageInit('DrawPpItem', function (page) {
    var mid = (page.query.id);

    //加载
    $.get(posturl + '?action=DrawPpItem&eid=' + encodeURI(localStorage.eid) + "&id=" + mid, function (data) {
        var pageList = data.items;


        var pageHtml = "";
        $.each(data.items, function (idx, item) {
            $('[name="GoodsTotal"]').val(item.GoodsTotal);
            $('[name="PayPrice"]').val(item.PayPrice);
            var state = item.State;
            $('[name="State"]').val(GetOrderJyppStateShow(item.State));
            $('[name="Remark"]').val(item.Remark);
            $(".img_qr").src = item.State;
            if (state > 0) {
                $('.DrawPpItembtn').hide();
                $('.DrawPpItembtnImg').hide();
                $('.DrawListpp_img').html("<img src='" + item.Pic + "' width='100%'>");
            }

        });


    }, 'json');



    //事件
    $('.DrawPpItembtn').click(function () {
        if (global_obj.check_form($('*[notnull]'))) { return false };
        $('.DrawPpItembtn').attr('disabled', true);
        $.post(posturl + '?action=DrawPpItembtn&eid=' + encodeURI(localStorage.eid) + '&id=' + mid, $('#DrawPpItem_form').serialize(), function (data) {

            if (data.status == 1) {

                // alert(openid);

                global_obj.win_alert(data.msg, function () {
                    //window.location.href = "member/DrawCz.html";

                    mainView.router.reloadPage("member/DrawListpp.html");


                });


                $('.DrawPpItembtn').attr('disabled', false);
            } else {

                global_obj.win_alert(data.msg, function () {
                    $('.DrawPpItembtn').attr('disabled', false);

                });
            }
        }, 'json');

    });

    //图片上传控件
    'use strict';
    // Change this to the location of your server-side upload handler:
    var url = postImgurl + '?action=upImg';
    $('#fileupload').fileupload({
        url: url,
        dataType: 'json',
        done: function (e, data) {
            //  alert(data.result.url);
            $('[name="hfShopAd1"]').val(data.result.url);
            //$.each(data.result.files, function (index, file) {
            //    $('<p/>').text(file.name).appendTo('#files');
            //});
            var img = new Image();
            var mb = (e.total / 1024) / 1024;
            img.src = data.result.url;
            img.style.width = "0.8rem";
            img.style.height = "0.8rem";
            $('.choice').hide();
            $('.imgdiv').html(img)
        },
        progressall: function (e, data) {
            //alert(data.result.url);
            var progress = parseInt(data.loaded / data.total * 100, 10);
            $('#progress .progress-bar').css(
                'width',
                progress + '%'
            );
        }
    }).prop('disabled', !$.support.fileInput)
        .parent().addClass($.support.fileInput ? undefined : 'disabled');


});




//DrawPpItem
myApp.onPageInit('DrawPpItem2', function (page) {
    var mid = (page.query.id);

    //加载
    $.get(posturl + '?action=DrawPpItem&eid=' + encodeURI(localStorage.eid) + "&id=" + mid, function (data) {
        var pageList = data.items;


        var pageHtml = "";
        $.each(data.items, function (idx, item) {
            $('[name="GoodsTotal"]').val(item.GoodsTotal);
            $('[name="PayPrice"]').val(item.PayPrice);
            var state = item.State;
            $('[name="State"]').val(GetOrderJyppStateShow(item.State));
            $('[name="Remark"]').val(item.Remark);


            $('[name="Phone"]').val(item.Phone);
            $('[name="RealName"]').val(item.RealName);

            $('[name="CreateTime"]').val(item.CreateTime);


            if (state > 0) {
                $('[name="PayTime"]').val(item.PayTime);
            }
            if (state == 2) {
                $('[name="TakeOverTime"]').val(item.TakeOverTime);
            }
            $('[name="EID"]').val(item.UID);



            if (state == 0 || state == 2) {
                $('.DrawPpItembtnDiv').hide();

            }
            $('.DrawListpp_img').html("<img src='" + item.Pic + "' width='100%'>");
        });
    }, 'json');
    //事件
    $('.DrawPpItembtn2').click(function () {
        //    alert(1);
        $('.DrawPpItembtn2').attr('disabled', true);
        $.post(posturl + '?action=DrawPpItembtn2&eid=' + encodeURI(localStorage.eid) + '&id=' + mid, $('#DrawPpItem_form').serialize(), function (data) {

            if (data.status == 1) {
                global_obj.win_alert(data.msg, function () {
                    //window.location.href = "member/DrawCz.html";
                    mainView.router.reloadPage("member/DrawListpp2.html");
                });
                $('.DrawPpItembtn2').attr('disabled', false);
            } else {
                global_obj.win_alert(data.msg, function () {
                    $('.DrawPpItembtn2').attr('disabled', false);

                });
            }
        }, 'json');

    });


    //事件
    $('.LybSu').click(function () {
        //    alert(1);

        mainView.router.reloadPage("member/LybSu.html?id=" + mid);


    });

});
//BankCardList
myApp.onPageInit('BankCardList', function (page) {


    //加载
    var mid = (page.query.id);

    //加载列表
    $.get(posturl + '?action=BankCardList&eid=' + encodeURI(localStorage.eid), function (data) {
        var pageList = data.items;

        var pageHtml = "";
        $.each(data.items, function (idx, item) {
            
            var date = new Date(item.CreateTime);
            pageHtml += " <div class='yinhangkaitem2 yxui-flex'  >" +
                " <div class='cell' >" + item.Bank + "" + "</div >" +
                "<div class='cell' >" + item.Card + " </div> " +
                "<div class='cell' >" + item.Category + " </div> " +
                //  "<div class='cell' >" + item.GoodsTotal + " </div> " +
                "   <div class='cell'><i class='icon iconfont icon-shanchu' ></i><a  data-id='" + item.ID + "'  class='bcl' >删除</a></div>" +
                //onclick='BankCardListDel(" + item.ID+")' 
                " </div >";
        });


        $(".show_main").html(pageHtml);
        //  $("#show_main").html(pageHtml);

    }, 'json');

 

    //事件
    $(".show_main").on("click", ".bcl", function () {
        //   $('.s_img').click(function () {
        //   bigimg
        var ID = $(this).attr('data-id');


        var delurl = posturl + '?action=BankCardListDel&eid=' + encodeURI(localStorage.eid) + "&id=" + ID;
        $.get(delurl, function (data) {

            alert(data.msg);

            mainView.router.refreshPage();


        }, 'json');
    });




});

//DrawPpItem
myApp.onPageInit('BankCards', function (page) {
    var mid = (page.query.id);
    if (mid > 0) {
        //加载
        $.get(posturl + '?action=BankCardsModel&eid=' + encodeURI(localStorage.eid) + "&id=" + mid, function (data) {
            var pageList = data.items;


            var pageHtml = "";
            $.each(data.items, function (idx, item) {

                $('[name="Card"]').val(item.Card);
                $('[name="Name"]').val(item.Name);
                $('[name="IDCard"]').val(item.IDCard);

                $('[name="Remark"]').val(item.Remark);

                //  $.bindSelect("Category", item.Category);
            });



        }, 'json');

    } else {

        $.get(posturl + '?action=GetUser&eid=' + encodeURI(localStorage.eid), function (data) {
            var su = data.items[0];



            $('[name="Name"]').val(su.RealName);


            //$('#ft_eid').html(GetZhType(1));
        }, 'json');

    }



    //事件
    $('.BankCardsBtn').click(function () {

        if (global_obj.check_form($('*[notnull]'))) { return false };


        $('.BankCardsBtn').attr('disabled', true);

        $.post(posturl + '?action=BankCardsBtn&eid=' + encodeURI(localStorage.eid) + '&id=' + mid, $('#BankCards_Form').serialize(), function (data) {

            if (data.status == 1) {

                // alert(openid);

                global_obj.win_alert(data.msg, function () {
                    //window.location.href = "member/DrawCz.html";

                    mainView.router.reloadPage("member/BankCardList.html");


                });


                $('.BankCardsBtn').attr('disabled', false);
            } else {

                global_obj.win_alert(data.msg, function () {
                    $('.BankCardsBtn').attr('disabled', false);

                });
            }
        }, 'json');





    });

});


//DrawCzbtn
myApp.onPageInit('Info', function (page) {



    //加载
    var mid = (page.query.id);
    $.get(posturl + '?action=GetUser&eid=' + encodeURI(localStorage.eid), function (data) {
        var su = data.items[0];


        //   $('.ft_eid').html("EID:" + su.ID);
        //   $('.ft_xinyong').html("信用：" + GetXing(su.Xing));

        $('[name="RealName"]').val(su.RealName);

        $('[name="hfShopAd1"]').html(su.HeadImg);
        $('[name="Yhk"]').val(su.Yhk);
        $('[name="Phone"]').val(su.Phone);

        // alert(su.Phone);

        //$('#ft_eid').html(GetZhType(1));
    }, 'json');

    ////				上传图片
    //$('.yxcheckimg').on('change', function (e) {
    //    var files = this.files;
    //    for (var i = 0; i < files.length; i++) {

    //        var reader = new FileReader();
    //        reader.readAsDataURL(files[i]);
    //        reader.onload = function (e) {
    //            var img = new Image();
    //            var mb = (e.total / 1024) / 1024;
    //            img.src = this.result;
    //            img.style.width = "0.8rem";
    //            img.style.height = "0.8rem";
    //            $('.choice').hide();
    //            $('.imgdiv').html(img)
    //        }
    //    }
    //});
    //图片上传控件
    'use strict';
    // Change this to the location of your server-side upload handler:
    var url = postImgurl + '?action=upImg';
    $('#fileupload').fileupload({
        url: url,
        dataType: 'json',
        done: function (e, data) {
            //  alert(data.result.url);
            $('[name="hfShopAd1"]').val(data.result.url);
            //$.each(data.result.files, function (index, file) {
            //    $('<p/>').text(file.name).appendTo('#files');
            //});
            var img = new Image();
            var mb = (e.total / 1024) / 1024;
            img.src = data.result.url;
            img.style.width = "0.8rem";
            img.style.height = "0.8rem";
            $('.choice').hide();
            $('.imgdiv').html(img)
        },
        progressall: function (e, data) {
            //alert(data.result.url);
            var progress = parseInt(data.loaded / data.total * 100, 10);
            $('#progress .progress-bar').css(
                'width',
                progress + '%'
            );
        }
    }).prop('disabled', !$.support.fileInput)
        .parent().addClass($.support.fileInput ? undefined : 'disabled');
    //加载
    $.get(posturl + '?action=Info&eid=' + encodeURI(localStorage.eid), function (data) {
        var pageList = data.items;

        var pageHtml = "";
        $.each(data.items, function (idx, item) {
            var date = new Date(item.CreateTime);
            pageHtml += " <div class='cwlic-item yxui-flex' style='padding-left: 0;'>" +
                " <div class='cell' >" + getMonth(date) + "-" + getDay(date) + "" + "</div >" +
                "<div class='cell' >" + item.Price + "</div> " +
                "  <div class='cell2' ><span> " + GetMoneyLogState(item.State, item.ID) + "</span></div> " +
                " </div >";
        });
        $(".mainlist").html(pageHtml);


    }, 'json');
    //事件
    $('.sendmsgBtn').click(function () {
        alert(111);

        var Phone = $('#Info_form input[name=Phone]').val();
        if (!Phone.match(/^1[3-9]\d{9}$/)) {
            global_obj.win_alert('请填写正确的手机号码！', function () {
                $('#Info_form input[name=Phone]').focus();
            });
            return false;
        }
        $('.sendmsgBtn').html('获取验证码').attr('disabled', true);

        var time = 0;
        time_obj = function () {
            if (time >= 60) {
                $('.sendmsgBtn').html('获取验证码').attr('disabled', false);
                time = 0;
                clearInterval(timer);
            } else {
                $('.sendmsgBtn').html('重新(' + (60 - time) + ')');
                time++;
            }
        }
        var timer = setInterval('time_obj()', 1000);


        //  alert($('#zjsp').html())
        $.post(posturl + '?action=PostCodeMsg&AppRegCode=' + localStorage.AppRegCode, $('#Info_form').serialize(), function (data) {

            if (data.status == 1) {
                localStorage.AppRegCode = data.msg;
                alert('发送成功');
                //奖品
            } else { //没次數
                alert(data.msg);
            }


        }, 'json');
        $('.sendmsgBtn').attr('disabled', true)





    });

    //事件
    $('.Infobtn').click(function () {

        if (global_obj.check_form($('*[notnull]'))) { return false };


        $('.Infobtn').attr('disabled', true)
        $.post(posturl + '?action=Infobtn&eid=' + encodeURI(localStorage.eid) + '&AppRegCode=' + localStorage.AppRegCode, $('#Info_form').serialize(), function (data) {

            if (data.status == 1) {

                // alert(openid);

                global_obj.win_alert(data.msg, function () {
                    //window.location.href = "member/DrawCz.html";

                    mainView.router.reloadPage("member/info.html");
                });


                $('.Infobtn').attr('disabled', false);
            } else {

                global_obj.win_alert(data.msg, function () {
                    $('.Infobtn').attr('disabled', false);
                });
            }
        }, 'json');





    });




});


//DrawListpp
myApp.onPageInit('ZrList', function (page) {


    //加载
    var mid = (page.query.id);

    //加载列表
    $.get(posturl + '?action=ZrList&eid=' + encodeURI(localStorage.eid), function (data) {
        var pageList = data.items;

        //var pageHtml = "";
        //$.each(data.items, function (idx, item) {
        //    var zctype = "转出";

        //    var date = new Date(item.CreateTime);
        //    pageHtml += " <div class='ls-item yxui-flex'  >" +
        //        " <div class='cell' >" + item.Name + "[" + item.RealName+"]" + "" + "</div >" +
        //        "<div class='cell' >" + item.Money + " </div> " +
        //        "<div class='cell' >" + GetZhType(item.Type)  + " </div> " +
        //        "  <div class='cell' > " + zctype + " </div> " +
        //        " <div class='cell' >" + date.getFullYear() + "-" + getMonth(date) + "-" + getDay(date) + "" + "</div >" +
        //        " </div >";
        //});
        var pageHtml = "";
        $.each(data.items, function (idx, item) {

            var zctype = "转入";
            var date = new Date(item.CreateTime);
            pageHtml += " <div class='ls-item yxui-flex'>" +
                //  " <div class='cell' style='font-size: 0.2rem;'>"  + item.Name2 + "[" + item.RealName2 + "]" + "</div >" +

                " <div class='cell' style='font-size: 0.2rem;'>" + item.Name2 + "</div >" +
                "<div class='cell' >" + item.Money + "</div> " +
                "  <div class='cell1'> " + GetZhType(item.Type) + "</div> " +
                "  <div class='cell1'> " + zctype + "</div> " +
                "  <div class='cell' > " + getMonth(date) + "-" + getDay(date) + "</div> " +
                " </div >";
        });




        $(".show_main").html(pageHtml);
        //  $("#show_main").html(pageHtml);

    }, 'json');



});

//DrawListpp
myApp.onPageInit('ZcList', function (page) {


    //加载
    var mid = (page.query.id);

    //加载列表
    $.get(posturl + '?action=ZcList&eid=' + encodeURI(localStorage.eid), function (data) {
        var pageList = data.items;

        //var pageHtml = "";
        //$.each(data.items, function (idx, item) {
        //    var zctype = "转出";

        //    var date = new Date(item.CreateTime);
        //    pageHtml += " <div class='ls-item yxui-flex'  >" +
        //        " <div class='cell' >" + item.Name + "[" + item.RealName+"]" + "" + "</div >" +
        //        "<div class='cell' >" + item.Money + " </div> " +
        //        "<div class='cell' >" + GetZhType(item.Type)  + " </div> " +
        //        "  <div class='cell' > " + zctype + " </div> " +
        //        " <div class='cell' >" + date.getFullYear() + "-" + getMonth(date) + "-" + getDay(date) + "" + "</div >" +
        //        " </div >";
        //});
        var pageHtml = "";
        $.each(data.items, function (idx, item) {

            var zctype = "转出";
            var date = new Date(item.CreateTime);
            pageHtml += " <div class='ls-item yxui-flex'>" +
                //  " <div class='cell' style='font-size: 0.2rem;'>"  + item.Name2 + "[" + item.RealName2 + "]" + "</div >" +

                " <div class='cell' style='font-size: 0.2rem;'>" + item.Name2 + "</div >" +
                "<div class='cell' >" + item.Money + "</div> " +
                "  <div class='cell1'> " + GetZhType(item.Type) + "</div> " +
                "  <div class='cell1'> " + zctype + "</div> " +
                "  <div class='cell' > " + getMonth(date) + "-" + getDay(date) + "</div> " +
                " </div >";
        });




        $(".show_main").html(pageHtml);
        //  $("#show_main").html(pageHtml);

    }, 'json');



});


//DrawListpp
myApp.onPageInit('SyList2', function (page) {


    //加载
    var mid = (page.query.id);
    //加载列表
    $.get(posturl + '?action=SyList2Model&eid=' + encodeURI(localStorage.eid), function (data) {

        $(".ls-htext").html("合计:" + data.msg);

    }, 'json');
    //加载列表
    $.get(posturl + '?action=SyList2&eid=' + encodeURI(localStorage.eid), function (data) {
        var pageList = data.items;


        var pageHtml = "";
        $.each(data.items, function (idx, item) {


            var date = new Date(item.CreateTime);
            pageHtml += " <div class='ls-item yxui-flex'>" +
                //  " <div class='cell' style='font-size: 0.2rem;'>"  + item.Name2 + "[" + item.RealName2 + "]" + "</div >" +
                "  <div class='cell' > " + getMonth(date) + "-" + getDay(date) + "</div> " +
                " <div class='cell'  >" + item.Money + "</div >" +
                "<div class='cell' >" + item.MoneyDs + "</div> " +
                "  <div class='cell'> " + item.MoneySc + "</div> " +

                " </div >";
        });


        $(".show_main").html(pageHtml);
        //  $("#show_main").html(pageHtml);

    }, 'json');



});

//DrawListpp
myApp.onPageInit('SyList', function (page) {


    //加载
    var mid = (page.query.id);


    //加载列表
    $.get(posturl + '?action=SyListModel&eid=' + encodeURI(localStorage.eid), function (data) {

        $(".ls-htext").html("合计:" + data.msg);

    }, 'json');

    //加载列表
    $.get(posturl + '?action=SyList&eid=' + encodeURI(localStorage.eid), function (data) {
        var pageList = data.items;


        var pageHtml = "";
        $.each(data.items, function (idx, item) {


            var date = new Date(item.CreateTime);
            pageHtml += " <div class='ls-item yxui-flex'>" +
                //  " <div class='cell' style='font-size: 0.2rem;'>"  + item.Name2 + "[" + item.RealName2 + "]" + "</div >" +
                "  <div class='cell' > " + getMonth(date) + "-" + getDay(date) + "</div> " +
                " <div class='cell'  >" + item.Money + "</div >" +
                "<div class='cell' >" + item.MoneyDs + "</div> " +
                "  <div class='cell'> " + item.MoneySc + "</div> " +
                "  <div class='cell'> " + getrLogType(item.Type) + "</div> " +
                " </div >";
        });




        $(".show_main").html(pageHtml);
        //  $("#show_main").html(pageHtml);

    }, 'json');



});

myApp.onPageInit('SumPai', function (page) {
    //加载
    var mid = (page.query.id);
    //加载列表
    $.get(posturl + '?action=SumPai&eid=' + encodeURI(localStorage.eid), function (data) {

        alert(data.msg);
        $(".sum1").html("" + data.msg);

    }, 'json');

});
myApp.onPageInit('SumPai2', function (page) {
    //加载
    var mid = (page.query.id);
    //加载列表
    $.get(posturl + '?action=SumPai2&eid=' + encodeURI(localStorage.eid), function (data) {

        $(".sum2").html("" + data.msg);

    }, 'json');

});


//DrawListpp
myApp.onPageInit('tsuList', function (page) {

    $.get(posturl + '?action=GetUser&eid=' + encodeURI(localStorage.eid), function (data) {
        var su = data.items[0];


        $('.ft_eid').html("EID:" + su.ID);
        $('.ft_xinyong').html("信用：" + GetXing(su.Xing));
        var img = "img/img.jpg";
        if (su.HeadImg != "") {
            img = su.HeadImg;
        }

        $('.headimg').attr('src', img);

        //$('#Money').html(su.Money);
        //$('#MoneyJy').html(su.MoneyJy);
        //$('#MoneyFt').html(su.MoneyFt);
        //$('#MoneyDs').html(su.MoneyDs);


        //$('#ft_eid').html(GetZhType(1));
    }, 'json');
    //加载
    var mid = (page.query.id);
    var srurl = "";
    var srval = page.query.keyword;
    if (srval != "") {
        srurl = "&keyword=" + srval;
    }
    //alert(srurl);
    //加载列表
    $.get(posturl + '?action=tsuList&eid=' + encodeURI(localStorage.eid) + srurl, function (data) {
        var pageList = data.items;

        var pageHtml = "";
        $.each(data.items, function (idx, item) {

            var date = new Date(item.CreateTime);
            pageHtml += "  <div class='cwlmy-items'>" +
                " <div class='part1' > <i class='icon iconfont icon-user'></i></div >" +
                "<div class='part2' > EID:" + item.ID + "[" + item.RealName + "]</div > " +
                "  <div class='part3' >" + GetVipXing(item.IsTuan) + " </div > " +
                " <div class='part4' > <a style='font-size: 15px;' href='member/ZcItem.html?id=" + item.ID + "'>Pay</a></div > " +
                " </div > ";
        });


        $(".tsuListdiv").html(pageHtml);
        //  $("#show_main").html(pageHtml);

    }, 'json');

    //事件
    $('.srbtn').click(function () {
        var srval = $('[name="keyword"]').val();

        mainView.router.reloadPage("member/tsuList.html?keyword=" + srval);
    });

});

//DrawListpp
myApp.onPageInit('tsuList2', function (page) {

    $.get(posturl + '?action=GetUser&eid=' + encodeURI(localStorage.eid), function (data) {
        var su = data.items[0];


        $('.ft_eid').html("EID:" + su.ID);
        $('.ft_xinyong').html("信用：" + GetXing(su.Xing));
        var img = "img/img.jpg";
        if (su.HeadImg != "") {
            img = su.HeadImg;
        }

        $('.headimg').attr('src', img);

        //$('#Money').html(su.Money);
        //$('#MoneyJy').html(su.MoneyJy);
        //$('#MoneyFt').html(su.MoneyFt);
        //$('#MoneyDs').html(su.MoneyDs);


        //$('#ft_eid').html(GetZhType(1));
    }, 'json');
    //加载
    var mid = (page.query.id);
    var srurl = "";
    var srval = page.query.keyword;
    if (srval != "") {
        srurl = "&keyword=" + srval;
    }
    //alert(srurl);
    //加载列表
    $.get(posturl + '?action=tsuList2&eid=' + encodeURI(localStorage.eid) + srurl, function (data) {
        var pageList = data.items;

        var pageHtml = "";
        $.each(data.items, function (idx, item) {

            var date = new Date(item.CreateTime);
            pageHtml += "  <div class='cwlmy-items'>" +
                " <div class='part1' > <i class='icon iconfont icon-user'></i></div >" +
                "<div class='part2' > EID:" + item.ID + "[" + item.RealName + "]<br>动力值:" + item.MoneyJy + "</div > " +
                "  <div class='part3' >" + GetVipXing(item.IsTuan) + " </div > " +
                " <div class='part4' > <a style='font-size: 15px;' href='member/ZcItem.html?id=" + item.ID + "'>Pay</a></div > " +
                " </div > ";
        });


        $(".tsuListdiv").html(pageHtml);
        //  $("#show_main").html(pageHtml);

    }, 'json');

    //事件
    $('.srbtn').click(function () {
        var srval = $('[name="keyword"]').val();

        mainView.router.reloadPage("member/tsuList2.html?keyword=" + srval);
    });

});


//Lyb
myApp.onPageInit('Lyb', function (page) {
    //图片上传控件
    'use strict';
    // Change this to the location of your server-side upload handler:
    var url = postImgurl + '?action=upImg';
    $('#fileupload').fileupload({
        url: url,
        dataType: 'json',
        done: function (e, data) {
            //  alert(data.result.url);
            $('[name="hfShopAd1"]').val(data.result.url);
            //$.each(data.result.files, function (index, file) {
            //    $('<p/>').text(file.name).appendTo('#files');
            //});
        },
        progressall: function (e, data) {
            //alert(data.result.url);
            var progress = parseInt(data.loaded / data.total * 100, 10);
            $('#progress .progress-bar').css(
                'width',
                progress + '%'
            );
        }
    }).prop('disabled', !$.support.fileInput)
        .parent().addClass($.support.fileInput ? undefined : 'disabled');
    //加载
    $.get(posturl + '?action=Lyb&eid=' + encodeURI(localStorage.eid), function (data) {
        var pageList = data.items;

        var pageHtml = "";
        $.each(data.items, function (idx, item) {
            var typename = '我';
            if (item.Type == 1) {
                typename = '<font style="font-size:0.2rem">平台</font>';
            }

            var showimg = "";
            if (item.Pic1 != "") {
                showimg = "<img class='s_img' src='" + item.Pic1 + "'>";
            }
            var date = new Date(item.CreateTime);
            pageHtml += " <div class='liuyanlist yxui-flex' >" +
                " <div class='cell' >" + getMonth(date) + "-" + getDay(date) + "" + "</div >" +
                "<div class='cell' >" + typename + ":" + item.Remark + "</div> " +
                "  <div class='cell' >" + showimg + "</div> " +
                " </div >";
        });

        $(".showlist").html(pageHtml);

    }, 'json');
    var mid = (page.query.id);
    //事件
    $(".showlist").on("click", ".s_img", function () {
        //   $('.s_img').click(function () {
        //   bigimg

        var imgsrc = $(this).attr('src');
        $('.bigimg').attr('src', imgsrc);

        $('.bigimg_div').show(800);
    });
    //事件
    $('.bigimg_div').click(function () {
        //   bigimg

        //alert($('.s_img').attr('data-img'));
        //$('.bigimg').attr('src', $('.s_img').attr('src'));
        $('.bigimg_div').hide(500);
        //$('.bigimg').show(1000);
    });
    //事件
    $('.Lybbtn').click(function () {

        if (global_obj.check_form($('*[notnull]'))) { return false };


        $('.Lybbtn').attr('disabled', true);

        $.post(posturl + '?action=Lybbtn&eid=' + encodeURI(localStorage.eid) + '&id=' + mid, $('#Lyb_form').serialize(), function (data) {

            if (data.status == 1) {

                // alert(openid);

                global_obj.win_alert(data.msg, function () {
                    //window.location.href = "member/DrawCz.html";

                    mainView.router.reloadPage("member/Lyb.html");


                });


                $('.Lybbtn').attr('disabled', false);
            } else {

                global_obj.win_alert(data.msg, function () {
                    $('.Lybbtn').attr('disabled', false);

                });
            }
        }, 'json');





    });

});


//Lyb
myApp.onPageInit('LybSu', function (page) {
    var mid = (page.query.id);

    //图片上传控件
    'use strict';
    // Change this to the location of your server-side upload handler:
    var url = postImgurl + '?action=upImg';
    $('#fileupload').fileupload({
        url: url,
        dataType: 'json',
        done: function (e, data) {
            //  alert(data.result.url);
            $('[name="hfShopAd1"]').val(data.result.url);
            //$.each(data.result.files, function (index, file) {
            //    $('<p/>').text(file.name).appendTo('#files');
            //});
        },
        progressall: function (e, data) {
            //alert(data.result.url);
            var progress = parseInt(data.loaded / data.total * 100, 10);
            $('#progress .progress-bar').css(
                'width',
                progress + '%'
            );
        }
    }).prop('disabled', !$.support.fileInput)
        .parent().addClass($.support.fileInput ? undefined : 'disabled');
    //加载
    $.get(posturl + '?action=LybSu&eid=' + encodeURI(localStorage.eid) + "&id=" + mid, function (data) {
        var pageList = data.items;

        var pageHtml = "";
        $.each(data.items, function (idx, item) {
            var typename = '我';
            if (item.Type == 1) {
                typename = '<font style="font-size:0.2rem">平台</font>';
            }

            var showimg = "";
            if (item.Pic1 != "") {
                showimg = "<img class='s_img' src='" + item.Pic1 + "'>";
            }
            var date = new Date(item.CreateTime);
            pageHtml += " <div class='liuyanlist yxui-flex' >" +
                " <div class='cell' >" + getMonth(date) + "-" + getDay(date) + "" + "</div >" +
                "<div class='cell' >" + typename + ":" + item.Remark + "</div> " +
                "  <div class='cell' >" + showimg + "</div> " +
                " </div >";
        });

        $(".showlist").html(pageHtml);

    }, 'json');

    //事件
    $(".showlist").on("click", ".s_img", function () {
        //   $('.s_img').click(function () {
        //   bigimg

        var imgsrc = $(this).attr('src');
        $('.bigimg').attr('src', imgsrc);

        $('.bigimg_div').show(800);
    });
    //事件
    $('.bigimg_div').click(function () {
        //   bigimg

        //alert($('.s_img').attr('data-img'));
        //$('.bigimg').attr('src', $('.s_img').attr('src'));
        $('.bigimg_div').hide(500);
        //$('.bigimg').show(1000);
    });
    //事件
    $('.LybbtnSu').click(function () {

        if (global_obj.check_form($('*[notnull]'))) { return false };


        $('.LybbtnSu').attr('disabled', true);

        $.post(posturl + '?action=LybbtnSu&eid=' + encodeURI(localStorage.eid) + '&id=' + mid, $('#Lyb_form').serialize(), function (data) {

            if (data.status == 1) {

                // alert(openid);

                global_obj.win_alert(data.msg, function () {
                    //window.location.href = "member/DrawCz.html";

                    mainView.router.reloadPage("member/LybSu.html?id=" + mid);


                });


                $('.LybbtnSu').attr('disabled', false);
            } else {

                global_obj.win_alert(data.msg, function () {
                    $('.LybbtnSu').attr('disabled', false);

                });
            }
        }, 'json');





    });

});


//DrawCzbtn
myApp.onPageInit('Infopwd', function (page) {



    //加载
    var mid = (page.query.id);
    $.get(posturl + '?action=GetUser&eid=' + encodeURI(localStorage.eid), function (data) {
        var su = data.items[0];


        //   $('.ft_eid').html("EID:" + su.ID);
        //   $('.ft_xinyong').html("信用：" + GetXing(su.Xing));

        //$('[name="RealName"]').val(su.RealName);

        //$('[name="hfShopAd1"]').html(su.HeadImg);
        //$('[name="Yhk"]').val(su.Yhk);
        $('[name="Phone"]').val(su.Phone);

        // alert(su.Phone);

        //$('#ft_eid').html(GetZhType(1));
    }, 'json');

    ////				上传图片
    //$('.yxcheckimg').on('change', function (e) {
    //    var files = this.files;
    //    for (var i = 0; i < files.length; i++) {

    //        var reader = new FileReader();
    //        reader.readAsDataURL(files[i]);
    //        reader.onload = function (e) {
    //            var img = new Image();
    //            var mb = (e.total / 1024) / 1024;
    //            img.src = this.result;
    //            img.style.width = "0.8rem";
    //            img.style.height = "0.8rem";
    //            $('.choice').hide();
    //            $('.imgdiv').html(img)
    //        }
    //    }
    //});


    //事件
    $('.sendmsgBtn').click(function () {
        alert(111);

        var Phone = $('input[name=Phone]').val();
        //if (!Phone.match(/^1[3-9]\d{9}$/)) {
        //    global_obj.win_alert('请填写正确的手机号码！', function () {
        //        $('#Info_form input[name=Phone]').focus();
        //    });
        //    return false;
        //}
        $('.sendmsgBtn').html('获取验证码').attr('disabled', true);

        var time = 0;
        time_obj = function () {
            if (time >= 60) {
                $('.sendmsgBtn').html('获取验证码').attr('disabled', false);
                time = 0;
                clearInterval(timer);
            } else {
                $('.sendmsgBtn').html('重新(' + (60 - time) + ')');
                time++;
            }
        }
        var timer = setInterval('time_obj()', 1000);


        //  alert($('#zjsp').html())
        $.post(posturl + '?action=PostCodeMsg&AppRegCode=' + localStorage.AppRegCode, $('#Infopwd_form').serialize(), function (data) {

            if (data.status == 1) {
                localStorage.AppRegCode = data.msg;
                alert('发送成功');
                //奖品
            } else { //没次數
                alert(data.msg);
            }


        }, 'json');
        $('.sendmsgBtn').attr('disabled', true)





    });

    //事件
    $('.Infopwdbtn').click(function () {




        $('.Infopwdbtn').attr('disabled', true)
        $.post(posturl + '?action=Infopwdbtn&eid=' + encodeURI(localStorage.eid) + '&AppRegCode=' + localStorage.AppRegCode, $('#Infopwd_form').serialize(), function (data) {

            if (data.status == 1) {

                // alert(openid);

                global_obj.win_alert(data.msg, function () {
                    //window.location.href = "member/DrawCz.html";

                    mainView.router.reloadPage("member/Infopwd.html");
                });


                $('.Infopwdbtn').attr('disabled', false);
            } else {

                global_obj.win_alert(data.msg, function () {
                    $('.Infopwdbtn').attr('disabled', false);
                });
            }
        }, 'json');





    });




});


//// 方式2：通过pageInit事件处理所有页面
//$$(document).on('pageInit', function (e) {
//  // 获取页面数据
//  var page = e.detail.page;

//  //判断是否是“关于”页面
//  if (page.name === 'about') {
//    alert('"关于"页面加载完毕2!');
//  }
//})

//// 方式2：通过pageInit事件处理所有页面(过滤出 data-page 属性为about的页面)
//$$(document).on('pageInit', '.page[data-page="about"]', function (e) {
//  alert('"关于"页面加载完毕3!');
//})




//通用方法
/// <summary>
/// 交易订单类型
/// </summary>
/// <param name="type"></param>
/// <returns></returns>
function GetOrderJyppState(state, ID) {
    // var strs = new Array("<font style='color:red;'>待转账</font><br><a href='member/DrawCz2.html?id=" + "" + "' >点击提交截图</a>", "审核中", "审核成功", "审核失败");
    var strs = new Array("<a style='color:blue; font-size:0.28rem' href='member/DrawPpItem.html?id=" + ID + "'>点击转出</a> ", "<a style='color:blue; font-size:0.28rem' href='member/DrawPpItem.html?id=" + ID + "'>已转出</a> ", "<a style='font-size:0.28rem' href='member/DrawPpItem.html?id=" + ID + "'>完成</a> ", "<a style='font-size:0.28rem' href='member/DrawPpItem.html?id=" + ID + "'>申诉中</a>", "<a style='font-size:0.28rem' href='member/DrawPpItem.html?id=" + ID + "'>超时取消</a>", "<a style='font-size:0.28rem' href='member/DrawPpItem.html?id=" + ID + "'>申诉取消</a>");
    try {
        return strs[state];
    }
    catch (Exception) {

        return "";
    }

}
function GetOrderJyppState2(state, ID) {
    // var strs = new Array("<font style='color:red;'>待转账</font><br><a href='member/DrawCz2.html?id=" + "" + "' >点击提交截图</a>", "审核中", "审核成功", "审核失败");
    var strs = new Array("<a style='color:blue; font-size:0.28rem' href='member/DrawPpItem2.html?id=" + ID + "'>等待转出</a> ", "<a style='color:blue; font-size:0.28rem' href='member/DrawPpItem2.html?id=" + ID + "'>点击确认</a> ", "<a style='font-size:0.28rem' href='member/DrawPpItem2.html?id=" + ID + "'>完成</a> ", "<a style='font-size:0.28rem' href='member/DrawPpItem2.html?id=" + ID + "'>申诉中</a>", "<a style='font-size:0.28rem' href='member/DrawPpItem2.html?id=" + ID + "'>超时取消</a>", "<a style='font-size:0.28rem' href='member/DrawPpItem2.html?id=" + ID + "'>申诉取消</a>");
    try {
        return strs[state];
    }
    catch (Exception) {

        return "";
    }

}
function GetOrderJyppStateShow(state) {
    var strs = new Array("待付款", "已转出", "完成", "申诉中", "超时取消", "申诉取消");
    try {
        return strs[state];
    }
    catch (Exception) {

        return "";
    }

}
//寄售类型
function GetOrderJyJsState(state, ID, price) {
    var strs = new Array("待匹配&nbsp;<a style='color:blue; font-size:0.28rem' data-id='" + ID + "'  class='DrawMoneyCs' >撤销</a> ", "完成", "<font style='color:blue;'>已撤销</font>", "", "", "");
    try {
        var str = strs[state];
        if (state == 0 && price == 0) {
            str = "已匹配";
        }
        return str;
    }
    catch (Exception) {

        return "";
    }

}

//充值
function GetMoneyLogState(i, id) {
    var strs = new Array("<font style='color:red;'>待转账</font><br><a href='member/DrawCz2.html?id=" + id + "' >点击提交截图</a>", "审核中", "审核成功", "审核失败");
    try {
        return strs[i]
            ;
    }
    catch (Exception) {

        return i.ToString();
    }
}


function gg(type) {
    var strs = new Array("[XT]", "充值[XT]", "原动力[XT]", "E派充值", "兑换分[后台]", "收派", "消费", "兑换积分[E派]", "消费赠分", "收派赠分", "兑换积分[原动力]");

    if (type <= 10) {
        return strs[type];
    }
    else {
        if (type == 11) {
            return "慈善分";
        }
        else if (type == 12) {
            return "兑换";
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
            return "17";
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

function GetXing(xing) {
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

    if (xing > 6) {
        i = 5;

        while (i < xing) {

            i++;




            strHtml += "  <img src='img/zuan.png' class='zuan' height='15px'>";
        }
    } else {
        while (i < xing) {
            i++;
            strHtml += " <i class='icon iconfont icon-star'></i>";
        }
    }

    return strHtml;
}

//释放类型
function getrLogType(type) {
    var strs = new Array("消费者", "VIP", "授权经销商", "合作商", "总D理");
    if (type == 0) {
        return "释放";
    }
    else if (type >= 1 && type < 100) {
        return "加速";
    }
    else {
        return "加速T";
    }


}




//时间格式

function datetimeFormatUtil(longTypeDate) {
    var dateTypeDate = "";
    var date = new Date();
    date.setTime(longTypeDate);
    dateTypeDate += date.getFullYear();   //年    
    dateTypeDate += "-" + getMonth(date); //月     
    dateTypeDate += "-" + getDay(date);   //日    
    //dateTypeDate += " " + getHours(date);   //时    
    //dateTypeDate += ":" + getMinutes(date);     //分  
    //dateTypeDate += ":" + getSeconds(date);     //分  
    return dateTypeDate;
}
/*  
 * 时间格式化工具 
 * 把Long类型的yyyy-MM-dd日期还原yyyy-MM-dd格式日期   
 */
function dateFormatUtil(longTypeDate) {
    var dateTypeDate = "";
    var date = new Date();
    date.setTime(longTypeDate);
    dateTypeDate += date.getFullYear();   //年    
    dateTypeDate += "-" + getMonth(date); //月     
    dateTypeDate += "-" + getDay(date);   //日    
    return dateTypeDate;
}
//返回 01-12 的月份值     
function getMonth(date) {
    var month = "";
    month = date.getMonth() + 1; //getMonth()得到的月份是0-11    
    if (month < 10) {
        month = "0" + month;
    }
    return month;
}
//返回01-30的日期    
function getDay(date) {
    var day = "";
    day = date.getDate();
    if (day < 10) {
        day = "0" + day;
    }
    return day;
}
//小时  
function getHours(date) {
    var hours = "";
    hours = date.getHours();
    if (hours < 10) {
        hours = "0" + hours;
    }
    return hours;
}
//分  
function getMinutes(date) {
    var minute = "";
    minute = date.getMinutes();
    if (minute < 10) {
        minute = "0" + minute;
    }
    return minute;
}
//秒  
function getSeconds(date) {
    var second = "";
    second = date.getSeconds();
    if (second < 10) {
        second = "0" + second;
    }
    return second;
}


