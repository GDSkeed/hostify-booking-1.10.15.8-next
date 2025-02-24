jQuery(document).ready(function($) {
    // Override Calentim default button labels with WordPress translations
    if ($.fn.calentim) {
        var originalCalentim = $.fn.calentim;
        $.fn.calentim = function(options) {
            // Get translations from WordPress global object
            var translations = (typeof hfyTranslations !== 'undefined') ? hfyTranslations : {};
            
            // Merge options with translations
            options = $.extend({}, options || {}, {
                cancelLabel: translations.calendarCancel || "Cancel",
                applyLabel: translations.calendarApply || "Apply", 
                resetLabel: translations.calendarReset || "Reset"
            });
            
            // Call original plugin with merged options
            return originalCalentim.call(this, options);
        };
    }
});