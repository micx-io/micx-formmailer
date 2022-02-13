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

    this.invalidForms = [];

    this.attrs = {
      "service_id": "%%SERVICE_ID%%",
      "endpoint_url": "%%ENDPOINT_URL%%",
      "ok_elem_selector": "*[role='sent']",
      "debug": false
    }
  }

  static get observedAttributes() {
      return ["service_id", "endpoint_url", "debug", "ok_elem_selector"];
  }

  _log() {
    if (this.attrs.debug !== false)
      console.log.apply(this, arguments);
  }

  attributeChangedCallback(attrName, oldVal, newVal) {
    if (typeof this.attrs[attrName] === "undefined")
      throw [`Invalid attribute '${attrName}' on MicxFormmailer node:`, this];
    this.attrs[attrName] = newVal;
  }

  _getFormData() {
    this.invalidForms = [];
    let formdata = {};
    for (let el of this.formEl.querySelectorAll("input,select,textarea")) {

      if (el.validity.valid === false)
        this.invalidForms.push(el);

      if (el.name === "" && el.id === "") {
        this._log("[Warm] Skipping Form-Element without id or name attribute", el);
        continue;
      }
      let name = el.name;
      if (name === "")
          name = el.id;

      if (el.type === "checkbox" && el.checked === false)
        continue;
      formdata[name] = el.value;
    }
    return formdata;
  }

  connectedCallback() {
    let fe = this.formEl = this.parentElement;
    this._log("Micx formmailer ", this, "initializing on form ", fe);
    if (fe.tagName !== "FORM")
      throw ["Invalid parent node tagName (!= 'form') on MicxFormmailer node: ", this, "Parent is", fe];

    fe.addEventListener("submit", (e) => {
      e.stopPropagation();
      e.preventDefault();
    });

    for (let sbe of fe.querySelectorAll("input[type='submit'], button[type='submit']")) {
      this._log("Micx formmailer ", this, "attached to button", sbe);
      sbe.addEventListener("click", (e) => {

        let formData = this._getFormData();
        this._log("Micx formmailer ", this, "onclick event:", e, "formdata:" formData);

        if (this.invalidForms.length > 0) {
          console.warn("Form data is invalid", this.invalidForms);
          this.dispatchEvent(new Event("invalid", {invalid_forms: this.invalidForms}));
          return;
        }

        fetch(this.attrs.endpoint_url, {
          method: "POST",
          headers: {"content-type": "application/json"},
          body: JSON.stringify(formData),
          cache: "no-cache"
        }).then((ret) => {
          if (ret.status !== 200) {
            this.dispatchEvent(new Event("error", {server_response: ret}));
            console.log("Unable to send mail: Response", ret);
            return;
          }
          this._log("Micx formmailer ", this, "received ok from server", ret, "fireing success event");
          this.dispatchEvent(new Event("success", {server_response: ret}));
          fe.querySelector(this.attrs.ok_elem_selector)?.removeAttribute("hidden");
        }).catch((e) => {
          this.dispatchEvent(new Event("error", {exception: e}));
          console.error("Micx Formmailer: request error:", e, "on element", fe);
          alert("Unable to send mail: Server Error.");
        })
      })
    }

    // Hide everything inside
    for (let sube of this.querySelectorAll("*")) {
      sube.setAttribute("hidden", "hidden");
    }
  }




}


customElements.define("micx-formmail", MicxFormmail);
