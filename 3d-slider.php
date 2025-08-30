<?php
/*
Plugin Name: WP 3D Folded Slider
Description: A simple 3D folded slider plugin with backend image thumbnails, shortcode [wp_3d_slider], and Elementor widget support.
Version: 1.7
Author: WP DESIGN LAB
*/

if (!defined('ABSPATH')) exit;

/* ------------------- ENQUEUE ASSETS ------------------- */
function wp3dslider_enqueue_assets() {
    $plugin_url = plugin_dir_url(__FILE__);

    // CSS
    wp_enqueue_style('wp3dslider-base', $plugin_url . 'css/base.css', [], '1.0');
    wp_enqueue_style('wp3dslider-horizontal', $plugin_url . 'css/horizontal.css', [], '1.0');

    // JS
    wp_enqueue_script('wp3dslider-imagesloaded', $plugin_url . 'js/imagesloaded.pkgd.min.js', ['jquery'], '1.0', true);
    wp_enqueue_script('wp3dslider-horizontal', $plugin_url . 'js/horizontal.js', ['jquery'], '1.0', true);
}
add_action('wp_enqueue_scripts', 'wp3dslider_enqueue_assets');


/* ------------------- ENQUEUE BACKEND ADMIN CSS ------------------- */
function wp3dslider_admin_assets($hook) {
    if ($hook != 'toplevel_page_wp-3d-slider') return;

    wp_enqueue_media();
    wp_enqueue_script('jquery-ui-sortable');
    wp_enqueue_style('wp3dslider-admin', plugin_dir_url(__FILE__) . 'css/admin.css');

    // Color picker
    wp_enqueue_style('wp-color-picker');
    wp_enqueue_script('wp-color-picker');
}
add_action('admin_enqueue_scripts', 'wp3dslider_admin_assets');


/* ------------------- ADMIN MENU ------------------- */
function wp3dslider_admin_menu() {
    add_menu_page(
        '3D Slider Settings',
        '3D Slider',
        'manage_options',
        'wp-3d-slider',
        'wp3dslider_settings_page',
        'dashicons-images-alt2',
        80
    );
}
add_action('admin_menu', 'wp3dslider_admin_menu');


/* ------------------- REGISTER SETTINGS ------------------- */
function wp3dslider_register_settings() {
    register_setting('wp3dslider_options_group', 'wp3dslider_images');

    register_setting('wp3dslider_options_group', 'wp3dslider_height', [
        'type'              => 'string',
        'sanitize_callback' => 'sanitize_text_field',
        'default'           => '50vh',
    ]);

    register_setting('wp3dslider_options_group', 'wp3dslider_fold_bg', [
        'type'              => 'string',
        'sanitize_callback' => 'sanitize_hex_color',
        'default'           => '#ccc',
    ]);

    register_setting('wp3dslider_options_group', 'wp3dslider_wrapper_bg', [
        'type'              => 'string',
        'sanitize_callback' => 'sanitize_hex_color',
        'default'           => '#f5f5f5',
    ]);

    register_setting('wp3dslider_options_group', 'wp3dslider_title_size', [
        'type'              => 'string',
        'sanitize_callback' => 'sanitize_text_field',
        'default'           => '20px',
    ]);
}
add_action('admin_init', 'wp3dslider_register_settings');


