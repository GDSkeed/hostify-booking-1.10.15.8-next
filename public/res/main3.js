
function fixDateFormat(value, d, cal)
{
    if (d) {
        return d.format(hfyDFdef);
    } else if ((typeof cal !== 'undefined') && cal.config.startDate) {
        return cal.config.startDate.format(hfyDFdef);
    }
    return value;
}


jQuery(document).ready(function($) {

    jQuery.ajaxSetup({ headers: { 'X-HFY-CSRF-TOKEN': jQuery('meta[name="hfy-csrf-token"]').attr('content') }});

    var calDisDates = typeof calendarDisabledDates === 'undefined' ? [] : calendarDisabledDates;

    var
        // calentimStart,
        // calentimEnd,
        inquiryCalentimStart,
        inquiryCalentimEnd

        // todo
        startSelected = null,
        endSelected = null
        ;


    if (typeof minNights === 'undefined') {
        var minNights = 1;
    }

    var minNightsDefault = minNights;

    var filterCustomStay = function (date) {
        let result = false;
        if (typeof calendarCustomStay !== 'undefined') {
            $.each(calendarCustomStay, function(key, value) {
                if (date.isBetween(value.date_start, value.date_end, "day", "[]")) {
                    result = value.min_stay;
                    return false;
                }
            });
        }
        return result;
    };

    var setMinNights = function (date) {
        let customMinDate = filterCustomStay(date);
        if (customMinDate) {
            minNights = customMinDate;
        } else {
            minNights = minNightsDefault;
        }
        let addDays = getMinstay(date);
        if (addDays > minNights) minNights = addDays;
    };

    // handle booking form(s)

    $('.hfy-listing-booking-form').each(function()
    {
        var $formWrap = $(this),
            calentimStart,
            calentimEnd,
            startSelected = null,
            endSelected = null;

        $('.booking-btn').on('click', function () {
            $formWrap.find('.booking-price-block').addClass('open')
            document.body.style.overflow = 'hidden';
        });

        $('.close-booking-btn').on('click', function () {
            $formWrap.find('.booking-price-block').removeClass('open')
            document.body.style.overflow = 'visible';
        });

        $formWrap.on("click", ".number-input .last, .number-input .first", function () {
            changeGuests($(this), $formWrap.find('.guests'));
            submitForm(true);
        });

        $formWrap.on("submit", "form", function () {
            reFormatSearchFormDateCal($('input[name=start_date]', this), startSelected);
            reFormatSearchFormDateCal($('input[name=end_date]', this), endSelected);
        });

        let updateInputs = function () {
            if (startSelected) {
                setMinNights(startSelected);
            }
            let addDays = getMinstay(startSelected);
            // let addDays = minNights;
            if (startSelected && endSelected && startSelected.hasOwnProperty("_isAMomentObject") && endSelected.hasOwnProperty("_isAMomentObject")) {
                if (startSelected.isAfter(endSelected, "day")) {
                    endSelected = startSelected.clone().add(addDays, "days");
                }
            }
            if (startSelected && startSelected.hasOwnProperty("_isAMomentObject")) {
                if (inquiryCalentimStart && inquiryCalentimEnd) {
                    inquiryCalentimStart.config.startEmpty = false;
                    inquiryCalentimStart.config.startDate = startSelected;
                    inquiryCalentimEnd.config.startDate = startSelected;
                    inquiryCalentimEnd.config.minDate = startSelected.clone().add(addDays, 'days');
                    inquiryCalentimStart.$elem.val(startSelected.format(inquiryCalentimStart.config.format));
                }

                calentimStart.config.startEmpty = false;
                calentimStart.config.startDate = startSelected;

                calentimEnd.config.startDate = startSelected;
                calentimEnd.config.minDate = startSelected.clone().add(addDays, 'days');

                calentimStart.$elem.val(startSelected.format(calentimStart.config.format));
            }
            if (endSelected && endSelected.hasOwnProperty("_isAMomentObject")) {
                if(inquiryCalentimStart && inquiryCalentimEnd){
                    inquiryCalentimStart.config.endDate = endSelected;
                    inquiryCalentimEnd.config.endDate = endSelected;
                    inquiryCalentimEnd.$elem.val(endSelected.format(inquiryCalentimEnd.config.format));
                }
                calentimStart.config.endDate = endSelected;
                calentimEnd.config.endDate = endSelected;
                calentimEnd.$elem.val(endSelected.format(calentimEnd.config.format));
            }
        };

        let ondrawEvent = function (instance) {
            if (instance.globals.initComplete) {
                updateInputs();
                // instance.updateHeader();
            }
        };

        // discount code

        let $dc = jQuery(".discount-code-wrap", this);
        let $dcCheck = $dc.find('.discount_code_cb');
        let $dcText = $dc.find('input[name=discount_code]');
        let $dcApply = $dc.find('.discount_code_check');

        function dcApplyShow() {
            // if ($.trim($dcText.val()) !== '') $dcApply.show(); else $dcApply.hide();
            if ($.trim($dcText.val()) !== '') $dcApply.removeClass('btn-light').addClass('btn-success');
        }

        $dcText.on('keyup blur change', function(){
            dcApplyShow();
        });

        $dcApply.on('click', function(){
            submitForm();
            // jQuery(this).hide();
        });

        $dcCheck.on('change', function(){
            let $t = jQuery(this);
            let $i = $dc.find('.input_wrap');
            $dcText.val('');
            if ($t.prop('checked')) {
                $i.show();
                dcApplyShow();
            } else {
                $i.hide();
                submitForm();
            }
        });

        if ($dcCheck.prop('checked')) {
            $dc.find('.input_wrap').show();
            submitForm();
        }

        let resetDate = function(e) {
            e && e.preventDefault();
            startSelected = null;
            if (calentimStart && calentimStart.config) {
                calentimStart.config.startEmpty = true;
                calentimStart.config.startDate = moment().startOf('day');
                calentimStart.config.endDate = moment().startOf('day');
                calentimEnd.config.startEmpty = true;
                calentimEnd.config.minDate = moment().startOf('day');
                calentimStart.setStart(moment().startOf('day'));
                calentimStart.$elem.val("");
                calentimEnd.setEnd(null);
                calentimEnd.$elem.val("");
            }
            endSelected = null;
            // window.history.replaceState('', '', location.pathname);
            let href = new URL(window.location.href);
            href.searchParams.set('start_date', '');
            href.searchParams.set('end_date', '');
            window.history.replaceState('', '', href.toString());

            $formListingPrice.find('.prices').html('');
            // jQuery('.booking-price-block .direct-inquiry-modal-open').hide();
        }

        $formWrap.on('click', '.reset-date', resetDate);

        // submit form

        let $formListingPrice = $formWrap.find('.listing-price');
        let $prices = $formListingPrice.find('.prices');
        let delaysubmitform;

        function submitForm(timeouted = false) {

            if ($formListingPrice.length <= 0) {
                return;
            }

            $formListingPrice.attr('method', 'get');

            let query = '?' + $formListingPrice.serialize();
            let queryObj = $.query.parseNew(query);

            if (
                (queryObj && queryObj.queryObject && queryObj.queryObject.keys) && (
                    ((queryObj.queryObject.keys.start_date || '') != '')
                    || ((queryObj.queryObject.keys.end_date || '') != '')
                )
            ) {
                $formWrap.find('.booking-price-block').removeClass('dates-is-selected');
            } else {
                $formWrap.find('.booking-price-block').addClass('dates-is-selected');
            }

            // get form values & fix queryObj values
            let vars = {};

            $.each(queryObj.keys, function(key, value) {
                let v = value;
                if (key == 'start_date') {
                    v = fixDateFormat(v, startSelected, calentimStart)
                } else if (key == 'end_date') {
                    v = fixDateFormat(v, endSelected, calentimEnd);
                }
                queryObj.keys[key] = v;
                if (value !== true) vars[key] = v;
            });

            // add missed items from current url parameters
            let href = new URL(window.location.href);
            let ent = Object.fromEntries(href.searchParams.entries());
            $.each(ent, function(key, value) {
                if (typeof vars[key] === 'undefined') vars[key] = value;
            });

            // fix dates
            $.each(vars, function(key, v) {
                let value = $.trim(v);
                if (key == 'start_date') {
                    if (startSelected) {
                        value = startSelected.format(hfyDFdef);
                    } else if ((typeof calentimStart !== 'undefined') && calentimStart.config.startDate) {
                        value = calentimStart.config.startDate.format(hfyDFdef);
                    }
                }
                if (key == 'end_date') {
                    if (endSelected) {
                        value = endSelected.format(hfyDFdef);
                    } else if ((typeof calentimEnd !== 'undefined') && calentimEnd.config.endDate) {
                        value = calentimEnd.config.endDate.format(hfyDFdef);
                    }
                }
                vars[key] = value;
            });

            // update url params
            window.history.replaceState('', '', queryObj);

            let fres = []; // different array format for sending
            $.each(vars, function(name, value) {
                fres.push({ name, value });
            });

            if (
                   typeof vars['start_date'] === 'undefined'
                || vars['start_date'] === false
                || vars['start_date'] === ''
                || typeof vars['end_date'] === 'undefined'
                || vars['end_date'] === false
                || vars['end_date'] === ''
            ) {
                return;
            }

            var do_ = function() {
                $('body').addClass('loading-price');
                $.ajax({
                    url: hfyx.url,
                    data: {
                        action: 'listing_price',
                        data: jQuery.param(fres),
                    },
                    type: 'POST',
                    success: function(data) {
                        $('body').removeClass('loading-price');
                        if (data['success'] == true) {
                            $prices.html(data['data']);
                            $prices.html(data['title']);
                            let chListId = $prices.data('channel-listing-id');
                            let $bookbnb = $formWrap.find('.book-on-airbnb');
                            let bookHref = $bookbnb.attr('href');
                            if (chListId && bookHref) {
                                $bookbnb.attr('href', bookHref.replace('channel_listing_id', chListId));
                            } else {
                                $bookbnb.removeAttr('href');
                            }
                            // jQuery('.booking-price-block .direct-inquiry-modal-open').show();
                            if ($('.direct-inquiry-modal .direct-inquiry-nights').length) {
                                $('.direct-inquiry-modal .direct-inquiry-nights').val(data['nights']);
                            }
                        } else {
                            $prices.html(data['data']);
                            $formListingPrice.find('.price-per-night').html($prices.data('price'));
                        }
                        window.dispatchEvent(new Event('resize'));
                        document.dispatchEvent(new Event('hfy-price-loaded'));
                    },
                    error: function (xhr, ajaxOptions, thrownError) {
                        $('body').removeClass('loading-price');
                        $prices.html('<div class="calendar-error">Please try again</div>');
                        $formListingPrice.find('.price-per-night').html($prices.data('price'));
                        window.dispatchEvent(new Event('resize'));
                    }
                });
            }

            clearTimeout(delaysubmitform);
            delaysubmitform = setTimeout(function(){ do_() }, timeouted ? 500 : 1);
        }

        window.doUpdatePriceBlock = function() { submitForm.call(); }

        // guests selector

        function handleGuestsSelector()
        {
            $formWrap.on("click", ".guests-count-num-wrap, .btn-close-guests-box", function () {
                let $dd = $formWrap.find('.select-guests-dropdown');
                if ($dd.length) {
                    if ($dd.is(':hidden')) {
                        $dd.show();
                    } else {
                        $dd.hide();
                    }
                }
            });

            let $guests_wrap = $('.hfy-listing-booking-form .select-guests-wrap');
            // if ($guests_wrap.length <= 0) {
            //     $guests_wrap = $('.hfy-search-form-wrap .select-guests-wrap');
            // }
            if ($guests_wrap.length <= 0) return;

            $(".guests-box, .btn-close-guests-box .btn").on('click', $guests_wrap, function(){
                $('.select-guests').toggleClass('active-menu');
            });

            let $guests = $('.hfy-listing-booking-form .guests-count-num');
            let $guests_input = $('.hfy-listing-booking-form input[name=guests]');

            let $adults = $('#adults', $guests_wrap);
            let $children = $('#children', $guests_wrap);
            let $infants = $('#infants', $guests_wrap);
            let $pets = $('#pets', $guests_wrap);

            let max = parseInt($adults.attr('max'), 10);

            let iAdults = Math.abs(parseInt($adults.data('val'), 10));
            let iChildren = Math.abs(parseInt($children.data('val'), 10));

            let x = fixGuests(max, iAdults, iChildren);

            $adults.val(x[0]);
            $children.val(x[1]);
            $guests.text(x[2]);
            $guests_input.val(x[2]);

            $('.number-guest-input .ctrl-dec, .number-guest-input .ctrl-inc', $guests_wrap).on('click', function() {

                let $t = $(this);
                let $ctrl = $t.siblings('input').first();
                let iname = $ctrl.attr('name') || false;
                let ctrlMax = $ctrl.attr('max') || 10;

                if (iname === false) return;

                if ($t.hasClass('disabled')) return;

                let change = $t.hasClass('ctrl-inc') ? 1 : -1;

                let x = fixGuests(max, Math.abs(parseInt($adults.val(), 10)), Math.abs(parseInt($children.val(), 10)));
                let adults = x[0];
                let children = x[1];

                if (iname == 'adults') {
                    if (change > 0) {
                        if (adults + children + 1 <= max) adults++;
                    } else {
                        if (adults > 1) adults--;
                    }
                    x = fixGuests(max, adults, children);
                    $adults.val(x[0]);
                    $children.val(x[1]);
                    $guests.text(x[2]);
                    $guests_input.val(x[2]);

                } else if (iname == 'children') {
                    if (change > 0) {
                        if (adults + children + 1 <= max) children++;
                    } else {
                        if (children > 0) children--;
                    }
                    x = fixGuests(max, adults, children);
                    $adults.val(x[0]);
                    $children.val(x[1]);
                    $guests.text(x[2]);
                    $guests_input.val(x[2]);

                } else {
                    let val = Math.abs(parseInt($ctrl.val(), 10)) + change;
                    if (val > ctrlMax) val = ctrlMax;
                    if (val < 0) val = 0;
                    $ctrl.val(val);
                }

                submitForm(true);
            });

            // $formWrap.on("click", ".select-guests-dropdown", function () {
            // });
        }

        handleGuestsSelector();

        $formWrap.find(".calentim-start").calentim({
            startOnMonday: hfyStartOnMonday,
            locale: hfyCurrentLang,
            enableMonthSwitcher: false,
            enableYearSwitcher: false,
            showHeader: false,// isMob,
            headerSeparator: "Select start date",
            showFooter: false,
            showTimePickers: false,
            calendarCount: isMob ? 24 : 2,
            isMobile: isMob,
            isIOS: isIOS,
            format: hfyDFopt,
            showOn: 'bottom',
            arrowOn: 'center',
            autoAlign: true,
            oneCalendarWidth: 280,
            continuous: true,
            minDate: moment().startOf('day'),
            disabledRanges: calDisDates,
            onCustomStyles: function (cell, cellMoment, cellDateUnix, cellStyle, minDateUnix, maxDateUnix, currentMonth) {
                if (isDayDisabledForCheckIn(cellMoment)) {
                    cellStyle += " calentim-disabled-day";
                }
                return cellStyle;
            },
            autoCloseOnSelect: !isMob,
            onaftershow: function(instance){
                updatePopupTipsIn(instance);
                instance.input.find(".calentim-apply").attr("disabled",false);
                // instance.updateHeader();
            },
            onaftermonthchange: updatePopupTipsIn,
            onafteryearchange: updatePopupTipsIn,
            onafterhide: function () {
                if (calentimStart && calentimEnd) {
                    submitForm();
                }
                calentimEnd.input.find(".calentim-header-start").css('opacity', 1);
            },
            oninit: function (instance) {
                moment.locale(hfyCurrentLang);
                instance.globals.delayInputUpdate = true;
                calentimStart = instance;
                let v = instance.$elem[0].getAttribute('value');
                if (moment(v, hfyDFdef).isSameOrAfter(calentimStart.config.minDate, 'day')) {
                    startSelected = moment(v, hfyDFdef);
                    calentimStart.config.startDate = startSelected;
                    calentimStart.$elem.val(startSelected.format(calentimStart.config.format));
                } else {
                    calentimStart.$elem.val("");
                }
                // if (calentimStart && calentimEnd && !isMob) {
                //     submitForm();
                // }
                instance.input.find(".calentim-apply").attr("disabled",false);
            },
            ondraw: ondrawEvent,
            onfirstselect: function (instance, start) {
                // endSelected = null;

                startSelected = start.clone();
                instance.globals.startSelected = false;
                updateInputs();
                instance.hideDropdown(instance);

                // BEGIN open disabled date for end select (calentim.js changes)
                let filterDays = function (date, maxNights) {
                    let result = false;
                    let dateMax = false;

                    dateMin = date.clone().add(minNights, 'days');
                    if (maxNights)
                        dateMax = moment(date.clone().add(maxNights, 'days'));

                    $.each(instance.config.disabledRanges, function(key, value) {
                        if (dateMin.isBefore(value.start, 'day') || dateMin.isSame(value.start, 'day')) {
                            result = value.start;
                            return false;
                        } else if (dateMin.isBetween(value.start, value.end, "day", "[]")) {
                            result = dateMin;
                            return false;
                        }
                    });

                    if ((!result && dateMax) || (result && dateMax && dateMax.isBefore(result, 'day'))) {
                        result = dateMax;
                    }

                    return result;
                };
                let maxDate = filterDays(startSelected, maxNights);
                if (maxDate) {
                    calentimEnd.config.maxDate = moment(maxDate);
                } else {
                    calentimEnd.config.maxDate = null;
                }
                // END open disabled date for end select

                let filterDisabled = function (date) {
                    let result = false;
                    $.each(instance.config.disabledRanges, function(key, value) {
                        if (date.isBetween(value.start, value.end, "day", "(]")) {
                            result = value.start;
                            return false;
                        }
                    });
                    return result;
                };

                if (endSelected && endSelected.isAfter(calentimEnd.config.maxDate, 'day')) {
                    min = startSelected.clone().add(minNights, 'days');
                    if (!filterDisabled(min)) {
                        endSelected = min;
                        calentimEnd.config.endDate = min;
                        calentimEnd.$elem.val(min.format(calentimEnd.config.format));
                        instance.config.endDate = min;
                    } else {
                        endSelected = null;
                        calentimEnd.config.endDate = null;
                        calentimEnd.$elem.val("");
                        window.history.replaceState('', '', location.pathname);
                        $formListingPrice.find('.prices').html('');
                    }
                }

                min = startSelected.clone().add(minNights, 'days');
                if (endSelected && endSelected.isSameOrBefore(min, 'days')) {
                    endSelected = null;
                    calentimEnd.config.endDate = null;
                    calentimEnd.$elem.val("");
                    window.history.replaceState('', '', location.pathname);
                    $formListingPrice.find('.prices').html('');
                }

                if (!endSelected) {
                    min = startSelected.clone().add(minNights, 'days');
                    if (!filterDisabled(min)) {
                        endSelected = min;
                        calentimEnd.config.endDate = min;
                        calentimEnd.$elem.val(min.format(calentimEnd.config.format));
                        instance.config.endDate = min;
                    } else {
                        instance.config.endDate = startSelected;
                    }
                }

                // if (startSelected && endSelected) {
                //     submitForm();
                // }

                instance.input.find(".calentim-apply").attr("disabled", true);

                calentimEnd.showDropdown(calentimEnd);
                calentimEnd.input.find(".calentim-header-start").css('opacity', 0);
            },
            onbeforeselect: function (instance, start, end) {
                startSelected = start.clone();
                endSelected = end.clone();
                updateInputs();
            },
            onbeforeshow: function (instance) {
                if (startSelected) {
                    instance.config.startEmpty = false;
                    instance.setStart(startSelected);
                    instance.config.startDate = startSelected;
                    instance.$elem.val(startSelected.format(instance.config.format));
                    instance.setDisplayDate(startSelected);
                    if (!endSelected) {
                        instance.setEnd(startSelected)
                        instance.config.endDate = startSelected;
                    }
                }
            }
        });

        $formWrap.find(".calentim-end").calentim({
            startOnMonday: hfyStartOnMonday,
            locale: hfyCurrentLang,
            enableMonthSwitcher: false,
            enableYearSwitcher: false,
            showHeader: false,// isMob,
            headerSeparator: "Select end date",
            showFooter: false,
            showTimePickers: false,
            calendarCount: isMob ? 24 : 2,
            isMobile: isMob,
            isIOS: isIOS,
            format: hfyDFopt,
            showOn: 'bottom',
            arrowOn: 'right',
            autoAlign: true,
            oneCalendarWidth: 280,
            continuous: true,
            autoCloseOnSelect: !isMob,
            onCustomStyles: function (cell, cellMoment, cellDateUnix, cellStyle, minDateUnix, maxDateUnix, currentMonth) {
                if (isDayDisabledForCheckOut(cellMoment)) {
                    cellStyle += " calentim-disabled-day";
                }
                return cellStyle;
            },
            disabledRanges: calDisDates,
            isHotelBooking: true,
            onaftershow: function(instance) {
                updatePopupTipsOut(instance);
                instance.input.find(".calentim-apply").attr("disabled",true);
                // instance.updateHeader();
            },
            onaftermonthchange: updatePopupTipsOut,
            onafteryearchange: updatePopupTipsOut,
            onafterhide: function () {
                if (calentimStart && calentimEnd) {
                    submitForm();
                }
            },
            oninit: function (instance) {
                moment.locale(hfyCurrentLang);
                let filterInitDisabled = function (dateStart, dateEnd) {
                    $.each(instance.config.disabledRanges, function(key, value) {
                        if (
                            dateStart.isBetween(value.start, value.end, "day", "[]") ||
                            dateEnd.isBetween(value.start, value.end, "day", "(]") ||
                            (dateStart.isSameOrBefore(value.start, "days") && dateEnd.isSameOrAfter(value.end, "days"))
                        ) {
                            return true;
                        }
                    });
                    return false;
                };
                instance.globals.delayInputUpdate = true;
                calentimEnd = instance;

                if (startSelected) {
                    setMinNights(startSelected);
                }

                let v = instance.$elem[0].getAttribute('value');
                if (
                    moment(startSelected).isSameOrAfter(calentimStart.config.minDate, 'day')
                    && moment(startSelected.clone().add(minNights, 'days')).isSameOrBefore(moment(v, hfyDFdef), 'days')
                    // && !filterInitDisabled(calentimStart.config.startDate, moment(v, hfyDFopt))
                ) {
                    endSelected = moment(v, hfyDFdef);
                    calentimEnd.config.startDate = startSelected;
                    calentimEnd.config.endDate = endSelected;
                    calentimEnd.$elem.val(endSelected.format(calentimEnd.config.format));
                    calentimStart.config.endDate = endSelected;

                    let filterDays = function (date, maxNights) {
                        let result = false;
                        let dateMax = false;

                        dateMin = date.clone().add(minNights, 'days');
                        if (maxNights) {
                            dateMax = moment(date.clone().add(maxNights, 'days'));
                        }

                        $.each(instance.config.disabledRanges, function(key, value) {
                            if (dateMin.isBefore(value.start, 'day') || dateMin.isSame(value.start, 'day')) {
                                result = value.start;
                                return false;
                            } else if (dateMin.isBetween(value.start, value.end, "day", "[]")) {
                                result = dateMin;
                                return false;
                            }
                        });

                        if ((!result && dateMax) || (result && dateMax && dateMax.isBefore(result, 'day'))) {
                            result = dateMax;
                        }

                        return result;
                    };
                    let maxDate = typeof maxNights !== 'undefined' ? filterDays(startSelected, maxNights) : null;
                    if (maxDate) calentimEnd.config.maxDate = moment(maxDate);
                } else {
                    if (v && v.length > 0 && calentimStart.$elem[0].getAttribute('value').length > 0) {
                        $formListingPrice.find('.prices').html('<div class="calendar-error">The listing is not available for your selected dates.</div>');
                    }
                    calentimEnd.$elem.val("");
                    calentimEnd.config.startDate = null;
                    calentimEnd.config.endDate = null;
                    calentimEnd.config.startEmpty = true;
                    endSelected = '';
                    startSelected = '';
                    calentimStart.config.startEmpty = true;
                    calentimStart.config.startDate = null;
                    calentimStart.config.endDate = null;
                    calentimStart.$elem.val("");
                }
                calentimEnd.config.minDate = calentimStart.config.minDate.clone().add(minNights, 'days');

                // if (calentimStart && calentimEnd && !isMob) {
                //     submitForm();
                // }
            },
            ondraw: ondrawEvent,
            onfirstselect: function (instance, start) {
                if (calentimStart.config.endDate) {
                    endSelected = start.clone().set({
                        hours: calentimStart.config.endDate.hours(),
                        minutes: calentimStart.config.endDate.minutes(),
                        seconds: calentimStart.config.endDate.seconds()
                    });
                } else {
                    endSelected = start.clone();
                }
                instance.globals.startSelected = false;
                updateInputs();
                instance.hideDropdown(null);
                if (isMob) {
                    calentimStart.showDropdown(calentimStart);
                }
                calentimStart.input.find(".calentim-apply").attr("disabled",false);
            },
            onbeforeselect: function (instance, start, end) {
                startSelected = start.clone();
                endSelected = end.clone();
                updateInputs();
            },
            onbeforeshow: function (instance) {
                if (startSelected && endSelected) {
                    instance.config.startEmpty = false;
                    instance.setStart(startSelected);
                    instance.config.startDate = startSelected;
                }
                if (startSelected && !endSelected) {
                    instance.setDisplayDate(startSelected);
                } else {
                    instance.setDisplayDate(endSelected);
                }
            },
            onafterselect: function(instance, start, end){
                // instance.config.maxDate = null;
                instance.input.find(".calentim-apply").attr("disabled",false);
            }
        });

        submitForm();
    });



    // handle search form(s)

    $('.hfy-search-form-wrap').each(function()
    {
        var $searchWrap = $(this),
            calentimStart,
            calentimEnd,
            startSelected = null,
            endSelected = null;

        $searchWrap.on("click", ".number-input .last, .number-input .first", function () {
            changeGuests($(this), $searchWrap.find('.guests'));
        });

        $searchWrap.on("click", "button.advanced", function () {
            $(this).toggleClass('active');
            $searchWrap.find('.hfy-search-form-row-advanced').toggle();
            return false;
        });

        $searchWrap.on("click", ".toggle-more-btn", function () {
            $(this).parent().find('.toggle-more-container').toggleClass('expanded');
            $(this).hide();
            return false;
        });

        $searchWrap.on("click", ".btn-reset", function () {
            let $x = $(this).parents('.hfy-search-form-row-advanced').first();
            $x.find('input').val('').removeAttr('checked');
            $x.find('select option').removeAttr('selected');
            $x.find('select').val('');
        });

        $searchWrap.on("submit", "form", function () {
            $('.col-action button', this).attr('disabled', true);
            $searchWrap.addClass('processing');
            reFormatSearchFormDateCal($('.hfy-search-form-row input[name=start_date]', this), startSelected);
            reFormatSearchFormDateCal($('.hfy-search-form-row input[name=end_date]', this), endSelected);
        });

        let updateInputs = function () {
            if (startSelected) {
                setMinNights(startSelected);
            }
            let addDays = getMinstay(startSelected);
            // let addDays = minNights;
            if (startSelected && endSelected && startSelected.hasOwnProperty("_isAMomentObject") && endSelected.hasOwnProperty("_isAMomentObject")) {
                if (startSelected.isAfter(endSelected, "day")) {
                    endSelected = startSelected.clone().add(addDays, "days");
                }
            }
            if (startSelected && startSelected.hasOwnProperty("_isAMomentObject")) {
                calentimStart.config.startEmpty = false;
                calentimStart.config.startDate = startSelected;

                calentimEnd.config.startDate = startSelected;
                calentimEnd.config.minDate = startSelected.clone().add(addDays, 'days');

                calentimStart.$elem.val(startSelected.format(calentimStart.config.format));
            }
            if (endSelected && endSelected.hasOwnProperty("_isAMomentObject")) {
                calentimStart.config.endDate = endSelected;
                calentimEnd.config.endDate = endSelected;
                calentimEnd.$elem.val(endSelected.format(calentimEnd.config.format));
            }
        };

        let ondrawEvent = function (instance) {
            if (instance.globals.initComplete) {
                updateInputs();
                // instance.updateHeader();
            }
        };

        function handleGuestsSelectorSearch()
        {
            $searchWrap.on("click", ".col-guests label, .col-guests .guests-input-label, .col-guests .guests-input-label > *", function (e) {
                let $dd = $searchWrap.find('.select-guests-dropdown');
                if ($dd.length) {
                    if ($dd.is(':hidden')) {
                        $dd.show();
                    } else {
                        $dd.hide();
                    }
                }
            });

            $(document).mouseup(function(e) {
                let g = $('.col-guests .select-guests-dropdown');
                if (!g.is(e.target) && g.has(e.target).length === 0) g.hide();
            });

            $searchWrap.on("click", ".btn-close-guests-box", function (e) {
                $searchWrap.find('.select-guests-dropdown').hide();
                e.stopPropagation();
            });

            let $guests_wrap = $('.hfy-search-form-wrap .booking-search-input-container');
            if ($guests_wrap.length <= 0) return;

            $(".guests-box, .btn-close-guests-box .btn", $guests_wrap).on('click', $guests_wrap, function(){
                $('.select-guests').toggleClass('active-menu');
            });

            let $swrap = $('.hfy-search-form-wrap .booking-search-input-container');
            let $guests = $('.guests-count-num', $swrap);
            let $guests_input = $('input[name=guests]', $swrap);

            let $adults = $('#adults', $guests_wrap);
            let $children = $('#children', $guests_wrap);
            let $infants = $('#infants', $guests_wrap);
            let $pets = $('#pets', $guests_wrap);

            let max = parseInt($adults.attr('max'), 10);

            let iAdults = Math.abs(parseInt($adults.data('val'), 10));
            let iChildren = Math.abs(parseInt($children.data('val'), 10));

            let all = parseInt($adults.val(), 10) + parseInt($children.val(), 10);
            if (!all) all = $guests_input.val() || 1;
            $guests.text(all);
            $guests_input.val(all);

            $('.number-guest-input .ctrl-dec, .number-guest-input .ctrl-inc', $guests_wrap).on('click', function() {
                let $t = $(this);
                let $ctrl = $t.siblings('input').first();
                let iname = $ctrl.attr('name') || false;
                let ctrlMax = max || 10;
                let $guests = $t.parents('.booking-search-input-container').first().find('.guests-count-num');
                let $guests_input = $t.parents('.booking-search-input-container').first().find('input.guests');

                if (iname === false) return;

                let adults = Math.abs(parseInt($adults.val(), 10));
                let children = Math.abs(parseInt($children.val(), 10));

                let change = $t.hasClass('ctrl-inc') ? 1 : -1;

                let val = Math.abs(parseInt($ctrl.val(), 10)) + change;
                if (val > ctrlMax) val = ctrlMax;
                if (val < 0) val = 0;
                $ctrl.val(val);

                let all = parseInt($adults.val(), 10) + parseInt($children.val(), 10);
                $guests.text(all);
                $guests_input.val(all);
            });

            $searchWrap.on("click", ".guests-count-num-wrap, .btn-close-guests-box .btn", function () {
                let $dd = $searchWrap.find('.select-guests-dropdown');
                if ($dd.length) {
                    if ($dd.is(':hidden')) {
                        $dd.show();
                    } else {
                        $dd.hide();
                    }
                }
            });
        }

        handleGuestsSelectorSearch();

        $searchWrap.find(".calentim-start").calentim({
            startOnMonday: hfyStartOnMonday,
            locale: hfyCurrentLang,
            enableMonthSwitcher: false,
            enableYearSwitcher: false,
            showHeader: false, // isMob,
            showFooter: false,
            showTimePickers: false,
            calendarCount: isMob ? 24 : 2,
            isMobile: isMob,
            isIOS: isIOS,
            format: hfyDFopt,
            showOn: "bottom",
            arrowOn: 'left',
            autoAlign: true,
            oneCalendarWidth: 280,
            continuous: true,
            autoCloseOnSelect: !isMob,
            minDate: moment().startOf('day'),
            oninit: function (instance) {
                moment.locale(hfyCurrentLang);
                instance.globals.delayInputUpdate = true;
                calentimStart = instance;
                let v = instance.$elem[0].getAttribute('value');
                let d = moment(v, hfyDFdef);
                if (d.isSameOrAfter(calentimStart.config.minDate)) {
                    startSelected = d;
                    calentimStart.config.startDate = startSelected;
                    calentimStart.$elem.val(startSelected.format(calentimStart.config.format));
                } else {
                    calentimStart.$elem.val("");
                }
            },
            ondraw: ondrawEvent,
            onfirstselect: function (instance, start) {
                startSelected = start.clone();
                instance.globals.startSelected = false;
                updateInputs();
                instance.hideDropdown(instance);
                calentimEnd.showDropdown(calentimEnd);
            },
            onbeforeselect: function (instance, start, end) {
                startSelected = start.clone();
                endSelected = end.clone();
                updateInputs();
            },
            onbeforeshow: function (instance) {
                if (startSelected) {
                    instance.config.startEmpty = false;
                    instance.setStart(startSelected);
                    instance.config.startDate = startSelected;
                    instance.setDisplayDate(startSelected);
                }
                // instance.input.find(".calentim-apply").attr("disabled",true);
            },
            onaftershow: function(instance) {
                instance.input.find(".calentim-apply").attr("disabled",false);
            }
        });

        $searchWrap.find(".calentim-end").calentim({
            startOnMonday: hfyStartOnMonday,
            locale: hfyCurrentLang,
            enableMonthSwitcher: false,
            enableYearSwitcher: false,
            showHeader: false, // isMob,
            showFooter: false,
            showTimePickers: false,
            calendarCount: isMob ? 24 : 2,
            isMobile: isMob,
            isIOS: isIOS,
            format: hfyDFopt,
            showOn: "bottom",
            arrowOn: 'center',
            autoAlign: true,
            oneCalendarWidth: 280,
            continuous: true,
            autoCloseOnSelect: !isMob,
            oninit: function (instance) {
                moment.locale(hfyCurrentLang);
                instance.globals.delayInputUpdate = true;
                calentimEnd = instance;
                if (moment(startSelected).isSameOrAfter(calentimStart.config.minDate)) {
                    let v = instance.$elem[0].getAttribute('value');
                    endSelected = moment(v, hfyDFdef);
                    calentimEnd.config.startDate = startSelected;
                    calentimEnd.config.endDate = endSelected;
                    calentimEnd.$elem.val(endSelected.format(calentimEnd.config.format));
                    calentimStart.config.endDate = endSelected;
                } else {
                    calentimEnd.$elem.val("");
                }
                calentimEnd.config.minDate = calentimStart.config.minDate.clone().add(1, 'days');
            },
            ondraw: ondrawEvent,
            onfirstselect: function (instance, start) {
                if (calentimStart.config.endDate)
                    endSelected = start.clone().set({
                        hours: calentimStart.config.endDate.hours(),
                        minutes: calentimStart.config.endDate.minutes(),
                        seconds: calentimStart.config.endDate.seconds()
                    });
                else
                    endSelected = start.clone();
                instance.globals.startSelected = false;
                updateInputs();
                instance.hideDropdown(null);
                if (isMob) {
                    calentimStart.showDropdown(calentimStart);
                }
                //submitForm();
                instance.input.find(".calentim-apply").attr("disabled",false);
                calentimStart.input.find(".calentim-apply").attr("disabled",false);
            },
            onbeforeselect: function (instance, start, end) {
                startSelected = start.clone();
                endSelected = end.clone();
                updateInputs();
            },
            onbeforeshow: function (instance) {
                if (startSelected && endSelected) {
                    instance.config.startEmpty = false;
                    instance.setStart(startSelected);
                    instance.config.startDate = startSelected;
                }
                if (startSelected && !endSelected) {
                    instance.setDisplayDate(startSelected);
                } else {
                    instance.setDisplayDate(endSelected);
                }
            },
            onaftershow: function(instance) {
                instance.input.find(".calentim-apply").attr("disabled",true);
            }
        });



    });


    // Inquiry dialog

    function initInquiry()
    {
        let $m = jQuery('.direct-inquiry-modal').last();

        jQuery(document).on('click', '.direct-inquiry-modal-open', function(){
            $m.find('.thx').hide();
            $m.find('.direct-inquiry-modal-content').show();
            $m.hfymodal('show');
        });

        $m.on($.hfymodal.OPEN, function(event, modal) {
            setTimeout(function(){
                let $mc = jQuery('.direct-inquiry-modal-content');
                $mc.find('#inquiry_adults').val(jQuery('.listing-price .guests').val());
                $mc.find('input[name=discount_code]').val(jQuery('.listing-price input[name=discount_code]').val());
                handleInquiryDatepickers();
            }, 200);
            document.dispatchEvent(new Event('direct-inquiry-modal-opened'), modal);
        });

        jQuery(document).on('change paste keyup', '.direct-inquiry-form :input', function() {
            let errorContainer = jQuery(this).siblings('.error');
            if (errorContainer.length > 0) errorContainer.html('');
            else {
                errorContainer = jQuery(this).parent().siblings('.error');
                if (errorContainer) errorContainer.html('');
            }
        });

        jQuery(document).on('submit', '.direct-inquiry-form', function(e) {
            e.preventDefault();

            let $submitButton = jQuery('.direct-inquiry-modal-submit-button');
            $submitButton.attr('disabled', 'disabled');
            $submitButton.addClass('btn-in-progress');

            let form = jQuery(this);

            let fa = form.serializeArray(), fres = [];
            fa.forEach(function(el){
                if (el.name == 'check_in') {
                    fres.push({ name: el.name, value: fixDateFormat(el.value, startSelected, inquiryCalentimStart) });
                } else if (el.name == 'check_out') {
                    fres.push({ name: el.name, value: fixDateFormat(el.value, endSelected, inquiryCalentimEnd) });
                } else {
                    fres.push(el);
                }
            });
            let data = jQuery.param(fres);

            let recaptchaResponse = grecaptcha.getResponse()
            if (recaptchaResponse.length == 0){
                jQuery('#g-recaptcha-error').html('<span style="color:red;"> Required field.</span>');
                $submitButton.removeClass('btn-in-progress');
                $submitButton.removeAttr('disabled');
                return false;
            }

            $.ajax({
                url: hfyx.url,
                data: {
                    action: 'inquiry',
                    data: data
                },
                type: 'POST',
                success: function(result) {
                    if (!!result.success) {
                        jQuery('.direct-inquiry-modal .direct-inquiry-modal-content').hide();
                        jQuery('.direct-inquiry-modal .thx').show();
                    } else {
                        let error = result.msg;
                        if (error) {
                            let errorContainer = jQuery('.direct-inquiry-modal #message_error');
                            if (errorContainer) {
                                errorContainer.html(error);
                                errorContainer.show();
                            }
                            // if (index == 'verifyCode') {
                            //     jQuery('.direct-inquiry-refresh-captcha').addClass('fa-spin');
                            //     jQuery('.direct-inquiry-captcha > img').click();
                            // }
                        }
                    }
                },
                complete: function(result) {
                    $submitButton.removeClass('btn-in-progress');
                    $submitButton.removeAttr('disabled');
                }
            });
        });
    }

    function handleInquiryDatepickers()
    {
        handleInquiryStart();
        handleInquiryEnd();
    }

    function inquiryUpdateInputs()
    {
        if (startSelected) {
            setMinNights(startSelected);
        }
        let addDays = getMinstay(startSelected);
        // let addDays = minNights;
        if (startSelected && endSelected && startSelected.hasOwnProperty("_isAMomentObject") && endSelected.hasOwnProperty("_isAMomentObject")) {
            if (startSelected.isAfter(endSelected, "day")) {
                endSelected = startSelected.clone().add(addDays, "days");
            }
        }
        if (startSelected && startSelected.hasOwnProperty("_isAMomentObject")) {
            if (inquiryCalentimStart && inquiryCalentimEnd) {
                inquiryCalentimStart.config.startEmpty = false;
                inquiryCalentimStart.config.startDate = startSelected;
                inquiryCalentimEnd.config.startDate = startSelected;
                inquiryCalentimEnd.config.minDate = startSelected.clone().add(addDays, 'days');
                inquiryCalentimStart.$elem.val(startSelected.format(inquiryCalentimStart.config.format));
            }
        }
        if (endSelected && endSelected.hasOwnProperty("_isAMomentObject")) {
            if (inquiryCalentimStart && inquiryCalentimEnd) {
                inquiryCalentimStart.config.endDate = endSelected;
                inquiryCalentimEnd.config.endDate = endSelected;
                inquiryCalentimEnd.$elem.val(endSelected.format(inquiryCalentimEnd.config.format));
            }
        }
    };

    function handleInquiryStart()
    {
        let inq1 = jQuery(".direct-inquiry-modal:last #inquiry_checkin");
        if (inq1.length) {
            let inst1 = inq1.data("calentim");

            if (typeof inst1 !== 'undefined') {
                if (startSelected) {
                    inquiryCalentimStart.config.startDate = startSelected;
                    inquiryCalentimStart.$elem.val(startSelected.format(inquiryCalentimStart.config.format));
                }
                return;
            }

            inq1.calentim({
                startOnMonday: hfyStartOnMonday,
                locale: hfyCurrentLang,
                enableMonthSwitcher: false,
                enableYearSwitcher: false,
                showHeader: false, // isMob,
                showFooter: false,
                showTimePickers: false,
                calendarCount: isMob ? 24 : 2,
                isMobile: isMob,
                isIOS: isIOS,
                format: hfyDFopt,
                showOn:"bottom",
                arrowOn: 'left',
                autoAlign: true,
                oneCalendarWidth: 280,
                continuous: true,
                minDate: moment().startOf('day'),
                onCustomStyles: function(cell, cellMoment, cellDateUnix, cellStyle, minDateUnix, maxDateUnix, currentMonth) {
                    if (isDayDisabledForCheckIn(cellMoment)) {
                        cellStyle += " calentim-disabled-day";
                    }
                    return cellStyle;
                },
                disabledRanges: calDisDates,
                autoCloseOnSelect: !isMob,
                onaftershow: updatePopupTipsIn,
                onaftermonthchange: updatePopupTipsIn,
                onafteryearchange: updatePopupTipsIn,
                oninit: function(instance) {
                    moment.locale(hfyCurrentLang);
                    instance.globals.delayInputUpdate = true;
                    inquiryCalentimStart = instance;
                    // let v = instance.$elem.val();
                    // let d = moment(v, hfyDFdef);
                    // if (d.isSameOrAfter(inquiryCalentimStart.config.minDate, 'day')) {
                    if (startSelected) {
                        inquiryCalentimStart.config.startDate = startSelected;
                        inquiryCalentimStart.$elem.val(startSelected.format(inquiryCalentimStart.config.format));
                    } else {
                        inquiryCalentimStart.$elem.val('');
                    }
                    instance.input.find(".calentim-apply").attr("disabled",false);
                },
                // ondraw: function (instance) {
                //     if (instance.globals.initComplete) {
                //         updateInputs();
                //         // instance.updateHeader();
                //     }
                // },
                onfirstselect: function(instance, start) {
                    // endSelected = null;
                    startSelected = start.clone();
                    instance.globals.startSelected = false;
                    inquiryUpdateInputs();
                    instance.hideDropdown(instance);

                    // BEGIN open disabled date for end select (calentim.js changes)
                    let filterDays = function(date, maxNights) {
                        let result = false;
                        let dateMax = false;

                        dateMin = date.clone().add(minNights, 'days');
                        if (maxNights)
                            dateMax = moment(date.clone().add(maxNights, 'days'));

                        $.each(instance.config.disabledRanges, function(key, value) {
                            if (dateMin.isBefore(value.start, 'day') || dateMin.isSame(value.start, 'day')) {
                                result = value.start;
                                return false;
                            } else if (dateMin.isBetween(value.start, value.end, "day", "[]")) {
                                result = dateMin;
                                return false;
                            }
                        });

                        if ((!result && dateMax) || (result && dateMax && dateMax.isBefore(result, 'day'))) {
                            result = dateMax;
                        }

                        return result;
                    };
                    let maxDate = typeof maxNights !== 'undefined' ? filterDays(startSelected, maxNights) : null;
                    if (maxDate) {
                        inquiryCalentimEnd.config.maxDate = moment(maxDate);
                    } else {
                        inquiryCalentimEnd.config.maxDate = null;
                    }
                    // END open disabled date for end select

                    let filterDisabled = function(date) {
                        let result = false;
                        $.each(instance.config.disabledRanges, function(key, value) {
                            if (date.isBetween(value.start, value.end, "day", "(]")) {
                                result = value.start;
                                return false;
                            }
                        });
                        return result;
                    };

                    if (endSelected && endSelected.isAfter(inquiryCalentimEnd.config.maxDate, 'day')) {
                        min = startSelected.clone().add(minNights, 'days');
                        if (!filterDisabled(min)) {
                            endSelected = min;
                            inquiryCalentimEnd.config.endDate = min;
                            inquiryCalentimEnd.$elem.val(min.format(inquiryCalentimEnd.config.format));
                            instance.config.endDate = min;
                        } else {
                            endSelected = null;
                            inquiryCalentimEnd.config.endDate = null;
                            inquiryCalentimEnd.$elem.val("");
                            // window.history.replaceState('', '', location.pathname);
                            // $formListingPrice.find('.prices').html('');
                        }
                    }

                    min = startSelected.clone().add(minNights, 'days');
                    if (endSelected && endSelected.isSameOrBefore(min, 'days')) {
                        endSelected = null;
                        inquiryCalentimEnd.config.endDate = null;
                        inquiryCalentimEnd.$elem.val("");
                        // window.history.replaceState('', '', location.pathname);
                        // $formListingPrice.find('.prices').html('');
                    }

                    if (!endSelected) {
                        min = startSelected.clone().add(minNights, 'days');
                        if (!filterDisabled(min)) {
                            endSelected = min;
                            inquiryCalentimEnd.config.endDate = min;
                            inquiryCalentimEnd.$elem.val(min.format(inquiryCalentimEnd.config.format));
                            instance.config.endDate = min;
                        } else {
                            instance.config.endDate = startSelected;
                        }
                    }

                    // if (!isMob && startSelected && endSelected) {
                    //     submitForm();
                    // }

                    instance.input.find(".calentim-apply").attr("disabled",true);
                    instance.hideDropdown(instance);
                    inquiryCalentimEnd.showDropdown(inquiryCalentimEnd);
                },
                onbeforeselect: function(instance, start, end) {
                    startSelected = start.clone();
                    endSelected = end.clone();
                    inquiryUpdateInputs();
                },
                onbeforeshow: function(instance) {
                    if (startSelected) {
                        instance.config.startEmpty = false;
                        instance.setStart(startSelected);
                        instance.config.startDate = startSelected;
                        instance.$elem.val(startSelected.format(instance.config.format));
                        instance.setDisplayDate(startSelected);
                        if (!endSelected) {
                            instance.setEnd(startSelected)
                            instance.config.endDate = startSelected;
                        }
                    }
                }
            });
        }
    }

    function handleInquiryEnd()
    {
        let inq2 = jQuery(".direct-inquiry-modal:last #inquiry_checkout");
        if (inq2.length) {
            let inst2 = inq2.data("calentim");

            if (typeof inst2 !== 'undefined') {
                if (endSelected) {
                    inquiryCalentimEnd.config.endDate = endSelected;
                    inquiryCalentimEnd.$elem.val(endSelected.format(inquiryCalentimEnd.config.format));
                }
                return;
            }

            inq2.calentim({
                startOnMonday: hfyStartOnMonday,
                locale: hfyCurrentLang,
                enableMonthSwitcher: false,
                enableYearSwitcher: false,
                showHeader: false, // isMob,
                showFooter: false,
                showTimePickers: false,
                calendarCount: isMob ? 24 : 2,
                isMobile: isMob,
                isIOS: isIOS,
                format: hfyDFopt,
                showOn: "bottom",
                arrowOn: 'right',
                autoAlign: true,
                oneCalendarWidth: 280,
                continuous: true,
                autoCloseOnSelect: !isMob,
                onCustomStyles: function(cell, cellMoment, cellDateUnix, cellStyle, minDateUnix, maxDateUnix, currentMonth) {
                    if (isDayDisabledForCheckOut(cellMoment)) {
                        cellStyle += " calentim-disabled-day";
                    }
                    return cellStyle;
                },
                disabledRanges: calDisDates,
                isHotelBooking: true,
                onaftershow: updatePopupTipsOut,
                onaftermonthchange: updatePopupTipsOut,
                onafteryearchange: updatePopupTipsOut,
                oninit: function(instance) {
                    moment.locale(hfyCurrentLang);
                    let filterInitDisabled = function(dateStart, dateEnd) {
                        $.each(instance.config.disabledRanges, function(key, value) {
                            if (dateStart.isBetween(value.start, value.end, "day", "[]") ||
                                dateEnd.isBetween(value.start, value.end, "day", "(]") ||
                                dateStart.isSameOrBefore(value.start, "days") && dateEnd.isSameOrAfter(value.end, "days")) {
                                return true;
                            }
                        });
                        return false;
                    };
                    instance.globals.delayInputUpdate = true;
                    inquiryCalentimEnd = instance;

                    if (startSelected) {
                        setMinNights(startSelected);
                    }

                    if (moment(startSelected).isSameOrAfter(inquiryCalentimStart.config.minDate, 'day') &&
                        moment(startSelected.clone().add(minNights, 'days')).isSameOrBefore(moment(instance.$elem.attr('value'), hfyDFopt), 'days') &&
                        !filterInitDisabled(inquiryCalentimStart.config.startDate, moment(instance.$elem.attr('value'), hfyDFopt))) {
                        endSelected = moment(instance.$elem.attr('value'), hfyDFopt);
                        inquiryCalentimEnd.config.startDate = startSelected;
                        inquiryCalentimEnd.config.endDate = endSelected;
                        inquiryCalentimEnd.$elem.val(endSelected.format(inquiryCalentimEnd.config.format));
                        inquiryCalentimStart.config.endDate = endSelected;

                        let filterDays = function(date, maxNights) {
                            let result = false;
                            let dateMax = false;

                            dateMin = date.clone().add(minNights, 'days');
                            if (maxNights)
                                dateMax = moment(date.clone().add(maxNights, 'days'));

                            $.each(instance.config.disabledRanges, function(key, value) {
                                if (dateMin.isBefore(value.start, 'day') || dateMin.isSame(value.start, 'day')) {
                                    result = value.start;
                                    return false;
                                } else if (dateMin.isBetween(value.start, value.end, "day", "[]")) {
                                    result = dateMin;
                                    return false;
                                }
                            });

                            if ((!result && dateMax) || (result && dateMax && dateMax.isBefore(result, 'day'))) {
                                result = dateMax;
                            }

                            return result;
                        };

                        filterDays(startSelected, typeof maxNights !== 'undefined' ? maxNights : null);
                        if (maxDate)
                            inquiryCalentimEnd.config.maxDate = moment(maxDate);
                    } else {
                        // if (instance.$elem.attr('value').length > 0 && inquiryCalentimStart.$elem.attr('value').length > 0) {
                        //     $formListingPrice.find('.prices').html('<div class="calendar-error">The listing is not available for your selected dates.</div>');
                        // }
                        inquiryCalentimEnd.$elem.val("");
                        inquiryCalentimEnd.config.startDate = null;
                        inquiryCalentimEnd.config.endDate = null;
                        inquiryCalentimEnd.config.startEmpty = true;
                        // endSelected = '';
                        // startSelected = '';
                        inquiryCalentimStart.config.startEmpty = true;
                        inquiryCalentimStart.config.startDate = null;
                        inquiryCalentimStart.config.endDate = null;
                        inquiryCalentimStart.$elem.val("");
                    }
                    inquiryCalentimEnd.config.minDate = inquiryCalentimStart.config.minDate.clone().add(minNights, 'days');
                    inquiryUpdateInputs();
                },
                // ondraw: function (instance) {
                //     if (instance.globals.initComplete) {
                //         updateInputs();
                //         // instance.updateHeader();
                //     }
                // },
                onfirstselect: function(instance, start) {
                    if (inquiryCalentimStart.config.endDate)
                        endSelected = start.clone().set({
                            hours: inquiryCalentimStart.config.endDate.hours(),
                            minutes: inquiryCalentimStart.config.endDate.minutes(),
                            seconds: inquiryCalentimStart.config.endDate.seconds()
                        });
                    else
                        endSelected = start.clone();
                    instance.globals.startSelected = false;
                    inquiryUpdateInputs();
                    instance.hideDropdown(null);
                    // if (!isMob) submitForm();
                    instance.input.find(".calentim-apply").attr("disabled",false);
                    if (isMob) inquiryCalentimStart.showDropdown(inquiryCalentimStart);
                },
                onbeforeselect: function(instance, start, end) {
                    startSelected = start.clone();
                    endSelected = end.clone();
                    inquiryUpdateInputs();
                },
                onbeforeshow: function(instance) {
                    if (startSelected && endSelected) {
                        instance.config.startEmpty = false;
                        instance.setStart(startSelected);
                        instance.config.startDate = startSelected;
                    }
                    if (startSelected && !endSelected) {
                        instance.setDisplayDate(startSelected);
                    } else {
                        instance.setDisplayDate(endSelected);
                    }
                }, onafterselect: function(instance, start, end){
                    // instance.config.maxDate = null;
                }
            });
        }
    }

    initInquiry();

});

