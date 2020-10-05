<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title><?php echo $lang['setting_title']; ?></title>
    <link href="__PUBLIC__/install/styles/install.css" rel="stylesheet" type="text/css"/>
    <script type="text/javascript" src="__ROOT__js/base64.min.js"></script>
    <script type="text/javascript" src="__PUBLIC__/install/js/transport.js"></script>
    <script type="text/javascript" src="__PUBLIC__/install/js/common.js"></script>
    <script type="text/javascript" src="__PUBLIC__/install/js/draggable.js"></script>
    <script type="text/javascript" src="__PUBLIC__/install/js/setting.js"></script>


    <script type="text/javascript">
        <?php foreach ($lang['js_languages'] as $key => $item): ?>
        <?php if (!is_array($item)): ?>
        var <?php echo $key;?> = "<?php echo $item;?>";
        <?php endif; ?>
        <?php endforeach; ?>

    </script>
</head>
<body id="checking">
<?php include VIEWS_PATH . 'header.php'; ?>
<div class="wrapper">
    <div class="w1000">
        <div class="attention" id="attention">

        </div>
        <div class="content">
            <div class="tab">
                <div class="step">
                    <div class="warp">
                        <i>1</i>
                        <span>欢迎使用</span>
                    </div>
                </div>
                <div class="step">
                    <div class="warp">
                        <i>2</i>
                        <span>检查环境</span>
                    </div>
                </div>
                <div class="step curr">
                    <div class="warp">
                        <i>3</i>
                        <span>配置系统</span>
                    </div>
                </div>
            </div>
            <div class="zoome" id="zoome">

            </div>
            <div class="right" id="right_ad">

            </div>
        </div>
    </div>
    <div class="footer">
        <?php include VIEWS_PATH . 'copyright.php'; ?></div>
</div>
</div>
<div class="loading" id="js-monitor" style="display:none;">
    <div class="loading-mask"></div>
    <div class="loading-content" id="loading-content">
        <div class="loading-top">
            <div class="tit" id="js-monitor-title">安装程序监测器</div>
            <div class="close" id="js-monitor-close"></div>
        </div>
        <div class="loading-warp">
            <div class="img"><img id="js-monitor-loading" src="__PUBLIC__/install/images/load.gif"/></div>
            <div class="desc" id="js-monitor-wait-please"></div>
            <div class="desc" id="js-notice">快速正在安装中，请稍后......</div>
        </div>
        <div class="loading-bottom"></div>
    </div>

