$(document).ready(function () {
    searchProductCategories();
    getTotalBlock();
});

(function () {
    var currentPage = 1;
    var runningAjaxReq = false;
    var dv = "#listing";

    searchProductCategories = function () {
        var data = '';
        fcom.ajax(fcom.makeUrl('productCategories', 'search'), data, function (res) {
            $(dv).html(res);
        });
    };

    getTotalBlock = function () {
        var data = '';
        fcom.ajax(fcom.makeUrl('productCategories', 'getTotalBlock'), data, function (res) {
            $("#total-block").html(res);
        });
    };

    setupCategory = function () {
        var frm = $('#frmProdCategory');
        var validator = $(frm).validation({errordisplay: 3});
        if (validator.validate() == false) {
            return false;
        }
        if (!$(frm).validate()) {
            return false;
        }
        var data = fcom.frmData(frm);
        fcom.updateWithAjax(fcom.makeUrl('ProductCategories', 'setup'), data, function (t) {
            if (t.status == 1) {
                searchProductCategories();
                getTotalBlock();
            }
        });
    };

    discardForm = function () {
        searchProductCategories();
        getTotalBlock();
    }

    deleteRecord = function (id) {
        if (!confirm(langLbl.confirmDelete)) {
            return;
        }
        data = 'id=' + id;
        fcom.ajax(fcom.makeUrl('productCategories', 'deleteRecord'), data, function (res) {
            var ans = $.parseJSON(res);
            if (ans.status == 1) {
                fcom.displaySuccessMessage(ans.msg);
                searchProductCategories();
                getTotalBlock();
            } else {
                fcom.displayErrorMessage(ans.msg);
            }
        });
    };

    toggleStatus = function (e, obj, canEdit) {
        if (canEdit == 0) {
            e.preventDefault();
            return;
        }
        if (!confirm(langLbl.confirmUpdateStatus)) {
            e.preventDefault();
            return;
        }
        var prodCatId = parseInt(obj.value);
        if (prodCatId < 1) {
            fcom.displayErrorMessage(langLbl.invalidRequest);
            return false;
        }
        data = 'prodCatId=' + prodCatId;
        fcom.displayProcessing();
        fcom.ajax(fcom.makeUrl('productCategories', 'changeStatus'), data, function (res) {
            var ans = $.parseJSON(res);
            if (ans.status == 1) {
                $(obj).toggleClass("active");
                fcom.displaySuccessMessage(ans.msg);
                searchProductCategories();
                getTotalBlock();
            } else {
                fcom.displa(ans.msg);
            }
        });
        $.systemMessage.close();
    };

    updateCatOrder = function (data) {
        fcom.updateWithAjax(fcom.makeUrl('productCategories', 'updateOrder'), data, function (res) {
            $("#js-cat-section").removeClass('overlay-blur');
        });
    }

    categoryImages = function (prodCatId, imageType, slide_screen, lang_id) {
        fcom.ajax(fcom.makeUrl('ProductCategories', 'images', [prodCatId, imageType, lang_id, slide_screen]), '', function (t) {
            if (imageType == 'icon') {
                $('#icon-image-listing').html(t);
                var prodCatId = $("[name='prodcat_id']").val();
                if (prodCatId == 0) {
                    var iconImageId = $("#icon-image-listing li").attr('id');
                    var selectedLangId = $(".icon-language-js").val();
                    $("[name='cat_icon_image_id[" + selectedLangId + "]']").val(iconImageId);
                }
            } else if (imageType == 'banner') {
                $('#banner-image-listing').html(t);
                var bannerImageId = $("#banner-image-listing li").attr('id');
                var selectedLangId = $(".banner-language-js").val();
                var screen = $(".prefDimensions-js").val();
                $("[name='cat_banner_image_id[" + selectedLangId + "_" + screen + "]']").val(bannerImageId);
            }
        });
    };

    deleteImage = function (fileId, prodcatId, imageType, langId, slide_screen) {
        if (!confirm(langLbl.confirmDeleteImage)) {
            return;
        }
        fcom.updateWithAjax(fcom.makeUrl('productCategories', 'removeImage', [fileId, prodcatId, imageType, langId, slide_screen]), '', function (t) {
            //categoryImages( prodcatId, imageType, slide_screen, langId );
            if (imageType == 'icon') {
                $("#icon-image-listing").html('');
                $("[name='cat_icon_image_id[" + langId + "]']").val('');
            } else if (imageType == 'banner') {
                $("#banner-image-listing").html('');
                $("[name='cat_banner_image_id[" + langId + "_" + slide_screen + "]']").val('');
            }
        });
    };