<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$prodCatLangFrm->setFormTagAttribute('id', 'prodCate');
$prodCatLangFrm->setFormTagAttribute('class', 'web_form form_horizontal layout--'.$formLayout);

$prodCatLangFrm->developerTags['colClassPrefix'] = 'col-md-';
$prodCatLangFrm->developerTags['fld_default_col'] = 12;

$langFld = $prodCatLangFrm->getField('lang_id');
$langFld->setfieldTagAttribute('onChange', "categoryLangForm(" . $prodcat_id . ", this.value);");
?>
<section class="section">
    <div class="sectionhead">

        <h4><?php echo Labels::getLabel('LBL_Product_Categories_Setup', $adminLangId); ?>
        </h4>
    </div>
    <div class="sectionbody space">
        <div class="col-sm-12">

            <div class="row">
                <div class="tabs_nav_container responsive flat">
                    <ul class="tabs_nav">
                        <li><a href="javascript:void(0);"
                                onclick="categoryForm(<?php echo $prodcat_id ?>);"><?php echo Labels::getLabel('LBL_General', $adminLangId); ?></a>
                        </li>
                        <li class="<?php echo (0 == $prodcat_id) ? 'fat-inactive' : ''; ?>">
                            <a class="active" href="javascript:void(0);">
                                <?php echo Labels::getLabel('LBL_Language_Data', $adminLangId); ?>
                            </a>
                        </li>
                        <li class="<?php echo (!$prodcat_id) ? 'fat-inactive' : ''; ?>">
                            <a href="javascript:void(0);" <?php if ($prodcat_id > 0) { ?>
                                onclick="categoryMediaForm(<?php echo $prodcat_id ?>);" <?php }?>>
                                <?php echo Labels::getLabel('LBL_Media', $adminLangId); ?>
                            </a>
                        </li>
                    </ul>
                    <div class="tabs_panel_wrap">
                        <?php 
                        $translatorSubscriptionKey = FatApp::getConfig('CONF_TRANSLATOR_SUBSCRIPTION_KEY', FatUtility::VAR_STRING, '');
                        $siteDefaultLangId = FatApp::getConfig('conf_default_site_lang', FatUtility::VAR_INT, 1);
                        if (!empty($translatorSubscriptionKey) && $prodcat_lang_id != $siteDefaultLangId) { ?> 
                            <div class="row justify-content-end"> 
                                <div class="col-auto mb-4">
                                    <input class="btn btn-primary" 
                                        type="button" 
                                        value="<?php echo Labels::getLabel('LBL_AUTOFILL_LANGUAGE_DATA', $adminLangId); ?>" 
                                        onClick="categoryLangForm(<?php echo $prodcat_id; ?>, <?php echo $prodcat_lang_id; ?>, 1)">
                                </div>
                            </div>
                        <?php } ?>
                        <div class="tabs_panel">
                            <?php echo $prodCatLangFrm->getFormHtml(); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>