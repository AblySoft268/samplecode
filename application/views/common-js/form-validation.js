(function($) {
    
    /*
    Validation Singleton
    */
    var Validation = function() {

        messages={
                required: "{caption} is mandatory.",
                email: "Please enter valid email ID for {caption}.",
                charonly: "Only characters are supported for {caption}.",
                integer: "Please enter integer value for {caption}.",
                floating: "Please enter numeric value for {caption}.",
                username: "{caption} must start with a letter and can contain only alphanumeric characters (letters, _, ., digits). Length must be between 4 to 20 characters.",
                password: "{caption} Length Must be between 6 to 20 characters.",
                user_regex: "Invalid value for {caption}.",
                lengthrange: "Length of {caption} must be between {minlength} and {maxlength}.",
                range: "Value of {caption} must be between {minval} and {maxval}.",
                comparewith_lt: "{caption} must be less than {comparefield}.",
                comparewith_le: "{caption} must be less than or equal to {comparefield}.",
                comparewith_gt: "{caption} must be greater than {comparefield}.",
                comparewith_ge: "{caption} must be greater than or equal to {comparefield}.",
                comparewith_eq: "{caption} must be same as {comparefield}.",
                comparewith_ne: "{caption} should not be same as {comparefield}."
        };
        
        parseDate = function(str, format) {
        	if ( typeof(datePickerFormatToDate) == 'function' ) {
    			return datePickerFormatToDate(str, format);
    		}
    		else {
    			return $.datepicker.parseDate( format, str );
    		}
        };
        
        var rules = {
            email : {
               check: function(rval, value) {
                   if(rval && value)
                       return testPattern(value,"^((?:(?:(?:[a-zA-Z0-9][\\.\\-\\+_]?)*)[a-zA-Z0-9])+)\\@((?:(?:(?:[a-zA-Z0-9][\\.\\-_]?){0,62})[a-zA-Z0-9])+)\\.([a-zA-Z0-9]{2,6})$");
                   return true;
               }
            },
            
            required : {
               check: function(rval, value) {
                  if(rval){ 
                       if(value){
                           return true;
                       }
                       else{
                           return false;
                       }
                  }
                  else{
                      return true;
                  }
               }
            },

            charonly:{
                check: function(rval, value){
                    if(rval && value){
                        return testPattern(value, '^[a-zA-Z\\s]*$');
                    }
                    return true;
                }
            },

            integer:{
                check: function(rval, value){
                    if(rval && value){
                        return testPattern(value, '^-?[\\d]+$');
                    }
                    return true;
                }
            },

            floating:{
                check: function(rval, value){
                    if(rval && value){
                        return testPattern(value, '^-?[\\d]*\\.?[\\d]+$');
                    }
                    return true;
                }
            },

            username:{
                check: function(rval, value){
                    if(rval && value){
                        return testPattern(value, '^[a-zA-Z][a-zA-Z_\\.0-9]{3,19}$');
                    }
                    return true;
                }
            },

            password:{
                check: function(rval, value){
                    if(rval && value){
                        return testPattern(value, '^.{6,20}$');
                    }
                    return true;
                }
            },

            user_regex:{
                check: function(rval, value){
                    if(value){
                        return testPattern(value, rval);
                    }
                    return true;
                }
            },

            lengthrange:{
                check: function(rval, value){
                    if(value && $.isArray(rval) && rval.length==2){
                        return (value.length>=rval[0] && value.length<=rval[1]);
                    }
                    return true;
                }
            },

            range:{
                check: function(rval, value){
                    if(value){
                        var minval=rval.minval;
                        var maxval=rval.maxval;
                        if(rval.dateFormat){
                        	minval = $.Validation.convertStringToDate(minval, rval.dateFormat).getTime();
                            maxval = $.Validation.convertStringToDate(maxval, rval.dateFormat).getTime();
                            value = $.Validation.convertStringToDate(value, rval.dateFormat).getTime();
                        }
                        if(rval.numeric){
                            minval=parseFloat(minval);
                            maxval=parseFloat(maxval);
                            value=parseFloat(value);
                        }
                        return(value >= minval && value <= maxval);
                    }
                    return true;
                }
            },

            comparewith:{
                check: function(rval, value, o){
                    
                    if(o.dateFormat){
                        rval = $.Validation.convertStringToDate(rval, o.dateFormat).getTime();
                        value = $.Validation.convertStringToDate(value, o.dateFormat).getTime();
                    }
                    if(o.numeric){
                        rval=parseFloat(rval);
                        value=parseFloat(value);
                    }
                    
                    switch (o.operator) {
                    case 'lt':
                        return(value < rval);
                        break;
                    case 'le':
                        return(value<=rval);
                        break;
                    case 'gt':
                        return(value>rval);
                        break;
                    case 'ge':
                        return(value>=rval);
                        break;
                    case 'ne':
                        return(value!=rval);
                        break;
                    default:
                        return(value==rval);
                        break;
                    }
                }
            }
        }
        var testPattern = function(value, pattern) {
            var regExp = new RegExp(pattern,"");
            return regExp.test(value);
        }
        return {
            
            addRule : function(name, rule) {

                rules[name] = rule;
            },
            getRule : function(name) {
                return rules[name];
            },

            getMessage: function(name){
                return messages[name];
            },

            setMessages: function(obj){
                jQuery.extend(messages, obj);
            },
            
            convertStringToDate: function (str, format) {
            	return parseDate(str, format);
            }
        }
    };
    
    /* 
    Form factory 
    */
    var Form = function(form, options) {
        this.form=form;
        this.options=options;
    }
    Form.prototype = {
    		validate : function() {
    			if(typeof FCKeditorAPI !== 'undefined'){
    				for(x in FCKeditorAPI.Instances){
    					this.form.find(':hidden[name="' + x + '"]').val(FCKeditorAPI.GetInstance(x).GetXHTML(true));
    				}
    			}
    			if ( window.CKEDITOR ){
    				for (x in CKEDITOR.instances) CKEDITOR.instances[x].updateElement();
    			}

    			if (typeof oUtil == 'object'){
    				var editors = oUtil.arrEditor;
    				for (x in editors){
    					var obj = eval(editors[x]);
    					$('#' + obj.idTextArea).val(obj.getXHTMLBody());
    				}
    			}

    			var $form = this;
    			$form.valid = true;
    			this.form.find("input, textarea, select").each(function() {
    				var field = new Field(this, $form.options);
    				field.validate();
    				$form.valid = $form.valid && field.valid;
    				if ( 0 == $form.options.errordisplay && !field.valid ) {
    					this.focus();
    					return false;
    				} 
    			});
    			
    		},
    		isValid : function(avoidFormDisable) {
    			return this.valid;
    		}
    }
    
    /* 
    Field factory 
    */
    var Field = function(field, options) {
        this.settings=options;
        this.field = $(field);
        this.valid = false;
        if(this.settings.errordisplay != 0 && this.settings.errordisplay != 1) this.attach("change");
        
        if(this.settings.errordisplay==1 && this.settings.summaryElementId=='validation_default') {
        	this.settings.summaryElementId=$(field).parents('form').attr('id');
        }
        if ($(field).attr('data-mbsunichk')) {
        	$(field).attr('autocomplete', 'off').keyup(function() { $(this).attr('data-mbsunichk', '2') });
        }
    }
    Field.prototype = {
        
        attach : function(event) {
        
            var obj = this;
            if(event == "change") {
                obj.field.bind("change",function() {
                    return obj.validate();
                });
            }
            if(event == "keyup") {
                obj.field.bind("keyup",function(e) {
                    return obj.validate();
                });
            }
        },
        validate : function() {
            var clname='erlist_' + this.field.attr('name').replace(/\[/g, '_').replace(/\]/g, '_');

            $('.'+clname).remove();
            
            var obj = this,
                field = obj.field,
                errorClass = "errorlist",
                errorlist = $(document.createElement("ul")).addClass(errorClass).addClass(clname),
                types = {};
            
            	if (jQuery(field).attr('data-fatreq')) {
            		var s = eval('[' + jQuery(field).attr('data-fatreq') + ']');
            		types = s[0];
            	}
            
                errors = []; 
            jQuery.each(types, function(rname, rval){
                if(rname!='customMessage'){
                    var rule=$.Validation.getRule(rname);
                    
                    var fldval = $.trim(field.val());
                    
                    if (field.attr('data-fatdateformat')) {
                    	rval.dateFormat = field.attr('data-fatdateformat');
                    }
                    
//                    console.log(field.attr('data-fatdateformat'));
                    
                    if (field.attr('type')){
                        if(field.attr('type').toLowerCase()==='checkbox'){
                            fldval=(field.is(':checked'))?fldval:'';
                        }
                    }
                    
                    if(rname=='comparewith'){
                        for (x in rval){
                        	if (field.attr('data-fatdateformat')) {
                            	rval[x].dateFormat = field.attr('data-fatdateformat');
                            }
                            var validvalue=rule.check($(field).parents('form').find('[name='+rval[x].fldname+']').val(), fldval, rval[x]);
                            if (!validvalue) break;
                        }
                    }
                    else{
                        var validvalue=rule.check(rval, fldval);
                    }
                    
                    
                    if(!validvalue){
                        field.addClass("error");
                        //var msg=(types.customMessage)?types.customMessage:$.Validation.getMessage(rname);
                        if(types.customMessage){
                            msg=types.customMessage;
                        }
                        else{
                            if(rname=='comparewith'){
                                msg=$.Validation.getMessage(rname+'_'+rval[x].operator);
                                msg=msg.replace("{comparefield}", $(field).parents('form').find('[name='+rval[x].fldname+']').attr('title'));
                            }
                            else{
                                msg=$.Validation.getMessage(rname);
                            }
                        }
                        msg=msg.replace("{caption}",$(field).attr('title'));
                        if(jQuery.isArray(rval) && rval.length==2){
                            msg=msg.replace("{minval}", rval[0]);
                            msg=msg.replace("{minlength}", rval[0]);
                            msg=msg.replace("{maxval}", rval[1]);
                            msg=msg.replace("{maxlength}", rval[1]);
                        }
                        if(rname=='range'){
                            msg=msg.replace("{minval}", rval.minval);
                            msg=msg.replace("{maxval}", rval.maxval);
                        }
                        
                        errors.push(msg);
                    }
            }});
            
            if(errors.length) {
                if(this.settings.errordisplay!=0 && this.settings.errordisplay!=1){
                    obj.field.unbind("keyup");
                    obj.attach("keyup");
                }
                switch(this.settings.errordisplay){
                case 1:
                    $('#'+this.settings.summaryElementId).append(errorlist.empty());
                    document.getElementById(this.settings.summaryElementId).scrollIntoView();
                    break;
                case 2:
                    field.before(errorlist.empty());
                    break;
                case 3:
                    $(field).after(errorlist.empty());
                    break;
                case 0:
                    
                    break;
                }
                for(error in errors) {
                    if(this.settings.errordisplay == 0){
                        alert(errors[error]);
                        return;
                    }
                    else {
                    		var li=$(document.createElement('li')).append($(document.createElement('a')).html(errors[error]).attr({'href':'javascript:void(0);'}).bind('click', function(){$(field).focus();}));
                    		li.appendTo(errorlist);
                    }
                }
                obj.valid = false;
            } 
            else {
                errorlist.remove();
                field.removeClass("error");
                obj.valid = true;
            }
            if (this.field.attr('data-mbsunichk')) {
            	if (this.field.attr('data-mbsunichk') == '2') {
            		obj.valid = false;
            		if (!this.field.hasClass('field-processing')) this.field.trigger('change');
            	}
            }
        }
    }
    
    /* 
    Validation extends jQuery prototype
    */
    $.extend($.fn, {
        
        validation : function(options) {
            
            var validator = new Form($(this), options);
            $.data($(this)[0], 'validator', validator);
            
            $(this).bind("submit", function(e) {
                validator.validate(); 
                if(!validator.isValid()) {
                    e.preventDefault();
                }
            });
            return validator;
        },
        validate : function() {
            var validator = $.data($(this)[0], 'validator');
            validator.validate();
            return validator.isValid();
        }
    });
    $.Validation = new Validation();
})(jQuery);

(function() {
	checkUnique = function(fld, tbl, tbl_fld, tbl_key, key_fld, constraints){
	    fld.addClass('field-processing');
	    var entered = fld.val();
	    $.ajax({
	        url: fcom.makeUrl('checkunique', 'check'),
	        type: 'POST',
	        dataType: 'json',
	        data: {'val':entered, 'tbl':tbl, 'tbl_fld':tbl_fld, 'tbl_key':tbl_key, 'key_val':key_fld.val(), 'constraints':constraints},
	        success: function(ans){
	            fld.removeClass('field-processing');
	            $(fld).attr('data-mbsunichk', 1);
	            if(ans.status==0){
	                alert(fld.attr('title') + " '" + entered + "' is not available");
	                fld.val(ans.existing_value);
	                fld.focus();
	            }
	        }
	    });
	}
})();