<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');

$routes->add('Generate/generate_pdf', 'Generate::generate_pdf');

$routes->group("api", function ($routes) {

    $routes->group("list", ['namespace' => 'App\Controllers\Api'], static function ($routes) {
        $routes->get("managementType", "ListController::managementType");
        $routes->get("visitorType", "ListController::visitorType");
        $routes->get("states", "ListController::states");
    });

    $routes->group("management", ['namespace' => 'App\Controllers\Api\Management'], static function ($routes) {
        $routes->post("register", "AuthController::register");
        $routes->post("emailCheck", "AuthController::emailCheck");
        $routes->post("login", "AuthController::login");
        $routes->post("forgotPassword", "AuthController::forgotPassword");
        $routes->post("codeCheck", "AuthController::codeCheck");
        $routes->post("updatePassword", "AuthController::updatePassword");
                
        $routes->post("profileUpdate", "ManagementController::profileUpdate");
        $routes->post("managementUniqueKey", "ManagementController::managementUniqueKey");
        $routes->post("managementUniqueKeyToId", "ManagementController::managementUniqueKeyToId");
        $routes->post("managementPerson", "ManagementController::managementPerson");
        $routes->post("visitorAdd", "ManagementController::visitorAdd");
        $routes->post("visitorOut", "ManagementController::visitorOut");

        $routes->post("managementPin", "ManagementController::managementPin");
        $routes->post("managementPinUpdate", "ManagementController::managementPinUpdate");
        
        $routes->group("keys", function ($routes) {
            $routes->post("add", "KeyController::add");
            $routes->post("edit", "KeyController::edit");
            $routes->post("delete", "KeyController::delete");
            $routes->post("list", "KeyController::list");
            $routes->post("userList", "KeyController::userList");
            $routes->post("list1", "KeyController::list1");
            $routes->post("history", "KeyController::history");
            $routes->post("assign", "KeyController::assign");
            $routes->post("return", "KeyController::return");
            $routes->post("checkout", "KeyController::checkout");
            
        });
        
        $routes->group("form", function ($routes) {
            $routes->post("add", "FormController::add");
            $routes->post("edit", "FormController::edit");
            $routes->post("delete", "FormController::delete");
            $routes->post("list", "FormController::list");
            $routes->post("active", "FormController::active");
            $routes->post("activeData", "FormController::activeData");
        });
        
        $routes->group("staff", function ($routes) {
            $routes->post("add", "StaffController::add");
            $routes->post("edit", "StaffController::edit");
            $routes->post("delete", "StaffController::delete");
            $routes->post("list", "StaffController::list");
            $routes->post("view", "StaffController::view");
            $routes->post("staffWeekSchedule", "StaffController::staffWeekSchedule");
            $routes->post("uploadCsv", "StaffController::uploadCsv");
            $routes->post("signOut", "StaffController::signOut");
            $routes->post("history", "StaffController::history");
        });
        
        $routes->group("message", function ($routes) {
            $routes->post("send", "MessagingController::send");
        });
        
        $routes->group("question", function ($routes) {
            $routes->post("add", "QuestionController::add");
            $routes->post("edit", "QuestionController::edit");
            $routes->post("delete", "QuestionController::delete");
            $routes->post("list", "QuestionController::list");
        });
        
        $routes->group("visitor", function ($routes) {
            $routes->post("add", "VisitorController::add");
            $routes->post("exites", "VisitorController::exites");
            $routes->post("search", "VisitorController::search");
            $routes->post("scan-qr", "VisitorController::scanQr");
            $routes->post("records", "VisitorController::records");
            $routes->post("checkoutNumber", "VisitorController::checkoutNumber");
            $routes->post("checkoutKey", "VisitorController::checkoutKey");
        });
        
        $routes->group("report", function ($routes) {
            $routes->post("curerntVisitor", "ReportController::curerntVisitor");
            $routes->post("dailyData", "ReportController::dailyData");
            $routes->post("weeklyData", "ReportController::weeklyData");
            $routes->post("filterData", "ReportController::filterData");
            $routes->post("keyAvalible", "ReportController::keyAvalible");
            $routes->post("keyOnLoan", "ReportController::keyOnLoan");
            $routes->post("allKeyList", "ReportController::allKeyList");
            $routes->post("endOfDayKey", "ReportController::endOfDayKey");
            $routes->post("weeklyStaffList", "ReportController::weeklyStaffList"); 
            $routes->post("rollCallList", "ReportController::rollCallList");             
            $routes->post("staffKey", "ReportController::staffKey");
        });

        $routes->group("staffReport", function ($routes) {
            $routes->post("filterSelectList", "StaffReportController::filterSelectList");
            $routes->post("filterData", "StaffReportController::filterData");
            $routes->post("currentWeekByName", "StaffReportController::currentWeekByName");
            $routes->post("currentWeekByDate", "StaffReportController::currentWeekByDate");
            $routes->post("currentMonthByName", "StaffReportController::currentMonthByName");
            $routes->post("currentMonthByDate", "StaffReportController::currentMonthByDate");
            $routes->post("lastWeekByName", "StaffReportController::lastWeekByName");
            $routes->post("lastWeekByDate", "StaffReportController::lastWeekByDate");
            $routes->post("lastMonthByName", "StaffReportController::lastMonthByName");
            $routes->post("lastMonthByDate", "StaffReportController::lastMonthByDate");
        });
        
        $routes->group("alert", function ($routes) {
            $routes->post("alertData", "AlertController::alertData");
            $routes->post("updateAlertData", "AlertController::updateAlertData");
            $routes->post("keyAlertData", "AlertController::keyAlertData");
            $routes->post("updateKeyAlertData", "AlertController::updateKeyAlertData");
        });

        $routes->group("admin", function ($routes) {
            $routes->post("list", "AdminController::list");
            $routes->post("accessUpdate", "AdminController::accessUpdate");
            $routes->post("alertUpdate", "AdminController::alertUpdate");
            $routes->post("passwordUpdate", "AdminController::passwordUpdate");            
        });

    });

    $routes->group("visitor", ['namespace' => 'App\Controllers\Api\Visitor'], static function ($routes) {
        $routes->post("register", "AuthController::register");
        $routes->post("emailCheck", "AuthController::emailCheck");
        $routes->post("mobileCheck", "AuthController::mobileCheck");
        $routes->post("login", "AuthController::login");
        $routes->post("signout", "AuthController::signout");
        
        $routes->post("profileUpdate", "VisitorController::profileUpdate");
        $routes->post("qrCheck", "VisitorController::qrCheck");
        $routes->post("acceptEntry", "VisitorController::acceptEntry");
        $routes->post("records", "VisitorController::records");
        $routes->post("activeManagementData", "VisitorController::activeManagementData");
        $routes->post("keyReturn", "VisitorController::keyReturn");
        $routes->post("keyList", "VisitorController::keyList");
        $routes->post("distanceCheck", "VisitorController::distanceCheck");
        $routes->post("visitorFind", "VisitorController::visitorFind");
        $routes->post("visitorQrtoData", "VisitorController::visitorQrtoData");
        
        $routes->group("company", function ($routes) {
            $routes->post("add", "CompanyController::add");
            $routes->get("list", "CompanyController::list");
        });
    });
});


//, ['filter' => 'authFilter']
