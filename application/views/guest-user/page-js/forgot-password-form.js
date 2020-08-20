(function() {
	forgot = function(frm, v) {
		v.validate();
		if (!v.isValid()) return;		
		fcom.updateWithAjax(fcom.makeUrl('GuestUser', 'forgotPassword'), fcom.frmData(frm), function(t) {
			if( t.status == 1){
				location.href = fcom.makeUrl('GuestUser', 'loginForm');
			}else{
				$.systemMessage(t.msg,'alert--danger');				
			}
			$.mbsmessage.close();
			return;
		});
    };
    forgotPwdForm = function(withPhone = 0) {
        $.systemMessage(langLbl.processing,'alert--process', false);
        fcom.ajax(fcom.makeUrl( 'GuestUser', 'forgotPasswordForm', [withPhone, 0]), '', function(t) {
            $.systemMessage.close();
            $('.forgotPwForm').html(t);
            if (0 < withPhone) {
                stylePhoneNumberFld();
            }
		});
    };

    getOtpForm = function (frm){
        if (!$(frm).validate()) return;
        var data = fcom.frmData(frm);
        $.systemMessage(langLbl.processing,'alert--process', false);
		fcom.ajax(frm.action, data, function(t) {
            t = $.parseJSON(t);
            if(1 > t.status){
                $.systemMessage(t.msg,'alert--danger', false);
                googleCaptcha();
                return false;
            }
            $.systemMessage.close();
            $('#otpFom').html(t.html);
            startOtpInterval();
        });
        return false;
    };
    
    validateOtp = function (frm){
		if (!$(frm).validate()) return;	
		var data = fcom.frmData(frm);
		fcom.ajax(fcom.makeUrl('GuestUser', 'validateOtp', [1, 1]), data, function(t) {	
            t = $.parseJSON(t);					
            if (1 == t.status) {
                window.location.href = t.redirectUrl;
            } else {
                invalidOtpField();
            }
        });	
        return false;
    };

    resendOtp = function (userId, getOtpOnly = 0){
        $.systemMessage(langLbl.processing,'alert--process', false);
		fcom.ajax(fcom.makeUrl( 'GuestUser', 'resendOtp', [userId, getOtpOnly]), '', function(t) {
            t = $.parseJSON(t);
            if(typeof t.status != 'undefined' &&  1 > t.status){
                $.systemMessage(t.msg,'alert--danger', false);
                return false;
            }
            $.systemMessage(t.msg,'alert--success', false);
            startOtpInterval();
        });
        return false;
	};
})();