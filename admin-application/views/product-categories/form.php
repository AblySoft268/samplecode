<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$siteDefaultLangId = FatApp::getConfig('conf_default_site_lang', FatUtility::VAR_INT, 1);
$prodCatFrm->setFormTagAttribute('class', 'web_form');
$prodCatFrm->setFormTagAttribute('id', 'frmProdCategory');
$prodCatFrm->setFormTagAttribute('onsubmit', 'setupCategory(); return(false);');

$statusFld = $prodCatFrm->getField('prodcat_active');

$statusFld->setOptionListTagAttribute('class', 'list-inline-checkboxes');
$statusFld->developerTags['rdLabelAttributes'] = array('class' => 'radio');
$statusFld->developerTags['rdHtmlAfterRadio'] = '<i class="input-helper"></i>';

$iconLangFld = $prodCatFrm->getField('icon_lang_id');
$iconLangFld->addFieldTagAttribute('class', 'icon-language-js');

$iconFld = $prodCatFrm->getField('cat_icon');
$iconFld->addFieldTagAttribute('class', 'btn btn--primary btn--sm');
$iconFld->addFieldTagAttribute('onChange', 'iconPopupImage(this)');
$iconFld->htmlAfterField = '<small class="text--small">' . sprintf(Labels::getLabel('LBL_This_will_be_displayed_in_%s_on_your_store', $adminLangId), '60*60') . '</small>';

$bannerFld = $prodCatFrm->getField('cat_banner');
$bannerFld->addFieldTagAttribute('class', 'btn btn--primary btn--sm');
$bannerFld->addFieldTagAttribute('onChange', 'bannerPopupImage(this)');
$bannerFld->htmlAfterField = '<small class="text--small" class="preferredDimensions-js">' . sprintf(Labels::getLabel('LBL_Preferred_Dimensions_%s', $adminLangId), '2000 x 500') . '</small>';

$bannerLangFld = $prodCatFrm->getField('banner_lang_id');
$bannerLangFld->addFieldTagAttribute('class', 'banner-language-js');

$screenFld = $prodCatFrm->getField('slide_screen');
$screenFld->addFieldTagAttribute('class', 'prefDimensions-js');

$btn = $prodCatFrm->getField('btn_submit');
$btn->setFieldTagAttribute('class', "btn-clean btn-sm btn-icon btn-secondary");

$fld = $prodCatFrm->getField('auto_update_other_langs_data');
$fld->developerTags['cbLabelAttributes'] = array('class' => 'checkbox');
$fld->developerTags['cbHtmlAfterCheckbox'] = '<i class="input-helper"></i>';
?>

