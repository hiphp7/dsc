/* 初始化一些全局变量 */
var lf = "<br />";
var iframe = null;
var notice = null;
var oriDisabledInputs = [];

/* Ajax设置 */
Ajax.onRunning = null;
Ajax.onComplete = null;

/**
 * 获得数据库列表
 */
function getDbList() {
    var f = $("js-setting");
    var base64password = encodeURIComponent(f["js-db-pass"].value);
    base64password = Base64.encode(base64password);

    var params = "db_host=" + f["js-db-host"].value + "&"
        + "db_port=" + f["js-db-port"].value + "&"
        + "db_user=" + encodeURIComponent(f["js-db-user"].value) + "&"
        + "db_pass=" + base64password + "&"
        + "lang=" + getAddressLang() + "&"
        + "IS_AJAX_REQUEST=yes";

    var result = Ajax.call("install?step=get_db_list", params, null, "GET", "JSON", false);

    if (typeof(result) === "object" && result["msg"] === "OK") {
        alert("数据库已连接成功！");
        return result["list"].split(",");
    } else {
        alert("连不上数据库，用户名或密码不正确！");
        return false;
    }
}

/**
 * 切换复选框的状态
 */
function switchInputsStatus() {
    var goodsTypes = document.getElementsByName("js-goods-type[]"),
        num = goodsTypes.length;

    if (this.checked) {
        for (var i = 0; i < num; i++) {
            goodsTypes[i].checked = "checked";
            goodsTypes[i].disabled = "true";
        }
    } else {
        for (var i = 0; i < num; i++) {
            goodsTypes[i].checked = "";
            goodsTypes[i].disabled = "";
        }
    }
}

/**
 * 安装程序主函数
 */
function install() {
    lockAllInputs();
    startNotice();
    $("js-install-at-once").setAttribute("disabled", "true");
    $("js-monitor").style.display = "block";
    try {
        createConfigFile();
    } catch (ex) {
    }
}

/**
 * 创建配置文件
 */
function createConfigFile() {
    var f = $("js-setting");
    var base64password = encodeURIComponent(f["js-db-pass"].value);
    base64password = Base64.encode(base64password);

    var tzs = f["js-timezones"],
        tz = tzs ? "timezone=" + tzs[tzs.selectedIndex].value : "",
        params = "db_host=" + f["js-db-host"].value + "&"
            + "db_port=" + f["js-db-port"].value + "&"
            + "db_user=" + encodeURIComponent(f["js-db-user"].value) + "&"
            + "db_pass=" + base64password + "&"
            + "db_name=" + encodeURIComponent(f["js-db-name"].value) + "&"
            + "db_prefix=" + f["js-db-prefix"].value + "&"
            + tz + "&"
            + "lang=" + getAddressLang() + "&"
            + "IS_AJAX_REQUEST=yes";

    notice.innerHTML = create_config_file;

    try {
        var result = Ajax.call("install?step=create_config_file", params, null, "GET", "JSON", false);
    } catch (ex) {
        //alert(ex);
    }

    if (typeof(result) === "object" && result["msg"] === "OK") {
        displayOKMsg();
        createDatabase();
        return true;
    } else {
        displayErrorMsg(result);
        return false;
    }
}

/**
 * 初始化数据库
 */
function createDatabase() {

    var f = $("js-setting");
    var base64password = encodeURIComponent(f["js-db-pass"].value);
    base64password = Base64.encode(base64password);

    var params = "db_host=" + f["js-db-host"].value + "&"
        + "db_port=" + f["js-db-port"].value + "&"
        + "db_user=" + encodeURIComponent(f["js-db-user"].value) + "&"
        + "db_pass=" + base64password + "&"
        + "db_name=" + encodeURIComponent(f["js-db-name"].value) + "&"
        + "lang=" + getAddressLang();

    notice.innerHTML += create_database;

    try {
        var result = Ajax.call("install?step=create_database", params, null, "GET", "JSON", false);
    } catch (ex) {
        //alert(ex);
    }

    if (typeof(result) === "object" && result["msg"] === "OK") {

        displayOKMsg();
        installBaseData(result["config"]);
        return true;
    } else {
        displayErrorMsg(result);
        return false;
    }
}

/**
 * 安装数据
 */
function installBaseData($config) {

    var base64password = Base64.encode($config['db_pass']);

    var params = "db_host=" + $config['db_host'] + "&"
        + "db_port=" + $config['db_port'] + "&"
        + "db_user=" + $config['db_user'] + "&"
        + "db_pass=" + base64password + "&"
        + "db_name=" + $config['db_name'] + "&"
        + "system_lang=" + getCheckedRadio("js-system-lang").value + "&"
        + "lang=" + getAddressLang();

    notice.innerHTML += install_data;

    try {
        var result = Ajax.call("install?step=install_base_data", params, null, "GET", "JSON", false);
    } catch (ex) {
        //alert(ex);
    }

    if (typeof(result) === "object" && result["msg"] === "OK") {
        displayOKMsg();
        createAdminPassport(result["config"]);
        return true;
    } else {
        displayErrorMsg(result);
        return false;
    }
}

/**
 * 创建管理员帐号
 */
