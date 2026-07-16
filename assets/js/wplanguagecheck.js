(function(){
    function debugLog(message, data) {
        if (typeof console === 'undefined' || typeof console.log !== 'function') return;
        if (typeof data !== 'undefined') {
            console.log('[WPLanguageCheck] ' + message, data);
            return;
        }
        console.log('[WPLanguageCheck] ' + message);
    }

    // Utilidades de cookies
    function setCookie(name, value, days) {
        let expires = "";
        if (days) {
            const date = new Date();
            date.setTime(date.getTime() + (days*24*60*60*1000));
            expires = "; expires=" + date.toUTCString();
        }
        document.cookie = name + "=" + (value || "") + expires + "; path=/";
    }

    function getCookie(name) {
        const nameEQ = name + "=";
        const ca = document.cookie.split(';');
        for(let i=0;i < ca.length;i++) {
            let c = ca[i];
            while (c.charAt(0)==' ') c = c.substring(1,c.length);
            if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
        }
        return null;
    }

    function hasValidCookie() {
        const cname = (window.WPLanguageCheckData && WPLanguageCheckData.cookie_name) || 'wp_language_check';
        return getCookie(cname) === '1';
    }

    function shouldShowModal() {
        if (!window.WPLanguageCheckData) return false;
        return !!WPLanguageCheckData.should_show;
    }

    function fetchModalContext() {
        var data = window.WPLanguageCheckData || {};
        if (!data.ajax_url || !data.ajax_nonce) {
            debugLog('No hay datos AJAX configurados');
            return Promise.resolve(null);
        }

        var body = new URLSearchParams({
            action: 'wplanguagecheck_get_modal_context',
            nonce: data.ajax_nonce
        });

        return fetch(data.ajax_url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
            },
            body: body.toString()
        })
        .then(function(response){ return response.json(); })
        .then(function(payload){
            if (!payload || !payload.success || !payload.data) {
                debugLog('Respuesta AJAX inválida', payload || null);
                return null;
            }

            return payload.data;
        })
        .catch(function(error){
            debugLog('Error en AJAX de contexto modal', error);
            return null;
        });
    }

    function setAcceptedCookie() {
        const cname = (window.WPLanguageCheckData && WPLanguageCheckData.cookie_name) || 'wp_language_check';
        setCookie(cname, '1', 365); // 1 año
    }

    function preventEscClose() {
        document.addEventListener('keydown', function(e){
            const isOpen = document.body.classList.contains('micromodal-open');
            if (isOpen && (e.key === 'Escape' || e.key === 'Esc' || e.keyCode === 27)) {
                e.preventDefault();
                e.stopPropagation();
                return false;
            }
        }, true);
    }

    function initModalFlow(contextData) {
        debugLog('DOMContentLoaded');

        // Inicializar MicroModal
        if (typeof MicroModal !== 'undefined') {
            debugLog('MicroModal disponible: init');
            MicroModal.init({
                awaitOpenAnimation: true,
                awaitCloseAnimation: true,
                disableScroll: false,
                disableFocus: true,
                disableClickOutside: true
            });
        } else {
            debugLog('MicroModal no disponible: se usará fallback de display');
        }

        debugLog('WPLanguageCheckData recibido', window.WPLanguageCheckData || null);

        var hasCookie = hasValidCookie();
        var shouldShow = !!(contextData && contextData.should_show);

        debugLog('Estado inicial', {
            has_cookie: hasCookie,
            should_show: shouldShow
        });

        // Si ya hay cookie válida o no toca mostrar por país/ruta, no mostramos el modal
        if (hasCookie) {
            debugLog('No se muestra modal: cookie válida detectada');
            return;
        }

        if (!shouldShow) {
            debugLog('No se muestra modal: should_show=false (país/ruta no aplican)');
            return;
        }

        // Evitar cierre por ESC
        preventEscClose();

        // Mostrar modal bloqueante
        if (typeof MicroModal !== 'undefined') {
            debugLog('Mostrando modal con MicroModal.show');
            MicroModal.show('modal-wplanguagecheck');
        } else {
            // Fallback: si no carga MicroModal, mostrar contenedor
            var modal = document.getElementById('modal-wplanguagecheck');
            if (modal) modal.style.display = 'block';
            debugLog('Mostrando modal con fallback manual');
        }

        var message = document.getElementById('wplanguagecheck-message');
        var btnAccept = document.getElementById('wplanguagecheck-accept');
        var btnContinue = document.getElementById('wplanguagecheck-continue');
        var btnClose = document.getElementById('wplanguagecheck-close');
        var title = document.getElementById('wplanguagecheck-title');
        var country = (contextData && contextData.country) || 'your country';
        var titleText = (contextData && contextData.title_text) || 'Confirm your location';
        var descriptionText = (contextData && contextData.description_text) || 'Select a region to see content specific to your location';
        var currentCountryLabel = (contextData && contextData.current_country_label) || (btnContinue && btnContinue.textContent) || 'Current country';
        var preferredCountryLabel = (contextData && contextData.preferred_country_label) || (btnAccept && btnAccept.textContent) || 'Suggested country';
        var languageDetected = (contextData && contextData.language_detected) || 'unknown';
        var visitorIp = (contextData && contextData.visitor_ip) || 'unknown';
        var wpmlCurrentLanguageCookie = (contextData && contextData.wpml_current_language_cookie) || 'empty';

        if (title) {
            title.textContent = titleText;
        }

        if (message) {
            message.textContent = descriptionText;
            debugLog('Mensaje del modal renderizado', {
                visitor_ip: visitorIp,
                wpml_current_language_cookie: wpmlCurrentLanguageCookie,
                country: country,
                title_text: titleText,
                description_text: descriptionText,
                current_country_label: currentCountryLabel,
                preferred_country_label: preferredCountryLabel,
                rendered_message: message.textContent,
                language_detected: languageDetected
            });
        } else {
            debugLog('No se encontró #wplanguagecheck-message');
        }

        if (btnContinue) {
            btnContinue.textContent = currentCountryLabel;
        }

        if (btnAccept) {
            btnAccept.textContent = preferredCountryLabel;
        }

        function closeModalAndRemember() {
            setAcceptedCookie();
            debugLog('Cookie aceptada guardada al cerrar modal');

            if (typeof MicroModal !== 'undefined') {
                debugLog('Cerrando modal con MicroModal.close');
                MicroModal.close('modal-wplanguagecheck');
            } else {
                var modal = document.getElementById('modal-wplanguagecheck');
                if (modal) modal.style.display = 'none';
                debugLog('Cerrando modal con fallback manual');
            }
        }

        if (btnContinue) {
            btnContinue.addEventListener('click', function(){
                debugLog('Click en Continuar');
                closeModalAndRemember();
            });
        } else {
            debugLog('No se encontró botón Continuar (#wplanguagecheck-continue)');
        }

        if (btnClose) {
            btnClose.addEventListener('click', function(event){
                debugLog('Click en Cerrar');
                if (event) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                closeModalAndRemember();
            });
        } else {
            debugLog('No se encontró botón Cerrar (#wplanguagecheck-close)');
        }

        if (btnAccept) {
            btnAccept.addEventListener('click', function(){
                debugLog('Click en Aceptar');
                setAcceptedCookie();
                debugLog('Cookie aceptada guardada en Aceptar');

                var preferredUrl = (contextData && contextData.preferred_url) || '';
                if (preferredUrl) {
                    debugLog('Redirigiendo a preferred_url', preferredUrl);
                    window.location.href = preferredUrl;
                    return;
                }

                debugLog('No hay preferred_url, cerrando modal');
                closeModalAndRemember();
            });
        } else {
            debugLog('No se encontró botón Aceptar (#wplanguagecheck-accept)');
        }
    }

    window.addEventListener('load', function(){
        var hasCookie = hasValidCookie();

        if (hasCookie) {
            debugLog('No se solicita contexto: cookie válida detectada');
            return;
        }

        debugLog('Web cargada: se solicitará contexto AJAX en 5 segundos');

        setTimeout(function(){
            if (hasValidCookie()) {
                debugLog('No se solicita contexto tras espera: cookie válida detectada');
                return;
            }

            fetchModalContext().then(function(contextData){
                if (!contextData) {
                    debugLog('Sin contexto modal por AJAX');
                    return;
                }

                initModalFlow(contextData);
            });
        }, 5000);
    });
})();