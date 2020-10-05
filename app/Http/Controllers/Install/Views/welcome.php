<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title><?php echo $lang['welcome_title']; ?></title>
    <link href="__PUBLIC__/install/styles/install.css" rel="stylesheet" type="text/css"/>

    <script type="text/javascript" src="__PUBLIC__/install/js/transport_jquery.js"></script>
    <script type="text/javascript" src="__PUBLIC__/install/js/common.js"></script>

    <script type="text/javascript" src="__PUBLIC__/install/js/jquery-1.12.4.min.js"></script>
    <script type="text/javascript" src="__PUBLIC__/install/js/scroll.js"></script>

</head>
<body id="welcome">
<?php include VIEWS_PATH . 'header.php'; ?>
<div class="wrapper">
    <div class="w1000">
        <div class="attention" id="attention">

        </div>
        <div class="content">
            <div class="tab">
                <div class="step curr">
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
                <div class="step">
                    <div class="warp">
                        <i>3</i>
                        <span>配置系统</span>
                    </div>
                </div>
            </div>
            <div class="zoome" id="zoome">
                <form action="./" method="post">
                    <div class="scrollBody" id="scrollBody">
                        <div class="desc" id="scrollMap">
                            <ul id="content">

                            </ul>
                        </div>
                        <div class="scrollBar" id="scrollBar">
                            <p id="city_bar"></p>
                        </div>
                    </div>
                    <div class="tfoot">
                        <div class="tfoot_left">
                            <input type="checkbox" id="js-agree"/>
                            <label for="js-agree"><?php echo $lang['agree_license']; ?></label>
                        </div>
                        <div class="tfoot_right">
                            <input type="submit" id="js-submit" disabled="disabled" class="btn"
                                   value="<?php echo $lang['next_step']; ?><?php echo $lang['setup_environment']; ?>"/>

                        </div>
                        <input name="ucapi" type="hidden" value="<?php echo $ucapi; ?>"/>
                        <input name="ucfounderpw" type="hidden" value="<?php echo $ucfounderpw; ?>"/>
                    </div>
                </form>
            </div>
            <div class="right" id="right_ad">

            </div>
        </div>
    </div>
    <div class="footer">
        <?php include VIEWS_PATH . 'copyright.php'; ?>
    </div>
</div>
<script type="text/javascript">
    $("#zoome").jScroll({id: "scrollBody"});

    Ajax.call('install/cloud?step=welcome', '', welcome_api, 'GET', 'TEXT', 'FLASE');
    Ajax.call('install/cloud?step=right_ad', '', right_ad_api, 'GET', 'TEXT', 'FLASE');
    Ajax.call('install/cloud?step=update_mend', '', update_mend_api, 'GET', 'TEXT', 'FLASE');

    function welcome_api(result) {
        if (result) {
            setInnerHTML('content', result);
            setInputCheckedStatus();

            var agree = $("#js-agree");
            var Submit = $("#js-submit");

            agree.click(function () {
                if (agree.prop("checked") == true) {
                    Submit.removeAttr('disabled');
                    Submit.addClass("btn-curr");
                    Submit.click(function () {
                        $("form").submit(function () {
                            $(this).attr("action", "install?lang=" + getAddressLang() + "&step=check");
                        });
                    });
                }
                else {
                    Submit.attr('disabled', 'true');
                    Submit.removeClass("btn-curr");
                }
            });

        }
    }

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

</script>
</body>
</html>
