<?php
    /*
     * Plugin Name: Form2PDF
     * Plugin URI: http://mrthun.com
     * Description: Exports Ninja Form submissions to PDF
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
    
    
    if(!class_exists('Form2pdf')) {
        
        class Form2pdf {
            var $plugin_url;
            var $plugin_dir;
            var $db_opt = 'Form2pdf_Options';
            
            public function __construct() {
                $this->plugin_url = trailingslashit( WP_PLUGIN_URL . '/' . dirname(plugin_basename(__FILE__)) );
                $this->plugin_dir = trailingslashit( plugin_dir_path(__FILE__) );
                
                
                if(is_admin()) {
                    add_action('admin_init', array($this, 'register_settings'));
                    add_action('admin_menu', array($this, 'options_page'));
                } else {
                    add_action('wp_footer', array($this,'ct_debug'));
                }
            }
                               
            function ct_debug() {
                echo '<br/>FORM 2 PDF DEBUGGING ON</br>';
            }
            
            public function install() {
                $this->get_options();
            }
            
            public function deactivate() {
            }
            
            public function get_options() {
                $options = array(
                                 'debug' => 0,
                                 'form_id' => 0,
                                 'convert' => 0,
                                 'url_field' => '',
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
                add_options_page('Form2PDF Settings', 'Form2pdf', 'manage_options', 'form2pdf', array($this, 'handle_options'));
            }
            
            function register_settings() {
                register_setting('Form2pdf_Options', $this->db_opt, array($this, 'validate_options'));
                

                add_settings_section(
                                     'form_settings',
                                     'Form Settings',
                                     array($this, 'form_settings_text'),
                                     'form2pdf'
                                     );
                add_settings_field(
                                   'form_id',
                                   'Form ID',
                                   array($this, 'form_id_input'),
                                   'form2pdf',
                                   'form_settings'
                                   );
                add_settings_field(
                                   'convert',
                                   'Convert',
                                   array($this, 'convert_select'),
                                   'form2pdf',
                                   'form_settings'
                                   );
                add_settings_field(
                                   'url_field',
                                   'Field where URL to PDF file is stored',
                                   array($this, 'url_field_input'),
                                   'form2pdf',
                                   'form_settings'
                                   );
                

                add_settings_section(
                                     'debug_it',
                                     'Debugging',
                                     array($this, 'debug_it_text'),
                                     'form2pdf'
                                     );
                
                add_settings_field(
                                   'debug',
                                   'Enable Debugging',
                                   array($this, 'debug_select'),
                                   'form2pdf',
                                   'debug_it'
                                   );

            }
            
            function debug_it_text() {
                echo 'Enable to show debug information in the footer and the ZIP search field in the menu bar';
                
            }
            
            function debug_select() {
                $options = $this->get_options();
                
                echo '<div><select name="'.$this->db_opt.'[debug]" id="debug"><option value="0"'.($options['debug'] == 0 ? ' selected="selected"' : '').'>No</option><option value="1"'.($options['debug'] == 1 ? ' selected="selected"' : '').'>Yes</option></select></div>';
            }
            
            function form_settings_text() {
                echo 'Select the form for which you want to convert submissions to PDF and the field where the PDF url is to be stored.';
            }
            function url_field_input() {
                $options = $this->get_options();
                
                echo '<input type="text" id="url_field" name="' . $this->db_opt . '[url_field]" value="' . $options['url_field'] . '" size="25" />';
            }
            function convert_select() {
                $options = $this->get_options();
                
                echo '<div><select name="'.$this->db_opt.'[convert]" id="convert"><option value="0"'.($options['convert'] == 0 ? ' selected="selected"' : '').'>No</option><option value="1"'.($options['convert'] == 1 ? ' selected="selected"' : '').'>Yes</option></select></div>';
            }
            function form_id_input() {
                $options = $this->get_options();
                
                echo '<input type="text" id="form_id" name="' . $this->db_opt . '[form_id]" value="' . $options['form_id'] . '" size="25" />';
            }
            


            function validate_options($input) {
                $valid['debug'] = intval($input['debug']);
                $valid['convert'] = intval($input['convert']);
                
                // TO BE FIXED!!!!
                $valid['url_field'] = $input['url_field'];
                $valid['form_id'] = $input['form_id'];

                return $valid;
            }
            
            public function handle_options() {
                $settings = $this->db_opt;
                include_once($this->plugin_dir . 'includes/form2pdf-options.php');
            }
            
        }
            
            
    }

    $Form2pdf = new Form2pdf();
    
    if($Form2pdf) {
        register_activation_hook( __FILE__, array(&$Form2pdf, 'install'));
        // Adds an ajax actions.

    }
    
?>