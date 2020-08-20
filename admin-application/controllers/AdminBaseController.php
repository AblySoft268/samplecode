<?php

class AdminBaseController extends FatController
{
    public function __construct($action)
    {
        if (!AdminAuthentication::isAdminLogged()) {
            FatApp::redirectUser(FatUtility::generateUrl('AdminGuest', 'loginForm'));
        }
        parent::__construct($action);

        if (!FatUtility::isAjaxCall()) {
            $this->set('adminName', AdminAuthentication::getLoggedAdminAttribute('admin_name'));
            // You can set the navigation etc based on permissions here.
        }
    }

}
