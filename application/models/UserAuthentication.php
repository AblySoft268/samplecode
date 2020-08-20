<?php

class UserAuthentication extends FatModel
{

    public const SESSION_ELEMENT_NAME = 'yogrowcerUserSession';
    public const AFFILIATE_SESSION_ELEMENT_NAME = 'yogrowcerAffiliateSession';
    public const YOKARTUSER_COOKIE_NAME = '_uyogrowcer';
    public const TEMP_SESSION_ELEMENT_NAME = 'yogrowcerTempUserSession';
    public const DB_TBL_USER_PRR = 'tbl_user_password_reset_requests';
    public const DB_TBL_UPR_PREFIX = 'uprr_';
    public const DB_TBL_USER_AUTH = 'tbl_user_auth_token';
    public const DB_TBL_UAUTH_PREFIX = 'uauth_';
    public const TOKEN_LENGTH = 32;

    private $commonLangId;

    public const AFFILIATE_REG_STEP1 = 1;
    public const AFFILIATE_REG_STEP2 = 2;
    public const AFFILIATE_REG_STEP3 = 3;
    public const AFFILIATE_REG_STEP4 = 4;

    public function __construct()
    {
        $this->commonLangId = CommonHelper::getLangId();
    }

    public static function getAffiliateRegisterationStepArr($langId)
    {
        $langId = FatUtility::int($langId);
        if ($langId == 0) {
            trigger_error("Language Id not specified.", E_USER_ERROR);
        }
        return array(
            static::AFFILIATE_REG_STEP1 => Labels::getLabel('LBL_Personal_Details', $langId),
            static::AFFILIATE_REG_STEP2 => Labels::getLabel('LBL_Company_Details', $langId),
            static::AFFILIATE_REG_STEP3 => Labels::getLabel('LBL_Payment_Information', $langId),
            static::AFFILIATE_REG_STEP4 => Labels::getLabel('LBL_Confirmation', $langId),
        );
    }

    public static function encryptPassword($pass)
    {
        return md5(PASSWORD_SALT . $pass . PASSWORD_SALT);
    }

    public function logFailedAttempt($ip, $username)
    {
        $db = FatApp::getDb();

        $db->deleteRecords(
                'tbl_failed_login_attempts',
                array(
                    'smt' => 'attempt_time < ?',
                    'vals' => array(date('Y-m-d H:i:s', strtotime("-7 Day"))))
        );

        $db->insertFromArray(
                'tbl_failed_login_attempts',
                [
                    'attempt_username' => $username,
                    'attempt_ip' => $ip,
                    'attempt_time' => date('Y-m-d H:i:s')
                ]
        );
    }

    public function clearFailedAttempt($ip, $username)
    {
        $db = FatApp::getDb();
        return $db->deleteRecords(
                        'tbl_failed_login_attempts',
                        array(
                            'smt' => 'attempt_username = ? and attempt_ip = ?',
                            'vals' => array($username, $ip))
        );
    }

    public function isBruteForceAttempt($ip, $username)
    {
        $db = FatApp::getDb();
        $ips = explode(',', FatApp::getConfig("CONF_WHITELISTED_IP", FatUtility::VAR_STRING, ''));
        if (in_array($ip, $ips)) {
            return false;
        }
        $srch = new SearchBase('tbl_failed_login_attempts');
        $srch->addCondition('attempt_ip', '=', $ip)->attachCondition('attempt_username', '=', $username);
        $srch->addCondition('attempt_time', '>=', date('Y-m-d H:i:s', strtotime("-5 minutes")));
        $srch->addFld('COUNT(*) AS total');
        $rs = $srch->getResultSet();

        $row = $db->fetch($rs);

        return ($row['total'] > 3);
    }

    public static function doCookieLogin($returnAuthRow = true)
    {
        $cookieName = self::YOKARTUSER_COOKIE_NAME;

        if (!array_key_exists($cookieName, $_COOKIE)) {
            return false;
        }

        $token = $_COOKIE[$cookieName];
        $authRow = false;

        $authRow = self::checkLoginTokenInDB($token);

        if (strlen($token) != self::TOKEN_LENGTH || empty($authRow)) {
            self::clearLoggedUserLoginCookie();
            return false;
        }

        $browser = CommonHelper::userAgent();
        if (strtotime($authRow['uauth_expiry']) < strtotime('now') || $authRow['uauth_browser'] != $browser || CommonHelper::userIp() != $authRow['uauth_last_ip']) {
            self::clearLoggedUserLoginCookie();
            return false;
        }

        $ths = new UserAuthentication();
        if ($ths->loginByCookie($authRow)) {
            if (true === $returnAuthRow) {
                return $authRow;
            }
            return true;
        }
        return false;
    }

