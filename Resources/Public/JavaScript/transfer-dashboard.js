import AjaxRequest from "@typo3/core/ajax/ajax-request.js";
import Notification from "@typo3/backend/notification.js";

const escapeHtml = (value) => String(value)
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;");

const getErrorMessage = async (error, fallback) => {
    if (typeof error?.resolve === "function") {
        try {
            const payload = await error.resolve("json");
            return payload.message || fallback;
        } catch (e) {
            return fallback;
        }
    }
    return error?.message || fallback;
};

const MIN_LOADER_MS = 400;

let loaderDepth = 0;
let loaderStartedAt = 0;
let loaderOverlay = null;
let loaderTextElement = null;

const getLoaderMessage = (key, fallback) => TYPO3.lang?.[key] || fallback;

const ensureLoaderOverlay = (root) => {
    if (loaderOverlay?.isConnected) {
        return;
    }

    const iconUri = root.dataset.loaderIcon || "";
    loaderOverlay = document.createElement("div");
    loaderOverlay.className = "tonictypes-transfer-loader";
    loaderOverlay.setAttribute("role", "status");
    loaderOverlay.setAttribute("aria-live", "polite");
    loaderOverlay.setAttribute("aria-busy", "false");
    loaderOverlay.innerHTML = `
        <div class="tonictypes-transfer-loader__content">
            <img class="tonictypes-transfer-loader__icon" src="${escapeHtml(iconUri)}" alt="" width="48" height="48">
            <p class="tonictypes-transfer-loader__text"></p>
        </div>
    `;
    loaderTextElement = loaderOverlay.querySelector(".tonictypes-transfer-loader__text");
    document.body.appendChild(loaderOverlay);
};

const showLoader = (root, messageKey = "loader.working", fallback = "Please wait…") => {
    ensureLoaderOverlay(root);
    loaderDepth += 1;
    if (loaderDepth > 1) {
        if (loaderTextElement) {
            loaderTextElement.textContent = getLoaderMessage(messageKey, fallback);
        }
        return;
    }

    loaderStartedAt = Date.now();
    if (loaderTextElement) {
        loaderTextElement.textContent = getLoaderMessage(messageKey, fallback);
    }
    loaderOverlay.classList.add("is-visible");
    loaderOverlay.setAttribute("aria-busy", "true");
    root.classList.add("tonictypes-transfer--busy");
    document.body.classList.add("tonictypes-transfer-page-busy");
};

const hideLoader = async (root) => {
    loaderDepth = Math.max(0, loaderDepth - 1);
    if (loaderDepth > 0 || !loaderOverlay) {
        return;
    }

    const elapsed = Date.now() - loaderStartedAt;
    if (elapsed < MIN_LOADER_MS) {
        await new Promise((resolve) => window.setTimeout(resolve, MIN_LOADER_MS - elapsed));
    }

    loaderOverlay.classList.remove("is-visible");
    loaderOverlay.setAttribute("aria-busy", "false");
    root.classList.remove("tonictypes-transfer--busy");
    document.body.classList.remove("tonictypes-transfer-page-busy");
};

const forceHideLoader = (root) => {
    loaderDepth = 0;
    if (loaderOverlay) {
        loaderOverlay.classList.remove("is-visible");
        loaderOverlay.setAttribute("aria-busy", "false");
    }
    root?.classList.remove("tonictypes-transfer--busy");
    document.body.classList.remove("tonictypes-transfer-page-busy");
};

const withLoader = async (root, callback, messageKey, fallback) => {
    showLoader(root, messageKey, fallback);
    try {
        return await callback();
    } finally {
        await hideLoader(root);
    }
};

const postFormData = async (url, formData) => {
    const response = await new AjaxRequest(url).post(formData);
    return response.resolve("json");
};