</div>
<script type="text/javascript">
    function check_mobile_code() {
        var f = $("js-setting");
        var mobile = f["mobile"].value;
        var mobile_code = f["mobile_code"].value;
        install();
    }

    Ajax.call('install/cloud?step=setting_ui', '', setting_ui_api, 'GET', 'TEXT', 'FLASE');
    Ajax.call('install/cloud?step=right_ad', '', right_ad_api, 'GET', 'TEXT', 'FLASE');
    Ajax.call('install/cloud?step=update_mend', '', update_mend_api, 'GET', 'TEXT', 'FLASE');

    function right_ad_api(result) {
        if (result) {
            setInnerHTML('right_ad', result);
        }
    }

    function update_mend_api(result) {
        if (result) {
            setInnerHTML('attention', result);
        }
    }

    function setting_ui_api(result) {
        if (result) {
            setInnerHTML('zoome', result);
            setInputCheckedStatus();
            var f = $("js-setting");

            f.setAttribute("action", "javascript:install();");

            f["js-db-name"].onblur = function () {
                var list = getDbList();
                for (var i = 0; i < list.length; i++) {
                    if (f["js-db-name"].value === list[i]) {
                        var answer = confirm(db_exists);
                        if (answer === false) {
                            f["js-db-name"].value = "";
                        } else {
                            continue;
                        }
                    }
                }
            }
            f["js-admin-password"].onblur = function () {
                var password = f['js-admin-password'].value;
                var confirm_password = f['js-admin-password2'].value;
                if (!(password.length >= 8 && /\d+/.test(password) && /[a-zA-Z]+/.test(password))) {
                    $("js-install-at-once").setAttribute("disabled", "true");
                    if (!(password.length >= 8)) {
                        $("js-admin-password-result").innerHTML = "<span class='comment'><img src='assets\/install\/images\/no.gif'>" + password_short + "<\/span>";
                    }
                    else {
                        $("js-admin-password-result").innerHTML = "<span class='comment'><img src='assets\/install\/images\/no.gif'>" + password_invaild + "<\/span>";
                    }
                }
                else {
                    $("js-admin-password-result").innerHTML = "<img src='assets\/install\/images\/yes.gif'>";
                    if (password == confirm_password) {
                        $("js-install-at-once").removeAttribute("disabled");
                        $("js-admin-confirmpassword-result").innerHTML = "<img src='assets\/install\/images\/yes.gif'>";
                    }
                    else {
                        $("js-install-at-once").setAttribute("disabled", "true");
                        if (confirm_password != '') {
                            $("js-admin-confirmpassword-result").innerHTML = "<span class='comment'><img src='assets\/install\/images\/no.gif'>" + password_not_eq + "<\/span>";
                        }
                    }
                }
            }
            f["js-admin-password2"].onblur = function () {
                var password = f['js-admin-password'].value;
                var confirm_password = f['js-admin-password2'].value;
                if (!(confirm_password.length >= 8 && /\d+/.test(confirm_password) && /[a-zA-Z]+/.test(confirm_password) && password == confirm_password)) {
                    $("js-install-at-once").setAttribute("disabled", "true");

                    if (!(confirm_password.length >= 8)) {
                        $("js-admin-confirmpassword-result").innerHTML = "<span class='comment'><img src='assets\/install\/images\/no.gif'>" + password_short + "<\/span>";
                    }
                    else {
                        if (password == confirm_password) {
                            $("js-admin-confirmpassword-result").innerHTML = "<span class='comment'><img src='assets\/install\/images\/no.gif'>" + password_invaild + "<\/span>";
                        }
                        else {
                            $("js-admin-confirmpassword-result").innerHTML = "<span class='comment'><img src='assets\/install\/images\/no.gif'>" + password_not_eq + "<\/span>";
                        }
                    }
                }
                else {
                    $("js-install-at-once").removeAttribute("disabled");
                    $("js-admin-confirmpassword-result").innerHTML = "<img src='assets\/install\/images\/yes.gif'>";
                }
            }
            f["js-admin-password"].onkeyup = function () {
                var pwd = f['js-admin-password'].value;
                var Mcolor = "#FFF", Lcolor = "#FFF", Hcolor = "#FFF";
                var Safety = 'pwd-strength';
                var m = 0;

                var Modes = 0;
                for (i = 0; i < pwd.length; i++) {
                    var charType = 0;
                    var t = pwd.charCodeAt(i);
                    if (t >= 48 && t <= 57) {
                        charType = 1;
                    }
                    else if (t >= 65 && t <= 90) {
                        charType = 2;
                    }
                    else if (t >= 97 && t <= 122) {
                        charType = 4;
                    }
                    else {
                        charType = 4;
                    }
                    Modes |= charType;
                }

                for (h = 0; h < 4; h++) {
                    if (Modes & 1) m++;
                    Modes >>>= 1;
                }

                if (pwd.length <= 4) {
                    m = 1;
                }

                switch (m) {
                    case 1 :
                        Safety = "pwd-strength weak";
                        break;
                    case 2 :
                        Safety = "pwd-strength middle";
                        break;
                    case 3 :
                        Safety = "pwd-strength strong";
                        break;
                    case 4 :
                        Safety = "pwd-strength strong";
                        break;
                    default :
                        break;
                }
                if (document.getElementById("Safety_style")) {
                    document.getElementById("Safety_style").className = Safety;
                }
            }

            $("js-monitor-close").onclick = function () {
                $("js-monitor").style.display = "none";
                unlockSpecInputs();
            };

            notice = $("js-notice");
            var d = new Draggable();
            d.bindDragNode("js-monitor", "js-monitor-title");

            $("js-system-lang-" + getAddressLang()).setAttribute("checked", "checked");

            $("js-pre-step").onclick = function () {
                location.href = "install?lang=" + getAddressLang() + "&step=check";
            };

            <?php

            if ($is_path == 1) {
            ?>
            f["js-install-demo"].onclick = switchInputsStatus;
            <?php
            } ?>

            var winHeight = window.innerHeight;
            var winWidth = window.innerWidth;
            var top = (winHeight - 310) / 2 + 'px';
            var left = (winWidth - 520) / 2 + 'px';

            var loading = $("loading-content");

            loading.style.top = top;
            loading.style.left = left;

        }
    }
</script>
</body>
</html>
