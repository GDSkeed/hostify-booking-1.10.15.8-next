<?php
/**
 * Handle calendar translations
 */
class Hostify_Calendar_Translations {

    /**
     * Initialize the translations
     */
    public static function init() {
        add_filter('hfy_translations', array(__CLASS__, 'add_calendar_translations'));
        add_action('wp_enqueue_scripts', array(__CLASS__, 'enqueue_translations'), 20);
    }

    /**
     * Add calendar translations to the global translations array
     * 
     * @param array $translations The existing translations array
     * @return array Modified translations array
     */
    public static function add_calendar_translations($translations) {
        return array_merge($translations, array(
            'calendarCancel' => __('Cancel', 'hostify-booking'),
            'calendarApply'  => __('Apply', 'hostify-booking'),
            'calendarReset'  => __('Reset', 'hostify-booking')
        ));
    }

    /**
     * Enqueue translations script
     */
    public static function enqueue_translations() {
        // Only enqueue if calendar script is loaded
        if (!wp_script_is('calentim', 'enqueued')) {
            return;
        }

        wp_enqueue_script(
            'hostify-calendar-translations',
            plugins_url('public/res/js/calendar-translations.js', HOSTIFY_BOOKING_FILE),
            array('jquery', 'calentim'),
            HOSTIFY_BOOKING_VERSION,
            true
        );
    }
}

// Initialize translations
add_action('plugins_loaded', array('Hostify_Calendar_Translations', 'init'));