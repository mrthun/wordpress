<?php
    /*
     * Plugin Name: CTsearch
     * Plugin URI: http://mrthun.com
     * Description: Job listing page using the SimplyHired API.
     * Version: 0.1
     * Author: Christian Thun
     * Author URI: http://mrthun.com
     * License: GPLv2
     */
    /*
     * LICENSE
     *
     * Copyright (C) 2013  Christian Thun (christian@mrthun.net)
     * This program is free software; you can redistribute it and/or
     * modify it under the terms of the GNU General Public License
     * as published by the Free Software Foundation; either version 2
     * of the License, or (at your option) any later version.
     *
     * This program is distributed in the hope that it will be useful,
     * but WITHOUT ANY WARRANTY; without even the implied warranty of
     * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
     * GNU General Public License for more details.
     
     * You should have received a copy of the GNU General Public License
     * along with this program; if not, write to the Free Software
     * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
     */
    
    
    if(!class_exists('CTsearch')) {
        
        class CTsearch {
            var $plugin_url;
            var $plugin_dir;
            var $db_opt = 'CTsearch_Options';
            
            public function __construct() {
                $this->plugin_url = trailingslashit( WP_PLUGIN_URL . '/' . dirname(plugin_basename(__FILE__)) );
                $this->plugin_dir = trailingslashit( plugin_dir_path(__FILE__) );
                
                // Include the libraries for the API.
                require_once($this->plugin_dir . '/includes/lib/SimplyHiredJobamaticAPI.php');
                
                if(is_admin()) {
                    add_action('admin_init', array($this, 'register_settings'));
                    add_action('admin_menu', array($this, 'options_page'));
                } else {
                    add_filter('the_content', array($this, 'content'));
                    add_filter('wp_nav_menu_items', array($this,'get_search_form'), 10, 2);
                    add_action('wp_footer', array($this,'ct_debug'));
                }
            }
                               
            function ct_debug() {
                $options = $this->get_options();
                echo 'Options:<br/>';
                var_dump($options);
                echo '<br/>Tags:';
                $posttags = get_the_tags();
                var_dump($posttags);
                $postcategories = get_the_category();
                echo '<br/>CATEGORIES:<br/>';
                if ($postcategories) {
                    foreach($postcategories as $postcategory) {
                        echo $postcategory ->cat_name. '<br/>';
                        echo '...';
                    }
                }
            }
            
            public function install() {
                $this->get_options();
            }
            
            public function deactivate() {
            }
            
            public function get_options() {
                $options = array(
                                 'publisher_id' => '',
                                 'domain' => '',
                                 'query' => 'Nurse', // should be set in the admin sections
                                 'per_page' => 10,
                                 'location' => '',
                                 'miles' => '',
                                 'sort' => 'rd',
                                 'post_id' => '',
                                 'advanced_search' => 1,
                                 'attribution' => 1
                                 );
                $saved = get_option($this->db_opt);
                if(!empty($saved)) {
                    foreach ($saved as $key => $option) {
                        $options[$key] = $option;
                    }
                }
                if($saved != $options) {
                    update_option($this->db_opt, $options);
                }
                return $options;
            }
            
            function options_page() {
                add_options_page('CTsearch Settings', 'CTsearch', 'manage_options', 'ctsearch', array($this, 'handle_options'));
            }
            
            function register_settings() {
                register_setting('CTsearch_Options', $this->db_opt, array($this, 'validate_options'));
                
                // XML API required settings.
                add_settings_section(
                                     'jobamatic_api_settings',
                                     'API Credential',
                                     array($this, 'api_credentials_text'),
                                     'ctsearch'
                                     );
                add_settings_field(
                                   'publisher_id',
                                   'Publisher ID',
                                   array($this, 'publisher_id_input'),
                                   'ctsearch',
                                   'jobamatic_api_settings'
                                   );
                add_settings_field(
                                   'domain',
                                   'Jobamatic domain',
                                   array($this, 'domain_input'),
                                   'ctsearch',
                                   'jobamatic_api_settings'
                                   );
                
                
                // Default search options.
                add_settings_section(
                                     'jobamatic_default_search',
                                     'Default Job Search',
                                     array($this, 'default_search_text'),
                                     'ctsearch'
                                     );
                
                add_settings_field(
                                   'query',
                                   'Search query',
                                   array($this, 'default_query_input'),
                                   'ctsearch',
                                   'jobamatic_default_search'
                                   );
                
                add_settings_field(
                                   'miles',
                                   'Miles',
                                   array($this, 'default_miles_input'),
                                   'ctsearch',
                                   'jobamatic_default_search'
                                   );
            }
            
            function api_credentials_text() {
                echo '<p>Enter your Jobamatic <strong>Publisher ID</strong> and <strong>Jobamatic domain</strong> in the fields below.</p>';
                echo '<p>You can obtain this information by logging in to the <a href="https://www.jobamatic.com/a/jbb/partner-login" target="_jobamatic">Jobamatic portal</a> then clicking on the <a href="https://www.jobamatic.com/a/jbb/partner-dashboard-advanced-xml-api" target="_jobamatic">XML API tab</a>.';
            }
            function publisher_id_input() {
                $options = $this->get_options();
                
                echo '<input type="text" id="publisher_id" name="' . $this->db_opt . '[publisher_id]" value="' . $options['publisher_id'] . '" size="25" />';
            }
            function domain_input() {
                $options = $this->get_options();
                
                echo '<input type="text" id="domain" name="' . $this->db_opt . '[domain]" value="' . $options['domain'] . '" size="25" />';
            }
            function default_search_text() {
                echo '<p>Enter the default job search criteria in the fields below.</p>';
            }
            function default_query_input() {
                $options = $this->get_options();
                
                echo '<div><input type="text" id="query" name="'.$this->db_opt.'[query]" value="'.$options['query'].'" size="35" /></div>';
            }
            function default_miles_input() {
                $options = $this->get_options();
                
                echo '<div><input type="text" name="'.$this->db_opt.'[miles]" id="miles" value="'.$options['miles'].'" size="10" /></div>';
            }

            function validate_options($input) {
                $valid['publisher_id'] = preg_replace('/[^0-9]/', '', $input['publisher_id']);
                $valid['domain'] = trim($input['domain']);
                
                if($valid['publisher_id'] != $input['publisher_id']) {
                    add_settings_error(
                                       $this->db_opt . '[publisher_id]',
                                       'publisher_id_error',
                                       'Publisher ID can only contain numbers',
                                       'error'
                                       );
                }
                
                if(trim($input['query']) == '') {
                    add_settings_error(
                                       $this->db_opt . '[query]',
                                       'query_error',
                                       'You must enter the default search criteria',
                                       'error'
                                       );
                    $valid['query'] = '';
                } else {
                    $valid['query'] = trim($input['query']);
                }
                
                $m = trim($input['miles']);
                if(is_numeric($m)) {
                    $m = intval($m);
                    if($m < 1 || $m > 100) {
                        $valid['miles'] = '';
                        add_settings_error(
                                           $this->db_opt . '[miles]',
                                           'miles_error',
                                           'Miles must be an integer between 1 and 100 <strong>OR</strong> the exact phrase &quot;exact&quot;.',
                                           'error'
                                           );
                    } else {
                        $valid['miles'] = $input['miles'];
                    }
                } elseif($m != '') {
                    if(strtolower($m) != 'exact') {
                        $valid['miles'] = '';
                        add_settings_error(
                                           $this->db_opt . '[miles]',
                                           'miles_error',
                                           'Miles must be an integer between 1 and 100 <strong>OR</strong> the exact phrase &quot;exact&quot;.',
                                           'error'
                                           );
                    } else {
                        $valid['miles'] = $m;
                    }
                }
                return $valid;
            }
            
            public function handle_options() {
                $settings = $this->db_opt;
                include_once( $this->plugin_dir . 'includes/jobamatic-options.php');
            }
            
            
            /** Admin section ends here and we get started with the real stuff */
            function content($text) {
                $options = $this->get_options();
                
                $this->add_frontend_css();
                $this->add_frontend_scripts();
                // $text .= $this->get_search_form($options);
                $text .= "\n" . '<div id="ctsearch-wrap"></div>';

                
                 return $text;
            }
            
            protected function add_frontend_scripts() {
                
                // pass tags - if any
                $location='';
                $posttags = get_the_tags();
                if ($posttags) {
                    foreach($posttags as $tag) {
                        $location .= $tag->name . ' ';
                    }
                }
                
                $jobsearch=false;
                $postcategories = get_the_category();
        
                if ($postcategories) {
                    foreach($postcategories as $postcategory) {
                        if($postcategory ->cat_name=='Jobs in') {
                            $jobsearch=true;
                        }
                    }
                }

                wp_register_script( "ctsearch_script", ($this->plugin_url . 'js/ctsearch.js'), array('jquery'));
                
                $nonce = wp_create_nonce('ctsearch_ajax_request');
                $params = array(
                                'ajax_url' => admin_url(('admin-ajax.php?action=do_search&token=' . $nonce)),
                                'tags' => $location,
                                'jobsearch' => $jobsearch,
                                );
                wp_localize_script('ctsearch_script', 'ctsearch', $params);
                wp_enqueue_script('ctsearch_script');
            }

            
            protected function add_frontend_css() {
                wp_enqueue_style('ctsearch_style', $this->plugin_url . 'css/ctsearch.css', null, null, 'all');
            }

            
            public function do_search() {
               
                header('content-type:text/plain');
                if (!wp_verify_nonce( $_REQUEST['token'], 'ctsearch_ajax_request')) {
                    die("You are not authorized to access this page.");
                }
                require_once($this->plugin_dir . 'includes/lib/SimplyHiredJobamaticAPI.php');
                $options = $this->get_options();
                $api = new SimplyHiredJobamaticAPI($options['publisher_id'], $options['domain']);
                
                /*
                 * Set up the search query and pagination variables.
                 */
                $pagination = array();
                $query = $options['query'];
                // $options['query'];
                $per_page = 10;  // number of entries per page - we could add this in admin options later
                $page = 1;
                $miles=$options['miles'];
                
                $has_request=false;
                foreach($_REQUEST as $key => $value) {
                    switch($key) {
                        case 'q':
                            $query = urldecode(trim($_REQUEST['q']));
                            $pagination['q'] = rawurlencode($query);
                            $has_request=true;
                            break;
                        case 'p':
                            $page = intval($_REQUEST['p']);
                            $has_request=true;
                            // Don't add the page to the pagination array because WP will add that for us.
                            break;
                        case 'l':
                            $location = urldecode(trim($_REQUEST['l']));
                            $has_request=true;
                            // Don't add the page to the pagination array because WP will add that for us.
                            break;
                        default:
                            // ignore all other variables.
                            break;
                    }
                }
                // if no search terms were provided don't execute the search
                if (!$has_request) {die;}
                
                $data = $api->search($query, $per_page, $page, $location, $miles);
                
                $template = 'job-results.php';
                if(file_exists(get_template_directory() . '/' . $template)) {
                    $template = get_template_directory() . '/' . $template;
                } elseif( file_exists($this->plugin_dir . 'templates/' . $template)) {
                    $template = $this->plugin_dir . 'templates/' . $template;
                } else {
                    echo '<div class="error">Error: Can not render search results &ndash; no template found.</div>';
                    die();
                }
                /*
                 * Set up pagination.
                 */
                $pager = FALSE;
                if( $data && $data->getTotalPages() ) {
                    $params = array(
                                    'format' => '?p=%#%',
                                    'total' => $data->getTotalPages(),
                                    'current' => $data->getCurrentPage(),
                                    'prev_next' => TRUE,
                                    'prev_text' => __('« Previous'),
                                    'next_text' => __('Next »'),
                                    'add_args' => $pagination
                                    );
                    
                    $pager = paginate_links($params);
                }
                
                ob_start();
                include_once($template);
                $return = ob_get_contents();
                ob_end_clean();
                print $return;
                die();
            }

            // There should be an admin option to hide the search form
            function get_search_form($items, $args) {
                $out = '<form action="'.get_permalink($options['post_id']).'" method="post" name="ctsearch-form" id="ctsearch-form">';
                $out .= '<label for="ctsearch-query">Search Jobs&nbsp&nbsp</label> <input type="text" size="8" name="l" id="ctsearch-query" value="" placeholder="ZIP Code"/>';
                $out .= '<span id="zip-button"><input type="submit" name="ctsearch-submit" id="ctsearch-submit" value="Go" /></span>&nbsp;';
                $out .= '</form>';
                $items = $items.'<li>'.$out.'</li>'; 
                return $items;
            }
            
            
        }
            
            
    }

    $CTsearch = new CTsearch();
    
    if($CTsearch) {
        register_activation_hook( __FILE__, array(&$CTsearch, 'install'));
        // Adds an ajax actions.
        add_action('wp_ajax_do_search', array(&$CTsearch, 'do_search'));
        add_action('wp_ajax_nopriv_do_search', array(&$CTsearch, 'do_search'));
    }
    
?>