function createAdminPassport($config) {

    var f = $("js-setting");
    var base64password = Base64.encode($config['db_pass']);

    var params = "db_host=" + $config['db_host'] + "&"
        + "db_port=" + $config['db_port'] + "&"
        + "db_user=" + $config['db_user'] + "&"
        + "db_pass=" + base64password + "&"
        + "db_name=" + $config['db_name'] + "&"
        + "admin_name=" + encodeURIComponent(f["js-admin-name"].value) + "&"
        + "admin_password=" + encodeURIComponent(f["js-admin-password"].value) + "&"
        + "admin_password2=" + encodeURIComponent(f["js-admin-password2"].value) + "&"
        + "admin_email=" + f["js-admin-email"].value + "&"
        + "lang=" + getCheckedRadio("js-system-lang").value;

    notice.innerHTML += create_admin_passport;

    try {
        var result = Ajax.call("install?step=create_admin_passport", params, null, "GET", "JSON", false);
    } catch (ex) {
        //alert(ex);
    }

    if (typeof(result) === "object" && result["msg"] === "OK") {
        displayOKMsg();
        doOthers(result["config"]);
        return true;
    } else {
        displayErrorMsg(result);
        return false;
    }
}

/**
 * 处理其它的操作
 */
function doOthers($config) {

    var base64password = Base64.encode($config['db_pass']);

    var f = $("js-setting"),
        disableCaptcha = f["js-disable-captcha"].checked ? 0 : 1,
        installDemo = f["js-install-demo"].checked ? 1 : 0,
        params = "db_host=" + $config['db_host'] + "&"
            + "db_port=" + $config['db_port'] + "&"
            + "db_user=" + $config['db_user'] + "&"
            + "db_pass=" + base64password + "&"
            + "db_name=" + $config['db_name'] + "&"
            + "disable_captcha=" + disableCaptcha + "&"
            + "system_lang=" + getCheckedRadio("js-system-lang").value + "&"
            + "install_demo=" + installDemo + "&"
            + "userinterface=" + f["userinterface"].value + "&"
            + "lang=" + getAddressLang();

    notice.innerHTML += do_others;

    try {
        var result = Ajax.call("install?step=do_others", params, null, "GET", "JSON", false);
    } catch (ex) {
        //alert(ex);
    }

    if (typeof(result) === "object" && result["msg"] === "OK") {
        displayOKMsg();
        goToDone();
        return true;
    } else {
        displayErrorMsg(result);
        return false;
    }
}

/**
 * 转到完成页
 */
function goToDone() {
    stopNotice();
    window.setTimeout(function () {
        location.href = "install?lang=" + getAddressLang() + "&step=done";
    }, 1000);
}

/* 在安装过程中调用该方法 */
function startNotice() {
    $("js-monitor-loading").src = "assets/install/images/load.gif";
    $("js-monitor-wait-please").innerHTML = "<strong style='color:blue'>" + wait_please + "</strong>";
};

/* 安装完毕调用该方法 */
function stopNotice() {
    $("js-monitor-loading").src = "assets/install/images/load.gif";
    $("js-monitor-wait-please").innerHTML = has_been_stopped;
};

/**
 * 取得所有选中的复选框
 */
function getCheckedBoxes(boxName) {
    var boxes = document.getElementsByName(boxName),
        num = boxes.length,
        checkedBoxes = [];

    for (var i = 0; i < num; i++) {
        if (boxes[i].checked) {
            checkedBoxes.push(boxes[i]);
        }
    }

    return checkedBoxes;
}

/**
 * 获得选中的单选框
 */
function getCheckedRadio(radioName) {
    var radios = document.getElementsByName(radioName);
    for (var i = 0; i < radios.length; i++) {
        if (radios[i].checked) {
            return radios[i];
        }
    }
}

/**
 * 锁定所有的输入组件
 */
function lockAllInputs() {
    recOriDisabledInputs();
    var elems = $("js-setting").elements;
    for (var i = 0; i < elems.length; i++) {
        elems[i].disabled = "true";
    }
}

/**
 * 解锁某些输入组件
 */
function unlockSpecInputs() {
    var elems = $("js-setting").elements;
    for (var i = 0; i < elems.length; i++) {
        if (oriDisabledInputs.inArray(elems[i])) {
            continue;
        }
        elems[i].removeAttribute("disabled");
    }
}

/**
 * 记录那些原先就被锁定的输入组件
 */
function recOriDisabledInputs() {
    var elems = $("js-setting").elements;
    for (var i = 0; i < elems.length; i++) {
        if (elems[i].disabled) {
            oriDisabledInputs.push(elems[i]);
        }
    }
}

/**
 * 给数组的原型定义一个方法，判断元素是不是属于某个数组
 */
Array.prototype.inArray = function (unit) {
    var length = this.length;
    for (var i = 0; i < length; i++) {
        if (unit === this[i]) {
            return true;
        }
    }
    return false;
}

/**
 * 显示完成信息
 */
function displayOKMsg() {
    notice.innerHTML += "<span style='color:green;'>" + success + "</span>" + lf;
}

/**
 * 显示错误信息
 */
function displayErrorMsg(result) {
    stopNotice();
    notice.innerHTML += "<span style='color:red;'>" + fail + "</span>" + lf + lf;

    notice.innerHTML += "<strong style='color:red'>" + result + "</strong>";
}
