//获取当前url指定参数的值
var StringUtil = {};
StringUtil.QueryString = function (name)
{
    var qs = name + "=";
    var str = location.search;
    if (str.length > 0)
    {
        begin = str.indexOf(qs);
        if (begin != -1)
        {
            begin += qs.length;
            end = str.indexOf("&", begin);
            if (end == -1) end = str.length;
            return (str.substring(begin, end));
        }
    }
    return null;
}
//设置当前Url
StringUtil.BuilderNewQuery = function (name, value)
{
    var str = location.search;
    if (str.length == 0)
    {
        str = $.format("?{0}={1}", name, value);
    }
    else
    {
        str = str.replace("?", "");
        var isExit = false; //是否存在指定的查下名称
        var QueryArry = str.split("&");
        for (var i = 0; i < QueryArry.length; i++)
        {
            var arry = QueryArry[i].split("=");
            if (arry.length = 2 && arry[0].toLowerCase() == name.toLowerCase())
            {
                isExit = true;
                arry[1] = value;
                QueryArry[i] = arry.join("=");
                break;
            }
        }
        str = isExit ? "?" + QueryArry.join("&") : location.search + "&" + name + "=" + value;
    }
    return location.pathname + str
}
/*
字符串格式化
$.format("ab{0}d","d");
*/

jQuery.format = function (source, params)
{
    if (arguments.length == 1)
        return function ()
        {
            var args = jQuery.makeArray(arguments);
            args.unshift(source);
            return jQuery.format.apply(this, args);
        };

    if (arguments.length > 2 && params.constructor != Array)
    {
        params = jQuery.makeArray(arguments).slice(1);
    }
    if (params.constructor != Array)
    {
        params = [params];
    }
    jQuery.each(params, function (i, n)
    {
        if (n == null)
        {
            n = "";
        }
        source = source.replace(new RegExp("\\{" + i + "\\}", "g"), n);
    });
    return source;
};
/*
*表单赋值
*/
(function ($)
{
    //绑定多选择框
    $.bindCheckBoxList = function (name, value)
    {
        var arry = value.split(",");
        $("[name='" + name + "']").each(function (i, n)
        {
        
            if (jQuery.inArray(n.value, arry) >= 0)
            {
                n.checked = true;
            }
            else
            {
                n.checked = false;
            }
            
        })
    };
    //绑定下拉框
    $.bindSelect = function (name, value)
    {
        $("[name='" + name + "']").val(value);
    };
})(jQuery)