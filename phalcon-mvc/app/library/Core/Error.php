<?php

/*
 * Copyright (C) 2015-2018 The OpenExam Project
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */

//
// File:    Error.php
// Created: 2015-03-19 10:10:46
//
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
//

namespace OpenExam\Library\Core;

use Exception;

/**
 * Error class.
 *
 * This class wraps an exception for error reporting to end user. It also
 * provides constants for some common used HTTP status codes.
 *
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class Error {

  /**
   * HTTP 400: Bad Request
   */
  const BAD_REQUEST = 400;
  /**
   * HTTP 401: Unauthorized
   */
  const UNAUTHORIZED = 401;
  /**
   * HTTP 403: Forbidden
   */
  const FORBIDDEN = 403;
  /**
   * HTTP 404: Not Found
   */
  const NOT_FOUND = 404;
  /**
   * HTTP 405: Method Not Allowed
   */
  const METHOD_NOT_ALLOWED = 405;
  /**
   * HTTP 406: Not Acceptable
   */
  const NOT_ACCEPTABLE = 406;
  /**
   * HTTP 410: Gone
   */
  const GONE = 410;
  /**
   * HTTP 412: Precondition Failed
   */
  const PRECONDITION_FAILED = 412;
  /**
   * HTTP 423: Locked
   */
  const LOCKED = 423;
  /**
   * HTTP 500: Internal Server Error
   */
  const INTERNAL_SERVER_ERROR = 500;
  /**
   * HTTP 501: Not Implemented
   */
  const NOT_IMPLEMENTED = 501;
  /**
   * HTTP 503: Service Unavailable
   */
  const SERVICE_UNAVAILABLE = 503;

  /**
   * The wrapped exception.
   * @var Exception
   */
  private $_exception;
  /**
   * The status code.
   * @var int
   */
  private $_status;
  /**
   * HTTP errors
   * @var array
   */
  private static $http_codes = array(
    //
    // Error code unset:
    //
    0 => 'Unknown Error',
    //
    // Security exception extensions:
    //
    1 => 'Failed Acquire Role',
    2 => 'Not Object Owner',
    3 => 'Access Denied',
    4 => 'Action Not Allowed',
    5 => 'ACL Service Missing',
    6 => 'User Service Missing',
    7 => 'Not Authenticated',
    //
    // Standard HTTP codes:
    //
    100 => 'Continue',
    101 => 'Switching Protocols',
    102 => 'Processing',
    200 => 'OK',
    201 => 'Created',
    202 => 'Accepted',
    203 => 'Non-Authoritative Information',
    204 => 'No Content',
    205 => 'Reset Content',
    206 => 'Partial Content',
    207 => 'Multi-Status',
    300 => 'Multiple Choices',
    301 => 'Moved Permanently',
    302 => 'Found',
    303 => 'See Other',
    304 => 'Not Modified',
    305 => 'Use Proxy',
    306 => 'Switch Proxy',
    307 => 'Temporary Redirect',
    400 => 'Bad Request',
    401 => 'Unauthorized',
    402 => 'Payment Required',
    403 => 'Forbidden',
    404 => 'Not Found',
    405 => 'Method Not Allowed',
    406 => 'Not Acceptable',
    407 => 'Proxy Authentication Required',
    408 => 'Request Timeout',
    409 => 'Conflict',
    410 => 'Gone',
    411 => 'Length Required',
    412 => 'Precondition Failed',
    413 => 'Request Entity Too Large',
    414 => 'Request-URI Too Long',
    415 => 'Unsupported Media Type',
    416 => 'Requested Range Not Satisfiable',
    417 => 'Expectation Failed',
    418 => 'I\'m a teapot',
    422 => 'Unprocessable Entity',
    423 => 'Locked',
    424 => 'Failed Dependency',
    425 => 'Unordered Collection',
    426 => 'Upgrade Required',
    449 => 'Retry With',
    450 => 'Blocked by Windows Parental Controls',
    500 => 'Internal Server Error',
    501 => 'Not Implemented',
    502 => 'Bad Gateway',
    503 => 'Service Unavailable',
    504 => 'Gateway Timeout',
    505 => 'HTTP Version Not Supported',
    506 => 'Variant Also Negotiates',
    507 => 'Insufficient Storage',
    509 => 'Bandwidth Limit Exceeded',
    510 => 'Not Extended',
  );

  /**
   * Constructor
   * @param Exception $exception
   */
  public function __construct($exception) {
    $this->_exception = $exception;
    $this->_status = $exception->getCode();

    if (!is_numeric($this->_status)) {
      $this->_status = self::INTERNAL_SERVER_ERROR;
    } elseif ($this->_status == 0) {
      $this->_status = self::INTERNAL_SERVER_ERROR;
    } elseif (!array_key_exists($this->_status, self::$http_codes)) {
      $this->_status = self::INTERNAL_SERVER_ERROR;
    }
  }

  /**
   * Destructor.
   */
  public function __destruct() {
    unset($this->_exception);
  }

  /**
   * Get wrapped exception.
   * @return Exception
   */
  public function getException() {
    return $this->_exception;
  }

  /**
   * Get exception message.
   * @return string
   */
  public function getMessage() {
    if (($prev = $this->_exception->getPrevious())) {
      return $this->_exception->getMessage() . ' (' . $prev->getMessage() . ')';
    } else {
      return $this->_exception->getMessage();
    }
  }

  /**
   * Get exception code.
   * @return int
   */
  public function getCode() {
    return $this->_exception->getCode();
  }

  /**
   * Get HTTP status string.
   * @return string
   */
  public function getString() {
    return self::lookup($this->_status);
  }

  /**
   * Get HTTP status code.
   * @return int
   */
  public function getStatus() {
    return $this->_status;
  }

  /**
   * Get HTTP status string.
   *
   * @param int $code The HTTP status code.
   * @return string
   */
  public static function lookup($code) {
    return self::$http_codes[$code];
  }

}
