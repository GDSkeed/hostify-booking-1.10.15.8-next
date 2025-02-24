jQuery(document).ready(function($) {
    // Override Calentim default button labels with WordPress translations
    if ($.fn.calentim) {
        $.fn.calentim.defaults.cancelLabel = typeof hfyTranslations !== 'undefined' && hfyTranslations.calendarCancel ? 
            hfyTranslations.calendarCancel : 'Cancel';
        $.fn.calentim.defaults.applyLabel = typeof hfyTranslations !== 'undefined' && hfyTranslations.calendarApply ? 
            hfyTranslations.calendarApply : 'Apply';
        $.fn.calentim.defaults.resetLabel = typeof hfyTranslations !== 'undefined' && hfyTranslations.calendarReset ? 
            hfyTranslations.calendarReset : 'Reset';
    }
});