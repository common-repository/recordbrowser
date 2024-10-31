<?php
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );
$path = $_SERVER['DOCUMENT_ROOT'];
/**
 * Adds Foo_Widget widget.
 */
class Recordbrowser_Slider_Widget extends WP_Widget {

    /**
     * Register widget with WordPress.
     */
    function __construct() {
        parent::__construct(
            'Recordbrowser_Slider_Widget', // Base ID
            esc_html__( 'Recordbrowser Slider', 'text_domain' ), // Name
            array( 'description' => esc_html__( 'Allows you to display random records in the sidebar', 'text_domain' ), ) // Args
        );
    }

    /**
     * Front-end display of widget.
     *
     * @see WP_Widget::widget()
     *
     * @param array $args     Widget arguments.
     * @param array $instance Saved values from database.
     */
    public function widget( $args, $instance ) {

        echo $args['before_widget'];
        if ( ! empty( $instance['title'] ) ) {
            echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ) . $args['after_title'];
        }


        if(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == "on") {
            $pre_url = "https://";
        }

        else {
            $pre_url = "http://";
        }

        echo "<div class='" . $this->id . "' id='randomrecordbrowsersidebarwidget'>";
        //display the records
        $text =  recordbrowser_slider_wiget( $instance['r_amount'], $instance['page_url'], $pre_url);
        echo $text;
        echo '</div>';
        echo '<br />';
        //display the load more
        $form ='<input type="hidden"  id="reload_recordbrowser_url"  value="' . $instance['page_url'] . '">';
        $form .='<input type="hidden"  id="reload_recordbrowser_pre_url"  value="' . $pre_url . '">';
        $form .='<input type="hidden"  id="reload_recordbrowser_limit"  value="' . $instance['r_amount'] . '">';
        $form .='<input class="reload_recordbrowser_random" type="submit" id="reload_recordbrowser_random title"  value="load more records">';
        echo $form;




        //End of display records / reload link, optional sponsoring link
        if($instance['powered_by'] == "yes") {
            echo '<br/><span class ="proudly_powered_by_recordbrowser"> discography powered by: 
            <a href="https://wordpress.org/plugins/recordbrowser/" target="_blank">recordbrowser</a></span>';
        }
        echo $args['after_widget'];
    }

    /**
     * Back-end widget form.
     *
     * @see WP_Widget::form()
     *
     * @param array $instance Previously saved values from database.
     */
    public function form( $instance ) {
        $title = ! empty( $instance['title'] ) ? $instance['title'] : esc_html__( 'New title', 'text_domain' );
        $page_url = ! empty( $instance['page_url'] ) ? $instance['page_url'] : esc_html__( '', 'text_domain' );
        $r_amount = ! empty( $instance['r_amount'] ) ? $instance['r_amount'] : esc_html__( '3', 'text_domain' );
        $powered_by = ! empty( $instance['powered_by'] ) ? $instance['powered_by'] : esc_html__( 'no', 'text_domain' );
        ?>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_attr_e( 'Title:', 'text_domain' ); ?></label>
            <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
        </p>
        <?php
        /**
         * if the user uses a page or post to display the [recordbrowser] shortcode, records in the sidebar widget will link to their details page
         */
        ?>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'page_url' ) ); ?>"><?php esc_attr_e( 'URL you use to display your records, like http://www.example.com/records:', 'text_domain' ); ?></label>
            <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'page_url' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'page_url' ) ); ?>" type="text" value="<?php echo esc_attr( $page_url ); ?>">
        </p>
        <?php
        /**
         * We want to allow the user to select between 1 and 5 records that are randomly selected.
         * Each pageload or click on a trigger displays a new set.
         */
        ?>
        <p>
           <label for="<?php echo esc_attr( $this->get_field_id( 'r_amount' ) ); ?>"><?php esc_attr_e( 'Select how many records do you want to display:', 'text_domain' ); ?></label>
            <select class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'r_amount' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'r_amount' ) ); ?>">
                <option value="<?php echo esc_attr( $r_amount );?>"><?php echo esc_attr( $r_amount );?></value>
                <option value="1">1</option>
                <option value="2">2</option>
                <option value="3">3</option>
                <option value="4">4</option>
                <option value="5">5</option>
            </select>
        </p>
    <?php
        /**
         * Asking the user if they want to display a powered by recordbrowser link, standard is no.
         */
        ?>
        <p>

        <label for="<?php echo $this->get_field_id( 'powered_by' ); ?>"><?php esc_attr_e('Display a small link to the Recordbrowser plugin? Opens in a new tab. ', 'text_domain');
        echo "<br /><b>Current Value: " . $powered_by; ?></b>
        </label><br />
        <input type="radio" id="<?php echo $this->get_field_id( 'powered_by' ); ?>" name="<?php echo $this->get_field_name( 'powered_by' ); ?>" value="no" style="" checked = "checked"/> No<br />
        <input type="radio" id="<?php echo $this->get_field_id( 'powered_by' ); ?>" name="<?php echo $this->get_field_name( 'powered_by' ); ?>" value="yes" style="" /> Yes<br />
    </p>
    <?php
    }

    /**
     * Sanitize widget form values as they are saved.
     *
     * @see WP_Widget::update()
     *
     * @param array $new_instance Values just sent to be saved.
     * @param array $old_instance Previously saved values from database.
     *
     * @return array Updated safe values to be saved.
     */
    public function update( $new_instance, $old_instance ) {
        $instance = array();
        $instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
        $instance['page_url'] = ( ! empty( $new_instance['page_url'] ) && recordbrowser_validate_url($new_instance['page_url']) == TRUE ) ? strip_tags( $new_instance['page_url'] ) : '';
        $instance['powered_by'] = ( ! empty( $new_instance['powered_by'] ) && ($new_instance['powered_by'] == "yes" || $new_instance['powered_by']) == "no" ) ? strip_tags( $new_instance['powered_by'] ) : '';
        $instance['r_amount'] = ( ! empty( $new_instance['r_amount'] ) && intval($new_instance['r_amount']) && $new_instance['r_amount'] < 6 ) ? strip_tags( $new_instance['r_amount'] ) : '';
        return $instance;
    }

} // class Recordbrowser_Slider_Widget
