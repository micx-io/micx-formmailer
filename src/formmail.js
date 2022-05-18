/**
 * Micx.io Formmailer
 *
 * Usage: See https://github.com/micx-io/micx-formmailer
 *
 * @licence MIT
 * @author Matthias Leuffen <m@tth.es>
 */


class MicxFormmail extends HTMLElement {

    constructor() {
        super();

        /**
         * The observed Form Element
         *
         * @type {HTMLFormElement}
         */
        this.formEl = null;
    }

    show(selector) {
        MicxFormmail.log("show(", selector, ")");
        for (let sube of this.parentElement.querySelectorAll(selector)) {
            sube.removeAttribute("hidden");
        }
    }

    hide(selector) {
        MicxFormmail.log("hide(", selector, ")");
        for (let sube of this.parentElement.querySelectorAll(selector)) {
            sube.setAttribute("hidden", "hidden");
        }
    }

    async connectedCallback() {
        let log = MicxFormmail.log;
        let fe = this.formEl = this.parentElement;



        log("Micx formmailer ", this, "initializing on form ", fe);
        if (fe.tagName !== "FORM")
            throw ["Invalid parent node tagName (!= 'form') on MicxFormmailer node: ", this, "Parent is", fe];

        // Prevent submit on enter
        fe.addEventListener("submit", (e) => {
            e.preventDefault();
        });

        this.innerHTML = "";

        fe.addEventListener("click", async (e) => {
            log("click", e);
            if (typeof e.explicitOriginalTarget !== "undefined") {
                if (e.explicitOriginalTarget.getAttribute("type") !== "submit") {
                    return false;
                }
            }
            e.preventDefault();
            e.target.setAttribute("disabled", "disabled");

            let formdata, invalid;
            [formdata, invalid] = MicxFormmail.collectFormData(fe);
            if (invalid.length > 0) {
                console.warn("Form data is invalid", invalid);
                this.dispatchEvent(new Event("invalid", {invalid_forms: invalid}));
                e.target.removeAttribute("disabled");
                return;
            }
            this.dispatchEvent(new Event("waiting", {invalid_forms: invalid}));
            let preset = "default";
            if (this.hasAttribute("preset"))
                preset = this.getAttribute("preset");


            try {
                let result = await MicxFormmail.sendMail(formdata, preset);
                this.dispatchEvent(new Event("submit", {ok: result}));
            } catch (e) {
                this.dispatchEvent(new Event("error", {error: e}));
            }
            e.target.removeAttribute("disabled");

        });

        this.dispatchEvent(new Event("load", {"ref": this}));
    }


}

MicxFormmail.config = {
        "service_id": "%%SERVICE_ID%%",
        "error": "%%ERROR%%",
        "endpoint_url": "%%ENDPOINT_URL%%",
        "debug": true
}

MicxFormmail.log = function () {
    if (MicxFormmail.config.debug !== false)
        console.log.apply(this, arguments);
}


MicxFormmail.collectFormData = function (formElem) {
    let invalidForms = [];
    let formdata = {};
    for (let el of formElem.querySelectorAll("input,select,textarea")) {

        if (el.validity.valid === false)
            invalidForms.push(el);

        if (el.name === "" && el.id === "") {
            if (el.type !== "submit")
                MicxFormmail.log("[Warning] Skipping Form-Element without id or name attribute", el);
            continue;
        }
        let name = el.name;
        if (name === "")
            name = el.id;

        name = name.trim();

        if (el.type === "checkbox" && el.checked === false)
            continue;
        if (name.endsWith("[]")) {
            name = name.slice(0, -2);
            if (!Array.isArray(formdata[name]))
                formdata[name] = [];
            formdata[name].push(el.value);
        } else {
            formdata[name] = el.value;
        }
    }
    return [formdata, invalidForms];
}


MicxFormmail.sendMail = function (data, preset="default") {
    let log = MicxFormmail.log;
    return new Promise(async (resolve, reject) => {
        log(`sending to preset ${preset}`, data)
        let result = await fetch(MicxFormmail.config.endpoint_url + `&preset=${preset}`, {
            method: "POST",
            headers: {"content-type": "application/json"},
            body: JSON.stringify(data),
            cache: "no-cache"
        });
        if ( ! result.ok) {
            let errorMsg = await result.text();
            log(`Server Error`, errorMsg);
            return reject("Cannot send mail: " + errorMsg);
        }
        let successMsg = await result.json();
        log(`message sent`, successMsg);
        return resolve(successMsg);
    });
}

customElements.define("micx-formmail", MicxFormmail);
