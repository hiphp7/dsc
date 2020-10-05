<script type="text/javascript" src="__PUBLIC__/install/js/check.js"></script>
<form action="./" method="post">
    <div class="install_file">
        <div class="item">
            <div class="title"><h1><?php echo $lang['system_environment']; ?></h1></div>
            <div class="list">
                <ul>
                    <?php foreach ($system_info as $info_item): ?>
                        <li>
                            <div class="list-left"><?php echo $info_item[0]; ?></div>
                            <div class="list-right"><?php echo $info_item[1]; ?></div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <div class="item">
            <div class="title"><h1><?php echo $lang['dir_priv_checking']; ?></h1></div>
            <div class="list">
                <ul>
                    <?php foreach ($dir_checking as $checking_item): ?>
                        <li>
                            <div class="list-left"><?php echo $checking_item[0]; ?></div>
                            <div class="list-right green">
                                <?php if ($checking_item[1] == $lang['can_write']): ?>
                                    <?php echo $checking_item[1]; ?>
                                <?php else: ?>
                                    <?php echo $checking_item[1]; ?>
                                <?php endif; ?>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <div class="item <?php if (empty($rename_priv)) : ?> last <?php endif; ?>">
            <div class="title"><h1><?php echo $lang['template_writable_checking']; ?></h1></div>
            <div class="list">
                <?php if ($has_unwritable_tpl == "yes"): ?>
                    <?php foreach ($template_checking as $checking_item): ?>
                        <p style="color:red;"><?php echo $checking_item; ?></p>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="green"><?php echo $template_checking; ?></p>
                <?php endif; ?>
            </div>
        </div>
        <?php if (!empty($rename_priv)) : ?>
            <div class="item last">
                <div class="title"><h1><?php echo $lang['rename_priv_checking']; ?></h1></div>
                <div class="list">
                    <?php foreach ($rename_priv as $checking_item): ?>
                        <p style="color:red;"><?php echo $checking_item; ?></p>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <div class="tfoot">
        <div class="tfoot_left">
            <input type="button" class="btn aga" id="js-recheck" value="<?php echo $lang['recheck']; ?>"/>
        </div>
        <div class="tfoot_right">
            <input type="button" class="btn" id="js-pre-step"
                   value="<?php echo $lang['prev_step']; ?><?php echo $lang['welcome_page']; ?>"/>
            <input type="submit" class="btn last btn-curr" id="js-submit"
                   value="<?php echo $lang['next_step'] . $lang['config_system']; ?>" <?php echo $disabled; ?> />
        </div>
        <input name="userinterface" id="userinterface" type="hidden" value="<?php echo $userinterface; ?>"/>
        <input name="ucapi" type="hidden" value="<?php echo $ucapi; ?>"/>
        <input name="ucfounderpw" type="hidden" value="<?php echo $ucfounderpw; ?>"/>
    </div>
</form>