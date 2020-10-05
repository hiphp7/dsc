<form action="./" method="post" id="js-setting">
                        <div class="install_file install_config">
                        	<div class="item" style="display:none">
                                <div class="title"><h1><?php echo $lang['mobile_check'];?></h1></div>
                                <div class="list">
                                    <div class="item">
                                        <div class="label"><?php echo $lang['mobile_num'];?></div>
                                        <div class="value"><input type="text" name="mobile" style="width:200px;" class="text" value="" />&nbsp;&nbsp;<input id="send_mobile_code" class="send_mobile_code" type="button" value="<?php echo $lang['send_mobile_code'];?>" /></div>
                                    </div>
                                    <div class="item">
                                        <div class="label"><?php echo $lang['mobile_check_code'];?></div>
                                        <div class="value"><input type="text" name="mobile_code"  value="" class="text"/><br/><span class="ts">(<?php echo $lang['mobile_code_explain'];?>)</span></div>
                                    </div>
                                </div>
                            </div>
                            <div class="item">
                                <div class="title"><h1><?php echo $lang['db_account'];?></h1></div>
                                <div class="list">
                                    <div class="item">
                                        <div class="label"><?php echo $lang['db_host'];?></div>
                                        <div class="value"><input type="text" name="js-db-host" class="text" value="127.0.0.1" /></div>
                                    </div>
                                    <div class="item">
                                        <div class="label"><?php echo $lang['db_port'];?></div>
                                        <div class="value"><input type="text" name="js-db-port"  value="3306" class="text"/></div>
                                    </div>
                                    <div class="item">
                                        <div class="label"><?php echo $lang['db_user'];?></div>
                                        <div class="value"><input type="text" name="js-db-user" class="text" value="root" /></div>
                                    </div>
                                    <div class="item">
                                        <div class="label"><?php echo $lang['db_pass'];?></div>
                                        <div class="value"><input type="password" autocomplete="off" name="js-db-pass" class="text" value="" /></div>
                                    </div>
                                    <div class="item">
                                        <div class="label"><?php echo $lang['db_name'];?></div>
                                        <div class="value">
                                        	<input type="text" name="js-db-name" id="" class="text" value="" />
                                        </div>
                                    </div>
                                    <div class="item">
                                        <div class="label"><?php echo $lang['db_prefix'];?></div>
                                        <div class="value"><input type="text" name="js-db-prefix" class="text" value="dsc_" /><br/><span class="ts">(<?php echo $lang['change_prefix'];?>)</span></div>
                                    </div>
                                </div>
                            </div>
                            <div class="item">
                                <div class="title"><h1><?php echo $lang['admin_account'];?></h1></div>
                                <div class="list">
                                	<div class="item">
                                    	<div class="label"><?php echo $lang['admin_name'];?></div>
                                        <div class="value"><input type="text" name="js-admin-name" class="text" value="" /></div>
                                    </div>
                                    <div class="item">
                                    	<div class="label"><?php echo $lang['admin_password'];?></div>
                                        <div class="value">
                                        <input type="password" name="js-admin-password" autocomplete="off" class="text" value="" />
                                        <span id="js-admin-password-result"></span>
                                        </div>
                                        <div class="pwd-strength weak" id="Safety_style">
                                        	<span class="pwd-strength-weak"><?php echo $lang['pwd_lower'];?></span>
                                            <span class="pwd-strength-middle"><?php echo $lang['pwd_middle'];?></span>
                                            <span class="pwd-strength-strong"><?php echo $lang['pwd_high'];?></span>
                                        </div>
                                    </div>
                                    <div class="item">
                                    	<div class="label"><?php echo $lang['admin_password2'];?></div>
                                        <div class="value">
                                        <input type="password" name="js-admin-password2" autocomplete="off" class="text" value="" />
                                        <span id="js-admin-confirmpassword-result"></span>
                                        </div>
                                    </div>
                                    <div class="item">
                                    	<div class="label"><?php echo $lang['admin_email'];?></div>
                                        <div class="value"><input type="text" name="js-admin-email" class="text" value="" /></div>
                                    </div>
                                </div>
                            </div>
                            <div class="item last">
                                <div class="title"><h1><?php echo $lang['mix_options'];?></h1></div>
                                <div class="list">
                                    <div class="item">
                                    	<div class="label"><?php echo $lang['select_lang_package'];?></div>
                                        <div class="value">
                                        	<?php if (EC_CHARSET == 'gbk') {
    ?>
                                        	<div class="item-item">
                                            	<input type="radio" name="js-system-lang" id="js-system-lang-zh_cn" value="zh_cn" checked='true'/>
                                                <label for="chinese"><?php echo $lang['simplified_chinese']; ?></label>
                                                <label class="yellow" for="yzm">(<?php echo $lang['current_version_lang']; ?>)</label>
                                            </div>
                                            <?php
} elseif (EC_CHARSET == 'utf-8') {
        ?>
                                            <div class="item-item">
                                            	<input type="radio" name="js-system-lang" id="js-system-lang-zh_cn" value="zh_cn"/>
                                                <label for="chinese"><?php echo $lang['simplified_chinese']; ?></label>
                                                <label class="yellow" for="yzm">(<?php echo $lang['current_version_lang']; ?>)</label>
                                            </div>
                                            <div class="item-item" style="display:none;">
                                            	<input type="radio" name="js-system-lang" disabled id="js-system-lang-zh_tw" value="zh_tw" />
                                                <label for="tchinese"><?php echo $lang['traditional_chinese']; ?></label>
                                            </div>
                                            <div class="item-item" style="display:none;">
                                            	<input type="radio" name="js-system-lang" disabled id="js-system-lang-en_us" value="en_us" />
                                                <label for="english"><?php echo $lang['americanese']; ?></label>
                                            </div>
                                            <?php
    } elseif (EC_CHARSET == 'big5') {
        ?>
                                            <div class="item-item" style="display:none;">
                                            	<input type="radio" name="js-system-lang" disabled id="js-system-lang-zh_tw" value="zh_tw" checked='true' />
                                                <label for="tchinese"><?php echo $lang['traditional_chinese']; ?></label>
                                            </div>
                                            <?php
    } ?>
                                        </div>
                                    </div>
                                    <?php if ($show_timezone == "yes"):?>
                                    <div class="item">
                                    	<div class="label"><?php echo $lang['set_timezone'];?></div>
                                        <div class="value">
                                        	<select name="js-timezones" class="text">
                                            <?php foreach ($timezones as $key => $item): ?>
                                                        <option value="<?php echo $key;?>" <?php if ($key == $local_timezone):?>selected="true"<?php $found = true;endif;?>><?php echo $item;?></option>
                                            <?php endforeach; ?>
                                            <?php if (!$found) :?>
                                                        <option value="<?php echo $local_timezone;?>" selected="true"><?php echo $local_timezone;?></option>
                                            <?php endif;?>
                                               </select>
                                        </div>
                                    </div>
                                    <?php endif;?>
                                    <div class="item" style="display:none">
                                    	<div class="label"><?php echo $lang['disable_captcha'];?></div>
                                        <div class="value">
                                        	<div class="check-item">
                                                <input type="checkbox" id="yzm" name="js-disable-captcha" <?php echo $checked . $disabled;?> />
                                                <label class="yellow" for="yzm">(<?php echo $lang['captcha_notice'];?>)</label>
                                            </div>
                                        </div>
                                    </div>
                                    <?php
                                    if ($is_path == 1) {
                                        ?>
                                    <div class="item" style="display: none;">
                                    	<div class="label"><?php echo $lang['install_demo']; ?></div>
                                        <div class="value">
                                        	<div class="check-item">
                                        	<input type="checkbox" name="js-install-demo" id="testData" checked />
                                            <label class="yellow" for="testData">(<?php echo $lang['demo_notice']; ?>)</label>
                                            </div>
                                        </div>
                                    </div>
                                    <?php
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                        <div class="tfoot">
                            <div class="tfoot_right">
                            <input type="button" id="js-pre-step" class="btn" value="<?php echo $lang['prev_step'] . $lang['check_system_environment'];?>" />
                            <input id="js-install-at-once" type="submit" class="btn last btn-curr" value="<?php echo $lang['install_at_once'];?>" />
                            </div>
                            <input name="userinterface" type="hidden" value="<?php echo $userinterface; ?>" />
                            <input name="ucapi" type="hidden" value="<?php echo $ucapi; ?>" />
                            <input name="ucfounderpw" type="hidden" value="<?php echo $ucfounderpw; ?>" />
                        </div>

                    </form>
