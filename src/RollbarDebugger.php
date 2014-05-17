<?php

namespace Tomaj\Rollbar;

use Nette\Diagnostics\Debugger as NDebugger;
use Rollbar;

class RollbarDebugger
{
    /** @var bool					Send errors to Rollbar */
    private static $sendErrors;

    /** @var bool					Is console mode */
    private static $consoleMode;

    /** @var array					Allowed severity for error handler */
    public static $severity = array(
        E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR, E_RECOVERABLE_ERROR, E_DEPRECATED, E_USER_DEPRECATED
    );

    /** @var array					List of unrecoverable errors */
    private static $unrecoverable = array(
        E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE
    );

    /** @var array					Ignored exceptions */
    private static $ignoredExceptions = array(
        'Nette\Application\InvalidPresenterException',	// HTTP 404
        'Nette\Application\BadRequestException'			// HTTP 404
    );

    /**
     * Initialization
     * @param \Nette\DI\Container $container
     * @param boolean $sendErrors
     */
    public static function init($container, $sendErrors = TRUE)
    {
        self::$sendErrors = $sendErrors;
        self::$consoleMode = $container->parameters['consoleMode'];

        $config = $container->parameters['rollbar'];
        unset($config['sendErrors']);

        Rollbar::init($config, FALSE, FALSE);

        register_shutdown_function(array(__CLASS__, '_shutdownHandler'));
        set_exception_handler(array(__CLASS__, '_exceptionHandler'));
        set_error_handler(array(__CLASS__, '_errorHandler'));
    }

    /**
     * Wrapper for Debugger::log() method
     * @param string $message
     * @param int $priority
     */
    public static function log($message, $priority = NDebugger::INFO)
    {
        NDebugger::log($message, $priority);

        if (!($message instanceof \Exception)) {
            $message = new \Exception($message);
        }

        if (self::$sendErrors && ($priority == NDebugger::ERROR)) {
            Rollbar::report_message($message);
        }
    }

    /**
     * Log message to cli if console mode is set
     * @param type $msg
     */
    public static function consoleLog($msg) {
        if (self::$consoleMode) {
            echo $msg;
        }
    }

    /**
     * Shutdown handler for log fatal errors
     */
    public static function _shutdownHandler() {
        $error = error_get_last();

        if (self::$sendErrors && (in_array($error['type'], self::$unrecoverable))) {
            Rollbar::report_exception($error);
        }
    }

    /**
     * Log exception
     * @param \Exception $exception
     * @param boolean $shutdown
     */
    public static function _exceptionHandler(\Exception $exception, $shutdown = FALSE)
    {
        if (self::$sendErrors) {
            $ignore = false;
            foreach (self::$ignoredExceptions as $ignoredException) {
                if ($exception instanceof $ignoredException) {
                    $ignore = true;
                }
            }
            if (!$ignore) {
                Rollbar::report_exception($exception);
            }
        }

        // Log by nette debugger
        NDebugger::_exceptionHandler($exception, $shutdown);
    }

    /**
     * Log error
     * @param int $severity
     * @param string $message
     * @param string $file
     * @param int $line
     * @param type $context
     */
    public static function _errorHandler($severity, $message, $file, $line, $context)
    {
        if (in_array(E_ALL, self::$severity) || in_array($severity, self::$severity)) {
            if (self::$sendErrors) {
                Rollbar::report_php_error($severity, $message, $file, $line);
            }
        }

        // Log by nette debugger
        NDebugger::_errorHandler($severity, $message, $file, $line, $context);
    }
}