class Mondiad {
    static LOGIN = 'mondiad_login';
    static LOGIN_CLIENT_ID = 'mondiad_login_username';
    static LOGIN_SECRET = 'mondiad_login_password';
    static LOGOUT = 'mondiad_logout';
    static LOGOUT_CLEAN = "mondiad_clean_data";

    static SITE_CREATE_CAT = "mondiad_create_site_category";

    static AD_CHANGE_ACTIVITY_IN = 'mondiad_change_activity_inpage';
    static AD_CHANGE_ACTIVITY_CLASSIC = 'mondiad_change_activity_classic';
    static AD_CHANGE_ACTIVITY_NATIVE = 'mondiad_change_activity_native';
    static AD_CHANGE_ACTIVITY_BANNER = 'mondiad_change_activity_banner';

    static SITE_SEARCH = 'mondiad_search_site';
    static SITE_SEARCH_NAME = 'mondiad_search_site_name';

    static SITE_SELECT = 'mondiad_select_site';
    static SITE_SELECT_ID = 'mondiad_select_site_id';
    static SITE_CHANGE = 'mondiad_change_site';

    static AD_SELECT_CLASSIC = 'mondiad_select_ad_classic';
    static AD_SELECT_IN = 'mondiad_select_ad_inpage';
    static AD_SELECT_ID = 'mondiad_select_ad_id';
    static AD_SELECT_UUID = 'mondiad_select_ad_uuid';

    static STATUS_PENDING = 'PENDING';
    static STATUS_ACCEPTED = 'ACCEPTED';
    static STATUS_REJECTED = 'REJECTED';
    static STATUS_DELETED = 'DELETED';
    static STATUS_HELD = 'HELD';

    static AD_STATUS_ACTIVE = 'ACTIVE';

    static METABOX_INPAGE_ID = 'mondiad-metabox-inpage-select';
    static METABOX_CLASSIC_ID = 'mondiad-metabox-classic-select';
    static METABOX_AD_DISABLED = 'disabled';

    static copy(target) {
        const text = target.innerText;

        let textArea = document.createElement("textarea");
        textArea.value = text;

        // Avoid scrolling to bottom
        textArea.style.top = "0";
        textArea.style.left = "0";
        textArea.style.position = "fixed";

        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();

        try {
            document.execCommand('copy');
            Mondiad.successAlert('Copied!')
        } catch (err) {
            console.error('Copy shortcode: Oops, unable to copy', err);
        }

        document.body.removeChild(textArea);
    }

    static makeAjaxRequest(form_data, successCallback, errorCallback) {
        jQuery.ajax({
            url: AJAX_URL,
            type: 'post',
            data: form_data
        }).done( function( response ) {
            const response_object = JSON.parse(response);
            if (response_object.code === 200) {
                successCallback(response_object.data);
            } else {
                Mondiad.errorAlert(response_object.status);
                if (typeof errorCallback === "function") {
                    errorCallback()
                }
            }
        }).fail( function(message) {
            Mondiad.errorAlert(message.statusText ?? message);
            if (typeof errorCallback === "function") {
                errorCallback()
            }
        })
    }

    static watchMessages() {
        const error_messages = MONDIAD_PHP_VAR.messages;
        if (error_messages) {
            this.errorAlert(error_messages);
        }
    }

    static successAlert(message) {
        Swal.fire({
            position: 'top-end',
            icon: 'success',
            title: 'Success',
            text: message,
            showConfirmButton: false,
            timer: 4000,
            toast: true,
            padding: '30px',
        })
    }

    static errorAlert(message) {
        Swal.fire({
            position: 'top-end',
            icon: 'error',
            title: 'Error',
            text: message,
            showConfirmButton: true,
            toast: true,
            padding: '30px',
        })
    }

    static searchSite(name, updateList, errorCallback) {
        const data = {'action': Mondiad.SITE_SEARCH, [Mondiad.SITE_SEARCH_NAME]: name };
        Mondiad.makeAjaxRequest(data, (response) => {
            updateList(response.value);
        }, errorCallback);
    }

    static selectSite(siteId) {
        const data = {'action': Mondiad.SITE_SELECT, [Mondiad.SITE_SELECT_ID]: siteId };
        Mondiad.makeAjaxRequest(data, () => {
            location.reload();
        });
    }

    static selectDefaultInpage(id, uuid, successCallback) {
        const data = {'action': Mondiad.AD_SELECT_IN, [Mondiad.AD_SELECT_ID]: id, [Mondiad.AD_SELECT_UUID]: uuid};
        Mondiad.makeAjaxRequest(data, successCallback);
    }

    static selectDefaultClassic(id, uuid, successCallback) {
        const data = {'action': Mondiad.AD_SELECT_CLASSIC, [Mondiad.AD_SELECT_ID]: id, [Mondiad.AD_SELECT_UUID]: uuid};
        Mondiad.makeAjaxRequest(data, successCallback);
    }

    static toggleInpageEnabled(setStateFn) {
        const data = {'action': Mondiad.AD_CHANGE_ACTIVITY_IN};
        Mondiad.makeAjaxRequest(data, (response) => {
            setStateFn(response.value);
        });
    }

    static toggleClassicEnabled(setStateFn) {
        const data = {'action': Mondiad.AD_CHANGE_ACTIVITY_CLASSIC};
        Mondiad.makeAjaxRequest(data, (response) => {
            setStateFn(response.value);
        });
    }

    static toggleNativeEnabled(setStateFn) {
        const data = {'action': Mondiad.AD_CHANGE_ACTIVITY_NATIVE};
        Mondiad.makeAjaxRequest(data, (response) => {
            setStateFn(response.value);
        });
    }

    static toggleBannerEnabled(setStateFn) {
        const data = {'action': Mondiad.AD_CHANGE_ACTIVITY_BANNER};
        Mondiad.makeAjaxRequest(data, (response) => {
            setStateFn(response.value);
        });
    }

    static login(login, pass, errorCallback) {
        const data = {'action': Mondiad.LOGIN, [Mondiad.LOGIN_CLIENT_ID]: login, [Mondiad.LOGIN_SECRET]: pass};
        Mondiad.makeAjaxRequest(data, () => {
            location.reload();
        }, errorCallback);
    }

    static changeWebsite(successCallback, errorCallback) {
        const data = {'action': Mondiad.SITE_CHANGE};
        Mondiad.makeAjaxRequest(data, successCallback, errorCallback);
    }

    static logout(cleanData, successCallback, errorCallback) {
        const data = {'action': Mondiad.LOGOUT, [Mondiad.LOGOUT_CLEAN]: cleanData};
        Mondiad.makeAjaxRequest(data, successCallback, errorCallback);
    }

    static init() {
        Mondiad.watchMessages();
    }
}

jQuery(document).ready(function($) {
    Mondiad.init()
})
