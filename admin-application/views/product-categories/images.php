<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>
<?php if (!empty($images)) { ?>
<ul class="mt-0" id="<?php if ($canEdit) { ?>sortable<?php } ?>">
    <li id="<?php echo $images['afile_id']; ?>">
        <div class="logoWrap">
            <div class="logothumb"> <img src="<?php echo CommonHelper::generateUrl('Category', $imageFunction, array($images['afile_record_id'], $images['afile_lang_id'], "THUMB", $images['afile_screen']), CONF_WEBROOT_FRONT_URL); ?>"
                    title="<?php echo $images['afile_name'];?>" alt="<?php echo $images['afile_name'];?>"> <?php if ($canEdit) { ?> <a class="deleteLink white" href="javascript:void(0);" title="Delete <?php echo $images['afile_name'];?>"
                    onclick="deleteImage(<?php echo $images['afile_id']; ?>, <?php echo $images['afile_record_id']; ?>, '<?php echo $imageType; ?>', <?php echo $images['afile_lang_id']; ?>, <?php echo $images['afile_screen']; ?> );" class="delete"><i class="ion-close-round"></i></a>
                <?php } ?>
            </div>
            <?php if (isset($imgTypesArr) && !empty($imgTypesArr[$images['afile_record_subid']])) {
            echo '<small class=""><strong>'.Labels::getLabel('LBL_Type', $adminLangId).': </strong> '.$imgTypesArr[$images['afile_record_subid']].'</small><br/>';
            }

                        $lang_name = Labels::getLabel('LBL_All', $adminLangId);
                        if ($images['afile_lang_id'] > 0) {
                            $lang_name = $languages[$images['afile_lang_id']]; ?>
            <?php
                        } ?>
            <small class="text--small"><?php echo Labels::getLabel('LBL_Language', $adminLangId); ?>: <?php echo $lang_name; ?></small>
        </div>
    </li>
</ul>
<?php }    ?>
