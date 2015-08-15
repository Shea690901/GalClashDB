<?php
namespace GalClash {
    class GCRequest extends \Tiger\Request {

        function __construct()
        {
            ini_set('request_order', 'P');
            parent::__construct();
        }

        public function __destruct()
        {
            parent::__destruct();
        }
    }
}

?>