    public function guestLogin($useremail, $name, $ip)
    {
        $db = FatApp::getDb();
        $srch = User::getSearchObject(true, 0, false);
        $srch->addCondition('credential_email', '=', $useremail);
        $rs = $srch->getResultSet();
        $row = $db->fetch($rs);
        if (!empty($row)) {
            if ($row['user_is_buyer'] != applicationConstants::YES) {
                $this->error = Labels::getLabel('MSG_Please_login_with_buyer_account', $this->commonLangId);
                return false;
            }

            if ($row['credential_verified'] == applicationConstants::YES && $row['credential_active'] == applicationConstants::ACTIVE) {
                $this->error = Labels::getLabel('ERR_YOUR_ACCOUNT_ALREADY_EXIST._PLEASE_LOGIN', $this->commonLangId);
                return false;
            }

            $rowUser = User::getAttributesById($row['user_id']);

            $rowUser['user_ip'] = $ip;
            $rowUser['user_is_guest'] = true;
            $rowUser['user_email'] = $row['credential_email'];
            Cart::setCartAttributes($row['user_id']);
            $this->setSession($rowUser);
            return true;
        }


        $userObj = new User();
        $db->startTransaction();

        $data = array(
            'user_name' => $name,
            'user_username' => $useremail,
            'user_email' => $useremail,
            'user_is_buyer' => 1,
            'user_preferred_dashboard' => User::USER_BUYER_DASHBOARD,
            'user_registered_initially_for' => User::USER_TYPE_BUYER,
        );
        $userObj->assignValues($data);

        if (!$userObj->save()) {
            $db->rollbackTransaction();
            $this->error = Labels::getLabel("MSG_USER_COULD_NOT_BE_SET", $this->commonLangId) . $userObj->getError();
            return false;
        }
        $userId = $userObj->getMainTableRecordId();

        $active = FatApp::getConfig('CONF_ADMIN_APPROVAL_REGISTRATION', FatUtility::VAR_INT, 1) ? 0 : 1;
        $verify = FatApp::getConfig('CONF_EMAIL_VERIFICATION_REGISTRATION', FatUtility::VAR_INT, 1) ? 0 : 1;

        $pass = CommonHelper::getRandomPassword(8);
        if (!$userObj->setLoginCredentials($useremail, $useremail, $pass, $active, $verify)) {
            $this->error = Labels::getLabel("MSG_LOGIN_CREDENTIALS_COULD_NOT_BE_SET", $this->commonLangId) . $userObj->getError();
            $db->rollbackTransaction();
            return false;
        }

        if (FatApp::getConfig('CONF_NOTIFY_ADMIN_REGISTRATION', FatUtility::VAR_INT, 1)) {
            if (!$userObj->notifyAdminRegistration($data, $this->commonLangId)) {
                $this->error = Labels::getLabel("MSG_NOTIFICATION_EMAIL_COULD_NOT_BE_SENT", $this->commonLangId);
                $db->rollbackTransaction();
                return false;
            }
        }

        if (FatApp::getConfig('CONF_WELCOME_EMAIL_REGISTRATION', FatUtility::VAR_INT, 1)) {

            $uData = User::getAttributesById($userId, ['user_dial_code', 'user_phone']);
            $data['user_phone'] = !empty($uData['user_phone']) ? $uData['user_dial_code'] . $uData['user_phone'] : '';

            if (!$userObj->guestUserWelcomeEmail($data, $this->commonLangId)) {
                $this->error = Labels::getLabel("MSG_WELCOME_EMAIL_COULD_NOT_BE_SENT", $this->commonLangId);
                $db->rollbackTransaction();
                return false;
            }
        }

        $db->commitTransaction();

        $srch = User::getSearchObject(true, 0, false);
        $srch->addCondition('credential_email', '=', $useremail);
        $rs = $srch->getResultSet();
        if (!$row = $db->fetch($rs)) {
            $this->logFailedAttempt($ip, $useremail);
            $this->error = Labels::getLabel('ERR_INVALID_USERNAME_OR_PASSWORD', $this->commonLangId);
            return false;
        }

        $rowUser = User::getAttributesById($row['credential_user_id']);

        $rowUser['user_ip'] = $ip;
        $rowUser['user_is_guest'] = true;
        $rowUser['user_email'] = $row['credential_email'];
        Cart::setCartAttributes($row['credential_user_id']);
        $this->setSession($rowUser);

        $this->clearFailedAttempt($ip, $useremail);

        return true;
    }