function getMinstay(ds)
{
    let ltm = typeof longTermMode !== 'undefined'
        ? longTermMode == 1
        : (typeof hfyltm !== 'undefined' ? hfyltm == 1 : false);
    // if (jQuery('.hfy-page-listing').length <= 0) {
    //     // search
    //     return ltm ? 31 : 1;
    // } else {
        // listing
        if (!ds) ds = new Date().toISOString().slice(0, 10);
        if (typeof ds !== 'string') ds = ds.format('YYYY-MM-DD');

        let minstay = (typeof calendarCustomMinStay !== 'undefined' && typeof calendarCustomMinStay[ds] !== 'undefined')
            ? calendarCustomMinStay[ds] : (typeof hfyminstay !== 'undefined' ? hfyminstay : 1);
        if (typeof calendarOverMinStay !== 'undefined' && typeof calendarOverMinStay[ds] !== 'undefined') {
            minstay = calendarCustomMinStay[ds];
        }

        if (typeof calendarCustomStay !== 'undefined') {
            let dsm = moment(ds);
            jQuery.each(calendarCustomStay, function(key, value) {
                if (dsm.isBetween(value.date_start, value.date_end, "day", "[]")) {
                    minstay = value.min_stay;
                }
            });
        }

        // if (isPropertyPage)
        minstay = (ltm && minstay < 31) ? 31 : minstay;
        return minstay;
    // }
}