/* ------------------- SETTINGS PAGE ------------------- */
function wp3dslider_settings_page() {
    $images      = get_option('wp3dslider_images', []);
    $height      = get_option('wp3dslider_height', '50vh');
    $fold_bg     = get_option('wp3dslider_fold_bg', '#ccc');
    $wrapper_bg  = get_option('wp3dslider_wrapper_bg', '#f5f5f5');
    $title_size  = get_option('wp3dslider_title_size', '20px');
    ?>
    <div class="wrap">
        <h1>WP 3D Slider Settings</h1>
        <form method="post" action="options.php">
            <?php settings_fields('wp3dslider_options_group'); ?>

            <table class="form-table" id="wp3dslider-table">
                <thead>
                    <tr>
                        <th>Thumbnail</th>
                        <th>Title</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="sortable">
                    <?php if(!empty($images)):
                        foreach($images as $index => $item): ?>
                            <tr>
                                <td>
                                    <img class="image-preview" src="<?php echo esc_url($item['url']); ?>" style="max-width:100px; display:block; margin-bottom:5px;" />
                                    <input type="hidden" name="wp3dslider_images[<?php echo $index; ?>][url]" value="<?php echo esc_url($item['url']); ?>" />
                                    <button class="upload_image_button button">Upload</button>
                                </td>
                                <td><input type="text" name="wp3dslider_images[<?php echo $index; ?>][title]" value="<?php echo esc_attr($item['title']); ?>" /></td>
                                <td><button class="remove_row button">Remove</button></td>
                            </tr>
                        <?php endforeach;
                    endif; ?>
                </tbody>
            </table>

            <button id="add_row" class="button">Add New</button>

            <h2>Slider Options</h2>
            <table class="form-table">
                <tr>
                    <th scope="row">Slider Height</th>
                    <td>
                        <input type="text" name="wp3dslider_height" value="<?php echo esc_attr($height); ?>" placeholder="e.g. 400px or 60vh" />
                    </td>
                </tr>
                <tr>
                    <th scope="row">Fold Background Color</th>
                    <td>
                        <input type="text" name="wp3dslider_fold_bg" value="<?php echo esc_attr($fold_bg); ?>" class="wp-color-picker-field" data-default-color="#ccc" />
                    </td>
                </tr>
                <tr>
                    <th scope="row">Wrapper Background Color</th>
                    <td>
                        <input type="text" name="wp3dslider_wrapper_bg" value="<?php echo esc_attr($wrapper_bg); ?>" class="wp-color-picker-field" data-default-color="#f5f5f5" />
                    </td>
                </tr>
                <tr>
                    <th scope="row">Title Font Size</th>
                    <td>
                        <input type="text" name="wp3dslider_title_size" value="<?php echo esc_attr($title_size); ?>" placeholder="e.g. 20px or 2em" />
                    </td>
                </tr>
            </table>

            <?php submit_button(); ?>
        </form>
    </div>

    <script>
    jQuery(document).ready(function($){
        $("#sortable").sortable({ items: "tr", cursor: "move", axis: "y" });

        $('body').on('click', '.upload_image_button', function(e) {
            e.preventDefault();
            var button = $(this);
            var customUploader = wp.media({
                title: 'Choose Image',
                button: { text: 'Choose Image' },
                multiple: false
            });
            customUploader.on('select', function() {
                var attachment = customUploader.state().get('selection').first().toJSON();
                button.siblings('input[type=hidden]').val(attachment.url);
                button.siblings('img.image-preview').attr('src', attachment.url);
            });
            customUploader.open();
        });

        $('#add_row').on('click', function(e){
            e.preventDefault();
            var count = $('#sortable tr').length;
            $('#sortable').append(
                '<tr>' +
                '<td><img class="image-preview" style="max-width:100px; display:block; margin-bottom:5px;" />' +
                '<input type="hidden" name="wp3dslider_images['+count+'][url]" /> ' +
                '<button class="upload_image_button button">Upload</button></td>' +
                '<td><input type="text" name="wp3dslider_images['+count+'][title]" /></td>' +
                '<td><button class="remove_row button">Remove</button></td>' +
                '</tr>'
            );
        });

        $('body').on('click', '.remove_row', function(e){
            e.preventDefault();
            $(this).closest('tr').remove();
        });

        $('.wp-color-picker-field').wpColorPicker();
    });
    </script>
    <?php
}


/* ------------------- FRONTEND DYNAMIC CSS ------------------- */
function wp3dslider_custom_css() {
    $height      = get_option('wp3dslider_height', '50vh');
    $fold_bg     = get_option('wp3dslider_fold_bg', '#ccc');
    $wrapper_bg  = get_option('wp3dslider_wrapper_bg', '#f5f5f5');
    $title_size  = get_option('wp3dslider_title_size', '20px');
    ?>
    <style>
        .wp-3d-slider-wrapper {
            background: <?php echo esc_attr($wrapper_bg); ?>;
        }
        .wp-3d-slider-wrapper .screen,
        .wp-3d-slider-wrapper .content {
            height: <?php echo esc_attr($height); ?>;
            justify-content: center;
        }
        .wp-3d-slider-wrapper .fold {
            background: <?php echo esc_attr($fold_bg); ?>;
        }
        .wp-3d-slider-wrapper .content__title {
            font-size: <?php echo esc_attr($title_size); ?>;
        }
    </style>
    <?php
}
add_action('wp_head', 'wp3dslider_custom_css');


/* ------------------- SHORTCODE ------------------- */
function wp3dslider_display_slider() {
    $images = get_option('wp3dslider_images', []);
    if (empty($images)) return '<p>No slider images found. Please add images in the 3D Slider settings.</p>';
    ob_start(); ?>
    <div class="wp-3d-slider-wrapper">
        <div class="slider-container">
            <div class="content">
                <div class="fold-content" id="base-content">
                    <?php foreach($images as $item): ?>
                        <img class="content__img" src="<?php echo esc_url($item['url']); ?>" alt="<?php echo esc_attr($item['title']); ?>" />
                        <h3 class="content__title"><?php echo esc_html($item['title']); ?></h3>
                    <?php endforeach; ?>
                </div>
                <div class="screen" id="fold-effect">
                    <div class="wrapper-3d">
                        <div class="fold fold-before fold-before-3"></div>
                        <div class="fold fold-before fold-before-2"></div>
                        <div class="fold fold-before fold-before-1"></div>
                        <div class="fold fold-main"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('wp_3d_slider', 'wp3dslider_display_slider');


/* ------------------- ELEMENTOR WIDGET ------------------- */
function wp3dslider_register_elementor_widget( $widgets_manager ) {
    require_once( __DIR__ . '/elementor-wp3dslider-widget.php' );
    $widgets_manager->register( new \WP3DSlider_Elementor_Widget() );
}
add_action( 'elementor/widgets/register', 'wp3dslider_register_elementor_widget' );
