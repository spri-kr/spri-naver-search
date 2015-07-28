<?php

class spri_naver_option {

    function __construct() {
        add_action( 'admin_menu', array( $this, 'add_page' ) );
        add_action( 'admin_init', array( $this, 'plugin_admin_init' ) );

    }

    function add_page() {
        add_options_page( 'SPRI Naver Search options',
                'SPRI Naver Search',
                'manage_options',
                'spri-naver-search',
                array( $this, 'options_view' ) );
    }

    function options_view() {
        ?>
        <div>
            <h2>SPRI Naver Search options</h2>
            attributes for search.
            <form action="options.php" method="post">

                <?php settings_fields( 'spri_naver_option_group' ); ?>
                <?php do_settings_sections( 'spri-naver-search' ); ?>

                <input name="Submit" type="submit" value="<?php esc_attr_e( 'Save Changes' ); ?>"/>
            </form>
        </div>
        <?php
    }

    function plugin_admin_init() {

        register_setting( 'spri_naver_option_group', 'spri_naver_option_name' );

        add_settings_section( 'spri_naver_option_section', 'Attrs', array( $this, 'attr_page' ), 'spri-naver-search' );

        add_settings_field(
                'api_key',
                'API key',
                array( $this, 'plugin_setting_string' ),
                'spri-naver-search',
                'spri_naver_option_section'
        );

    }

    function attr_page() {
    }

    function plugin_setting_string() {
        $options = get_option( 'spri_naver_option_name' );
        echo "<input name='spri_naver_option_name[api_key]' size='40' type='text' value='{$options['api_key']}' />";

    }

}

;
