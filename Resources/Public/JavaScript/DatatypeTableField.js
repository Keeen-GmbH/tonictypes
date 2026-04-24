import $ from 'jquery';
import DocumentService from "@typo3/core/document-service.js";
import Modal from "@typo3/backend/modal.js";
import Severity from "@typo3/backend/severity.js";

class DatatypeTableField {
    constructor() {
        this.tableStatusFieldId = 'div#tonictypes_table_status',
        this.loaderId = 'div#tonictypes_table_status_loader',
        this.refreshButtonId = 'button#tonictypes_table_status_refresh',
        this.deleteButtonId = 'button#tonictypes_table_delete',
        this.migrateButtonId = 'button#tonictypes_table_status_migrate',
        this.generateTcaButtonId = 'button#tonictypes_table_generate_tca',
        this.messages = {
            error: null,
            really: null,
            yes: null,
            no: null
        }
        ;

        DocumentService.ready().then((() => {

            let dataset = $(this.tableStatusFieldId).data();

            this.changeAllowed = (dataset.changeAllowed == 'true')? true : false;
            this.fieldName = dataset.fieldName;
            this.fieldId = dataset.fieldId;
            this.datatypeId = dataset.datatypeId;
            this.datatypeNameFieldId = '[data-formengine-input-name="'+dataset.datatypeNameFieldId+'"]';
            this.tablenameFieldId = dataset.tablenameFieldId;

            if (!this.fieldId) {
                // In TYPO3 v13 the element id is not always provided/known.
                // We can still resolve the input via its formengine input name.
                if (window.TYPO3?.debug) {
                    // eslint-disable-next-line no-console
                    console.debug('Tonictypes: Missing fieldId for DatatypeTableField, falling back to fieldName', dataset);
                }
            }

            this.messages.error = dataset.messagesError;
            this.messages.really = dataset.messagesReally
            this.messages.yes = dataset.messagesYes
            this.messages.no = dataset.messagesNo

            // Assign refresh button
            $(this.refreshButtonId).click(() => {
                 this.refresh();
            });

            // Initialize table status
            this.refresh();

            // Assign keyup to datatype name field
            this.assignDatatypeNameListener();
        }));
    }

    getFieldElement() {
        if (this.fieldId) {
            const byId = document.getElementById(this.fieldId);
            if (byId) {
                return byId;
            }
        }

        const inputName = this.tablenameFieldId || this.fieldName;
        if (inputName) {
            const escaped = (window.CSS && typeof window.CSS.escape === 'function') ? window.CSS.escape(inputName) : inputName;
            return document.querySelector(`[data-formengine-input-name="${escaped}"]`)
                || document.querySelector(`[name="${escaped}"]`);
        }

        return null;
    }

    getTableNameValue() {
        const el = this.getFieldElement();
        return el ? el.value : '';
    }

    refresh() {
        this.request('tonictypes_table_status');
    }

    migrate() {
        this.request('tonictypes_table_migrate');
    }

    generateTca() {
        this.request('tonictypes_table_generate_tca');
    }

    del() {
      const message = this.messages.really;
      Modal.confirm(this.getTableNameValue(), message, Severity.error, [
          {
            text: this.messages.yes,
            active: true,
            btnClass: "btn-danger",
            trigger: () => {
                this.request('tonictypes_table_delete');
                Modal.dismiss();
            }
          }, {
            text: this.messages.no+'!',
            trigger: function() {
              Modal.dismiss();
            }
          }
      ]);

    }
    assignDatatypeNameListener() {
        $(this.datatypeNameFieldId).on("keyup", (e) => {

            const fieldEl = this.getFieldElement();
            if (!fieldEl) {
                return;
            }

            if (fieldEl.value === '') {
                this.changeAllowed = true;
            }

            if (this.changeAllowed == true) {
                let val = 'tx_tonictypes_domain_model_record_' + this.slug(e.target.value);
                fieldEl.value = val;
                // Let FormEngine react to changed values (without calling FormEngineValidation directly,
                // since it requires initialization with a formEngineInstance in TYPO3 v13)
                fieldEl.dispatchEvent(new Event('change', { bubbles: true }));
                this.refresh();
            }
        });
    }

    showError(error) {
        var buttonHtml = '<button type="button" class="btn btn-default" id="tonictypes_table_delete">' + this.messages.delete_table + '</button>';
        $(this.tableStatusFieldId).html('<div class=\"callout callout-danger\">' + error + '<br /><br />' + buttonHtml + '</div>');
        $(this.deleteButtonId).click(function () {
            this.del();
        });
    }

    request(type) {
        $(this.loaderId).show();

        $(this.tableStatusFieldId).html('');
        $(this.tableStatusFieldId).show();

        const tableName = this.getTableNameValue();
        if (!tableName) {
            $(this.loaderId).hide();
            this.showError('<strong>Error:</strong> ' + this.messages.error);
            return;
        }

        $.ajax({
            context: this,
            url: TYPO3.settings.ajaxUrls[type],
            data: {
                tableName: tableName,
                datatypeId: this.datatypeId
            },
            type: 'post',
            dataType: 'json',
            cache: false
        }).done(function (msg) {
            $(this.loaderId).hide();
            if (msg.success && msg.success == true) {
                if (msg.html) {
                    $(this.tableStatusFieldId).html(msg.html);

                    // Allow migration of table when button is shown
                    $(this.migrateButtonId).click(() => {
                        this.migrate();
                    });

                    $(this.generateTcaButtonId).click(() => {
                        this.generateTca();
                    });

                    $(this.deleteButtonId).click(() => {
                        this.del();
                    });
                }
                // Some endpoints return no html and only perform an action.
                if (!msg.html) {
                    this.refresh();
                }
            } else {
                if (msg.success == false) {
                    if (msg.html) {
                        this.showError('<strong>Error:</strong> ' + msg.html);
                        return;
                    }
                }
                this.showError('<strong>Error:</strong> ' + this.messages.error);
            }
        }).fail(function (e) {
            this.showError('<strong>Error:</strong> ' + this.messages.error);
        });
    }

    slug(str) {
        str = str.replace(/^\s+|\s+$/g, ''); // trim
        str = str.toLowerCase();

        // remove accents, swap ñ for n, etc
        var from = "ãàáäâẽèéëêìíïîõòóöôùúüûñç·/_,:;";
        var to = "aaaaaeeeeeiiiiooooouuuunc------";
        for (var i = 0, l = from.length; i < l; i++) {
            str = str.replace(new RegExp(from.charAt(i), 'g'), to.charAt(i));
        }

        str = str.replace(/[^a-z -]/g, '') // remove invalid chars
            .replace(/\s+/g, '_') // collapse whitespace
            .replace(/-+/g, '_'); // collapse dashes

        return str;

    }
}

export default new DatatypeTableField;
