<?php
/**
 * Translates group and profile field labels.
 * 
 * Uses icl_t(), icl_register_string() and icl_unregister_string().
 */
class BPML_XProfile
{

    protected $_context = 'Buddypress Multilingual', $_field_string_prefix = 'profile field ', $_group_string_prefix = 'profile group ';

    public function __construct() {

        add_action( 'bp_init', array($this, 'bp_init') );

        // AJAX string registration
        add_action( 'wp_ajax_bpml_register_fields', array( $this, 'ajax_register' ) );
        
        // Register actions
        add_action( 'xprofile_fields_saved_field', array( $this, 'saved_field_action' ) );
        add_action( 'xprofile_fields_deleted_field', array( $this, 'deleted_field_action' ) );
        add_action( 'xprofile_groups_saved_group', array( $this, 'saved_group_action' ) );
        add_action( 'xprofile_groups_deleted_group', array( $this, 'deleted_group_action' ) );

        // Translation filters
        add_filter( 'bp_get_the_profile_field_name', array($this, 't_name') );
        add_filter( 'bp_get_the_profile_field_description', array($this, 't_description') );
        add_filter( 'bp_xprofile_field_get_children', array($this, 't_options') );
        add_filter( 'bp_get_the_profile_field_options_checkbox', array($this, 't_checkbox'), 0, 5 );
        add_filter( 'bp_get_the_profile_field_options_radio', array($this, 't_radio'), 0, 5 );
        add_filter( 'bp_get_the_profile_field_options_multiselect', array($this, 't_multiselect_option'), 0, 5 );
        add_filter( 'bp_get_the_profile_field_options_select', array($this, 't_select_option'), 0, 5 );
        add_filter( 'bp_get_the_profile_field_value', array($this, 't_value_profile_view'), 9, 2 );
        add_filter( 'bp_get_the_profile_group_name', array($this, 't_group_name') );
        add_filter( 'xprofile_filter_profile_group_tabs', array($this, 't_group_edit_tabs'), 10, 3 );
    }

    public function bp_init() {
        // BP Profile Fields admin screen
        if ( isset( $_GET['page'] ) && $_GET['page'] == 'bp-profile-setup' ) {
            // Scan needed check
            if ( $this->_scan_needed() ) {
                add_action( 'admin_notices', array( $this, 'scan_needed_warning' ) );
            }
            wp_enqueue_script( 'bpml', BPML_RELPATH . '/js/admin.js', array('jquery'), BPML_VERSION, true );
        }
    }

    public function register_fields() {
        if ( $groups = bp_xprofile_get_groups( array('fetch_fields' => true) ) ) {
            foreach ( $groups as $group ) {
                $this->saved_group_action( $group );
                if ( !empty( $group->fields ) && is_array( $group->fields ) ) {
                    foreach ( $group->fields as $field ) {
                        $this->saved_field_action( $field );
                    }
                }
            }
        }
    }
    
    public function saved_field_action( $field ) {
        // Happens that new field has no accesible 'id' property
        if ( empty( $field->id ) ) {
            if ( $field_id = xprofile_get_field_id_from_name( $field->name ) ) {
                $field->id = $field_id;
            } else {
                return;
            }
        }
        // Register name
        if ( !empty( $field->name ) ) {
            icl_register_string( $this->_context,
                    "{$this->_field_string_prefix}{$field->id} name", $field->name );
        }
        // Register description
        if ( !empty( $field->description ) ) {
            icl_register_string( $this->_context,
                    "{$this->_field_string_prefix}{$field->id} description", $field->description );
        }
        // Register options
        if ( in_array( $field->type, array('radio', 'checkbox', 'selectbox', 'multiselectbox') ) ) {
            $bp_field = xprofile_get_field( $field->id );
            $options = $bp_field->get_children();
            foreach ( $options as $option ) {
                if ( !empty( $option->name ) ) {
                    icl_register_string( $this->_context,
                            $this->sanitize_option_basename( $option, $field->id ) . ' name',
                            $option->name );
                }
                if ( !empty( $option->description ) ) {
                    icl_register_string( $this->_context,
                            $this->sanitize_option_basename( $option, $field->id ) . ' description',
                            $option->description );
                }
            }
        }
    }

