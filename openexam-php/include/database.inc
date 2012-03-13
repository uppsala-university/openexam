<?php

// 
// Copyright (C) 2009-2010 Computing Department BMC, 
// Uppsala Biomedical Centre, Uppsala University.
// 
// File:   include/database.inc
// Author: Anders Lövgren
// Date:   2009-06-25
// 
// Datebase related classes. It's usage are:
// 
//   //
//   // Each class defines static methods that returns the data
//   // based on query predicates.
//   // 
//   class Bookings {
//       public static getUserBookings($user) {
//           $db = Database::getConnection();
//           $result = $db->query("SELECT * FROM bookings WHERE user = '$user'");
//           return new ResultSet($result);
//       }
//   }
// 
//   try {
//       $bookings = Bookings::getUserAll("anders");
//       foreach($bookings as $booking) {
//           printf("Room: %s, Locker: %s, Start: %s, End: %s\n",
//                   $booking->getRoom(), $booking->getLocker(),
//                   $booking->getStart(), $booking->getEnd());
//       }
//   } catch(DatabaseException $exception) {
//       error_log($exception);
//   }
// 
// The Database class can be used without the ResultSet and DataRecord 
// classes. This might be preferable if a large number of rows is returned.
// 

class DatabaseException extends Exception
{

        private $userinfo;  // Extended information, not for public.

        function __construct($message, $code = 0, $userinfo = null)
        {
                parent::__construct($message, $code);
                $this->userinfo = $userinfo;
        }

        public function getUserInfo()
        {
                return $this->userinfo;
        }

        function __toString()
        {
                return $this->message;
        }

}

// 
// This class maintenance the PEAR/MDB2 database connection.
// 
class Database
{

        private $dsn;                      // DSN array or string.
        private $connection = null;        // MDB2 connection.
        private static $instance = null;   // This class object.
        //
        // Private to prevent explicit instantiation:

        //

        private function __construct()
        {
                $this->dsn = $GLOBALS['dsn'];
        }

        //
        // Let PHP decide on calling the destructor.
        //
        public function __destruct()
        {
                $this->closeConnection();
        }

        //
        // Prevent object from being cloned:
        //
        private function __clone()
        {
                trigger_error("Cloning of database object is not allowed.", E_USER_ERROR);
        }

        //
        // Use another DSN than the default. You should call getInstance() to
        // create the object and obtain the object reference. Then call this
        // method using the object handle. This method can only be called before
        // first use of getConnection() or after closeConnection() has been
        // called.
        //
        public function setDSN($dsn)
        {
                if (isset($this->connection)) {
                        throw new DatabaseException(_("Can't use setDSN() on existing connection."));
                }
                $this->dsn = $dsn;
        }

        //
        // This function is mainly useful to get an handle on the
        // database object to call closeConnection() on it.
        //
        public static function &getInstance()
        {
                if (!isset(self::$instance)) {
                        self::$instance = new Database();
                }
                return self::$instance;
        }

        //
        // Create database object if not exists and opens the database
        // connection. Returns the (MDB2) database connection.
        //
        public static function &getConnection()
        {
                if (!isset(self::$instance)) {
                        self::$instance = new Database();
                }
                if (!isset(self::$instance->connection)) {
                        self::$instance->openConnection();
                }
                return self::$instance->connection;
        }

        //
        // Opens and return the database connection object.
        //
        private function openConnection()
        {
                $this->connection = MDB2::factory($this->dsn);
                if (PEAR::isError($this->connection)) {
                        throw new DatabaseException($this->connection->getMessage());
                }
                $this->connection->setFetchMode(MDB2_FETCHMODE_ASSOC);
        }

        //
        // Closes the database connection object.
        //
        public function closeConnection()
        {
                if (isset($this->connection)) {
                        $this->connection->disconnect();
                        $this->connection = null;
                }
        }

}

// 
// This class implements an iteratable collection from an database query
// response. In ieration loop we return an DataRecord object that
// encapsulates one database row from the query result.
// 
class ResultSet implements Iterator
{

        private $rows = array();

        //
        // Create the result set from the resource pointing to the
        // result of an database query.
        //
        public function __construct($res)
        {
                if (PEAR::isError($res)) {
                        throw new DatabaseException($res->getMessage(),
                                $res->getCode(),
                                $res->getUserInfo());
                }
                $this->rows = $res->fetchAll();
        }

        public function rewind()
        {
                reset($this->rows);
        }

        public function current()
        {
                $row = current($this->rows);
                return new DataRecord($row);
        }

        public function key()
        {
                $row = key($this->rows);
                return new DataRecord($row);
        }

        public function next()
        {
                $row = next($this->rows);
                return new DataRecord($row);
        }

        public function first()
        {
                $row = reset($this->rows);
                return new DataRecord($row);
        }

        public function last()
        {
                $row = end($this->rows);
                return new DataRecord($row);
        }

        public function valid()
        {
                return current($this->rows) !== false;
        }

        public function count()
        {
                return count($this->rows);
        }

}

// 
// This class provides read-only access to an associative array thru 
// emulated getters functions.
// 
class DataRecord
{

        private $row = array();

        public function __construct($row)
        {
                $this->row = $row;
        }

        //
        // Emulate getXXX(), setXXX() and hasXXX() where XXX is the field name as
        // returned from the database query. The value is always (?) passed as
        // an array.
        //
        public function __call($name, $value)
        {
                switch (substr($name, 0, 3)) {
                        case "get":
                                return $this->row[substr(strtolower($name), 3)];
                                break;
                        case "has":
                                return isset($this->row[substr(strtolower($name), 3)]);
                                break;
                        case "set":
                                $this->row[substr(strtolower($name), 3)] = $value[0];
                                break;
                }
        }

        //
        // Utility function that returns a date string for timestamp formatted
        // using the configured date format (see conf/config.inc).
        //
        public static function formatDateTime($stamp)
        {
                if (is_int($stamp)) {
                        return strftime(DATETIME_FORMAT, $stamp);
                }
                if (is_string($stamp)) {
                        return strftime(DATETIME_FORMAT, strtotime($stamp));
                }
        }

        //
        // From reinaldorock on http://php.net/manual/en/language.types.float.php.
        // 
        // Converts a locale specific string to a float point number. Users 
        // typical submit floating point numbers having country specific decimal
        // separator. This might lead to truncation errors.
        // 
        // This function could be used to convert request parameters (input) to 
        // float point numbers that is stored in the database.
        //
        public static function str2num($str)
        {
                if (strpos($str, '.') < strpos($str, ',')) {
                        $str = str_replace('.', '', $str);
                        $str = strtr($str, ',', '.');
                } else {
                        $str = str_replace(',', '', $str);
                }
                return (float) $str;
        }

}

?>
