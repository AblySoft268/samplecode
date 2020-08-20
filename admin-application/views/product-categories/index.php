<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>
<div class='page' id="js-cat-section">
    <div class='container container-fluid'>
        <div class="row">
            <div class="col-lg-12 col-md-12 space">
                <div class="page__title">
                    <div class="row">
                        <div class="col--first col-lg-6">
                            <span class="page__icon"><i class="ion-android-star"></i></span>
                            <h5><?php echo Labels::getLabel('LBL_Categories', $adminLangId); ?> </h5>
                            <?php $this->includeTemplate('_partial/header/header-breadcrumb.php'); ?>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-9">
                        <section class="section  section-height" id="listing">
                        </section>
                    </div>
                    <div class="col-md-3">
                        <section class="section">
                            <div class="sectionhead">
                                <h4><?php echo Labels::getLabel('LBL_Total_', $adminLangId); ?></h4>
                            </div>
                            <div class="sectionbody" id="total-block">
                            </div>
                        </section>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
