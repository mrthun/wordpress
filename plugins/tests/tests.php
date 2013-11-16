<?php
    /*
     * Plugin Name: Tests
     * Plugin URI: http://mrthun.com
     * Description: Shows debug information in the footer and is used to test things
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
    
    

    
    
    
    
    
    if(!class_exists('Tests')) {
        
        class Tests {
            var $plugin_url;
            var $plugin_dir;
            var $db_opt = 'Test_Options';
            
            public function __construct() {
                $this->plugin_url = trailingslashit( WP_PLUGIN_URL . '/' . dirname(plugin_basename(__FILE__)) );
                $this->plugin_dir = trailingslashit( plugin_dir_path(__FILE__) );
                $options = $this->get_options();
                
                if(is_admin()) {
                    add_action('admin_init', array($this, 'register_settings'));
                    add_action('admin_menu', array($this, 'options_page'));
                } else {
                   // add_filter('the_content', array($this, 'content'));
                   // add_filter('wp_nav_menu_items', array($this,'get_search_form'), 10, 2);
                    if ($options['debug']==1) {add_action('wp_footer', array($this,'ct_debug'));}
                }
            }
                               
            
            // the debug information is displayed here
            function ct_debug() {
                $options = $this->get_options();
                echo 'OPTIONS:<br/>';
                var_dump($options);

                echo '<br/>FORMS:<br/>';
                $results= $this->ninja_get_submissions(1);



            }
            
            function ninja_get_submissions($form_id=0) {
                
                $args = array(
                              'form_id' => 1,
                              );
                $user_ID = get_current_user_id();
                
                $all_fields = ninja_forms_get_subs( $args );
                var_dump($all_fields);
                //$all_fields = $ninja_forms_processing->get_all_fields();
                if( is_array( $all_fields ) ){
                    foreach( $all_fields as $field ){
                        if ($field['user_id']==$user_ID && is_array( $field['data'] )) {
                            echo 'TEST' . $this->plugin_dir . 'template.htm';
                        $file = file_get_contents($this->plugin_dir . 'template.htm', true);
                            
                         foreach( $field['data'] as $data) {
                            $file = str_replace('%'.$data['field_id'].'%', $data['user_value'], $file) .$data['field_id'] ;
                         }
                         $file_name = $this->plugin_dir . 'test' . $field['user_id'] . '.htm';
                            echo $file_name;
                         file_put_contents($file_name, $file);
                      }
                    }
                }

                
                return $result;
             }
            
            public function install() {
                $this->get_options();
            }
            
            public function deactivate() {
            }
            
            public function get_options() {
                $options = array(
                                 'debug' => 1
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
                add_options_page('Test Settings', 'Tests', 'manage_options', 'tests', array($this, 'handle_options'));
            }
            
            function register_settings() {
                register_setting('Test_Options', $this->db_opt, array($this, 'validate_options'));
                
                add_settings_section(
                                     'debug_settings',
                                     'Debug',
                                     array($this, 'debug_settings_text'),
                                     'tests'
                                     );
                add_settings_field(
                                   'debug',
                                   'Activate Debug Information',
                                   array($this, 'debug_settings_input'),
                                   'tests',
                                   'debug_settings'
                                   );

            }

            function debug_settings_input() {
                $options = $this->get_options();

                echo '<div><select name="'.$this->db_opt.'[debug]" id="debug"><option value="0"'.($options['debug'] == 0 ? ' selected="selected"' : '').'>No</option><option value="1"'.($options['debug'] == 1 ? ' selected="selected"' : '').'>Yes</option></select></div>';
            }
            
            function debug_settings_text() {
                echo 'Enable to show debug information in the footer';

            }
       

            function validate_options($input) {
                $valid['debug'] = intval($input['debug']);
                return $valid;
            }
            
            public function handle_options() {
                $settings = $this->db_opt;
                include_once( $this->plugin_dir . 'includes/tests-options.php');
            }
            
            
            /** Admin section ends here and we get started with the real stuff */
            function content($text) {
                // for later
                
                 return $text;
            }
        }
        
    }

    $Tests = new Tests();
    
    if($Tests) {
        register_activation_hook( __FILE__, array(&$Tests, 'install'));
    }
    
?>