    public function deleted_field_action( $field ) {
        // Unregister name
        if ( !empty( $field->name ) ) {
            icl_unregister_string( $this->_context,
                    "{$this->_field_string_prefix}{$field->id} name", $field->name );
        }
        // Unregister description
        if ( !empty( $field->description ) ) {
            icl_unregister_string( $this->_context,
                    "{$this->_field_string_prefix}{$field->id} description", $field->description );
        }
        // Unregister options
        if ( in_array( $field->type, array('radio', 'checkbox', 'selectbox', 'multiselectbox') ) ) {
            $bp_field = xprofile_get_field($field->id);
            $options = $bp_field->get_children();
            foreach ( $options as $option ) {
                if ( !empty( $option->name ) ) {
                    icl_unregister_string( $this->_context,
                            $this->sanitize_option_basename( $option, $field->id ) . ' name',
                            $option->name );
                }
                if ( !empty( $option->description ) ) {
                    icl_unregister_string( $this->_context,
                            $this->sanitize_option_basename( $option, $field->id ) . ' description',
                            $option->description );
                }
            }
        }
    }

    public function saved_group_action( $group ) {
        // Register name
        if ( !empty( $group->name ) ) {
            icl_register_string( $this->_context,
                    "{$this->_group_string_prefix}{$group->id} name", $group->name );
        }
        // Register description
        if ( !empty( $group->description ) ) {
            icl_register_string( $this->_context,
                    "{$this->_group_string_prefix}{$group->id} description", $group->description );
        }
    }

    public function deleted_group_action( $group ) {
        // Unregister name
        if ( !empty( $group->name ) ) {
            icl_unregister_string( $this->_context,
                    "{$this->_group_string_prefix}{$group->id} name", $group->name );
        }
        // Unregister description
        if ( !empty( $group->description ) ) {
            icl_unregister_string( $this->_context,
                    "{$this->_group_string_prefix}{$group->id} description", $group->description );
        }
    }

    public function t_name( $name ) {
        global $field;
        return icl_t( $this->_context, "{$this->_field_string_prefix}{$field->id} name", $name );
    }

    public function t_description( $description ) {
        global $field;
        return icl_t( $this->_context, "{$this->_field_string_prefix}{$field->id} description", $description );
    }

    public function t_options( $options ) {
        global $field;
        foreach ( $options as &$option ) {
            // Just translate description. Name can messup forms.
            if ( !empty( $option->description ) ) {
                $option->description = icl_t( $this->_context,
                        $this->sanitize_option_basename( $option, $field->id ) . ' description',
                        $option->description );
            }
        }
        return $options;
    }

    protected function _t_option_name( $option, $field_id ) {
        if ( !empty( $option->name ) ) {
            return icl_t( $this->_context,
                    $this->sanitize_option_basename( $option, $field_id ) . ' name',
                    $option->name );
        }
        return isset( $option->name ) ? $option->name : '';
    }

    /**
     * Adjusts HTML output for radio field.
     */
    public function t_radio( $html, $option, $field_id, $selected, $k ) {
        $label = $this->_t_option_name( $option, $field_id );
        return preg_replace( '/"\>(.*)\<\/label\>/', "\">{$label}</label>", $html );
    }

    /**
     * Adjusts HTML output for checkbox field.
     */
    public function t_checkbox( $html, $option, $field_id, $selected, $k ) {
        return $this->t_radio( $html, $option, $field_id, $selected, $k );
    }

    /**
     * Adjusts HTML output for select field.
     */
    public function t_select_option( $html, $option, $field_id, $selected, $k ) {
        $label = $this->_t_option_name( $option, $field_id );
        return preg_replace( '/"\>(.*)\<\/option\>/', "\">{$label}</option>", $html );
    }

    /**
     * Adjusts HTML output for multiselect field.
     */
    public function t_multiselect_option( $html, $option, $field_id, $selected, $k ) {
        return $this->t_select_option( $html, $option, $field_id, $selected, $k );
    }

