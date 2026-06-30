<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */


if( !class_exists( 'VOYNOTIF_field' ) ) {
    class VOYNOTIF_field {
        
        function __construct( $field ) {           
            
            //Default settings
            $this->id = VOYNOTIF_FIELD_PREFIXE.$field['id'];
            $this->label = $field['label'];
            $this->description = null;
            $this->params = null;
            $this->screen = $field['screen'];
            $this->post_id = null;
            $this->type = $field['type'];
            
            //Add settings
            if( ! empty( $field['description'] ) ) {
                $this->description = $field['description'];
            }
            if( ! empty( $field['choices'] ) ) {
                $this->choices = $field['choices'];
            } 
            if( ! empty( $field['params'] ) ) {
                $this->params = $field['params'];
            } 
            if( ! empty( $field['post_id'] ) ) {
                $this->post_id = $field['post_id'];
            }  
            
        }
        
        /**
         * @update 2016-10-25
         * 
         * @param type $element
         * @return type
         */
        function is_post_id($element) {
            return !preg_match ("/[^0-9]/", $element);
        }
        
        
        /**
         * 
         * @update 2016-10-25
         * 
         * @return type
         */
        public function display() {
            //Sanitize main args 
            $type = $this->type;
            $context = $this->screen;
            $id = $this->id;
            $label = esc_html($this->label);

            //Get description
            $description = '';
            if( !empty($this->description) ) {
                $description = $this->description;
            }


            //Get select2 class if needed. Will be displayed later
            $select2 = '';
            if( isset($this->params['select2']) AND $this->params['select2'] == true ) {
                $select2 = 'select2';
            }

            //Check for conditions
            $visibility_class = '';
            if( isset($args['condition']) AND is_array($args['condition']) ) {       
                if( get_post_meta($post_id, $args['condition']['field_id'], true) == $args['condition']['value']  ) {
                    $visibility_class = '';
                } else {
                    $visibility_class = 'invisible';
                }
            }  

            //Set up placeholder
            $placeholder = '';
            if( ! empty( $this->params['placeholder'] ) ) {
                $placeholder = 'placeholder="'.$this->params['placeholder'].'"';
            }
            
            //Get value
            if( $this->screen == 'post' OR empty( $this->screen ) ) {
                $value = get_post_meta( $this->post_id, $this->id, true );
            } else {
                $value = get_option( $this->id, false );
            }

            //Build input regarding to field type
            $input = '';
            switch( $type ) {

                //Hidden field
                case 'hidden' :
                    $input = '<input type="hidden" name="'.$this->id.'" id="'.$this->id.'" value="'.esc_html(get_post_meta($post_id, $id, true)).'">';
                    break;
                
                case 'text' :
                    $input = '<input type="text" name="'.$this->id.'" id="'.$this->id.'" value="'.esc_html($value).'">';
                    break;
                
                case 'number' :
                    $input = '<input type="number" name="'.$this->id.'" id="'.$this->id.'" value="'.esc_html($value).'">';
                    break;

                //Select field
                case 'select' :
                    $input = '<select id="'.$this->id.'" name="'.$this->id.'" '.$placeholder.'>';
                    foreach( $this->choices as $k => $v ) {
                        $selected = '';
                        if( $value == $k ) {
                            $selected = 'selected';
                        }
                        $input .= '<option value="'.$k.'" '.$selected.'>'.$v.'</option>';
                    }
                    $input .= '</select>'; 
                    break;

                //Select multiple field
                case 'selectmultiple' :
                    $input = '<select multiple name="'.$id.'[]" '.$placeholder.'>';
                    foreach( $args['choices'] as $k => $v ) {
                        $selected = '';

                        if( ! is_array( get_post_meta($post_id, $id, true) ) ) {
                            if( get_post_meta($post_id, $id, true) == $k ) {
                                $selected = 'selected';
                            }
                        } else {
                            if(in_array( $k, get_post_meta($post_id, $id, true) ) ) {
                                $selected = 'selected';
                            }                
                        }

                        $input .= '<option value="'.$k.'" '.$selected.'>'.$v.'</option>';
                    }
                    $input .= '</select>';  
                    break;

                //Boolean field
                case 'boolean' :
                    $checked = '';
                    if( $value == true ) {
                        $checked = 'checked';
                    }
                    $input = '<input name="'.$id.'" type="checkbox" id="'.$id.'" value="true" '.$checked.'> <label for="'.$id.'">'.$this->params['title'].'</label>';
                    break;

                //WYSIWYG field
                case 'wysiywg' :
                    $input = '';
                    break;

            }

            //Display field
            ob_start(); 
            ?>
            <div id="voy_field_<?php echo $id; ?>" class="voy-field_wrapper voy_field_type_<?php echo $type; ?> <?php echo $visibility_class; ?>">
                <div class="voy-field_label">
                    <span class="label"><?php echo $label; ?></span>
                    <?php if( ! empty( $description ) ) {?>
                        <span class="description"><?php echo $description; ?></span>
                    <?php } ?>
                </div>
                <div class="voy-field_input <?php echo $select2; ?>">
                    <?php echo $input; ?>
                </div>
            </div>
            <?php //echo $this->_get_js_conditions($args); ?>
            <?php
            return ob_get_clean();
        }
        
        
        /**
         * 
         */
        public function save( $value ) {

            //Sanitize value for each field type
            switch ($this->type) {
                
                //Hidden field
                 case 'hidden' :
                    $this->save_value( sanitize_text_field($value) );
                    break;

                //Text field
                case 'text' :
                    $this->save_value( sanitize_text_field($value) );
                    break;
                
                //Text field
                case 'number' :
                    $this->save_value( intval($value) );
                    break;

                //Select field
                case 'select' :
                    if( array_key_exists( $value, $this->choices ) ) {
                        $this->save_value( sanitize_text_field($value) );
                    } else {
                        $this->save_value( '' );
                    }
                    break;

                //Multiple select field
                case 'selectmultiple' :
                    $array_to_save = array();
                    if( is_array( $value ) AND ! empty( $value ) ) {
                        foreach($value as $val_to_save) {
                            if( array_key_exists( $val_to_save, $field['choices'] ) ) {
                                $array_to_save[] = $val_to_save; 
                            }
                        }
                    }
                    $this->save_value( $array_to_save );
                    break; 

                //Boolean field
                case 'boolean' :
                    if( $value == true ) {
                        $this->save_value( true );
                    } else {
                        $this->save_value( false );
                    }
                    break;

            }            
        }
        
        
        /**
         * 
         * @param type $value
         */
        function save_value( $value ) {
            
            if( $this->screen == 'post' OR empty( $this->screen ) ) {
                $result = update_post_meta($this->post_id, $this->id, $value);
                do_action('voynotif/save_field', $this->id, $value, $result, 'post');
            } else {
                $result = update_option( $this->id, $value );
                do_action('voynotif/save_field', $this->id, $value, $result, 'option');
            }

        }
        
     
    }
}
