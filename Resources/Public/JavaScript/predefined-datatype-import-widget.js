import AjaxRequest from "@typo3/core/ajax/ajax-request.js";
import Notification from "@typo3/backend/notification.js";

const ROUTE = "tonictypes_predefined_datatype_import";

if (!document.body.dataset.tonictypesImportWidgetBound) {
    document.body.dataset.tonictypesImportWidgetBound = "1";
    document.addEventListener("click", async (event) => {
        const button = event.target.closest("[data-import-button]");
        if (!button) {
            return;
        }
        const root = button.closest("[data-tonictypes-predefined-import-widget]");
        if (!root) {
            return;
        }
        event.preventDefault();

        const select = root.querySelector("[data-storage-pid]");
        const result = root.querySelector("[data-import-result]");
        const show = (type, message) => {
            result.className = `alert alert-${type}`;
            result.textContent = message;
            result.classList.remove("d-none");
        };

        if (!select?.value) {
            show("warning", "Please select a storage PID first.");
            return;
        }

        const url = TYPO3.settings?.ajaxUrls?.[ROUTE];
        if (!url) {
            return;
        }

        button.disabled = true;
        select.disabled = true;
        show("info", "Importing...");

        try {
            const formData = new FormData();
            formData.append("storagePid", select.value);
            const payload = await (await new AjaxRequest(url).post(formData)).resolve("json");
            const ok = payload.success && !payload.alreadyImported;
            const type = ok ? "success" : payload.alreadyImported ? "info" : "danger";
            show(type, payload.message);
            Notification[type === "danger" ? "error" : type]("Tonictypes", payload.message);
        } catch (error) {
            show("danger", "Import failed.");
            Notification.error("Tonictypes", "Import failed.");
        } finally {
            button.disabled = false;
            select.disabled = false;
        }
    });
}
