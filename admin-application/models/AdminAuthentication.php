<?php

class AdminAuthentication extends FatModel
{

    const SESSION_ELEMENT_NAME = 'sampleAdmin';

    public static function isAdminLogged($ip = '')
    {
        if ($ip == '') {
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        if (isset($_SESSION[static::SESSION_ELEMENT_NAME]) && $_SESSION[static::SESSION_ELEMENT_NAME]['admin_ip'] == $ip) {
            return true;
        }

        return false;
    }

    public function login($username, $password, $ip)
    {
        $objUserAuthentication = new UserAuthentication();
        if ($objUserAuthentication->isBruteForceAttempt($ip, $username)) {
            $this->error = 'Login attempt limit exceeded. Please try after some time.';
            return false;
        }

        $password = UserAuthentication::encryptPassword($password);
        $db = FatApp::getDb();
        $srch = new SearchBase('tbl_admin');
        $srch->addCondition('admin_username', '=', $username);
        $srch->addCondition('admin_password', '=', $password);
        $rs = $srch->getResultSet();


        if (!$row = $db->fetch($rs)) {
            $objUserAuthentication->logFailedAttempt($ip, $username);
            $this->error = 'Invalid Username or Password';
            return false;
        }
        if (strtolower($row['admin_username']) != strtolower($username) || $row['admin_password'] != $password) {
            $objUserAuthentication->logFailedAttempt($ip, $username);
            $this->error = 'Invalid Username or Password';
            return false;
        }

        $_SESSION[static::SESSION_ELEMENT_NAME] = array(
            'admin_id' => $row['admin_id'],
            'admin_name' => $row['admin_name'],
            'admin_ip' => $ip
        );

        return true;
    }

    public static function getLoggedAdminAttribute($key, $returnNullIfNotLogged = false)
    {
        if (!static::isAdminLogged()) {
            if ($returnNullIfNotLogged) {
                return null;
            }
            if (FatUtility::isAjaxCall()) {
                FatUtility::dieWithError('Your session seems to be expired.');
            }
            FatApp::redirectUser(FatUtility::generateUrl());
        }

        return $_SESSION[static::SESSION_ELEMENT_NAME][$key];
    }

    public function logout()
    {
        if (isset($_SESSION[static::SESSION_ELEMENT_NAME])) {
            unset($_SESSION[static::SESSION_ELEMENT_NAME]);
        }
        return true;
    }

}
