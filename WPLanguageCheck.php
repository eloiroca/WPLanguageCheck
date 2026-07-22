<?php
/**
 * Plugin Name: WPLanguageCheck
 * Description: Sugerencia de idioma mediante geolocalización. Muestra un banner no intrusivo y guarda la preferencia del usuario en cookie.
 * Version: 1.0.0
 * Author: Eloi
 * Author URI:        https://github.com/eloiroca/eloiroca
 */

// Constantes como en WPBooking
if (!defined('DIRECTORI_PLUGIN_WPLANGUAGECHECK')) {
    define('DIRECTORI_PLUGIN_WPLANGUAGECHECK', plugin_dir_path(__FILE__));
}
if (!defined('URL_PLUGIN_WPLANGUAGECHECK')) {
    define('URL_PLUGIN_WPLANGUAGECHECK', plugin_dir_url(__FILE__));
}
if (!defined('ASSETS_VERSION_WPLANGUAGECHECK')) {
    define('ASSETS_VERSION_WPLANGUAGECHECK', '1.0.30');
}

if (!defined('WPLANGUAGECHECK_COOKIE_NAME')) {
    define('WPLANGUAGECHECK_COOKIE_NAME', 'wp_language_check');
}

//Language Definition
add_action('plugins_loaded', function() {
    if (!defined('LANG_WPLANGUAGECHECK')) {
        if (defined('ICL_LANGUAGE_CODE')) {
            define('LANG_WPLANGUAGECHECK', ICL_LANGUAGE_CODE);
        } elseif (function_exists('pll_current_language')) {
            define('LANG_WPLANGUAGECHECK', pll_current_language());
        } else {
            define('LANG_WPLANGUAGECHECK', get_locale());
        }
    }

});

function wplanguagecheck_get_client_ip() {
    $keys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];

    foreach ($keys as $key) {
        if (empty($_SERVER[$key])) continue;

        $value = sanitize_text_field(wp_unslash($_SERVER[$key]));
        $candidate = trim(explode(',', $value)[0]);

        if (filter_var($candidate, FILTER_VALIDATE_IP)) {
            return $candidate;
        }
    }

    return '';
}

function wplanguagecheck_get_ip_info($ip) {
    if (empty($ip)) return [];

    $response = wp_remote_get('https://ipinfo.io/' . rawurlencode($ip) . '/geo', [
        'timeout' => 8,
    ]);

    if (is_wp_error($response)) return [];

    $status = wp_remote_retrieve_response_code($response);
    if ($status !== 200) return [];

    $body = wp_remote_retrieve_body($response);
    $decoded = json_decode($body, true);

    return is_array($decoded) ? $decoded : [];
}

function wplanguagecheck_get_country_code() {
    if (!empty($_SERVER['HTTP_CF_IPCOUNTRY'])) {
        $code = strtoupper(sanitize_text_field(wp_unslash($_SERVER['HTTP_CF_IPCOUNTRY'])));
        if (preg_match('/^[A-Z]{2}$/', $code)) {
            return $code;
        }
    }

    $ip = wplanguagecheck_get_client_ip();
    $ipInfo = wplanguagecheck_get_ip_info($ip);

    if (!empty($ipInfo['country'])) {
        $code = strtoupper((string) $ipInfo['country']);
        if (preg_match('/^[A-Z]{2}$/', $code)) {
            return $code;
        }
    }

    return '';
}

function wplanguagecheck_get_languages_config() {
    return [
        // Inglés (por defecto en /)
        'en' => [
            'slug' => '',
            'home_path' => '/',
            'is_default' => true,
            'countries' => ['GB'],
            'label' => 'England',
        ],

        // Inglés Estados Unidos
        'en-usa' => [
            'slug' => 'en-usa',
            'home_path' => '/en-usa/',
            'countries' => ['US', 'AU', 'NZ'],
            'label' => 'United States',
        ],

        // Español
        'es' => [
            'slug' => 'es',
            'home_path' => '/es/',
            'countries' => [
                'ES', 'MX', 'AR', 'CO', 'CL', 'PE', 'VE', 'EC', 'GT', 'CU', 'BO', 'DO', 'HN', 'PY', 'SV', 'NI', 'CR', 'PA', 'UY'
            ],
            'label' => 'Spain',
        ],

        // Francés
        'fr' => [
            'slug' => 'fr',
            'home_path' => '/fr/',
            'countries' => ['FR'],
            'label' => 'France',
        ],

        // Alemán
        'de' => [
            'slug' => 'de',
            'home_path' => '/de/',
            'countries' => ['DE', 'CH'],
            'label' => 'Germany',
        ],

        // Catalán
        'ca' => [
            'slug' => 'ca',
            'home_path' => '/ca/',
            'countries' => ['AD'],
            'label' => 'Catalonia',
        ],

        // Ruso
        'ru' => [
            'slug' => 'ru',
            'home_path' => '/ru/',
            'countries' => ['RU'],
            'label' => 'Russia',
        ],
    ];
}

