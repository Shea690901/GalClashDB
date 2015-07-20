<?php
namespace Tiger {
    class DB_Exception extends \Exception {
        const NO_ERROR            = 0;
        const DB_INACCESSABLE     = 1;
        const DB_EXECUTION_ERROR  = 2;

        public function __construct($code, $msg = NULL, $e = NULL)
        {
            if($msg === NULL)
            {
                switch($code)
                {
                    case DB_Exception::NO_ERROR:
                        $msg = 'No error…';
                        break;
                    case DB_Exception::DB_INACCESSABLE:
                        $msg = 'Can\'t access Database!';
                        break;
                    case DB_Exception::DB_EXECUTION_ERROR:
                        $msg = 'Error while executing SQL-statement!';
                        break;
                    default:
                        $msg = 'Unknown exception!';
                }
            }
            parent::__construct($msg, $code, $e);
        }
    }
}

?>
