<?php

/**
 * Class and Function List:
 * Function list:
 * - __construct()
 * - _form_prep()
 * - index()
 * - raw()
 * - rss()
 * - embed()
 * - download()
 * - lists()
 * - trends()
 * - view()
 * - cron()
 * - about()
 * - captcha()
 * - _valid_lang()
 * - _valid_captcha()
 * - _valid_recaptcha()
 * - _valid_ip()
 * - _blockwords_check()
 * - _autofill_check()
 * - _valid_authentication()
 * - get_cm_js()
 * - error_404()
 * Classes list:
 * - Main extends CI_Controller
 */
class Main extends CI_Controller {

    private $data;
    
    function __construct() {
        parent::__construct();
        $this->load->model('languages');
        $this->load->model('pastes');
        $this->load->library('tank_auth');
        
        if (config_item('require_auth')) {
            $this->load->library('auth_ldap');
        }

        //recaptcha
        $this->recaptcha_publickey = config_item('recaptcha_publickey');
        $this->recaptcha_privatekey = config_item('recaptcha_privatekey');

        if ($this->recaptcha_publickey != '' && $this->recaptcha_privatekey != '') {
            $this->load->helper('recaptcha');
            $this->use_recaptcha = true;
        }
    }

    function _form_prep($lang = false, $title = '', $paste = '', $reply = false) {
        $this->load->model('languages');
        $this->load->helper('form');
        $data['languages'] = $this->languages->get_languages();

        //codemirror languages
        $this->load->config('codemirror_languages');
        $codemirror_languages = config_item('codemirror_languages');
        $data['codemirror_languages'] = $codemirror_languages;

        //codemirror modes
        $cmm = array();
        foreach ($codemirror_languages as $geshi_name => $l) {

            if (gettype($l) == 'array') {
                $cmm[$geshi_name] = $l['mode'];
            }
        }
        $data['codemirror_modes'] = $cmm;

        //recaptcha
        $data['use_recaptcha'] = $this->use_recaptcha;
        $data['recaptcha_publickey'] = $this->recaptcha_publickey;

        if (!$this->input->post('submit')) {

/*    
            if (!$this->db_session->userdata('expire')) {
                $default_expiration = config_item('default_expiration');
                $this->db_session->set_userdata('expire', $default_expiration);
            }

            if ($this->db_session->flashdata('settings_changed')) {
                $data['status_message'] = 'Settings successfully changed';
            }
            $data['name_set'] = $this->db_session->userdata('name');
            $data['expire_set'] = $this->db_session->userdata('expire');
            $data['private_set'] = $this->db_session->userdata('private');
            $data['snipurl_set'] = $this->db_session->userdata('snipurl');
 * 
 */

           if (!$this->session->userdata('expire')) {
                $default_expiration = config_item('default_expiration');
                $this->session->set_userdata('expire', $default_expiration);
            }

            if ($this->session->flashdata('settings_changed')) {
                $data['status_message'] = 'Settings successfully changed';
            }
            $data['name_set'] = $this->session->userdata('name');
            $data['expire_set'] = $this->session->userdata('expire');
            $data['private_set'] = $this->session->userdata('private');
            $data['snipurl_set'] = $this->session->userdata('snipurl');
 
            $data['paste_set'] = $paste;
            $data['title_set'] = $title;
            $data['reply'] = $reply;

            if (!$lang) {
                $lang = config_item('default_language');
            }
            $data['lang_set'] = $lang;
        } else {
            $data['name_set'] = $this->input->post('name');
            $data['expire_set'] = $this->input->post('expire');
            $data['private_set'] = $this->input->post('private');
            $data['snipurl_set'] = $this->input->post('snipurl');
            $data['paste_set'] = $this->input->post('code');
            $data['title_set'] = $this->input->post('title');
            $data['reply'] = $this->input->post('reply');
            $data['lang_set'] = $this->input->post('lang');
        }

        return $data;
    }

    function index() {
        //$this->_valid_authentication();
        $this->load->helper('json');

        $this->load->model('pastes');

        $this->data['recent'] = $this->pastes->getLists(null,null,null,30);
        $this->data['trends'] = $this->pastes->getTrends(null,null,null,30);
        $this->data['message'] = $this->session->flashdata('message');
        
        $this->data['title'] = "Code Snippets and Samples";
        
        $this->load->view('home', $this->data);
    }

