<?php
ob_start();
phpinfo();
$info = ob_get_clean();
echo preg_replace('#\<style.*\</style\>#ims', '<link href="../iv-admin/assets/css/imagevue.admin.css" media="all" rel="stylesheet" type="text/css" />', $info);
?>