    public function login($username, $password, $ip, $encryptPassword = true, $isAdmin = false, $tempUserId = 0, $userType = 0, $withPhone = false)
    {
        $db = FatApp::getDb();
        if ($this->isBruteForceAttempt($ip, $username)) {
            $userSrch = User::getSearchObject(true, 0, false);
            $userSrch->addCondition('credential_username', '=', $username);
            $userRs = $userSrch->getResultSet();

            if ($row = $db->fetch($userRs)) {
                $email = new EmailHandler();
                $email->failedLoginAttempt(FatApp::getConfig('CONF_DEFAULT_SITE_LANG', FatUtility::VAR_INT, 1), $row);
            }

            $this->error = Labels::getLabel('ERR_LOGIN_ATTEMPT_LIMIT_EXCEEDED_PLEASE_TRY_LATER', $this->commonLangId);
            return false;
        }

        if ($encryptPassword) {
            $password = UserAuthentication::encryptPassword($password);
        }

        $srch = User::getSearchObject(true, 0, false);
        $condition = $srch->addCondition('credential_username', '=', $username);
        $condition->attachCondition('credential_email', '=', $username, 'OR');
        $condition->attachCondition('mysql_func_CONCAT(user_dial_code, user_phone)', '=', $username, 'OR', true);
        $srch->addCondition('credential_password', '=', $password);
        if (0 < $userType) {
            switch ($userType) {
                case User::USER_TYPE_BUYER:
                    $srch->addCondition('user_is_buyer', '=', applicationConstants::YES);
                    break;
                case User::USER_TYPE_SELLER:
                    $srch->addCondition('user_is_supplier', '=', applicationConstants::YES);
                    break;
                case User::USER_TYPE_ADVERTISER:
                    $srch->addCondition('user_is_advertiser', '=', applicationConstants::YES);
                    break;
                case User::USER_TYPE_AFFILIATE:
                    $srch->addCondition('user_is_affiliate', '=', applicationConstants::YES);
                    break;
                default:
                    $srch->addCondition('user_registered_initially_for', '=', $userType);
                    break;
            }
        }

        $rs = $srch->getResultSet();
        if (!$row = $db->fetch($rs)) {
            $this->error = Labels::getLabel('ERR_INVALID_USERNAME_OR_PASSWORD', $this->commonLangId);
            if ($withPhone) {
                $this->error = Labels::getLabel('ERR_INVALID_PHONE_NUMBER_OR_PASSWORD', $this->commonLangId);
            }
            return false;
        }

        if ($row && $row['user_deleted'] == applicationConstants::YES) {
            $this->logFailedAttempt($ip, $username);
            $this->error = Labels::getLabel('ERR_USER_INACTIVE_OR_DELTED', $this->commonLangId);
            return false;
        }

        if ($row['user_is_shipping_company'] == applicationConstants::YES) {
            $this->logFailedAttempt($ip, $username);
            $this->error = Labels::getLabel('ERR_Shipping_user_are_not_allowed_to_login', $this->commonLangId);
            return false;
        }

        if ((!(strtolower($row['credential_username']) === strtolower($username) || strtolower($row['credential_email']) === strtolower($username) || ($row['user_dial_code'] . $row['user_phone']) === $username)) || $row['credential_password'] !== $password) {
            $this->logFailedAttempt($ip, $username);
            $this->error = Labels::getLabel('ERR_INVALID_USERNAME_OR_PASSWORD', $this->commonLangId);
            return false;
        }
        if (!$isAdmin) {
            if ($row['credential_verified'] != applicationConstants::YES) {
                $emailErrorMsg = str_replace("{clickhere}", '<a href="javascript:void(0)" onclick="resendVerificationLink(' . "'" . $username . "'" . ')">' . Labels::getLabel('LBL_Click_Here', $this->commonLangId) . '</a>', Labels::getLabel('MSG_Your_Account_verification_is_pending_{clickhere}', $this->commonLangId));

                $message = Labels::getLabel('MSG_THIS_PHONE_NUMBER_IS_NOT_VERIFIED_YET._DO_YOU_WANT_TO_CONTINUE?_{CONTINUE-BTN}', $this->commonLangId);
                $replacements = [
                    '{CONTINUE-BTN}' => '<a class="btn btn-outline-white" href="javascript:void(0);" onclick="loginPopupOtp(' . $row['user_id'] . ', ' . applicationConstants::NO . ')">' . Labels::getLabel('MSG_PROCEED', $this->commonLangId) . '</a>'
                ];
                $phoneErrorMsg = CommonHelper::replaceStringData($message, $replacements);

                if (strtolower($row['credential_email']) === strtolower($username)) {
                    $this->error = $emailErrorMsg;
                } else if (($row['user_dial_code'] . $row['user_phone']) === $username) {
                    $json['userId'] = FatUtility::convertToType($row['user_id'], FatUtility::VAR_STRING);
                    $this->error = $phoneErrorMsg;
                } else {
                    $this->error = !empty($row['credential_email']) ? $emailErrorMsg : $phoneErrorMsg;
                }

                if (true === MOBILE_APP_API_CALL) {
                    $this->error = Labels::getLabel('MSG_Your_Account_verification_is_pending', $this->commonLangId);
                }

                if (FatUtility::isAjaxCall() || true === MOBILE_APP_API_CALL) {
                    $json['status'] = 0;
                    $json['msg'] = $this->error;
                    $json['notVerified'] = 1;
                    die(json_encode($json));
                }
                return false;
            }

            if ($row['credential_active'] != applicationConstants::ACTIVE) {
                $this->error = Labels::getLabel('ERR_YOUR_ACCOUNT_HAS_BEEN_DEACTIVATED_OR_NOT_ACTIVE', $this->commonLangId);
                return false;
            }

            $rowUser = User::getAttributesById($row['credential_user_id']);
            if (0 < $rowUser['user_parent']) {
                $parentUser = new User($rowUser['user_parent']);
                $parentSrch = $parentUser->getUserSearchObj();
                $parentSrch->addCondition('credential_active', '=', applicationConstants::ACTIVE);
                $rs = $parentSrch->getResultSet();
                $parentData = FatApp::getDb()->fetch($rs);
                if (false == $parentData || null == $parentData) {
                    $this->error = Labels::getLabel('ERR_YOUR_ACCOUNT_HAS_BEEN_DEACTIVATED_OR_NOT_ACTIVE', $this->commonLangId);
                    return false;
                }
            }
        } else {
            $rowUser = User::getAttributesById($row['credential_user_id']);
        }



        $rowUser['user_ip'] = $ip;
        $rowUser['user_email'] = $row['credential_email'];
        Cart::setCartAttributes($row['credential_user_id'], $tempUserId);
        $this->setSession($rowUser);
        
        /* clear failed login attempt for the user [ */
        $this->clearFailedAttempt($ip, $username);
        /* ] */

        return true;
    }