    function add() {
        if (! $this->tank_auth->is_logged_in()) {	
            redirect("login");
        }
//print "<pre>sess:"; print_r($this->session); exit;        
        
        $this->_valid_authentication();
        $this->load->helper('json');

        if (!$this->input->post('submit')) {
            $lang = $this->session->userdata('last_lang');
            $this->data = $this->_form_prep($lang);
            $this->load->view('add', $this->data);
        } else {
            $this->load->model('pastes');
            $this->load->library('form_validation');

            //rules
            $rules = array(
                array(
                    'field' => 'code',
                    'label' => 'Main Paste',
                    'rules' => 'required',
                ),
                array(
                    'field' => 'lang',
                    'label' => 'Language',
                    'rules' => 'min_length[1]|required|callback__valid_lang',
                ),
                array(
                    'field' => 'captcha',
                    'label' => 'Captcha',
                    'rules' => 'callback__valid_captcha',
                ),
                array(
                    'field' => 'valid_ip',
                    'label' => 'Valid IP',
                    'rules' => 'callback__valid_ip',
                ),
                array(
                    'field' => 'blockwords_check',
                    'label' => 'No blocked words',
                    'rules' => 'callback__blockwords_check',
                ),
                array(
                    'field' => 'email',
                    'label' => 'Field must remain empty',
                    'rules' => 'callback__autofill_check',
                ),
            );

            //form validation
            $this->form_validation->set_rules($rules);
            $this->form_validation->set_message('min_length', lang('empty'));
            $this->form_validation->set_error_delimiters('<div class="message error"><div class="container">', '</div></div>');

            if ($this->form_validation->run() == FALSE) {
                $data = $this->_form_prep();
                $this->load->view('add', $data);
            } else {

                if (config_item('private_only')) {
                    $_POST['private'] = 1;
                }

                if ($this->input->post('reply') == false) {
                    $user_data = array(
                        'name' => $this->input->post('name'),
                        'lang' => $this->input->post('lang'),
                        'expire' => $this->input->post('expire'),
                        'snipurl' => $this->input->post('snipurl'),
                        'private' => $this->input->post('private'),
                    );
                    //$this->db_session->set_userdata($user_data);
                    $this->session->set_userdata($user_data);
                }
                $redirect = $this->pastes->createPaste();
                
                $this->session->set_userdata('last_lang', $user_data['lang']);
                $this->session->set_flashdata('message', 'Your kode has been saved!');
                
                redirect($redirect);
            }
        }
    }

    function raw() {
        $this->_valid_authentication();
        $this->load->model('pastes');
        $check = $this->pastes->checkPaste(3);

        if ($check) {
            $data = $this->pastes->getPaste(3);
            $this->load->view('view/raw', $data);
        } else {
            show_404();
        }
    }

    function rss() {
        $this->_valid_authentication();
        $this->load->model('pastes');
        $check = $this->pastes->checkPaste(3);

        if ($check) {
            $this->load->helper('text');
            $paste = $this->pastes->getPaste(3);
            $data = $this->pastes->getReplies(3);
            $data['page_title'] = $paste['title'] . ' - ' . config_item('site_name');
            $data['feed_url'] = site_url('view/rss/' . $this->uri->segment(3));
            $this->load->view('view/rss', $data);
        } else {
            show_404();
        }
    }

    function embed() {
        $this->_valid_authentication();
        $this->load->model('pastes');
        $check = $this->pastes->checkPaste(3);

        if ($check) {
            $data = $this->pastes->getPaste(3, true, $this->uri->segment(4) == 'diff');
            $this->load->view('view/embed', $data);
        } else {
            show_404();
        }
    }

    function download() {
        $this->_valid_authentication();
        $this->load->model('pastes');
        $check = $this->pastes->checkPaste(3);

        if ($check) {
            $data = $this->pastes->getPaste(3);
            $this->load->view('view/download', $data);
        } else {
            show_404();
        }
    }

    function lists() {
        $this->_valid_authentication();

        if (config_item('private_only')) {
            show_404();
        } else {
            $this->load->model('pastes');
            $data = $this->pastes->getLists();

            if ($this->uri->segment(2) == 'rss') {
                $this->load->helper('text');
                $data['page_title'] = config_item('site_name');
                $data['feed_url'] = site_url('lists/rss');
                $data['replies'] = $data['pastes'];
                unset($data['pastes']);
                $this->load->view('view/rss', $data);
            } else {
                $this->load->view('list', $data);
            }
        }
    }

    function trends() {
        $this->_valid_authentication();

        if (config_item('private_only')) {
            show_404();
        } else {
            $filter = $this->uri->segment(2);

            $filter = array(
                'where' => array(
                    'lang' => $filter,
                ),
            );
            $this->load->model('pastes');
            $data = $this->pastes->getTrends('trends/',2, $filter);
            $this->load->view('trends', $data);
        }
    }

    function view() {
        $this->_valid_authentication();
        $this->load->helper('json');
        $this->load->model('pastes');
        $check = $this->pastes->checkPaste(2);

        
        
        if ($check) {
/*
            if ($this->db_session->userdata('view_raw')) {
                redirect('view/raw/' . $this->uri->segment(2));
            }
 * 
 */
            if ($this->session->userdata('view_raw')) {
                redirect('view/raw/' . $this->uri->segment(2));
            }

            $this->data = $this->pastes->getPaste(2, true, $this->uri->segment(3) == 'diff');
                             
            $this->data['reply_form'] = $this->_form_prep($this->data['lang_code'], 'Re: ' . $this->data['title'], $this->data['raw'], $this->data['pid']);

            $this->data['show_tools'] = 0;
            if($this->session->userdata('user_id') == $this->data['user_id']) {
                $this->data['show_tools'] = 1;
            }

            $this->load->view('view/view', $this->data);
        } else {
            show_404();
        }
    }

