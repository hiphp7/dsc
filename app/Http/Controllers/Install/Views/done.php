<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title><?php echo $lang['install_done_title'];?></title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<link href="__PUBLIC__/install/styles/install.css" rel="stylesheet" type="text/css" />
<script type="text/javascript" src="__PUBLIC__/install/js/common.js"></script>
<script type="text/javascript" src="__PUBLIC__/install/js/transport.js"></script>
</head>

<body>
<?php include VIEWS_PATH . 'header.php';?>
    <div class="wrapper">
    	<div class="w1000" id="content">
        	
        </div>
        <div class="footer">
            <?php include VIEWS_PATH . 'copyright.php';?>
        </div>
    </div>
<script type="text/javascript">
Ajax.call('install/cloud?step=done','', welcome_api, 'GET', 'TEXT','FLASE');
function welcome_api(result)
{
  if(result)
  {
    setInnerHTML('content',result);
  }
}
</script>

</body>
</html>