function wplanguagecheck_get_clean_language_label($languageCode) {
    $labels = [
        'en' => 'English',
        'es' => 'Español',
        'ca' => 'Català',
        'de' => 'Deutsch',
        'ru' => 'Русский',
        'fr' => 'Français',
        'en-usa' => 'English USA',
    ];
    return $labels[strtolower($languageCode)] ?? $languageCode;
}

function wplanguagecheck_get_route_prefix_by_language($language) {
    $config = wplanguagecheck_get_languages_config();
    $language = strtolower((string) $language);

    if (empty($config[$language])) return '/';

    $slug = isset($config[$language]['slug']) ? trim((string) $config[$language]['slug'], '/') : '';
    $isDefault = !empty($config[$language]['is_default']);

    if ($isDefault || $slug === '') return '/';

    return '/' . strtolower($slug) . '/';
}

function wplanguagecheck_get_language_country_label($language, $targetLanguage = null) {
    $config = wplanguagecheck_get_languages_config();
    $language = strtolower((string) $language);

    if (empty($config[$language]['label'])) return strtoupper($language);

    return __wpl($config[$language]['label'], $targetLanguage);
}

function wplanguagecheck_get_country_label_by_code($countryCode, $targetLanguage = null) {
    $countryCode = strtoupper((string) $countryCode);
    if ($countryCode === '') {
        return __wpl('your country', $targetLanguage);
    }

    $config = wplanguagecheck_get_languages_config();
    foreach ($config as $language => $data) {
        if (empty($data['countries']) || !is_array($data['countries'])) continue;

        $countries = array_map(static function($code) {
            return strtoupper((string) $code);
        }, $data['countries']);

        if (in_array($countryCode, $countries, true)) {
            $label = !empty($data['label']) ? $data['label'] : $countryCode;
            return __wpl($label, $targetLanguage);
        }
    }

    return $countryCode;
}

function wplanguagecheck_get_current_path_language() {
    $config = wplanguagecheck_get_languages_config();
    $currentLanguage = defined('LANG_WPLANGUAGECHECK') ? strtolower((string) LANG_WPLANGUAGECHECK) : '';
    $currentLanguage = str_replace('_', '-', $currentLanguage);

    if (!empty($config[$currentLanguage])) {
        return $currentLanguage;
    }

    $baseLanguage = explode('-', $currentLanguage)[0];

    if (!empty($config[$baseLanguage])) {
        return $baseLanguage;
    }

    return '';
}

function wplanguagecheck_get_preferred_language_by_country($countryCode) {
    $countryCode = strtoupper((string) $countryCode);

    if ($countryCode === '') return '';

    $config = wplanguagecheck_get_languages_config();

    foreach ($config as $language => $data) {
        if (empty($data['countries']) || !is_array($data['countries'])) continue;

        $countries = array_map(static function($code) {
            return strtoupper((string) $code);
        }, $data['countries']);

        if (in_array($countryCode, $countries, true)) {
            return strtolower((string) $language);
        }
    }

    return '';
}