const parseFilenameFromDisposition = (disposition) => {
    if (!disposition) {
        return "";
    }
    const utf8Match = disposition.match(/filename\*=UTF-8''([^;]+)/i);
    if (utf8Match?.[1]) {
        return decodeURIComponent(utf8Match[1]);
    }
    const match = disposition.match(/filename="?([^";]+)"?/i);
    return match?.[1] ? match[1].trim() : "";
};

const downloadExportArchive = async (url, fields) => {
    const formData = new FormData();
    Object.entries(fields).forEach(([name, value]) => {
        formData.append(name, value);
    });

    const response = await fetch(url, {
        method: "POST",
        body: formData,
        credentials: "same-origin",
    });

    const contentType = response.headers.get("Content-Type") || "";
    if (!response.ok || contentType.includes("application/json")) {
        let message = "Export failed.";
        try {
            const payload = await response.json();
            message = payload.message || message;
        } catch (error) {
            // Keep default message when the error body is not JSON.
        }
        throw new Error(message);
    }

    const blob = await response.blob();
    const filename = parseFilenameFromDisposition(response.headers.get("Content-Disposition"))
        || "tonictypes-export.t3tt.zip";
    const objectUrl = URL.createObjectURL(blob);
    const link = document.createElement("a");
    link.href = objectUrl;
    link.download = filename;
    link.style.display = "none";
    document.body.appendChild(link);
    link.click();
    link.remove();
    window.setTimeout(() => URL.revokeObjectURL(objectUrl), 1000);
};