    private function setSession($data)
    {
        session_regenerate_id();
        $_SESSION[UserAuthentication::SESSION_ELEMENT_NAME] = array(
            'user_id' => $data['user_id'],
            'user_name' => $data['user_name'],
            'user_ip' => $data['user_ip'],
            'user_email' => $data['user_email'],
            'user_is_guest' => isset($data['user_is_guest']) ? $data['user_is_guest'] : false,
        );
        return true;
    }

    private function loginByCookie($authRow)
    {
        $userObj = new User($authRow['uauth_user_id']);
        if ($row = $userObj->getProfileData()) {
            if ($row['credential_verified'] != applicationConstants::YES) {
                return false;
            }

            if ($row['credential_active'] != applicationConstants::YES) {
                return false;
            }

            $row['user_ip'] = CommonHelper::userIp();
            $this->setSession($row);
            return true;
        }
        return false;
    }

    public static function clearLoggedUserLoginCookie()
    {
        if (!isset($_COOKIE[static::YOKARTUSER_COOKIE_NAME])) {
            return false;
        }

        $db = FatApp::getDb();
        if (strlen($_COOKIE[static::YOKARTUSER_COOKIE_NAME])) {
            $db->deleteRecords(
                    static::DB_TBL_USER_AUTH,
                    array(
                        'smt' => static::DB_TBL_UAUTH_PREFIX . 'token = ?',
                        'vals' => array($_COOKIE[static::YOKARTUSER_COOKIE_NAME])
                    )
            );
        }

        setcookie($_COOKIE[static::YOKARTUSER_COOKIE_NAME], '', time() - 3600, CONF_WEBROOT_URL);
        return true;
    }

    public static function isGuestUserLogged($ip = '')
    {
        if ($ip == '') {
            $ip = CommonHelper::getClientIp();
        }

        if (isset($_SESSION [static::SESSION_ELEMENT_NAME])
                /* && $_SESSION [static::SESSION_ELEMENT_NAME] ['user_ip'] == $ip */ && $_SESSION [static::SESSION_ELEMENT_NAME] ['user_is_guest'] == true && is_numeric($_SESSION [static::SESSION_ELEMENT_NAME] ['user_id']) && 0 < $_SESSION [static::SESSION_ELEMENT_NAME] ['user_id']
        ) {
            return true;
        }
        return false;
    }

