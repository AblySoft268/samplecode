
$(document).on('click', '.tabs_001', function () {
    var prodCatid = $("input[name='prodcat_id']").val();
    catInitialSetUpFrm(prodCatid);
});

$(document).on('click', '.tabs_002', function () {
    var prodCatid = $("input[name='prodcat_id']").val();
    if (prodCatid > 0) {
        categoryCustomFieldsForm(prodCatid);
    } else {
        catInitialSetUpFrm();
    }
});

catInitialSetUpFrm = function () {
    $(".tabs_panel").hide();
    $(".tabs_nav  > li > a").removeClass('active');
    $("#tabs_001").show();
    $("a[rel='tabs_001']").addClass('active');
}

categoryCustomFieldsForm = function (prodCatid) {
    var data = 'prodCategoryId=' + prodCatid;
    fcom.ajax(fcom.makeUrl('Attributes', 'form', []), data, function (res) {
        $(".tabs_panel").hide();
        $(".tabs_nav  > li > a").removeClass('active');
        $("#tabs_002").show();
        $("a[rel='tabs_002']").addClass('active');
        $("#custom-fields-form-js").html(res);

        var langId = $("input[name='lang_id']").val();
        prodCatAttributesByLangId(langId);

    });
};

setupAttr = function (frm) {
    if (!$(frm).validate())
        return;
    var data = fcom.frmData(frm);
    fcom.updateWithAjax(fcom.makeUrl('Attributes', 'setup'), data, function (t) {
        var catId = $("input[name='prodcat_id']").val();
        categoryCustomFieldsForm(catId);
    });
};

prodCatAttributesByLangId = function (langId) {
    var catId = $("input[name='prodcat_id']").val();
    var data = 'catId=' + catId + '&langId=' + langId;
    fcom.ajax(fcom.makeUrl('ProductCategories', 'getAttributes'), data, function (res) {
        $("#custom-fields-listing-js").html(res);
    });
};

editAttr = function (attrId) {
    var catId = $("input[name='prodcat_id']").val();
    var data = 'prodCategoryId=' + catId + '&attrId=' + attrId;
    fcom.ajax(fcom.makeUrl('Attributes', 'form', []), data, function (res) {
        $('#attr_form').html(res);
    });
};

deleteAttr = function (attrId) {
    var catId = $("input[name='prodcat_id']").val();
    var data = 'attrId=' + attrId + '&status=0';
    fcom.updateWithAjax(fcom.makeUrl('Attributes', 'changeStatus'), data, function (res) {
        categoryCustomFieldsForm(catId);
    });
};

$(document).on('change', '#attr-type-js', function() {
    var selOption = $('#attr-type-js').val();
    if (selOption == 3) { /* SHOW ONLY IF ATTRIBUTE TYPE IS SELECT BOX */
        $('.attr-options-js').show();
		$('.display-in-filter-field-js').show();
    } else {
        $('.attr-options-js').hide();
		$('.display-in-filter-field-js').hide();
    }
});

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
            fcom.displaySuccessMessage(t.msg);
        }
    });
};

translateAttributeData = function (item, defaultLang, toLangId) {
	console.log(defaultLang)
    var autoTranslate = $("input[name='auto_update_other_langs_data']:checked").length;
    var attr_name = $("input[name='attr_name[" + defaultLang + "]']").val();
    var attr_postfix = $("input[name='attr_postfix[" + defaultLang + "]']").val();
    var attrgrp_name = $("input[name='attrgrp_name[" + defaultLang + "]']").val();
	var attr_options = $("textarea[name='attr_options["+ defaultLang +"]']").val();
	if (attr_options == "undefined" || attr_options === undefined) {
		attr_options = "";
	}

    var alreadyOpen = $('#collapse_' + toLangId).hasClass('active');
    if (autoTranslate == 0 || attr_name == "" || alreadyOpen == true) {
        return false;
    }

    var data = "attr_name=" + attr_name + "&attr_postfix=" + attr_postfix + "&attrgrp_name=" + attrgrp_name + "&toLangId=" + toLangId + "&attr_options="+ attr_options;
    fcom.updateWithAjax(fcom.makeUrl('Attributes', 'translateData'), data, function (t) {
        if (t.status == 1) {
            $("input[name='attr_name[" + toLangId + "]']").val(t.attr_name);
            $("input[name='attr_postfix[" + toLangId + "]']").val(t.attr_postfix);
            $("input[name='attrgrp_name[" + toLangId + "]']").val(t.attrgrp_name);
            $("textarea[name='attr_options["+ toLangId +"]']").val(t.attr_options);
        }
    });
};