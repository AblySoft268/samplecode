<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>
</div>
<!--footer start here-->	
<footer id="footer">
    <p>
        <?php
        if (CommonHelper::demoUrl()) {
            $replacements = array(
                '{YEAR}' => '&copy; ' . date("Y"),
                '{PRODUCT}' => '<a target="_blank" href="javascript:void(0)">Product Name</a>',
                '{OWNER}' => '<a target="_blank" href="https://www.fatbit.com/">FATbit Technologies</a>',
            );
            echo CommonHelper::replaceStringData(Labels::getLabel('LBL_COPYRIGHT_TEXT', $adminLangId), $replacements);
        } else {
            echo FatApp::getConfig("CONF_WEBSITE_NAME_" . $adminLangId, FatUtility::VAR_STRING, 'Copyright &copy; ' . date('Y') . ' <a href="https://www.fatbit.com/">FATbit.com');
            ?> 
        <?php
        }
        echo " " . CONF_WEB_APP_VERSION;
        ?>
    </p>

</footer>
<!--footer start here-->
</div>

<?php
$alertClass = '';
if (Message::getInfoCount() > 0)
    $alertClass = 'alert--info';
elseif (Message::getErrorCount() > 0)
    $alertClass = 'alert--danger';
elseif (Message::getMessageCount() > 0)
    $alertClass = 'alert--success';
?>

<div class="system_message alert alert--positioned-bottom-center alert--positioned-small <?php echo $alertClass; ?>">
    <div class="close"></div>
    <div class="sysmsgcontent content">
<?php
$haveMsg = false;
if (Message::getMessageCount() || Message::getErrorCount()) {
    $haveMsg = true;
    echo html_entity_decode(Message::getHtml());
}
?>
    </div>
</div>

<?php if ($haveMsg) { ?>
    <script type="text/javascript">
        $("document").ready(function () {
            if (CONF_AUTO_CLOSE_SYSTEM_MESSAGES == 1) {
                var time = CONF_TIME_AUTO_CLOSE_SYSTEM_MESSAGES * 1000;
                setTimeout(function () {
                    $.systemMessage.close();
                }, time);
            }
        });
    </script>
<?php } ?>

<!--wrapper end here-->

<?php
if (CommonHelper::demoUrl()) {
    if (FatApp::getConfig('CONF_SITE_TRACKER_CODE', FatUtility::VAR_STRING, '')) {
        echo FatApp::getConfig('CONF_SITE_TRACKER_CODE', FatUtility::VAR_STRING, '');
    }
    if (FatApp::getConfig('CONF_AUTO_RESTORE_ON', FatUtility::VAR_INT, 1) && CommonHelper::demoUrl()) {
        $this->includeTemplate('restore-system/page-content.php');
    }
}
?>
<?php if (FatApp::getConfig('CONF_PWA_SERVICE_WORKER', FatUtility::VAR_INT, 1)) { ?>
    <script>
        $(document).ready(function () {
            if ('serviceWorker' in navigator) {
                window.addEventListener('load', function () {
                    navigator.serviceWorker.register('<?php echo CONF_WEBROOT_FRONTEND; ?>sw.js?t=<?php echo filemtime(CONF_INSTALLATION_PATH . 'public/sw.js'); ?>&f').then(function (registration) {
                    });
                });
            }
        });
    </script>
<?php } ?>
</body>
</html>
