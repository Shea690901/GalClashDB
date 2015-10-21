<?php
namespace GalClash {
    abstract class GCMode extends \Tiger\Base {
        private $messages;
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
            $this->messages = [];
        }

        /*
        ** __destructor
        */
        public function __destruct()
        {
            parent::__destruct();
        }

        /*
        ** did we have any messages stored?
        ** if yes: print them now
        */
        public function msg_boxes()
        {
            if(count($msgs = $this->messages) > 0)
            {
                foreach($msgs as $msg)
                {
                    switch($msg['type'])
                    {
                        case 'success':
                            success_message($msg['text']);
                            break;
                        case 'info':
                            info_message($msg['text']);
                            break;
                        case 'warning':
                            warning_message($msg['text']);
                            break;
                        case 'error':
                            error_message($msg['text']);
                            break;
                        default:
                            message($msg['text'], $msg['subtype'], $msg['close']);
                            break;
                    }
                }
            }
        }

        /*
        ** Some simple message stores
        */
        function store_message($msg, $type, $close = FALSE)
        {
            $this->messages[] = array('text' => $msg, 'subtype' => $type, 'close' => $close);
        }

        function store_success_message($msg)
        {
            $this->messages[] = array('text' => $msg, 'type' => 'success');
        }

        function store_info_message($msg)
        {
            $this->messages[] = array('text' => $msg, 'type' => 'info');
        }

        function store_warning_message($msg)
        {
            $this->messages[] = array('text' => $msg, 'type' => 'warning');
        }

        function store_error_message($msg)
        {
            $this->messages[] = array('text' => $msg, 'type' => 'error');
        }
    } // class GCMode
} // namespace GalClash
?>