const initTransferDashboard = () => {
    const root = document.querySelector(".tonictypes-transfer");
    if (!root || root.dataset.initialized === "1") {
        return;
    }
    root.dataset.initialized = "1";

    let selectedFile = null;
    let isBusy = false;
    let storagePages = [];
    let previewItems = [];
    try {
        storagePages = JSON.parse(root.dataset.storagePages || "[]");
    } catch (error) {
        storagePages = [];
    }

    const actionButtons = () => root.querySelectorAll(
        "#tonictypes-transfer-export-button, #tonictypes-transfer-import-execute-button, #tonictypes-transfer-import-file"
    );

    const setBusy = (busy) => {
        isBusy = busy;
        actionButtons().forEach((button) => {
            button.disabled = busy;
        });
    };

    const exportCheckboxes = () => root.querySelectorAll(".tonictypes-transfer-export-checkbox");

    root.querySelectorAll("[data-tab]").forEach((button) => {
        button.addEventListener("click", () => {
            const tab = button.dataset.tab;
            root.querySelectorAll("[data-tab]").forEach((item) => item.classList.toggle("active", item.dataset.tab === tab));
            root.querySelectorAll("[data-tab-panel]").forEach((panel) => {
                panel.classList.toggle("d-none", panel.dataset.tabPanel !== tab);
            });
        });
    });

    const selectAll = root.querySelector("#tonictypes-transfer-select-all");
    const syncSelectAllState = () => {
        if (!selectAll) {
            return;
        }
        const boxes = Array.from(exportCheckboxes());
        const checkedCount = boxes.filter((checkbox) => checkbox.checked).length;
        selectAll.checked = boxes.length > 0 && checkedCount === boxes.length;
        selectAll.indeterminate = checkedCount > 0 && checkedCount < boxes.length;
    };
    const setAllExportCheckboxes = (checked) => {
        exportCheckboxes().forEach((checkbox) => {
            checkbox.checked = checked;
        });
        syncSelectAllState();
    };

    if (selectAll) {
        const onSelectAllToggle = () => setAllExportCheckboxes(selectAll.checked);
        selectAll.addEventListener("change", onSelectAllToggle);
        selectAll.addEventListener("input", onSelectAllToggle);
    }
    exportCheckboxes().forEach((checkbox) => {
        checkbox.addEventListener("change", syncSelectAllState);
    });

    document.getElementById("tonictypes-transfer-export-button")?.addEventListener("click", async () => {
        if (isBusy) {
            return;
        }

        const uids = Array.from(root.querySelectorAll(".tonictypes-transfer-export-checkbox:checked")).map((item) => item.value);
        if (uids.length === 0) {
            Notification.warning(TYPO3.lang["error.no_selection"] || "Please select at least one datatype.");
            return;
        }

        setBusy(true);
        try {
            await withLoader(
                root,
                () => downloadExportArchive(root.dataset.exportUri, {
                    datatypeUids: uids.join(","),
                }),
                "loader.export",
                "Preparing export…"
            );
            Notification.success(TYPO3.lang["export.success"] || "Export completed. Check your browser downloads.");
        } catch (error) {
            Notification.error(error?.message || TYPO3.lang["export.failed"] || "Export failed.");
        } finally {
            forceHideLoader(root);
            setBusy(false);
        }
    });

    const clearImportMapping = () => {
        previewItems = [];
        document.getElementById("tonictypes-transfer-import-preview")?.classList.add("d-none");
        document.getElementById("tonictypes-transfer-import-preview-body").innerHTML = "";
        clearImportMessage();
    };

    const clearImportMessage = () => {
        const result = document.getElementById("tonictypes-transfer-import-result");
        if (!result) {
            return;
        }
        result.classList.add("d-none");
        result.className = "d-none alert mb-0";
        result.innerHTML = "";
    };

    const linkifyPlainText = (message) => {
        const escaped = escapeHtml(message);
        return escaped.replace(
            /(https?:\/\/[^\s<]+)/g,
            '<a href="$1" target="_blank" rel="noopener noreferrer">$1</a>'
        );
    };

    const showImportMessage = (message, type = "danger") => {
        const result = document.getElementById("tonictypes-transfer-import-result");
        if (!result) {
            return;
        }
        const alertClass = type === "success"
            ? "alert-success"
            : (type === "warning" ? "alert-warning" : "alert-danger");
        result.className = `alert ${alertClass} mb-0`;
        result.innerHTML = linkifyPlainText(message);
        result.classList.remove("d-none");
        result.scrollIntoView({ behavior: "smooth", block: "nearest" });
    };

    const formatPageLabel = (uid, title) => {
        if (uid <= 0) {
            return "—";
        }
        return title ? `[${uid}] ${title}` : `[${uid}]`;
    };

    const buildPageSelectOptions = (selectedPid) => {
        const selected = parseInt(selectedPid, 10) || 0;
        const options = [`<option value="0">--</option>`];
        storagePages.forEach((page) => {
            const uid = parseInt(page.uid, 10) || 0;
            if (uid <= 0) {
                return;
            }
            const isSelected = uid === selected ? " selected" : "";
            options.push(`<option value="${uid}"${isSelected}>[${uid}] ${escapeHtml(page.title || "")}</option>`);
        });
        return options.join("");
    };

    const buildPidMapping = () => {
        const mapping = {};
        let isValid = true;
        root.querySelectorAll("[data-pid-mapping]").forEach((select) => {
            const exportKey = select.dataset.pidMapping;
            const pid = parseInt(select.value, 10) || 0;
            if (!exportKey || pid <= 0) {
                isValid = false;
                return;
            }
            mapping[exportKey] = pid;
        });
        return { mapping, isValid };
    };

    const buildImportFormData = (pidMapping) => {
        const formData = new FormData();
        formData.append("importFile", selectedFile);
        formData.append("pidMapping", JSON.stringify(pidMapping));
        return formData;
    };

    const validateImport = () => {
        if (!selectedFile) {
            Notification.warning(TYPO3.lang["error.no_file"] || "Please choose an archive file.");
            return false;
        }

        if (previewItems.length === 0) {
            Notification.warning(TYPO3.lang["import.preview_failed"] || "Preview failed.");
            return false;
        }

        const { isValid } = buildPidMapping();
        if (!isValid) {
            Notification.warning(TYPO3.lang["error.no_mapping"] || "Please assign a storage page to each datatype in the preview.");
            return false;
        }
        return true;
    };

    const renderImportMapping = (items) => {
        const body = document.getElementById("tonictypes-transfer-import-preview-body");
        body.innerHTML = items.map((item) => {
            const selectedPid = item.suggestedPid > 0 ? item.suggestedPid : 0;
            const sourceLabel = formatPageLabel(item.sourcePid, item.sourcePageTitle);
            const statusLabel = item.exists ? "Update" : "Create";
            return `
                <tr data-export-key="${escapeHtml(item.exportKey)}">
                    <td>${escapeHtml(item.name)}</td>
                    <td><code>${escapeHtml(item.tablename)}</code></td>
                    <td>${item.fieldCount ?? 0}</td>
                    <td>${statusLabel}</td>
                    <td class="text-muted">${escapeHtml(sourceLabel)}</td>
                    <td>
                        <select class="form-select form-select-sm" data-pid-mapping="${escapeHtml(item.exportKey)}">
                            ${buildPageSelectOptions(selectedPid)}
                        </select>
                    </td>
                </tr>
            `;
        }).join("");
        document.getElementById("tonictypes-transfer-import-preview").classList.remove("d-none");
    };

    const loadImportMapping = async () => {
        if (!selectedFile) {
            clearImportMapping();
            return;
        }

        setBusy(true);
        try {
            const formData = new FormData();
            formData.append("importFile", selectedFile);
            const payload = await withLoader(
                root,
                () => postFormData(root.dataset.importPreviewUri, formData),
                "loader.mapping",
                "Reading archive…"
            );
            storagePages = Array.isArray(payload.storagePages) ? payload.storagePages : storagePages;
            previewItems = Array.isArray(payload.preview) ? payload.preview : [];
            if (previewItems.length === 0) {
                clearImportMapping();
                showImportMessage(TYPO3.lang["import.preview_failed"] || "Preview failed.", "warning");
                return;
            }
            clearImportMessage();
            renderImportMapping(previewItems);
        } catch (error) {
            clearImportMapping();
            showImportMessage(
                await getErrorMessage(error, TYPO3.lang["import.preview_failed"] || "Preview failed."),
                "danger"
            );
        } finally {
            setBusy(false);
        }
    };

    document.getElementById("tonictypes-transfer-import-file")?.addEventListener("change", (event) => {
        selectedFile = event.target.files[0] || null;
        clearImportMapping();
        if (selectedFile) {
            loadImportMapping();
        }
    });

    document.getElementById("tonictypes-transfer-import-execute-button")?.addEventListener("click", async () => {
        if (isBusy) {
            return;
        }

        if (!validateImport()) {
            return;
        }

        setBusy(true);
        try {
            const { mapping } = buildPidMapping();
            const payload = await withLoader(
                root,
                () => postFormData(root.dataset.importExecuteUri, buildImportFormData(mapping)),
                "loader.import",
                "Importing datatypes…"
            );
            const result = document.getElementById("tonictypes-transfer-import-result");
            const log = (payload.log || [])
                .filter((entry) => Boolean(entry.message))
                .map((entry) => {
                    const message = escapeHtml(entry.message);
                    if (entry.status === "warning") {
                        return `<span class="text-warning-emphasis">${message}</span>`;
                    }
                    if (entry.status === "error") {
                        return `<span class="text-danger">${message}</span>`;
                    }
                    return message;
                })
                .join("<br>");
            result.className = `alert ${payload.success === false ? "alert-warning" : "alert-success"} mb-0`;
            result.innerHTML = log || (TYPO3.lang["import.success"] || "Import completed.");
            result.classList.remove("d-none");
            result.scrollIntoView({ behavior: "smooth", block: "nearest" });
        } catch (error) {
            showImportMessage(
                await getErrorMessage(error, TYPO3.lang["import.failed"] || "Import failed."),
                "danger"
            );
        } finally {
            setBusy(false);
        }
    });
};

if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initTransferDashboard);
} else {
    initTransferDashboard();
}