    public static function logout($fcmToken = '')
    {
        if (isset($_SESSION['access_token'])) {
            unset($_SESSION['access_token']);
        }

        include_once CONF_INSTALLATION_PATH . 'library/facebook/facebook.php';
        $facebook = new Facebook(
                array(
            'appId' => FatApp::getConfig("CONF_FACEBOOK_APP_ID"),
            'secret' => FatApp::getConfig("CONF_FACEBOOK_APP_SECRET"),
                )
        );

        $user = $facebook->getUser();

        if ($user) {
            unset($_SESSION['fb_' . FatApp::getConfig("CONF_FACEBOOK_APP_ID") . '_code']);
            unset($_SESSION['fb_' . FatApp::getConfig("CONF_FACEBOOK_APP_ID") . '_access_token']);
            unset($_SESSION['fb_' . FatApp::getConfig("CONF_FACEBOOK_APP_ID") . '_user_id']);
        }

        unset($_SESSION[UserAuthentication::SESSION_ELEMENT_NAME]);

        unset($_SESSION[UserAuthentication::SESSION_ELEMENT_NAME]);
        unset($_SESSION[UserAuthentication::AFFILIATE_SESSION_ELEMENT_NAME]);
        unset($_SESSION['activeTab']);
        unset($_SESSION['referer_page_url']);
        unset($_SESSION['registered_supplier']['id']);

        self::clearLoggedUserLoginCookie();
    }

    public static function isUserLogged($ip = '', $token = '')
    {
        if ($ip == '') {
            $ip = CommonHelper::getClientIp();
        }

        $token = empty($token) ? CommonHelper::getAppToken() : $token;

        if ($token != '' && static::doAppLogin($token)) {
            return true;
        }

        if (isset($_SESSION [static::SESSION_ELEMENT_NAME])
                /* && $_SESSION [static::SESSION_ELEMENT_NAME] ['user_ip'] == $ip */ && $_SESSION [static::SESSION_ELEMENT_NAME] ['user_is_guest'] == false && is_numeric($_SESSION [static::SESSION_ELEMENT_NAME] ['user_id']) && 0 < $_SESSION [static::SESSION_ELEMENT_NAME] ['user_id']
        ) {
            return true;
        }

        if (static::doCookieLogin(false)) {
            return true;
        }

        return false;
    }

    public static function getLoggedUserAttribute($attr, $returnNullIfNotLogged = false)
    {
        if (!static::isUserLogged() && !static::isGuestUserLogged()) {
            if ($returnNullIfNotLogged) {
                return null;
            }
            $message = Labels::getLabel('MSG_USER_NOT_LOGGED', CommonHelper::getLangId());
            if (true === MOBILE_APP_API_CALL) {
                LibHelper::dieJsonError($message);
            }
            Message::addErrorMessage($message);
            FatUtility::dieWithError(Message::getHtml());
        }


        if (array_key_exists($attr, $_SESSION [static::SESSION_ELEMENT_NAME])) {
            return $_SESSION [static::SESSION_ELEMENT_NAME][$attr];
        }

        return User::getAttributesById($_SESSION[static::SESSION_ELEMENT_NAME]['user_id'], $attr);
    }

    public static function getLoggedUserId($returnZeroIfNotLogged = false)
    {
        return FatUtility::int(static::getLoggedUserAttribute('user_id', $returnZeroIfNotLogged));
    }

    public static function checkLogin($redirect = true)
    {
        if (static::isUserLogged() || static::isGuestUserLogged()) {
            return true;
        }
        if (true === MOBILE_APP_API_CALL) {
            $message = Labels::getLabel('MSG_Session_seems_to_be_expired', CommonHelper::getLangId());
            FatUtility::dieJsonError($message);
        }

        if (FatUtility::isAjaxCall()) {
            Message::addErrorMessage(Labels::getLabel('MSG_Session_seems_to_be_expired', CommonHelper::getLangId()));
            FatUtility::dieWithError(Message::getHtml());
        }

        $_SESSION['referer_page_url'] = CommonHelper::getCurrUrl();
        if ($redirect == true) {
            FatApp::redirectUser(CommonHelper::generateUrl('GuestUser', 'loginForm'));
        }

        return false;
    }
}
