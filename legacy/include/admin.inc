<?php

// 
// Copyright (C) 2010 Computing Department BMC, 
// Uppsala Biomedical Centre, Uppsala University.
// 
// File:   include/admin.inc
// Author: Anders Lövgren
// Date:   2010-03-05
// 
// Common admin support and template page.
// 

class Admin
{

        //
        // Return true if user has administrative privileges.
        //
        public static function userIsAdmin($user)
        {
                $db = Database::getConnection();
                $db->setFetchMode(MDB2_FETCHMODE_ORDERED);
                $sql = sprintf("SELECT COUNT(*)
                                FROM admins
                                WHERE user = '%s'", $user);
                $res = $db->query($sql);
                if (PEAR::isError($res)) {
                        throw new DatabaseException($res->getMessage());
                }
                $row = $res->fetchRow();

                $db->setFetchMode(MDB2_FETCHMODE_ASSOC);
                return intval($row[0]) >= 1;
        }

        //
        // Get the list of all common tasks.
        //
        public static function getCommonTasks()
        {
                $tasks = array(
                        "admin" => array(
                                "href"    => "admin.php",
                                "desc"    => _("Grant and revoke administrative privileges to trusted users."),
                                "name"    => _("Admins")
                        ),
                        "teacher" => array(
                                "href" => "teacher.php",
                                "desc" => _("Grant and revoke teacher privileges to trusted users."),
                                "name" => _("Teachers")
                        )
                );
                return $tasks;
        }

        //
        // Get list of all users that has been granted administrative privileges.
        //
        public static function getAdminUsers()
        {
                $sql = "SELECT  id AS UserID, user AS UserName
                        FROM admins";
                $res = Database::getConnection()->query($sql);
                return new ResultSet($res);
        }

        //
        // Grant admin rights to user.
        //
        public static function grantUserRights($user)
        {
                $sql = sprintf("INSERT INTO admins(user) VALUES('%s')", $user);
                $res = Database::getConnection()->query($sql);
                if (PEAR::isError($res)) {
                        throw new DatabaseException($res->getMessage());
                }
        }

        //
        // Revoke admin rights from users.
        //
        public static function revokeUserRights($user_id)
        {
                $sql = sprintf("DELETE FROM admins WHERE id = '%d'", $user_id);
                $res = Database::getConnection()->query($sql);
                if (PEAR::isError($res)) {
                        throw new DatabaseException($res->getMessage());
                }
        }

}

// 
// The base class for all admin user interfaces.
// 
class AdminPage extends BasePage
{

        public function __construct($title, &$params = array())
        {
                parent::__construct($title, $params);
                $this->authorize();
        }

        public function printMenu()
        {
                $links->admin->href = "admin.php";
                $links->admin->name = _("Admins");
                $links->users->href = "teacher.php";
                $links->users->name = _("Users");
                $links->exams->href = "exams.php";
                $links->exams->name = _("Exams");

                printf("<span id=\"menuhead\">%s:</span>\n", _("System"));
                printf("<ul>\n");
                foreach ($links as $sect) {
                        printf("<li><a href=\"%s\">%s</a></li>\n", $sect->href, $sect->name);
                }
                printf("</ul>\n");
        }

        //
        // Validate access and request parameters:
        //
        public function authorize()
        {
                if (!Admin::userIsAdmin(phpCAS::getUser())) {
                        $this->fatal(_("Access denied!"), _("Only users granted administrative privileges (system managers) can access this page. The script processing has halted."));
                }
        }

}

?>
