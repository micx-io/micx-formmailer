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
      "error": "%%ERROR%%",
      "endpoint_url": "%%ENDPOINT_URL%%",
      "debug": false
    }
  }

  static get observedAttributes() {
      return ["service_id", "endpoint_url", "debug"];
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

  show(selector) {
    this._log("show(", selector, ")");
    for (let sube of this.parentElement.querySelectorAll(selector)) {
      sube.removeAttribute("hidden");
    }
  }

  hide(selector) {
    this._log("hide(", selector, ")");
    for (let sube of this.parentElement.querySelectorAll(selector)) {
        sube.setAttribute("hidden", "hidden");
    }
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
    if (this.attrs.error !== "") {
      console.error("Error loading Micx Formmailer: " + this.attrs.error);
      return false;
    }

    document.addEventListener("DOMContentLoaded", () => {
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
        sbe.addEventListener("submit", (e) => { e.preventDefault(); e.stopPropagation() });
        sbe.addEventListener("click", (e) => {
          this._log("Micx formmailer ", this, "onclick event:", e);

          // Prevent ENTER submit
          if (e.explicitOriginalTarget !== sbe) {
            return false;
          }
          let formData = this._getFormData();

          if (this.invalidForms.length > 0) {
            console.warn("Form data is invalid", this.invalidForms);
            this.dispatchEvent(new Event("invalid", {invalid_forms: this.invalidForms}));
            return;
          }


          this.dispatchEvent(new Event("waiting", {invalid_forms: this.invalidForms}));

          sbe.setAttribute("disabled", "disabled");

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
            sbe.removeAttribute("disabled");
            this._log("Micx formmailer ", this, "received ok from server", ret, "fireing success event");
            this.dispatchEvent(new Event("submit", {server_response: ret}));
          }).catch((e) => {
            sbe.removeAttribute("disabled");
            this.dispatchEvent(new Event("error", {exception: e}));
            console.error("Micx Formmailer: request error:", e, "on element", fe);
            alert("Unable to send mail: Server Error.");
          })
          return false;
        })

      }

      // Hide everything inside
      for (let sube of this.querySelectorAll("*")) {
        sube.setAttribute("hidden", "hidden");
      }

      this.dispatchEvent(new Event("load", {"ref": this}));
    })
  }




}


customElements.define("micx-formmail", MicxFormmail);
