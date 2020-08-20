<div class="form-side-inner">
    <div class="section-head">
        <div class="section__heading otp-heading">
            <h2>
                <?php echo Labels::getLabel('LBL_Sign_Up', $siteLangId);?>
               
            </h2>
             
                    <?php if (isset($registerdata['signUpWithPhone']) && true === $smsPluginStatus) {
                            if (0 == $registerdata['signUpWithPhone']) { ?>
                                <a  class="otp-link"  href="javaScript:void(0)" onClick="signUpWithPhone()"><?php echo Labels::getLabel('LBL_USE_PHONE_NUMBER_INSTEAD', $siteLangId); ?></a>
                            <?php } else { ?>
                                <a  class="otp-link" href="javaScript:void(0)" onClick="signUpWithEmail()"><?php echo Labels::getLabel('LBL_USE_EMAIL_INSTEAD', $siteLangId); ?></a>
                            <?php } ?>
                    <?php } ?>
                
        </div>
    </div>
    <?php $this->includeTemplate('guest-user/registerationFormTemplate.php', $registerdata, false); ?>
</div>