    function edit() {
        
    }
    
    function delete($pid=null) {
        if(!empty($pid)) {
            $this->pastes->delete_paste($pid);
            $this->session->set_flashdata('message', 'Kode Deleted');
            redirect('/');
        }
    }
    
    function cron() {
        
        $key = $this->uri->segment(2);

        if ($key != config_item('cron_key')) {
            show_404();
        } else {
            $this->pastes->cron();
            return 0;
        }
    }

    function about() {
        $this->load->view('about');
    }

    function captcha() {
        $this->load->helper('captcha');

        //get "word"
        $pool = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ@';
        $str = '';
        for ($i = 0; $i < 4; $i++) {
            $str.= substr($pool, mt_rand(0, strlen($pool) - 1), 1);
        }
        $word = $str;

        //save
        /*
        $this->db_session->set_userdata(array(
            'captcha' => $word
        ));
         * 
         */
        $this->session->set_userdata(array(
            'captcha' => $word
        ));

        //view
        $this->load->view('view/captcha', array(
            'word' => $word
        ));
    }

    function _valid_lang($lang) {
        $this->load->model('languages');
        $this->form_validation->set_message('_valid_lang', lang('valid_lang'));
        return $this->languages->valid_language($lang);
    }

    function _valid_captcha($text) {

        if (config_item('enable_captcha')) {
            $this->form_validation->set_message('_valid_captcha', lang('captcha'));

            if ($this->use_recaptcha) {
                return $this->_valid_recaptcha();
            } else {
                //return strtolower($text) == strtolower($this->db_session->userdata('captcha'));
                return strtolower($text) == strtolower($this->session->userdata('captcha'));
            }
        } else {
            return true;
        }
    }

    function _valid_recaptcha() {

        if ($this->input->post('recaptcha_response_field')) {
            $pk = $this->recaptcha_privatekey;
            $ra = $_SERVER['REMOTE_ADDR'];
            $cf = $this->input->post('recaptcha_challenge_field');
            $rf = $this->input->post('recaptcha_response_field');

            //check
            $resp = recaptcha_check_answer($pk, $ra, $cf, $rf);
            return $resp->is_valid;
        } else {
            return false;
        }
    }

    function _valid_ip() {

        //get ip
        $ip_address = $this->input->ip_address();
        $ip = explode('.', $ip_address);
        $ip_firstpart = $ip[0] . '.' . $ip[1] . '.';

        //setup message
        $this->form_validation->set_message('_valid_ip', lang('not_allowed'));

        //lookup
        $this->db->select('ip_address, spam_attempts');
        $this->db->like('ip_address', $ip_firstpart, 'after');
        $query = $this->db->get('blocked_ips');

        //check

        if ($query->num_rows() > 0) {

            //update spamcount
            $blocked_ips = $query->result_array();
            $spam_attempts = $blocked_ips[0]['spam_attempts'];
            $this->db->where('ip_address', $ip_address);
            $this->db->update('blocked_ips', array(
                'spam_attempts' => $spam_attempts + 1,
            ));

            //return for the validation
            return false;
        } else {
            return true;
        }
    }

    function _blockwords_check() {

        //setup message
        $this->form_validation->set_message('_blockwords_check', lang('blocked_words'));

        //check
        $blocked_words = config_item('blocked_words');
        $post = $this->input->post();
        $raw = $post['code'];

        if (!$blocked_words) {
            return true;
        }

        //we have blocked words
        foreach (explode(',', $blocked_words) as $word) {
            $word = trim($word);

            if (stristr($raw, $word)) {
                return false;
            }
        }
        return true;
    }

    function _autofill_check() {

        //setup message
        $this->form_validation->set_message('_autofill_check', lang('robot'));

        //check
        return (!$this->input->post('email') && !$this->input->post('url'));
    }

    function _valid_authentication() {

        if (config_item('require_auth')) {

            if (!$this->auth_ldap->is_authenticated()) {
                $this->db_session->set_flashdata('tried_to', "/" . $this->uri->uri_string());
                redirect('/auth');
            }
        }
    }

    function get_cm_js() {
        $lang = $this->uri->segment(3);
        $this->load->config('codemirror_languages');
        $cml = config_item('codemirror_languages');

        //file path
        $file_path = 'themes/' . config_item('theme') . '/js/';

        if (!file_exists($file_path)) {
            $file_path = 'themes/default/js/';
        }

        if (isset($cml[$lang]) && gettype($cml[$lang]) == 'array') {
            header('Content-Type: application/x-javascript; charset=utf-8');
            header('Expires: ' . gmdate('D, d M Y H:i:s \G\M\T', time() + 60 * 60 * 24 * 30));
            foreach ($cml[$lang]['js'] as $js) {
                echo file_get_contents($file_path . $js[0]);
            }
        }
        exit;
    }

    function error_404() {
        show_404();
    }

}
