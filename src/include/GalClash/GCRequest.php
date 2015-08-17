<?php
namespace GalClash {
    class GCRequest extends \Tiger\Request {

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
            $this->state	    = "start";      /* state for statemachine */

            $this->alliance     = "";           /* alliance name to search for */
            $this->name         = "";           /* player name to search for */

            $this->galaxy   	= 0;
            $this->system   	= 0;            /* coordinates of planet */
            $this->planet   	= 0;

            $this->exact    	= FALSE;        /* search exact or aproximatly when searching for names */

            /*
            ** renaming
            */
            $this->oalliance	= "";           /* old alliance name */
            $this->nalliance	= "";           /* new alliance name */
            $this->oname      	= "";           /* old player name */
            $this->nname      	= "";           /* new player name */
            $this->force        = 0;            /* force rename despite conflict? */
        }
    }
}

?>
