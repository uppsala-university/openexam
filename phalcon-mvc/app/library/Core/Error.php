<?php

// 
// The source code is copyrighted, with equal shared rights, between the
// authors (see the file AUTHORS) and the OpenExam project, Uppsala University 
// unless otherwise explicit stated elsewhere.
// 
// File:    Error.php
// Created: 2015-03-19 10:10:46
// 
// Author:  Anders Lövgren (Computing Department at BMC, Uppsala University)
// 

namespace OpenExam\Library\Core;

/**
 * Error class.
 * 
 * This class wraps an exception for error reporting to end user. It also
 * provides constants for some common used HTTP status codes.
 * 
 * @author Anders Lövgren (Computing Department at BMC, Uppsala University)
 */
class Error
{

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
         * @var \Exception 
         */
        private $exception;

        /**
         * Constructor
         * @param \Exception $exception
         */
        public function __construct($exception)
        {
                $this->exception = $exception;
        }

        /**
         * Get wrapped exception.
         * @return \Exception
         */
        public function getException()
        {
                return $this->exception;
        }

        /**
         * Get exception message.
         * @return string
         */
        public function getMessage()
        {
                return $this->exception->getMessage();
        }

        /**
         * Get exception code.
         * @return int
         */
        public function getCode()
        {
                return $this->exception->getCode();
        }

        /**
         * Get HTTP status string.
         * @return string 
         */
        public function getString()
        {
                return self::lookup($this->exception->getCode());
        }

        /**
         * Get HTTP status string.
         * 
         * @param int $code The HTTP status code.
         * @return string
         */
        public static function lookup($code)
        {
                static $http_codes = array(
                        0   => 'Unknown error',
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
                        510 => 'Not Extended'
                );
                return $http_codes[$code];
        }

}
