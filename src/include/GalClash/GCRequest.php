<?php

namespace GalClash {
    class GCRequest extends \Tiger\Request
    {
        public function __construct()
        {
            ini_set('request_order', 'P');
            parent::__construct();
            $this->set_defaults();
            $this->init();
        }

        public function __destruct()
        {
            parent::__destruct();
        }

        private function set_defaults()
        {
            $this->state = 'start';      /* state for statemachine */
        }
    }
}