<div class="tabs_nav_container wizard-tabs-horizontal">                    

    <ul class="tabs_nav">
        <li>
            <a class="active tabs_001" rel="tabs_001" href="javascript:void(0)">
                <div class="tabs-head">
                    <div class="tabs-title"><?php echo Labels::getLabel('LBL_General', $adminLangId); ?><span><?php echo Labels::getLabel('LBL_General', $adminLangId); ?></span></div>
                </div>
            </a>
        </li>
        <li>
            <a rel="tabs_002" class="tabs_002" href="javascript:void(0)"> 
                <div class="tabs-head">
                    <div class="tabs-title"><?php echo Labels::getLabel('LBL_Custom_fields', $adminLangId); ?><span><?php echo Labels::getLabel('LBL_Add_custom_fields', $adminLangId); ?></span></div>
                </div>
            </a>
        </li>
    </ul>
    <div class="tabs_panel_wrap">
        <div id="tabs_001" class="tabs_panel" style="display: block;">
            <?php echo $prodCatFrm->getFormTag(); ?>
            <div class="sectionhead">
                <h4></h4>
                <div class="section__toolbar">
                    <a href="<?php echo commonHelper::generateUrl('ProductCategories'); ?>">
                    <?php echo $prodCatFrm->getFieldHtml('btn_discard'); ?>
                    </a>
                    <?php echo $prodCatFrm->getFieldHtml('btn_submit'); ?>
                </div>
            </div>
            <div class="sectionbody space">

                <div class="row">
                    <div class="col-lg-12 col-md-12">
                        <div class="row justify-content-center">
                            <div class="col-md-9">
                                <h3 class="form__heading"><?php echo Labels::getLabel('LBL_General', $adminLangId); ?></h3>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="field-set">
                                            <div class="caption-wraper">
                                                <label class="field_label">
                                                    <?php
                                                    $fld = $prodCatFrm->getField('prodcat_name[' . $siteDefaultLangId . ']');
                                                    echo $fld->getCaption();
                                                    ?>
                                                    <span class="spn_must_field">*</span></label>
                                            </div>
                                            <div class="field-wraper">
                                                <div class="field_cover">
                                                    <?php echo $prodCatFrm->getFieldHtml('prodcat_name[' . $siteDefaultLangId . ']'); ?>
                                                    <?php echo $prodCatFrm->getFieldHtml('prodcat_id'); ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="field-set">
                                            <div class="caption-wraper">
                                                <label class="field_label">
                                                    <?php
                                                    $fld = $prodCatFrm->getField('prodcat_parent');
                                                    echo $fld->getCaption();
                                                    ?></label>
                                            </div>
                                            <div class="field-wraper">
                                                <div class="field_cover">
                                                    <?php echo $prodCatFrm->getFieldHtml('prodcat_parent'); ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="field-set d-flex align-items-center">
                                            <div class="caption-wraper w-auto pr-4">
                                                <label class="field_label">
                                                    <?php
                                                    $fld = $prodCatFrm->getField('prodcat_active');
                                                    echo $fld->getCaption();
                                                    ?></label>
                                            </div>
                                            <div class="field-wraper w-auto">
                                                <div class="field_cover">
                                                    <?php echo $prodCatFrm->getFieldHtml('prodcat_active'); ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php
                                    $translatorSubscriptionKey = FatApp::getConfig('CONF_TRANSLATOR_SUBSCRIPTION_KEY', FatUtility::VAR_STRING, '');
                                    if (!empty($translatorSubscriptionKey) && count($otherLangData) > 0) {
                                        ?>
                                        <div class="col-md-6">
                                            <div class="field-set">
                                        <?php echo $prodCatFrm->getFieldHtml('auto_update_other_langs_data'); ?>
                                            </div>
                                        </div>
                                        <?php } ?>
                                </div>
                                <div class="p-4 mb-4 border rounded">
                                    <h3 class="mb-4"><?php echo Labels::getLabel('LBL_Icon', $adminLangId); ?></h3>
                                    <div class="row">
                                        <div class="col-md-3">
                                            <div class="field-set">
                                                <div class="caption-wraper"><label class="field_label">
                                                    <?php
                                                    $fld = $prodCatFrm->getField('icon_lang_id');
                                                    echo $fld->getCaption();
                                                    ?>
                                                    </label></div>
                                                <div class="field-wraper">
                                                    <div class="field_cover">
                                                        <?php echo $prodCatFrm->getFieldHtml('icon_lang_id'); ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-7">
                                            <div class="field-set">
                                                <div class="caption-wraper">
                                                    <label class="field_label"></label>
                                                </div>
                                                <div class="field-wraper">
                                                    <div class="field_cover">
                                                        <?php echo $prodCatFrm->getFieldHtml('icon_file_type');
                                                        echo $prodCatFrm->getFieldHtml('cat_icon');
                                                        ?>
                                                        <?php
                                                        foreach ($mediaLanguages as $key => $data) {
                                                            echo $prodCatFrm->getFieldHtml('cat_icon_image_id[' . $key . ']');
                                                        }
                                                        ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-2" id="icon-image-listing"></div>
                                    </div>
                                    <div class="divider"></div>
                                    <h3 class="mb-4"><?php echo Labels::getLabel('LBL_Banner', $adminLangId); ?></h3>
                                    <div class="row">
                                        <div class="col-md-3">
                                            <div class="field-set">
                                                <div class="caption-wraper"><label class="field_label">
                                                        <?php
                                                        $fld = $prodCatFrm->getField('banner_lang_id');
                                                        echo $fld->getCaption();
                                                        ?>
                                                    </label></div>
                                                <div class="field-wraper">
                                                    <div class="field_cover">
                                                        <?php echo $prodCatFrm->getFieldHtml('banner_lang_id'); ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="field-set">
                                                <div class="caption-wraper"><label class="field_label">
                                                        <?php
                                                        $fld = $prodCatFrm->getField('slide_screen');
                                                        echo $fld->getCaption();
                                                        ?>
                                                    </label></div>
                                                <div class="field-wraper">
                                                    <div class="field_cover">
                                                        <?php echo $prodCatFrm->getFieldHtml('slide_screen'); ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-5">
                                            <div class="field-set">
                                                <div class="caption-wraper"><label class="field_label">
                                                    </label></div>
                                                <div class="field-wraper">
                                                    <div class="field_cover">
                                                        <?php echo $prodCatFrm->getFieldHtml('banner_file_type');
                                                        echo $prodCatFrm->getFieldHtml('cat_banner');
                                                        ?>
                                                        <?php
                                                        foreach ($mediaLanguages as $key => $data) {
                                                            foreach ($screenArr as $key1 => $screen) {
                                                                echo $prodCatFrm->getFieldHtml('cat_banner_image_id[' . $key . '_' . $key1 . ']');
                                                            }
                                                        }
                                                        ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-2" id="banner-image-listing"></div>
                                    </div>
                                </div>
                                    <?php
                                    if (!empty($otherLangData)) {
                                        foreach ($otherLangData as $langId => $data) {
                                            ?>
                                        <div class="accordians_container accordians_container-categories" defaultLang= "<?php echo $siteDefaultLangId; ?>" language="<?php echo $langId; ?>" id="accordion-language_<?php echo $langId; ?>" onClick="translateData(this)">
                                            <div class="accordian_panel">
                                                <span class="accordian_title accordianhead accordian_title" id="collapse_<?php echo $langId; ?>">
                                                                        <?php echo $data . " ";
                                                                        echo Labels::getLabel('LBL_Language_Data', $adminLangId); ?>
                                                </span>
                                                <div class="accordian_body accordiancontent" style="display: none;">
                                                    <div class="row">
                                                        <div class="col-md-12">
                                                            <div class="field-set">
                                                                <div class="caption-wraper">
                                                                    <label class="field_label">
                                                                        <?php $fld = $prodCatFrm->getField('prodcat_name[' . $langId . ']');
                                                                        echo $fld->getCaption();
                                                                        ?>
                                                                    </label>
                                                                </div>
                                                                <div class="field-wraper">
                                                                    <div class="field_cover">
                                                                        <?php echo $prodCatFrm->getFieldHtml('prodcat_name[' . $langId . ']'); ?>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <?php
                                        }
                                    }
                                    ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
                <?php
                echo $prodCatFrm->getFieldHtml('banner_min_width');
                echo $prodCatFrm->getFieldHtml('banner_min_height');
                echo $prodCatFrm->getFieldHtml('logo_min_width');
                echo $prodCatFrm->getFieldHtml('logo_min_height');
                ?>
            </form>
                <?php echo $prodCatFrm->getExternalJS(); ?>
        </div>
        <div id="tabs_002" class="tabs_panel" style="display: none;">
            <div class="row">
                <div class="col-md-7 mb-3 mb-md-0" id="custom-fields-listing-js">
                </div>
                <div class="col-md-5 mb-3 mb-md-0" id="custom-fields-form-js">
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    $('input[name=banner_min_width]').val(2000);
    $('input[name=banner_min_height]').val(500);
    $('input[name=logo_min_width]').val(150);
    $('input[name=logo_min_height]').val(150);
    var aspectRatio = 4 / 1;
    $(document).on('change', '.prefDimensions-js', function () {
        var screenDesktop = <?php echo applicationConstants::SCREEN_DESKTOP ?>;
        var screenIpad = <?php echo applicationConstants::SCREEN_IPAD ?>;

        if ($(this).val() == screenDesktop)
        {
            $('.preferredDimensions-js').html((langLbl.preferredDimensions).replace(/%s/g, '2000 x 500'));
            $('input[name=banner_min_width]').val(2000);
            $('input[name=banner_min_height]').val(500);
            aspectRatio = 4 / 1;
        } else if ($(this).val() == screenIpad)
        {
            $('.preferredDimensions-js').html((langLbl.preferredDimensions).replace(/%s/g, '1024 x 360'));
            $('input[name=banner_min_width]').val(1024);
            $('input[name=banner_min_height]').val(360);
            aspectRatio = 128 / 45;
        } else {
            $('.preferredDimensions-js').html((langLbl.preferredDimensions).replace(/%s/g, '640 x 360'));
            $('input[name=banner_min_width]').val(640);
            $('input[name=banner_min_height]').val(360);
            aspectRatio = 16 / 9;
        }
    });
</script>