    /**
     * Filters field values on profile view template.
     */
    public function t_value_profile_view( $value, $field_type ) {
        global $field;

        // Only for fields with options
        if ( in_array( $field_type,
                        array(
                    'radio',
                    'checkbox',
                    'selectbox',
                    'multiselectbox') ) ) {
            $bp_field = xprofile_get_field( $field->id );
            $options = $bp_field->get_children();
            switch ( $field_type ) {
                case 'radio':
                case 'selectbox':
                    $_value = false;
                    foreach ( $options as $option ) {
                        if ( isset($option->name) && $option->name == $field->data->value ) {
                            $_value = icl_t( $this->_context,
                                    $this->sanitize_option_basename( $option, $field->id ) . ' name',
                                    $option->name );
                        }
                    }
                    if ( $_value ) {
                        // Expected format is search URL
                        $value = preg_replace("/(<([\w]+)[^>]*>){$field->data->value}(<\/\\2>)/",
                                "$1{$_value}$3", $value, -1, $count);
                        if (!$count) {
                            $value = $_value;
                        }
                    }
                    break;

                case 'multiselectbox':
                case 'checkbox':
                    foreach ( $options as $option ) {
                        $_value = icl_t($this->_context,
                                $this->sanitize_option_basename( $option, $field->id ) . ' name',
                                $option->name);
                        // Expected format is search URL
                        $value = preg_replace("/(<([\w]+)[^>]*>){$option->name}(<\/\\2>)/",
                                "$1{$_value}$3", $value, -1, $count);
                        // CSV list
                        if ( !$count && strpos( $value, $option->name ) !== false ) {
                            $_ex_values = explode( ',', $value );
                            if ( !empty( $_ex_values ) ) {
                                foreach ( $_ex_values as &$v ) {
                                    if ( trim( $v ) == $option->name ) {
                                        $v = $_value;
                                    }
                                }
                                $value = implode( ', ', $_ex_values );
                            }
                        }
                    }
                    break;

                default:
                    break;
            }
        }
        return $value;
    }

    public function t_group_name( $group_name ) {
        global $group;
        return icl_t( $this->_context,
                "{$this->_group_string_prefix}{$group->id} name", $group->name );
    }

    public function t_group_edit_tabs( $tabs, $groups, $group_name ) {
        foreach ( $groups as $k => $group ) {
            $group_name = icl_t( $this->_context,
                "{$this->_group_string_prefix}{$group->id} name", $group->name );
            $tabs[$k] = preg_replace("/(<([\w]+)[^>]*>){$group->name}(<\/\\2>)/",
                                "$1{$group_name}$3", $tabs[$k], -1);
        }
        return $tabs;
    }

    public function sanitize_option_basename( $option, $field_id ) {
        $sanitized_string = bpml_sanitize_string_name( $option->name, 30 );
        return "{$this->_field_string_prefix}{$field_id} - option '{$sanitized_string}'";
    }

    protected function verify_nonce() {
        if ( !wp_verify_nonce( $_POST['nonce'], 'bpml-xprofile' ) ) {
            die('0');
        }
        return true;
    }

    protected function _scan_needed() {
        if ( $groups = bp_xprofile_get_groups( array('fetch_fields' => true) ) ) {
            foreach ( $groups as $group ) {
                $is_registered = icl_st_is_registered_string($this->_context,
                    "{$this->_group_string_prefix}{$group->id} name");
                if ( !$is_registered ) {
                    return true;
                }
                if ( !empty( $group->fields ) && is_array( $group->fields ) ) {
                    foreach ( $group->fields as $field ) {
                        $is_registered = icl_st_is_registered_string( $this->_context,
                            "{$this->_field_string_prefix}{$field->id} name" );
                        if ( !$is_registered ) {
                            return true;
                        }
                    }
                }
            }
        }
        return false;
    }

    public function scan_needed_warning() {
        echo '<div class="updated error"><p>'
                . __('Buddypress Multilingual: some profile fields are not registered for translation','bpml')
                . '&nbsp;<a class="button edit js-bpml-register-fields" href="javascript:void(0)" data-bpml="nonce='
                . wp_create_nonce( 'bpml-xprofile' )
                . '&action=bpml_register_fields">'
                . __('Register fields','bpml') . '</a>'
                . '</p></div>';
    }

    public function ajax_register() {
        $response = '0';
        if ( $this->verify_nonce() ) {
            $this->register_fields();
            $response = __( 'Fields registered', 'bpml' );
        }
        die( $response );
    }

}

if ( function_exists( 'icl_t' ) && function_exists( 'icl_register_string' )
        && function_exists( 'icl_unregister_string' )
        && function_exists( 'icl_st_is_registered_string' ) ) {
    new BPML_XProfile();
}
