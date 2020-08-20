<?php defined('SYSTEM_INIT') or die('Invalid Usage.');  ?>
<div class="sectionhead">
<h4></h4>
    <div class="section__toolbar">                                  
    <?php if ($canEdit) { ?>
        <a href="javascript:void(0);" onClick="categoryForm(0);" title="<?php echo  Labels::getLabel('LBL_Add_Category', $adminLangId); ?>" class="btn-clean btn-sm btn-icon btn-secondary"><i class="fas fa-plus"></i></a>
    <?php }?>
    </div>
</div>
<div class="sectionbody space">
    <div class="accordion-categories">
    
        <?php 
        if (count($arr_listing) > 0) {
        ?>
        <ul id="sorting-categories" class="sorting-categories">
            <?php foreach ($arr_listing as $sn => $row) {  ?>
            <li id="<?php echo $row['prodcat_id'];?>" class="sortableListsClosed <?php if($row['subcategory_count'] == 0 ) { ?>no-children<?php } ?>">
                <div>
                    <div class="sorting-bar">
                        <div class="sorting-title"><span><?php echo $row['prodcat_identifier']; ?></span> <a href="<?php echo commonHelper::generateUrl('Products', 'index', array($row['prodcat_id'])); ?>" class="badge badge-secondary badge-pill clickable" title="<?php echo  Labels::getLabel('LBL_Category_Products', $adminLangId); ?>"><?php echo $row['category_products']; ?></a></div>
                        <div class="sorting-actions">
                            <?php
                            $active = "";
                            if ($row['prodcat_active']) {
                                $active = 'checked';
                            }
                            $statusAct = ($canEdit === true) ? 'toggleStatus(event,this,' .applicationConstants::YES. ')' : 'toggleStatus(event,this,' .applicationConstants::NO. ')';
                            $statusClass = ($canEdit === false) ? 'disabled' : '';
                            ?>
                            <label class="statustab statustab-sm">
                                <input <?php echo $active; ?> type="checkbox" id="switch<?php echo $row['prodcat_id'];?>" value="<?php echo $row['prodcat_id'];?>" onclick="<?php echo $statusAct;?>" class="switch-labels"/>
                                <i class="switch-handles <?php echo $statusClass;?> clickable"></i>
                            </label>
                            <?php if ($canEdit) { ?>
                            <button onClick="goToProduct(<?php echo $row['prodcat_id']; ?>)" title="<?php echo  Labels::getLabel('LBL_Add_Product', $adminLangId); ?>" class="btn btn-clean btn-sm btn-icon clickable"><i class="fas fa-plus clickable"></i></button>
                            <a href="<?php echo commonHelper::generateUrl('ProductCategories', 'form', array($row['prodcat_id'])); ?>">
                            <button  href="<?php echo commonHelper::generateUrl('ProductCategories', 'form', array($row['prodcat_id'])); ?>" title="<?php echo  Labels::getLabel('LBL_Edit', $adminLangId); ?>" class="btn btn-clean btn-sm btn-icon clickable"><i class="far fa-edit clickable"></i></button>
                            </a>
                            <button title="<?php echo  Labels::getLabel('LBL_Delete', $adminLangId); ?>" onclick = "deleteRecord(<?php echo $row['prodcat_id']; ?>)" class="btn btn-clean btn-sm btn-icon clickable"><i class="fa fa-trash clickable"></i></button>
                            <?php } ?>
                        </div>
                    </div>
                    <?php if($row['subcategory_count'] > 0 ) { ?>
                    <span class="sortableListsOpener" ><i class="fa fa-plus clickable sort-icon" onClick="displaySubCategories(this)"></i></span>
                    <?php } ?>
                </div>
            </li>
            <?php } ?>
        </ul>
        <?php }else{ 
                $this->includeTemplate('_partial/no-record-found.php', array('adminLangId'=>$adminLangId));
            } 
        ?>
    </div>
</div>

<script type="text/javascript">
 $(function() {
     var optionsPlus = {
         insertZone: 0,
         insertZonePlus: true,
         placeholderCss: {
             'background-color': '#e5f5ff',
         },
         hintCss: {
             'background-color': '#6dc5ff'
         },
         baseCss: {
            'list-style-type': 'none',
         },
         onDragStart: function( e, cEl ) { 
            var catId =  $( cEl ).attr('id');
            $("#"+catId).children().children().children('.sorting-title').css('margin-left', '25px');
            $("#"+catId).children('ul').css('list-style-type', 'none');
         },
         complete: function( cEl ) { 
            var catId =  $( cEl ).attr('id');
            $("#"+catId).children().children().children('.sorting-title').css('margin-left', '0px');           
         },
         onChange: function(cEl)
         {
            $("#js-cat-section").addClass('overlay-blur');
            var catId =  $( cEl ).attr('id'); 
            var parentCatId = $( cEl ).parent('ul').parent('li').attr('id');
            var catOrder = [] ;               
            $($( cEl ).parent().children()).each(function(i){               
                catOrder[i+1] = $(this).attr('id');
            });
            var data = "catId="+catId+"&parentCatId="+parentCatId+"&catOrder="+JSON.stringify(catOrder);

            if(typeof parentCatId != 'undefined') {
                displaySubCategories(cEl, parentCatId, data);
                $(cEl).parents('li').each(function() {                    
                    var rootCat = $(this).attr('id');
                    $("#"+rootCat).children( 'div' ).children( '.sortableListsOpener' ).remove();
                    $("#"+rootCat).removeClass('sortableListsClosed').addClass('sortableListsOpen');
                    $("#"+rootCat).children( 'div' ).append('<span class="sortableListsOpener" ><i class="fa fa-minus clickable sort-icon" onClick="hideItems(this)"></i></span>');  
                });
                $("#"+catId).parent('ul').addClass('append-ul');
            }else{
                updateCatOrder(data);
            }            
         },
         opener: {
             active: true,
             as: 'html', // if as is not set plugin uses background image
             close: '<i class="fa fa-minus clickable sort-icon" onClick="hideItems(this)"></i>',
             open: '<i class="fa fa-plus c3 clickable sort-icon" onClick="displaySubCategories(this)"></i>',
             openerCss: {
             }
         },
         ignoreClass: 'clickable'
     };

     $('#sorting-categories').sortableLists(optionsPlus);

 });
</script>

