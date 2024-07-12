<?php
/**
 * Plugin Name: Custom Sale Styles
 * Description: Plugin para mostrar un cuadro con el porcentaje de descuento al lado del precio en productos en oferta y permitir ajustar los estilos desde el panel de administración.
 * Version: 1.0
 * Author: Luciano Lyall | Expiey
 */

add_action('admin_menu', 'custom_sale_styles_menu');
function custom_sale_styles_menu() {
    add_menu_page('Estilos de Descuento', 'Estilos de Descuento', 'manage_options', 'custom-sale-styles', 'custom_sale_styles_page');
}

function custom_sale_styles_page() {
    ?>
    <div class="wrap">
        <h1>Crear estilos de cartel de % de descuento</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('custom_sale_styles_options');
            do_settings_sections('custom-sale-styles');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

add_action('admin_init', 'custom_sale_styles_init');
function custom_sale_styles_init() {
    register_setting('custom_sale_styles_options', 'custom_sale_styles_settings', 'custom_sale_styles_sanitize');

    add_settings_section('custom_sale_styles_section', 'Configuración de Estilos', 'custom_sale_styles_section_cb', 'custom-sale-styles');

    add_settings_field('background_color', 'Color de Fondo', 'background_color_field_cb', 'custom-sale-styles', 'custom_sale_styles_section');
    add_settings_field('text_color', 'Color de Texto', 'text_color_field_cb', 'custom-sale-styles', 'custom_sale_styles_section');
    add_settings_field('font_size', 'Tamaño de letra', 'font_size_field_cb', 'custom-sale-styles', 'custom_sale_styles_section');
    add_settings_field('padding', 'Padding (px)', 'padding_field_cb', 'custom-sale-styles', 'custom_sale_styles_section');
    add_settings_field('show_regular_price', 'Mostrar Precio Tachado', 'show_regular_price_field_cb', 'custom-sale-styles', 'custom_sale_styles_section');
}

function custom_sale_styles_sanitize($input) {
    $sanitized_input = array();

    if (isset($input['background_color'])) {
        $sanitized_input['background_color'] = sanitize_hex_color($input['background_color']);
    } else {
        $sanitized_input['background_color'] = '#000000'; // Fondo negro por defecto
    }

    if (isset($input['text_color'])) {
        $sanitized_input['text_color'] = sanitize_hex_color($input['text_color']);
    } else {
        $sanitized_input['text_color'] = '#ffffff'; // Letras blancas por defecto
    }

    $sanitized_input['font_size'] = isset($input['font_size']) ? $input['font_size'] : '12px'; // Tamaño de letra por defecto: 12px

    // Sanitize padding as integer
    $sanitized_input['padding'] = isset($input['padding']) ? absint($input['padding']) : 4; // Padding por defecto: 4px

    // Sanitize show_regular_price as boolean
    $sanitized_input['show_regular_price'] = isset($input['show_regular_price']) ? true : false;

    return $sanitized_input;
}

function custom_sale_styles_section_cb() {
    echo '<p>En este panel puedes asignarle el color de fondo y de frente al indicador de descuento, así como ajustar el tamaño de letra y el padding.</p>';
}

// Campos de configuración para color de fondo, color de texto, tamaño de letra, padding y mostrar precio tachado
function background_color_field_cb() {
    $options = get_option('custom_sale_styles_settings');
    echo '<input type="color" id="background_color" name="custom_sale_styles_settings[background_color]" value="' . esc_attr($options['background_color']) . '" />';
}
function text_color_field_cb() {
    $options = get_option('custom_sale_styles_settings');
    echo '<input type="color" id="text_color" name="custom_sale_styles_settings[text_color]" value="' . esc_attr($options['text_color']) . '" />';
}
function font_size_field_cb() {
    $options = get_option('custom_sale_styles_settings');
    $font_size = isset($options['font_size']) ? $options['font_size'] : '12px'; // Tamaño de letra por defecto: 12px
    echo '<input type="text" id="font_size" name="custom_sale_styles_settings[font_size]" value="' . esc_attr($font_size) . '" />';
}
function padding_field_cb() {
    $options = get_option('custom_sale_styles_settings');
    $padding = isset($options['padding']) ? $options['padding'] : 4; // Padding por defecto: 4px
    echo '<input type="number" id="padding" name="custom_sale_styles_settings[padding]" value="' . esc_attr($padding) . '" min="0" max="20" />';
}
function show_regular_price_field_cb() {
    $options = get_option('custom_sale_styles_settings');
    $checked = isset($options['show_regular_price']) && $options['show_regular_price'] ? 'checked' : '';
    echo '<label><input type="checkbox" id="show_regular_price" name="custom_sale_styles_settings[show_regular_price]" value="1" ' . $checked . '> Mostrar precio tachado</label>';
}

// Aplicar estilos al porcentaje de descuento y manejar precio tachado
add_filter('woocommerce_get_price_html', 'custom_sale_styles_filter', 10, 2);
function custom_sale_styles_filter($price_html, $product) {
    if ($product->is_on_sale() && !is_admin()) {
        $options = get_option('custom_sale_styles_settings');
        $show_regular_price = isset($options['show_regular_price']) ? $options['show_regular_price'] : false;

        if (!$show_regular_price) {
            // Ocultar precio tachado eliminando la etiqueta <del>
            $price_html = preg_replace('/<del(.*?)<\/del>/', '', $price_html);
        }

        // Convertir los precios a números
        $sale_price = floatval($product->get_sale_price());
        $regular_price = floatval($product->get_regular_price());

        // Verificar que el precio regular no sea cero
        if ($regular_price > 0) {
            // Calcular el porcentaje de ahorro
            $saving_percentage = round(100 - ($sale_price / $regular_price * 100)); // Redondear porcentaje

            $background_color = isset($options['background_color']) ? $options['background_color'] : '#000000'; // Fondo negro por defecto
            $text_color = isset($options['text_color']) ? $options['text_color'] : '#ffffff'; // Letras blancas por defecto
            $font_size = isset($options['font_size']) ? $options['font_size'] : '12px'; // Tamaño de letra por defecto: 12px
            $padding = isset($options['padding']) ? $options['padding'] : 4; // Padding por defecto: 4px

            $percentage_html = '<span class="custom-sale-percentage" style="background-color: ' . $background_color . '; color: ' . $text_color . '; font-size: ' . $font_size . '; padding: ' . $padding . 'px; border-radius: 5px; margin-left: 5px;">-' . $saving_percentage . '%</span>';

            // Construir el HTML completo con el precio tachado y el porcentaje de descuento
            $price_html .= $percentage_html;
        }
    }

    return $price_html;
}
