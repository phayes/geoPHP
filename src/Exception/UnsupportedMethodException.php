<?php

namespace geoPHP\Exception;

/**
 * Should be thrown if a method is not implemented yet
 */
class UnsupportedMethodException extends \Exception {

    /**
     * Public constructor.
     *
     * @param string $method Name of the unsupported method
     * @param int $code
     * @param string|null $message Additional message
     */
    public function __construct($method, $code = 0, $message = null) {
        $message = 'The method ' . $method . '() is not supported yet.' . ($message) ? ' ' . $message : '';
        parent::__construct($message, $code);
    }

    /**
     * Method is supported only with GEOS installed
     *
     * @param string $methodName Name of the unsupported method
     * @return UnsupportedMethodException
     */
    public static function geos($methodName)
    {
        return new self($methodName, null, 'Please install GEOS extension.');
    }
}
