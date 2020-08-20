<?php

class GuestUserController extends MyAppController
{

    private const FB_LOGIN = 1;
    private const GOOGLE_LOGIN = 2;
    private const APPLE_LOGIN = 3;

    public function loginForm($isRegisterForm = 0)
    {
        if (UserAuthentication::isGuestUserLogged()) {
            FatApp::redirectUser(CommonHelper::generateUrl('home'));
        }

        if (UserAuthentication::isUserLogged()) {
            FatApp::redirectUser(CommonHelper::generateUrl('account'));
        }

        $socialLoginApis = Plugin::getDataByType(Plugin::TYPE_SOCIAL_LOGIN, $this->siteLangId);

        $loginFrm = $this->getLoginForm();
        $loginData = array(
            'loginFrm' => $loginFrm,
            'socialLoginApis' => $socialLoginApis,
            'siteLangId' => $this->siteLangId,
        );

        $this->registerFormDetail($isRegisterForm);

        $this->set('loginData', $loginData);
        $this->_template->render();
    }

    public function registerFormDetail($isRegisterForm, $signUpWithPhone = 0)
    {
        $registerFrm = $this->getRegistrationForm(true, $signUpWithPhone);
        $cPageSrch = ContentPage::getSearchObject($this->siteLangId);
        $cPageSrch->addCondition('cpage_id', '=', FatApp::getConfig('CONF_TERMS_AND_CONDITIONS_PAGE', FatUtility::VAR_INT, 0));
        $cpage = FatApp::getDb()->fetch($cPageSrch->getResultSet());
        if (!empty($cpage) && is_array($cpage)) {
            $termsAndConditionsLinkHref = CommonHelper::generateUrl('Cms', 'view', array($cpage['cpage_id']));
        } else {
            $termsAndConditionsLinkHref = 'javascript:void(0)';
        }
        $registerdata = array(
            'registerFrm' => $registerFrm,
            'termsAndConditionsLinkHref' => $termsAndConditionsLinkHref,
            'siteLangId' => $this->siteLangId,
            'signUpWithPhone' => $signUpWithPhone,
        );
        $isRegisterForm = FatUtility::int($isRegisterForm);

        $this->set('smsPluginStatus', SmsArchive::canSendSms(SmsTemplate::LOGIN));
        $this->set('isRegisterForm', $isRegisterForm);
        $this->set('registerdata', $registerdata);
    }

    public function signUpWithPhone()
    {
        $obj = new Plugin();
        $active = $obj->getDefaultPluginData(Plugin::TYPE_SMS_NOTIFICATION, 'plugin_active');
        $status = SmsTemplate::getTpl(SmsTemplate::LOGIN, 0, 'stpl_status');
        $signUpWithPhone = (false != $active && !empty($active) && 0 < $status ? applicationConstants::YES : applicationConstants::NO);
        $this->registerFormDetail(applicationConstants::YES, $signUpWithPhone);
        $this->_template->render(false, false, 'guest-user/register-form-detail.php');
    }

    public function signUpWithEmail()
    {
        $this->registerFormDetail(applicationConstants::YES, applicationConstants::NO);
        $this->_template->render(false, false, 'guest-user/register-form-detail.php');
    }