function wplanguagecheck_get_preferred_url($preferredLanguage) {
    $config = wplanguagecheck_get_languages_config();
    $preferredLanguage = strtolower((string) $preferredLanguage);

    $homePath = empty($config[$preferredLanguage]['home_path']) ? '/' : $config[$preferredLanguage]['home_path'];
    $homePath = '/' . ltrim((string) $homePath, '/');

    $host = isset($_SERVER['HTTP_HOST']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_HOST'])) : '';
    if ($host === '') return home_url($homePath);

    $scheme = is_ssl() ? 'https' : 'http';

    return esc_url_raw($scheme . '://' . $host . $homePath);
}

function wplanguagecheck_is_cookie_defined() {
    if (empty($_COOKIE[WPLANGUAGECHECK_COOKIE_NAME])) return false;
    return sanitize_text_field(wp_unslash($_COOKIE[WPLANGUAGECHECK_COOKIE_NAME])) === '1';
}

function wplanguagecheck_get_wpml_current_language_cookie() {
    if (empty($_COOKIE['wp-wpml_current_language'])) return '';
    return sanitize_text_field(wp_unslash($_COOKIE['wp-wpml_current_language']));
}

function wplanguagecheck_is_admin_user_logged_in() {
    return is_user_logged_in() && current_user_can('manage_options');
}

function wplanguagecheck_get_modal_context() {
    $countryCode = wplanguagecheck_get_country_code();
    $preferredLanguage = wplanguagecheck_get_preferred_language_by_country($countryCode);
    $currentLanguage = wplanguagecheck_get_current_path_language();

    $shouldShow = !wplanguagecheck_is_admin_user_logged_in()
        && !wplanguagecheck_is_cookie_defined()
        && !empty($preferredLanguage)
        && !empty($currentLanguage)
        && $currentLanguage !== $preferredLanguage;

    $preferredUrl = wplanguagecheck_get_preferred_url($preferredLanguage);

    return [
        'should_show' => $shouldShow,
        'country' => $countryCode,
        'current_language' => $currentLanguage,
        'preferred_language' => $preferredLanguage,
        'preferred_url' => $preferredUrl,
    ];
}


if (!function_exists('__wpl')) {
    function wpbooking_translations_array($target_lang = null) {
        static $translations = [];

        $lang = $target_lang ? $target_lang : (defined('LANG_WPLANGUAGECHECK') ? LANG_WPLANGUAGECHECK : 'en');
        $lang = strtolower((string)$lang);

        if (isset($translations[$lang])) return $translations[$lang];

        $path = DIRECTORI_PLUGIN_WPLANGUAGECHECK . "lang/$lang.php";

        if (file_exists($path)) {
            $translations[$lang] = include $path;
        } else {
            // Intenta separar por _ o - para caer a un idioma base (ej: en_US o en-usa a en)
            $base_lang = $lang;
            if (strpos($base_lang, '_') !== false) {
                $base_lang = explode('_', $base_lang)[0];
            } elseif (strpos($base_lang, '-') !== false) {
                $base_lang = explode('-', $base_lang)[0];
            }
            $base_path = DIRECTORI_PLUGIN_WPLANGUAGECHECK . "lang/$base_lang.php";
            if (file_exists($base_path)) {
                $translations[$lang] = include $base_path;
            } else {
                $translations[$lang] = [];
            }
        }

        return $translations[$lang];
    }

    function __wpl($key, $target_lang = null) {
        $translations = wpbooking_translations_array($target_lang);
        return $translations[$key] ?? $key;
    }
}

/************************************************************
 * Registrar estilos y scripts
 ************************************************************/
function wplanguagecheck_enqueue_assets() {
    // Solo frontend
    if (is_admin()) return;

    // No mostrar ni cargar el banner a usuarios administradores logeados.
    if (wplanguagecheck_is_admin_user_logged_in()) return;

    $ver = ASSETS_VERSION_WPLANGUAGECHECK;

    // MicroModal
    wp_enqueue_style('style-wplanguagecheck-micromodal', URL_PLUGIN_WPLANGUAGECHECK . 'assets/css/micromodal/micromodal.css', [], $ver);
    wp_enqueue_script('script-wplanguagecheck-micromodal', URL_PLUGIN_WPLANGUAGECHECK . 'assets/js/micromodal/micromodal.js', [], $ver, true);

    // Estilos y JS propios
    wp_enqueue_style('style-wplanguagecheck', URL_PLUGIN_WPLANGUAGECHECK . 'assets/css/wplanguagecheck.css', ['style-wplanguagecheck-micromodal'], $ver);
    wp_enqueue_script('script-wplanguagecheck', URL_PLUGIN_WPLANGUAGECHECK . 'assets/js/wplanguagecheck.js', ['script-wplanguagecheck-micromodal'], $ver, true);

    // Datos para JS
    wp_localize_script('script-wplanguagecheck', 'WPLanguageCheckData', [
        'cookie_name' => WPLANGUAGECHECK_COOKIE_NAME,
        'ajax_url' => admin_url('admin-ajax.php'),
        'ajax_nonce' => wp_create_nonce('wplanguagecheck_get_modal_context'),
    ]);
}
add_action('wp_enqueue_scripts', 'wplanguagecheck_enqueue_assets');

function wplanguagecheck_ajax_get_modal_context() {
    check_ajax_referer('wplanguagecheck_get_modal_context', 'nonce');

    $modalContext = wplanguagecheck_get_modal_context();
    $visitorIp = wplanguagecheck_get_client_ip();
    $wpmlCurrentLanguageCookie = wplanguagecheck_get_wpml_current_language_cookie();

    $preferredLanguage = !empty($modalContext['preferred_language']) ? $modalContext['preferred_language'] : 'en';

    $titleText = __wpl('Location confirmation title', $preferredLanguage);

    $other_languages = [];
    $all_languages = ['en', 'es', 'ca', 'de', 'ru', 'fr', 'en-usa'];
    foreach ($all_languages as $lang_code) {
        if ($lang_code === strtolower($preferredLanguage)) {
            continue;
        }
        $other_languages[] = [
            'code' => $lang_code,
            'label' => wplanguagecheck_get_clean_language_label($lang_code),
            'url' => wplanguagecheck_get_preferred_url($lang_code)
        ];
    }

    wp_send_json_success([
        'cookie_name' => WPLANGUAGECHECK_COOKIE_NAME,
        'language_detected' => LANG_WPLANGUAGECHECK,
        'should_show' => !empty($modalContext['should_show']),
        'visitor_ip' => $visitorIp,
        'wpml_current_language_cookie' => $wpmlCurrentLanguageCookie,
        'title_text' => $titleText,
        'other_languages' => $other_languages,
        'close_text' => __wpl('Close', $preferredLanguage),
    ]);
}
add_action('wp_ajax_wplanguagecheck_get_modal_context', 'wplanguagecheck_ajax_get_modal_context');
add_action('wp_ajax_nopriv_wplanguagecheck_get_modal_context', 'wplanguagecheck_ajax_get_modal_context');

/************************************************************
 * Registrar Template
 ************************************************************/
include_once( DIRECTORI_PLUGIN_WPLANGUAGECHECK . 'includes/wplanguagecheck-template.php' );
