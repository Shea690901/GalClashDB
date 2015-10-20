<?php
namespace GalClash {
    abstract class GCMode extends \Tiger\Base {
        // each mode of operation needs to process the request it's designed for
        abstract public function process_request($arg = NULL);
        // each mode of operation has at least one formular
        abstract public function put_form();

        /*
        ** __constructor
        */
        public function __construct(GCRequest $req, GCSession $ses, GCDB $db)
        {
            parent::__construct();
            $this->req = $req;
            $this->ses = $ses;
            $this->db  = $db;
        }

        /*
        ** __destructor
        */
        public function __destruct()
        {
            parent::__destruct();
        }
    } // class GCMode
} // namespace GalClash
?>