    public function login()
    {
        $authentication = new UserAuthentication();
        $userType = FatApp::getPostedData('userType', FatUtility::VAR_INT, 0);
        if (true === MOBILE_APP_API_CALL && 1 > $userType) {
            FatUtility::dieJsonError(Labels::getLabel('MSG_INVALID_REQUEST', $this->siteLangId));
        }
        $userName = FatApp::getPostedData('username');
        $dialCode = FatApp::getPostedData('user_dial_code', FatUtility::VAR_STRING, '');
        $withPhone = false;
        if (!empty($dialCode)) {
            $userName = trim($dialCode) . trim($userName);
            $withPhone = true;
        }

        if (!$authentication->login($userName, FatApp::getPostedData('password'), $_SERVER['REMOTE_ADDR'], true, false, $this->app_user['temp_user_id'], $userType, $withPhone)) {
            $message = Labels::getLabel($authentication->getError(), $this->siteLangId);
            FatUtility::dieJsonError($message);
        }

        $this->app_user['temp_user_id'] = 0;

        $userId = UserAuthentication::getLoggedUserId();

        if (true === MOBILE_APP_API_CALL) {
            $uObj = new User($userId);
            if (!$token = $uObj->setMobileAppToken()) {
                FatUtility::dieJsonError(Labels::getLabel('MSG_INVALID_REQUEST', $this->siteLangId));
            }

            $userInfo = $uObj->getUserInfo(array('user_name', 'user_id', 'user_dial_code', 'user_phone', 'credential_email'), true, true, true);

            $this->set('token', $token);
            $this->set('userInfo', $userInfo);
            $this->_template->render();
        }

        $rememberme = FatApp::getPostedData('remember_me', FatUtility::VAR_INT, 0);
        if ($rememberme == 1) {
            if (!$this->setUserLoginCookie()) {
                Message::addErrorMessage(Labels::getLabel('MSG_COOKIES_NOT_ADDED', $this->siteLangId));
            }
        }

        setcookie('uc_id', $userId, time() + 3600 * 24 * 30, CONF_WEBROOT_URL);

        $data = User::getAttributesById($userId, array('user_preferred_dashboard', 'user_registered_initially_for'));

        $preferredDashboard = 0;
        if ($data != false) {
            $preferredDashboard = $data['user_preferred_dashboard'];
        }

        $redirectUrl = '';

        if (isset($_SESSION['referer_page_url'])) {
            $redirectUrl = $_SESSION['referer_page_url'];
            unset($_SESSION['referer_page_url']);


            $userPreferedDashboardType = ($data['user_preferred_dashboard']) ? $data['user_preferred_dashboard'] : $data['user_registered_initially_for'];

            switch ($userPreferedDashboardType) {
                case User::USER_TYPE_BUYER:
                    $_SESSION[UserAuthentication::SESSION_ELEMENT_NAME]['activeTab'] = 'B';
                    break;
                case User::USER_TYPE_SELLER:
                    $_SESSION[UserAuthentication::SESSION_ELEMENT_NAME]['activeTab'] = 'S';
                    break;
                case User::USER_TYPE_AFFILIATE:
                    $_SESSION[UserAuthentication::SESSION_ELEMENT_NAME]['activeTab'] = 'AFFILIATE';
                    break;
                case User::USER_TYPE_ADVERTISER:
                    $_SESSION[UserAuthentication::SESSION_ELEMENT_NAME]['activeTab'] = 'Ad';
                    break;
            }
        }
        if ($redirectUrl == '') {
            $redirectUrl = User::getPreferedDashbordRedirectUrl($preferredDashboard);
        }

        if ($redirectUrl == '') {
            $redirectUrl = CommonHelper::generateUrl('Account');
        }
        $this->set('redirectUrl', $redirectUrl);
        $this->set('msg', Labels::getLabel("MSG_LOGIN_SUCCESSFULL", $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php');
    }

    public function setUserPushNotificationToken()
    {
        $fcmToken = FatApp::getPostedData('deviceToken', FatUtility::VAR_STRING, '');
        $deviceOs = FatApp::getPostedData('deviceOs', FatUtility::VAR_INT, 0);
        $userType = FatApp::getPostedData('userType', FatUtility::VAR_INT, User::USER_TYPE_BUYER);
        if (empty($fcmToken)) {
            FatUtility::dieJSONError(Labels::getLabel('Msg_Invalid_Request', $this->siteLangId));
        }

        if (!UserAuthentication::isUserLogged()) {
            if (!User::setGuestFcmToken($userType, $fcmToken, $deviceOs, $this->getAppTempUserId())) {
                FatUtility::dieJsonError(Labels::getLabel('MSG_UNABLE_TO_UPDATE.', $this->siteLangId));
            }
        } else {
            $userId = UserAuthentication::getLoggedUserId();
            $uObj = new User($userId);
            if (!$uObj->setPushNotificationToken($this->appToken, $fcmToken, $userType, $deviceOs)) {
                FatUtility::dieJsonError(Labels::getLabel('MSG_UNABLE_TO_UPDATE', $this->siteLangId));
            }
        }
        $this->set('msg', Labels::getLabel('Msg_Successfully_Updated', $this->siteLangId));
        $this->_template->render();
    }

    public function guestLogin()
    {
        $frm = $this->getGuestUserForm($this->siteLangId);
        $post = $frm->getFormDataFromArray(FatApp::getPostedData());

        if ($post == false) {
            Message::addErrorMessage(current($frm->getValidationErrors()));
            FatUtility::dieJsonError(Message::getHtml());
        }

        $authentication = new UserAuthentication();
        if (!$authentication->guestLogin(FatApp::getPostedData('user_email'), FatApp::getPostedData('user_name'), $_SERVER['REMOTE_ADDR'])) {
            Message::addErrorMessage(Labels::getLabel($authentication->getError(), $this->siteLangId));
            FatUtility::dieJsonError(Message::getHtml());
        }

        $redirectUrl = '';

        if (isset($_SESSION['referer_page_url'])) {
            $redirectUrl = $_SESSION['referer_page_url'];
            unset($_SESSION['referer_page_url']);
        }

        if ($redirectUrl == '') {
            $redirectUrl = User::getPreferedDashbordRedirectUrl(User::USER_BUYER_DASHBOARD);
        }

        if ($redirectUrl == '') {
            $redirectUrl = CommonHelper::generateUrl('Home');
        }

        $this->set('redirectUrl', $redirectUrl);
        $this->set('msg', Labels::getLabel("MSG_GUEST_LOGIN_SUCCESSFULL", $this->siteLangId));
        $this->_template->render(false, false, 'json-success.php');
    }

    private function setUserLoginCookie()
    {
        $userId = UserAuthentication::getLoggedUserAttribute('user_id', true);

        if (null == $userId) {
            return false;
        }

        $token = $this->generateLoginToken();
        $expiry = strtotime("+7 DAYS");

        $values = array(
            'uauth_user_id' => $userId,
            'uauth_token' => $token,
            'uauth_expiry' => date('Y-m-d H:i:s', $expiry),
            'uauth_browser' => CommonHelper::userAgent(),
            'uauth_last_access' => date('Y-m-d H:i:s'),
            'uauth_last_ip' => CommonHelper::getClientIp(),
        );

        if (UserAuthentication::saveLoginToken($values)) {
            $cookieName = UserAuthentication::YOKARTUSER_COOKIE_NAME;
            $cookres = setcookie($cookieName, $token, $expiry, CONF_WEBROOT_URL);
            return true;
        }
        return false;
    }

    private function generateLoginToken()
    {
        return substr(md5(rand(1, 99999) . microtime()), 0, UserAuthentication::TOKEN_LENGTH);
    }

    public function LogInFormPopUp()
    {
        $includeGuestLogin = FatApp::getPostedData('includeGuestLogin', FatUtility::VAR_STRING, false);
        $frm = $this->getLoginForm($includeGuestLogin);
        $socialLoginApis = Plugin::getDataByType(Plugin::TYPE_SOCIAL_LOGIN, $this->siteLangId);
        $data = array(
            'loginFrm' => $frm,
            'siteLangId' => $this->siteLangId,
            'socialLoginApis' => $socialLoginApis,
            'includeGuestLogin' => $includeGuestLogin,
            'smsPluginStatus' => SmsArchive::canSendSms(SmsTemplate::LOGIN),
        );
        $this->set('data', $data);
        $this->_template->render(false, false);
    }

    public function form()
    {
        $frm = $this->getGuestUserForm($this->siteLangId);
        $this->set('frm', $frm);
        $this->_template->render(false, false);
    }

    public function checkAjaxUserLoggedIn()
    {
        $json = array();
        $json['isUserLogged'] = FatUtility::int(UserAuthentication::isUserLogged());
        if (!$json['isUserLogged']) {
            $json['isUserLogged'] = FatUtility::int(UserAuthentication::isGuestUserLogged());
        }
        die(json_encode($json));
    }

    public function registrationForm()
    {
        if (UserAuthentication::isGuestUserLogged()) {
            FatApp::redirectUser(CommonHelper::generateUrl('home'));
        }

        if (UserAuthentication::isUserLogged()) {
            FatApp::redirectUser(CommonHelper::generateUrl('account'));
        }

        $registerFrm = $this->getRegistrationForm();

        $cPageSrch = ContentPage::getSearchObject($this->siteLangId);
        $cPageSrch->addCondition('cpage_id', '=', FatApp::getConfig('CONF_TERMS_AND_CONDITIONS_PAGE', FatUtility::VAR_INT, 0));
        $cpage = FatApp::getDb()->fetch($cPageSrch->getResultSet());
        if (!empty($cpage) && is_array($cpage)) {
            $termsAndConditionsLinkHref = CommonHelper::generateUrl('Cms', 'view', array($cpage['cpage_id']));
        } else {
            $termsAndConditionsLinkHref = 'javascript:void(0)';
        }
        $data = array(
            'registerFrm' => $registerFrm,
            'termsAndConditionsLinkHref' => $termsAndConditionsLinkHref,
            'siteLangId' => $this->siteLangId
        );
        $obj = new Extrapage();
        $pageData = $obj->getContentByPageType(Extrapage::REGISTRATION_PAGE_RIGHT_BLOCK, $this->siteLangId);
        $this->set('pageData', $pageData);
        $this->set('data', $data);
        $this->_template->render(true, true, 'guest-user/registration-form.php');
    }

    public function register()
    {
        $signUpWithPhone = FatApp::getPostedData('signUpWithPhone', FatUtility::VAR_INT, 0);
        $showNewsLetterCheckBox = 0 < $signUpWithPhone ? false : true;

        $frm = $this->getRegistrationForm($showNewsLetterCheckBox, $signUpWithPhone);
        $post = $frm->getFormDataFromArray(FatApp::getPostedData());

        if ($post == false) {
            $message = Labels::getLabel(current($frm->getValidationErrors()), $this->siteLangId);
            LibHelper::exitWithError($message, false, true);
            FatApp::redirectUser(CommonHelper::generateUrl('GuestUser', 'loginForm', array(applicationConstants::YES)));
        }

        $dialCode = FatApp::getPostedData('user_dial_code', FatUtility::VAR_STRING, '');
        $countryIso = FatApp::getPostedData('user_country_iso', FatUtility::VAR_STRING, '');
        $phoneNumber = isset($post['user_phone']) ? FatUtility::int($post['user_phone']) : '';
        if ((0 < $signUpWithPhone && empty($phoneNumber)) || (!empty($phoneNumber) && (empty($dialCode) || empty($countryIso)))) {
            $message = Labels::getLabel("MSG_INVALID_PHONE_NUMBER", $this->siteLangId);
            LibHelper::exitWithError($message, false, true);
            FatApp::redirectUser(CommonHelper::generateUrl('GuestUser', 'loginForm', array(applicationConstants::YES)));
        }

        $post['user_dial_code'] = $dialCode;
        $post['user_phone'] = isset($post['user_phone']) ? FatUtility::int(str_replace($post['user_dial_code'], "", $post['user_phone'])) : null;
        $post['user_is_buyer'] = User::USER_TYPE_BUYER;
        $post['user_preferred_dashboard'] = User::USER_BUYER_DASHBOARD;
        $post['user_registered_initially_for'] = User::USER_TYPE_BUYER;
        $post['user_is_supplier'] = (FatApp::getConfig("CONF_ADMIN_APPROVAL_SUPPLIER_REGISTRATION", FatUtility::VAR_INT, 1) || FatApp::getConfig("CONF_ACTIVATE_SEPARATE_SIGNUP_FORM", FatUtility::VAR_INT, 1)) ? 0 : 1;
        $post['user_is_advertiser'] = (FatApp::getConfig("CONF_ADMIN_APPROVAL_SUPPLIER_REGISTRATION", FatUtility::VAR_INT, 1) || FatApp::getConfig("CONF_ACTIVATE_SEPARATE_SIGNUP_FORM", FatUtility::VAR_INT, 1)) ? 0 : 1;
        $post['user_active'] = FatApp::getConfig('CONF_ADMIN_APPROVAL_REGISTRATION', FatUtility::VAR_INT, 1) ? 0 : 1;
        $post['user_verify'] = FatApp::getConfig('CONF_EMAIL_VERIFICATION_REGISTRATION', FatUtility::VAR_INT, 1) ? 0 : 1;

        $userObj = new User();
        $returnUserId = (true == MOBILE_APP_API_CALL && 0 < $signUpWithPhone ? true : false);
        if (!$userId = $userObj->saveUserData($post, false, $returnUserId)) {
            $message = Labels::getLabel($userObj->getError(), $this->siteLangId);
            if (0 < $signUpWithPhone) {
                $row = $userObj->checkUserByPhoneOrUserName($post['user_username'], $post['user_phone']);
                $userId = $row['user_id'];
                $replacements = [
                    '{CONTINUE-BTN}' => '<a class="btn btn-outline-white" href="javascript:void(0);" onclick="resendOtp(' . $userId . ')">' . Labels::getLabel('MSG_PROCEED', $this->siteLangId) . '</a>'
                ];
                $message = CommonHelper::replaceStringData($message, $replacements);
            }

            LibHelper::exitWithError($message, false, true);
            FatApp::redirectUser(CommonHelper::generateUrl('GuestUser', 'loginForm', array(applicationConstants::YES)));
        }

        if (false === $userObj->updateUserMeta('user_country_iso', $countryIso)) {
            LibHelper::exitWithError($user->getError(), false, true);
            FatApp::redirectUser(CommonHelper::generateUrl('GuestUser', 'loginForm'));
        }

        if (1 > $signUpWithPhone && !FatApp::getConfig('CONF_EMAIL_VERIFICATION_REGISTRATION', FatUtility::VAR_INT, 1)) {
            $cartObj = new Cart();
            $isCheckOutPage = (isset($post['isCheckOutPage']) && $cartObj->hasProducts()) ? FatUtility::int($post['isCheckOutPage']) : 0;
            $confAutoLoginRegisteration = ($isCheckOutPage) ? 1 : FatApp::getConfig('CONF_AUTO_LOGIN_REGISTRATION', FatUtility::VAR_INT, 1);
            if ($confAutoLoginRegisteration && !(FatApp::getConfig('CONF_ADMIN_APPROVAL_REGISTRATION', FatUtility::VAR_INT, 1))) {
                $authentication = new UserAuthentication();
                if (!$authentication->login(FatApp::getPostedData('user_username'), FatApp::getPostedData('user_password'), $_SERVER['REMOTE_ADDR'])) {
                    $message = Labels::getLabel($authentication->getError(), $this->siteLangId);
                    LibHelper::exitWithError($message, false, true);
                    FatApp::redirectUser(CommonHelper::generateUrl('GuestUser', 'loginForm'));
                }

                if (false === MOBILE_APP_API_CALL) {
                    $redirectUrl = CommonHelper::generateUrl('Account');
                    if ($isCheckOutPage) {
                        $this->set('needLogin', 1);
                        $redirectUrl = CommonHelper::generateUrl('Checkout');
                    }
                    if (FatUtility::isAjaxCall()) {
                        $this->set('msg', Labels::getLabel('LBL_Registeration_Successfull', $this->siteLangId));
                        $this->set('redirectUrl', $redirectUrl);
                        $this->_template->render(false, false, 'json-success.php');
                        exit;
                    }
                    FatApp::redirectUser($redirectUrl);
                }
            }
        }

        if (true === MOBILE_APP_API_CALL) {
            if (0 < $signUpWithPhone) {
                $this->set('data', ['userId' => $userId]);
                $this->set('msg', Labels::getLabel('MSG_OTP_SENT!_PLEASE_CHECK_YOUR_PHONE.', $this->siteLangId));
            } else {
                $this->set('msg', Labels::getLabel('LBL_REGISTERATION_SUCCESSFULL', $this->siteLangId));
            }
            $this->_template->render();
        }

        $actionUrl = 'registrationSuccess';
        if (0 < $signUpWithPhone) {
            $actionUrl = 'otpForm';
        }

        $redirectUrl = CommonHelper::generateUrl('GuestUser', $actionUrl);
        if (FatUtility::isAjaxCall()) {
            $this->set('msg', Labels::getLabel('LBL_Registeration_Successfull', $this->siteLangId));
            $this->set('redirectUrl', $redirectUrl);
            $this->_template->render(false, false, 'json-success.php');
            exit;
        }
        FatApp::redirectUser($redirectUrl);
    }

    public function validateOtp($recoverPwd = 0, $forgotPw = false)
    {
        $this->validateOtpApi(0, (!$forgotPw));
        $userId = FatApp::getPostedData('user_id', FatUtility::VAR_INT, 0);
        if (0 < $recoverPwd) {
            $obj = new UserAuthentication();
            $record = $obj->getUserResetPwdToken($userId);
            $token = $record['uprr_token'];
            $redirectUrl = CommonHelper::generateFullUrl('GuestUser', 'resetPassword', array($userId, $token));
        } else {
            $redirectUrl = isset($_SESSION['referer_page_url']) ? $_SESSION['referer_page_url'] : CommonHelper::generateUrl('Account');
        }
        unset($_SESSION[UserAuthentication::TEMP_SESSION_ELEMENT_NAME]);
        $this->set('redirectUrl', $redirectUrl);
        $this->_template->render(false, false, 'json-success.php');
    }

    public function userCheckEmailVerification($code)
    {
        $code = FatUtility::convertToType($code, FatUtility::VAR_STRING);
        if (strlen($code) < 1) {
            Message::addMessage(Labels::getLabel("MSG_PLEASE_CHECK_YOUR_EMAIL_IN_ORDER_TO_VERIFY", $this->siteLangId));
            FatApp::redirectUser(CommonHelper::generateUrl('GuestUser', 'loginForm'));
        }

        $arrCode = explode('_', $code, 2);

        $userId = FatUtility::int($arrCode[0]);
        if ($userId < 1) {
            Message::addErrorMessage(Labels::getLabel('MSG_INVALID_CODE', $this->siteLangId));
            FatApp::redirectUser(CommonHelper::generateUrl('GuestUser', 'loginForm'));
        }

        $userObj = new User($userId);
        $userData = User::getAttributesById($userId, array('user_id', 'user_is_affiliate'));
        if (!$userData) {
            Message::addErrorMessage(Labels::getLabel('MSG_INVALID_CODE', $this->siteLangId));
            FatApp::redirectUser(CommonHelper::generateUrl('GuestUser', 'loginForm'));
        }

        $db = FatApp::getDb();
        $db->startTransaction();

        if (!$userObj->verifyUserEmailVerificationCode($code)) {
            $db->rollbackTransaction();
            Message::addErrorMessage(Labels::getLabel("ERR_MSG_INVALID_VERIFICATION_REQUEST", $this->siteLangId));
            FatApp::redirectUser(CommonHelper::generateUrl('GuestUser', 'loginForm'));
        }

        if ($userData['user_is_affiliate'] != applicationConstants::YES) {
            $srch = new SearchBase('tbl_user_credentials');
            $srch->addCondition('credential_user_id', '=', $userId);
            $rs = $srch->getResultSet();
            $checkActiveRow = $db->fetch($rs);
            if ($checkActiveRow['credential_active'] != applicationConstants::ACTIVE) {
                $active = FatApp::getConfig('CONF_ADMIN_APPROVAL_REGISTRATION', FatUtility::VAR_INT, 1) ? 0 : 1;
                if (!$userObj->activateAccount($active)) {
                    $db->rollbackTransaction();
                    Message::addErrorMessage(Labels::getLabel('MSG_INVALID_CODE', $this->siteLangId));
                    FatApp::redirectUser(CommonHelper::generateUrl('GuestUser', 'loginForm'));
                }
            }
        }

        if (!$userObj->verifyAccount()) {
            $db->rollbackTransaction();
            Message::addErrorMessage(Labels::getLabel('MSG_INVALID_CODE', $this->siteLangId));
            FatApp::redirectUser(CommonHelper::generateUrl('GuestUser', 'loginForm'));
        }

        $userdata = $userObj->getUserInfo(array('credential_email', 'credential_password', 'user_name', 'credential_active'), false);

        if (FatApp::getConfig('CONF_WELCOME_EMAIL_REGISTRATION', FatUtility::VAR_INT, 1)) {
            $data['user_email'] = $userdata['credential_email'];
            $data['user_name'] = $userdata['user_name'];

            //ToDO::Change login link to contact us link
            $link = CommonHelper::generateFullUrl('GuestUser', 'loginForm');
            if (!$userObj->userWelcomeEmailRegistration($data, $link, $this->siteLangId)) {
                Message::addErrorMessage(Labels::getLabel("MSG_WELCOME_EMAIL_COULD_NOT_BE_SENT", $this->siteLangId));
                $db->rollbackTransaction();
                FatApp::redirectUser(CommonHelper::generateUrl('GuestUser', 'loginForm'));
            }
        }

        $db->commitTransaction();

        if (FatApp::getConfig('CONF_AUTO_LOGIN_REGISTRATION', FatUtility::VAR_INT, 1)) {
            $authentication = new UserAuthentication();

            if (!$authentication->login($userdata['credential_email'], $userdata['credential_password'], $_SERVER['REMOTE_ADDR'], false)) {
                Message::addErrorMessage(Labels::getLabel($authentication->getError(), $this->siteLangId));
                FatApp::redirectUser(CommonHelper::generateUrl('GuestUser', 'loginForm'));
            }
            FatApp::redirectUser(CommonHelper::generateUrl('Account'));
        }

        Message::addMessage(Labels::getLabel("MSG_EMAIL_VERIFIED", $this->siteLangId));

        FatApp::redirectUser(CommonHelper::generateUrl('GuestUser', 'loginForm'));
    }

    public function changeEmailVerification($code)
    {
        $code = FatUtility::convertToType($code, FatUtility::VAR_STRING);
        if (strlen($code) < 1) {
            Message::addMessage(Labels::getLabel("MSG_PLEASE_CHECK_YOUR_EMAIL_IN_ORDER_TO_VERIFY", $this->siteLangId));
            FatApp::redirectUser(CommonHelper::generateUrl('GuestUser', 'loginForm'));
        }

        $arrCode = explode('_', $code, 2);

        $userId = FatUtility::int($arrCode[0]);
        if ($userId < 1) {
            Message::addErrorMessage(Labels::getLabel('MSG_INVALID_CODE', $this->siteLangId));
            FatApp::redirectUser(CommonHelper::generateUrl('GuestUser', 'loginForm'));
        }

        $userObj = new User($userId);

        $newUserEmail = $userObj->verifyUserEmailVerificationCode($code);

        if (!$newUserEmail) {
            Message::addErrorMessage(Labels::getLabel("ERR_MSG_INVALID_VERIFICATION_REQUEST", $this->siteLangId));
            FatApp::redirectUser(CommonHelper::generateUrl('GuestUser', 'loginForm'));
        }

        $usr = new User();
        $srch = $usr->getUserSearchObj(array('uc.credential_email'));
        $srch->addCondition('uc.credential_email', '=', $newUserEmail);
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();

        $rs = $srch->getResultSet();
        $record = FatApp::getDb()->fetch($rs);

        if ($record) {
            Message::addErrorMessage(Labels::getLabel("ERR_DUPLICATE_EMAIL", $this->siteLangId));
            FatApp::redirectUser(CommonHelper::generateUrl('GuestUser', 'loginForm'));
        }


        $srchUser = $usr->getUserSearchObj(array('u.user_name', 'u.user_dial_Code', 'u.user_phone', 'uc.credential_email'));
        $srchUser->addCondition('u.user_id', '=', $userId);
        $srchUser->doNotCalculateRecords();
        $srchUser->doNotLimitRecords();
        $rs = $srchUser->getResultSet();
        $data = FatApp::getDb()->fetch($rs);

        if (!$userObj->changeEmail($newUserEmail)) {
            Message::addErrorMessage(Labels::getLabel("MSG_UPDATED_EMAIL_COULD_NOT_BE_SET", $this->siteLangId) . $userObj->getError());
            FatApp::redirectUser(CommonHelper::generateUrl('GuestUser', 'loginForm'));
        }

        $email = new EmailHandler();
        $currentEmail = $data['credential_email'];
        $phone = !empty($data['user_phone']) ? $data['user_dial_code'] . $data['user_phone'] : '';
        if (!empty($currentEmail) && !$email->sendEmailChangedNotification($this->siteLangId, array('user_name' => $data['user_name'], 'user_email' => $data['credential_email'], 'user_new_email' => $newUserEmail, 'user_phone' => $phone))) {
            Message::addErrorMessage(Labels::getLabel("MSG_UNABLE_TO_SEND_EMAIL_CHANGE_NOTIFICATION", $this->siteLangId) . $userObj->getError());
            FatApp::redirectUser(CommonHelper::generateUrl('GuestUser', 'loginForm'));
        }

        if (FatApp::getConfig('CONF_AUTO_LOGIN_REGISTRATION', FatUtility::VAR_INT, 1) || UserAuthentication::isUserLogged()) {
            $userdata = $userObj->getUserInfo(array('credential_username', 'credential_password'));
            $authentication = new UserAuthentication();
            if (!$authentication->login($userdata['credential_username'], $userdata['credential_password'], $_SERVER['REMOTE_ADDR'], false)) {
                Message::addErrorMessage(Labels::getLabel($authentication->getError(), $this->siteLangId));
                FatApp::redirectUser(CommonHelper::generateUrl('GuestUser', 'loginForm'));
            }
            FatApp::redirectUser(CommonHelper::generateUrl('Account'));
        }

        Message::addMessage(Labels::getLabel("MSG_EMAIL_VERIFIED", $this->siteLangId));
        FatApp::redirectUser(CommonHelper::generateUrl('GuestUser', 'loginForm'));
    }

    public function registrationSuccess()
    {
        if (FatApp::getConfig('CONF_EMAIL_VERIFICATION_REGISTRATION', FatUtility::VAR_INT, 1)) {
            $this->set('registrationMsg', Labels::getLabel("MSG_SUCCESS_USER_SIGNUP_EMAIL_VERIFICATION_PENDING", $this->siteLangId));
        } elseif (FatApp::getConfig('CONF_ADMIN_APPROVAL_REGISTRATION', FatUtility::VAR_INT, 1)) {
            $this->set('registrationMsg', Labels::getLabel("MSG_SUCCESS_USER_SIGNUP_ADMIN_APPROVAL_PENDING", $this->siteLangId));
        } else {
            $this->set('registrationMsg', Labels::getLabel("MSG_REGISTERED_SUCCESSFULLY", $this->siteLangId));
        }

        $this->_template->render();
    }

    public function forgotPasswordForm($withPhone = 0, $includeHeaderAndFooter = 1)
    {
        $frm = $this->getForgotForm($withPhone);
        $obj = new Extrapage();
        $pageData = $obj->getContentByPageType(Extrapage::FORGOT_PAGE_RIGHT_BLOCK, $this->siteLangId);

        $this->set('smsPluginStatus', SmsArchive::canSendSms(SmsTemplate::LOGIN));
        $this->set('withPhone', $withPhone);
        $this->set('pageData', $pageData);
        $this->set('frm', $frm);
        $this->set('siteLangId', $this->siteLangId);
        if (1 > $withPhone && 0 < $includeHeaderAndFooter) {
            $this->_template->render();
            return;
        }
        $this->_template->render(false, false);
    }

    public function forgotPassword()
    {
        $withPhone = FatApp::getPostedData('withPhone', FatUtility::VAR_INT, 0);
        $frm = $this->getForgotForm($withPhone);
        $post = $frm->getFormDataFromArray(FatApp::getPostedData());
        if (false === $post) {
            if (true === MOBILE_APP_API_CALL || FatUtility::isAjaxCall()) {
                FatUtility::dieJsonError(current($frm->getValidationErrors()));
            }
            Message::addErrorMessage($frm->getValidationErrors());
            FatApp::redirectUser(CommonHelper::generateUrl('GuestUser', 'forgotPasswordForm'));
        }

        if (false === MOBILE_APP_API_CALL && FatApp::getConfig('CONF_RECAPTCHA_SITEKEY', FatUtility::VAR_STRING, '') != '' && FatApp::getConfig('CONF_RECAPTCHA_SECRETKEY', FatUtility::VAR_STRING, '') != '') {
            if (!CommonHelper::verifyCaptcha()) {
                $message = Labels::getLabel('MSG_That_captcha_was_incorrect', $this->siteLangId);
                if (FatUtility::isAjaxCall()) {
                    FatUtility::dieJsonError($message);
                }
                Message::addErrorMessage($message);
                FatApp::redirectUser(CommonHelper::generateUrl('GuestUser', 'forgotPasswordForm'));
            }
        }
        $dialCode = FatApp::getPostedData('user_dial_code', FatUtility::VAR_STRING, '');
        $countryIso = FatApp::getPostedData('user_country_iso', FatUtility::VAR_STRING, '');
        $user = (0 < $withPhone) ? $dialCode . trim($post['user_phone']) : $post['user_email_username'];

        $userAuthObj = new UserAuthentication();
        if (0 < $withPhone) {
            $row = $userAuthObj->getUserByPhone($user, '', false);
        } else {
            $row = $userAuthObj->getUserByEmailOrUserName($user, '', false);
        }

        if (!$row || false === $row) {
            $message = Labels::getLabel($userAuthObj->getError(), $this->siteLangId);
            if (true === MOBILE_APP_API_CALL || FatUtility::isAjaxCall()) {
                FatUtility::dieJsonError($message);
            }
            Message::addErrorMessage($message);
            FatApp::redirectUser(CommonHelper::generateUrl('GuestUser', 'forgotPasswordForm'));
        }

        if ($row['user_is_shipping_company'] == applicationConstants::YES) {
            $message = Labels::getLabel('ERR_Shipping_user_are_not_allowed_to_place_forgot_password_request', $this->siteLangId);
            if (true === MOBILE_APP_API_CALL || FatUtility::isAjaxCall()) {
                FatUtility::dieJsonError($message);
            }
            Message::addErrorMessage($message);
            FatApp::redirectUser(CommonHelper::generateUrl('GuestUser', 'forgotPasswordForm'));
        }

        if (1 > $withPhone && $userAuthObj->checkUserPwdResetRequest($row['user_id'])) {
            $message = Labels::getLabel($userAuthObj->getError(), $this->siteLangId);
            if (true === MOBILE_APP_API_CALL || FatUtility::isAjaxCall()) {
                FatUtility::dieJsonError($message);
            }
            Message::addErrorMessage($message);
            FatApp::redirectUser(CommonHelper::generateUrl('GuestUser', 'forgotPasswordForm'));
        }

        $token = UserAuthentication::encryptPassword(FatUtility::getRandomString(20));
        $row['token'] = $token;

        $recordId = 0 < $withPhone ? $row['user_id'] : 0;
        $userAuthObj->deleteOldPasswordResetRequest($recordId);

        $db = FatApp::getDb();
        $db->startTransaction();
        // commonHelper::printArray($row); die;
        if (!$userAuthObj->addPasswordResetRequest($row)) {
            $db->rollbackTransaction();
            $message = Labels::getLabel($userAuthObj->getError(), $this->siteLangId);
            if (true === MOBILE_APP_API_CALL || FatUtility::isAjaxCall()) {
                FatUtility::dieJsonError($message);
            }
            Message::addErrorMessage($message);
            FatApp::redirectUser(CommonHelper::generateUrl('GuestUser', 'forgotPasswordForm'));
        }
        $row['link'] = CommonHelper::generateFullUrl('GuestUser', 'resetPassword', array($row['user_id'], $token));
        $row['user_email'] = $row['credential_email'];

        /* Send verification email if email not verified[ */
        $srch = new SearchBase('tbl_user_credentials');
        $srch->addCondition('credential_user_id', '=', $row['user_id']);
        $rs = $srch->getResultSet();
        $checkVerificationRow = $db->fetch($rs);

        $userObj = new User($row['user_id']);
        $notVerified = false;
        if ($checkVerificationRow['credential_verified'] == applicationConstants::NO) {
            $error = false;
            if (0 < $withPhone) {
                $row['user_country_iso'] = $countryIso;
                $row['user_dial_code'] = $dialCode;
                $row['user_phone'] = $post['user_phone'];
                if (!$userObj->userPhoneVerification($row, $this->siteLangId)) {
                    $message = !empty($userObj->getError()) ? $userObj->getError() : Labels::getLabel("ERR_ERROR_IN_SENDING_VERFICATION_SMS", $this->siteLangId);
                    $error = true;
                }
                $notVerified = true;
            } else {
                if (!$userObj->userEmailVerification($row, $this->siteLangId)) {
                    $message = Labels::getLabel("MSG_VERIFICATION_EMAIL_COULD_NOT_BE_SENT", $this->siteLangId);
                    $error = true;
                }
            }
            if (true === $error) {
                if (true === MOBILE_APP_API_CALL || FatUtility::isAjaxCall()) {
                    FatUtility::dieJsonError($message);
                }
                Message::addErrorMessage($message);
                FatApp::redirectUser(CommonHelper::generateUrl('GuestUser', 'forgotPasswordForm'));
            }
        }
        /* ] */

        if (1 > $withPhone) {
            $email = new EmailHandler();
            $uData = User::getAttributesById($row['user_id'], ['user_dial_code', 'user_phone']);
            $row = array_merge($row, $uData);
            if (!$email->sendForgotPasswordLinkEmail($this->siteLangId, $row)) {
                $db->rollbackTransaction();
                $message = Labels::getLabel("MSG_ERROR_IN_SENDING_PASSWORD_RESET_LINK_EMAIL", $this->siteLangId);
                if (true === MOBILE_APP_API_CALL || FatUtility::isAjaxCall()) {
                    FatUtility::dieJsonError($message);
                }
                Message::addErrorMessage($message);
                FatApp::redirectUser(CommonHelper::generateUrl('GuestUser', 'forgotPasswordForm'));
            }
        } else {
            if (false === $notVerified && !$userObj->resendOtp()) {
                $message = $userObj->getError();
                if (true === MOBILE_APP_API_CALL || FatUtility::isAjaxCall()) {
                    FatUtility::dieJsonError($message);
                }
                Message::addErrorMessage($message);
                FatApp::redirectUser(CommonHelper::generateUrl('GuestUser', 'forgotPasswordForm'));
            }
        }

        $db->commitTransaction();
        if (1 > $withPhone) {
            $message = Labels::getLabel("MSG_YOUR_PASSWORD_RESET_INSTRUCTIONS_TO_YOUR_EMAIL", $this->siteLangId);
        } else {
            $message = Labels::getLabel("MSG_AN_OTP_SENT_ON_YOUR_PHONE", $this->siteLangId);
        }

        if (true === MOBILE_APP_API_CALL || FatUtility::isAjaxCall()) {
            $this->set('msg', $message);
            if (true === MOBILE_APP_API_CALL) {
                if (0 < $withPhone) {
                    $this->set('data', ['user_id' => $row['user_id']]);
                }
                $this->_template->render();
            } else if (0 < $withPhone) {
                $frm = $this->getOtpForm();
                $frm->fill(['user_id' => $row['user_id']]);
                $this->set('frm', $frm);
                $json['html'] = $this->_template->render(false, false, 'guest-user/otp-form.php', true, false);
                FatUtility::dieJsonSuccess($json);
            }
            $this->_template->render(false, false, 'json-success.php');
            exit;
        }

        Message::addMessage($message);
        FatApp::redirectUser(CommonHelper::generateUrl('GuestUser', 'loginForm'));
    }

    public function resendVerification($usernameOrEmail = '')
    {
        $frm = $this->getForgotForm();
        if (empty($usernameOrEmail)) {
            FatUtility::dieJsonError(Labels::getLabel('MSG_Invalid_Request', $this->siteLangId));
        }

        $userAuthObj = new UserAuthentication();

        if (!$row = $userAuthObj->getUserByEmailOrUserName($usernameOrEmail, false, false)) {
            FatUtility::dieJsonError(Labels::getLabel($userAuthObj->getError(), $this->siteLangId));
        }

        $row['user_email'] = $row['credential_email'];
        $db = FatApp::getDb();
        $srch = new SearchBase('tbl_user_credentials');
        $srch->addCondition('credential_email', '=', $row['user_email']);
        $rs = $srch->getResultSet();
        $checkVerificationRow = $db->fetch($rs);

        $userObj = new User($row['user_id']);
        if ($checkVerificationRow['credential_verified'] != 1) {
            if (!$userObj->userEmailVerification($row, $this->siteLangId)) {
                FatUtility::dieJsonError(Labels::getLabel("MSG_VERIFICATION_EMAIL_COULD_NOT_BE_SENT", $this->siteLangId));
            } else {
                $message = Labels::getLabel("MSG_VERIFICATION_EMAIL_HAS_BEEN_SENT_AGAIN", $this->siteLangId);
                if (true === MOBILE_APP_API_CALL) {
                    $this->set('msg', $message);
                    $this->_template->render();
                }
                FatUtility::dieJsonSuccess($message);
            }
        } else {
            FatUtility::dieJsonError(Labels::getLabel("MSG_You_are_already_verified_please_login.", $this->siteLangId));
        }
    }

    public function resetPassword($userId = 0, $token = '')
    {
        $userId = FatUtility::int($userId);

        if ($userId < 1 || strlen(trim($token)) < 20) {
            Message::addErrorMessage(Labels::getLabel('MSG_INVALID_RESET_PASSWORD_REQUEST'), $this->siteLangId);
            FatApp::redirectUser(CommonHelper::generateUrl('GuestUser', 'loginForm'));
        }

        $userAuthObj = new UserAuthentication();

        if (!$userAuthObj->checkResetLink($userId, trim($token), 'form')) {
            Message::addErrorMessage($userAuthObj->getError());
            FatApp::redirectUser(CommonHelper::generateUrl('GuestUser', 'loginForm'));
        }

        $frm = $this->getResetPwdForm($userId, trim($token));
        $obj = new Extrapage();
        $pageData = $obj->getContentByPageType(Extrapage::RESET_PAGE_RIGHT_BLOCK, $this->siteLangId);
        $this->set('pageData', $pageData);
        $this->set('frm', $frm);
        $this->_template->render();
    }

    public function resetPasswordSetup()
    {
        $newPwd = FatApp::getPostedData('new_pwd');
        $confirmPwd = FatApp::getPostedData('confirm_pwd');
        $userId = FatApp::getPostedData('user_id', FatUtility::VAR_INT);
        $token = FatApp::getPostedData('token', FatUtility::VAR_STRING);

        if ($userId < 1 && strlen(trim($token)) < 20) {
            FatUtility::dieJsonError(Labels::getLabel('MSG_REQUEST_IS_INVALID_OR_EXPIRED', $this->siteLangId));
        }
        $frm = $this->getResetPwdForm($userId, $token);
        $post = $frm->getFormDataFromArray(FatApp::getPostedData());

        if ($post == false) {
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }

        $userAuthObj = new UserAuthentication();

        if (!$userAuthObj->checkResetLink($userId, trim($token), 'submit')) {
            FatUtility::dieJsonError($userAuthObj->getError());
        }

        if (!$userAuthObj->resetUserPassword($userId, $newPwd)) {
            FatUtility::dieJsonError($userAuthObj->getError());
        }

        $email = new EmailHandler();

        $userObj = new User($userId);
        $row = $userObj->getUserInfo(array(User::tblFld('name'), User::DB_TBL_CRED_PREFIX . 'email', 'user_dial_code', 'user_phone'), '', false);
        $row['link'] = CommonHelper::generateFullUrl('GuestUser', 'loginForm');
        $email->sendResetPasswordConfirmationEmail($this->siteLangId, $row);

        $this->set('msg', Labels::getLabel('MSG_PASSWORD_CHANGED_SUCCESSFULLY', $this->siteLangId));
        if (true === MOBILE_APP_API_CALL) {
            $this->_template->render();
        }
        $this->_template->render(false, false, 'json-success.php');
    }

    public function resendOtp($userId, $getOtpOnly = 0)
    {
        $userId = FatUtility::int($userId);
        $userObj = new User($userId);
        if (false == $userObj->resendOtp()) {
            FatUtility::dieJsonError($userObj->getError());
        }

        $getOtpOnly = (true === MOBILE_APP_API_CALL) ? applicationConstants::YES : $getOtpOnly;
        if (0 < $getOtpOnly) {
            $this->set('msg', Labels::getLabel('MSG_OTP_SENT!_PLEASE_CHECK_YOUR_PHONE.', $this->siteLangId));
            if (true === MOBILE_APP_API_CALL) {
                $this->_template->render();
            }
            $this->_template->render(false, false, 'json-success.php');
        }
        $this->otpForm($userId);
    }

    public function otpForm($userId = 0)
    {
        $userId = FatUtility::int($userId);
        if (1 > $userId && !isset($_SESSION[UserAuthentication::TEMP_SESSION_ELEMENT_NAME]['otpUserId'])) {
            FatApp::redirectUser(CommonHelper::generateUrl('GuestUser', 'loginForm'));
        }

        $userId = 0 < $userId ? $userId : $_SESSION[UserAuthentication::TEMP_SESSION_ELEMENT_NAME]['otpUserId'];

        $frm = $this->getOtpForm();
        $frm->fill(['user_id' => $userId]);
        $this->set('frm', $frm);
        $json['html'] = $this->_template->render(false, false, 'guest-user/otp-form.php', true, false);
        FatUtility::dieJsonSuccess($json);
    }

    public function configureEmail()
    {
        $phoneNumber = User::getAttributesById(UserAuthentication::getLoggedUserId(), 'user_phone');
        $canSendSms = (empty($phoneNumber) && SmsArchive::canSendSms(SmsTemplate::LOGIN));
        $this->set('canSendSms', $canSendSms);
        $this->_template->render();
    }

    public function changeEmailForm()
    {
        $frm = $this->getChangeEmailForm(false);

        $this->set('frm', $frm);
        $this->set('siteLangId', $this->siteLangId);
        $this->set('canSendSms', SmsArchive::canSendSms(SmsTemplate::LOGIN));
        $this->_template->render(false, false, 'account/change-email-form.php');
    }

    public function configurePhoneForm()
    {
        $phData = User::getAttributesById(UserAuthentication::getLoggedUserId(), ['user_dial_code', 'user_phone']);
        $frm = $this->getPhoneNumberForm();
        $dialCode = isset($phData['user_dial_code']) && !empty($phData['user_dial_code']) ? $phData['user_dial_code'] : '';
        $this->set('dialCode', $dialCode);
        $this->set('frm', $frm);
        $this->set('updatePhnFrm', applicationConstants::YES);
        $this->set('siteLangId', $this->siteLangId);
        $this->_template->render(false, false, 'account/change-phone-form.php');
    }

    public function updateEmail()
    {
        $emailFrm = $this->getChangeEmailForm(false);
        $post = $emailFrm->getFormDataFromArray(FatApp::getPostedData());

        if (false === $post) {
            $message = current($emailFrm->getValidationErrors());
            LibHelper::dieJsonError($message);
        }

        if ($post['new_email'] != $post['conf_new_email']) {
            $message = Labels::getLabel('MSG_New_email_confirm_email_does_not_match', $this->siteLangId);
            LibHelper::dieJsonError($message);
        }

        $userObj = new User(UserAuthentication::getLoggedUserId());
        $srch = $userObj->getUserSearchObj(array('user_id', 'credential_email', 'user_name', 'user_dial_code', 'user_phone'));
        $rs = $srch->getResultSet();

        if (!$rs) {
            $message = Labels::getLabel('MSG_INVALID_REQUEST', $this->siteLangId);
            LibHelper::dieJsonError($message);
        }

        $data = FatApp::getDb()->fetch($rs, 'user_id');
        if ($data === false || $data['credential_email'] != '') {
            $message = Labels::getLabel('MSG_INVALID_REQUEST', $this->siteLangId);
            LibHelper::dieJsonError($message);
        }

        $phone = !empty($data['user_phone']) ? $data['user_dial_code'] . $data['user_phone'] : '';
        $arr = array(
            'user_name' => $data['user_name'],
            'user_phone' => $phone,
            'user_email' => $post['new_email']
        );

        if (!$this->userEmailVerifications($userObj, $arr, true)) {
            $message = Labels::getLabel('MSG_ERROR_IN_SENDING_VERFICATION_EMAIL', $this->siteLangId);
            LibHelper::dieJsonError($message);
        }

        $this->set('msg', Labels::getLabel('MSG_UPDATE_EMAIL_REQUEST_SENT_SUCCESSFULLY._YOU_NEED_TO_VERIFY_YOUR_NEW_EMAIL_ADDRESS_BEFORE_ACCESSING_OTHER_MODULES', $this->siteLangId));
        if (true === MOBILE_APP_API_CALL) {
            $this->_template->render();
        }
        $this->_template->render(false, false, 'json-success.php');
    }

    public function logout()
    {
        UserAuthentication::logout();
        if (true === MOBILE_APP_API_CALL) {
            $fcmToken = FatApp::getPostedData('fcmToken', FatUtility::VAR_STRING, '');
            $userType = FatApp::getPostedData('userType', FatUtility::VAR_INT, User::USER_TYPE_BUYER);
            if (empty($fcmToken)) {
                FatUtility::dieJSONError(Labels::getLabel('MSG_INVALID_REQUEST', $this->siteLangId));
            }

            $values = User::getUserAuthFcmFormattedData($userType, $fcmToken, null, applicationConstants::NO);
            $where = array('smt' => 'uauth_fcm_id = ?', 'vals' => [$fcmToken]);
            if (!UserAuthentication::updateFcmDeviceToken($values, $where)) {
                LibHelper::dieJsonError(Labels::getLabel('MSG_UNABLE_TO_UPDATE_FCM_TOKEN', $this->siteLangId));
            }

            $this->_template->render();
        }

        FatApp::redirectUser(CommonHelper::generateUrl('GuestUser', 'loginForm'));
    }

    private function getForgotForm($withPhone = 0)
    {
        $frm = new Form('frmPwdForgot');
        $frm->addHiddenField('', 'withPhone', $withPhone);
        if (1 > $withPhone) {
            $frm->addRequiredField(Labels::getLabel('LBL_Username_or_email', $this->siteLangId), 'user_email_username');
        } else {
            $frm->addRequiredField(Labels::getLabel('LBL_PHONE_NUMBER', $this->siteLangId), 'user_phone', '', array('placeholder' => Labels::getLabel('LBL_PHONE_NUMBER', $this->siteLangId)));
        }

        CommonHelper::addCaptchaField($frm);
        $label = (1 > $withPhone) ? Labels::getLabel('LBL_SUBMIT', $this->siteLangId) : Labels::getLabel('LBL_GET_OTP', $this->siteLangId);
        $frm->addSubmitButton('', 'btn_submit', $label);
        return $frm;
    }

    private function getResetPwdForm($uId, $token)
    {
        $frm = new Form('frmResetPwd');
        $fld_np = $frm->addPasswordField(Labels::getLabel('LBL_NEW_PASSWORD', $this->siteLangId), 'new_pwd');
        $fld_np->htmlAfterField = '<p class="note">' . sprintf(Labels::getLabel('LBL_Example_password', $this->siteLangId), 'User@123') . '</p>';
        $fld_np->requirements()->setRequired();
        $fld_np->requirements()->setRegularExpressionToValidate(ValidateElement::PASSWORD_REGEX);
        $fld_np->requirements()->setCustomErrorMessage(Labels::getLabel('MSG_PASSWORD_MUST_BE_EIGHT_CHARACTERS_LONG_AND_ALPHANUMERIC', $this->siteLangId));
        $fld_cp = $frm->addPasswordField(Labels::getLabel('LBL_CONFIRM_NEW_PASSWORD', $this->siteLangId), 'confirm_pwd');
        $fld_cp->requirements()->setRequired();
        $fld_cp->requirements()->setCompareWith('new_pwd', 'eq', '');

        $frm->addHiddenField('', 'user_id', $uId, array('id' => 'user_id'));
        $frm->addHiddenField('', 'token', $token, array('id' => 'token'));

        $frm->addSubmitButton('', 'btn_submit', Labels::getLabel('LBL_RESET_PASSWORD', $this->siteLangId));
        return $frm;
    }

    public function redirectAbandonedCartUser($userId, $selProdId, $reminderEmail = false)
    {
        $userId = FatUtility::int($userId);
        $selProdId = FatUtility::int($selProdId);
        if (!UserAuthentication::isUserLogged()) {
            FatApp::redirectUser(CommonHelper::generateUrl('GuestUser', 'loginForm'));
        }
        if ($reminderEmail == true) {
            FatApp::redirectUser(CommonHelper::generateUrl('Cart'));
        }

        $cart = new Cart($userId);
        if (!$cart->hasProducts()) {
            FatApp::redirectUser(CommonHelper::generateUrl('Products', 'view', array($selProdId)));
        }
        $cartProducts = $cart->getProducts($this->siteLangId);
        $found = false;
        foreach ($cartProducts as $key => $data) {
            if ($data['selprod_id'] == $selProdId) {
                $found = true;
                break;
            }
        }
        if ($found == true) {
            FatApp::redirectUser(CommonHelper::generateUrl('Cart'));
        } else {
            FatApp::redirectUser(CommonHelper::generateUrl('Products', 'view', array($selProdId)));
        }
    }

}
