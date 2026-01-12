(function ($) {
    'use strict';

    /**
     * Import Module
     * Handles file upload, column mapping, preview, and import execution
     */

    var importState = {
        uploadedFile: null,
        headers: [],
        mapping: {},
        previewData: null,
        currentStep: 1
    };

    /**
     * Initialize import functionality
     */
    function initImport() {
        initFileUpload();
        initColumnMapping();
        initPreview();
        initNavigation();
    }

    /**
     * Initialize file upload with drag-and-drop
     */
    function initFileUpload() {
        var $dropzone = $('#import-dropzone');
        var $fileInput = $('#import-file-input');

        // Click to open file picker
        $dropzone.on('click', function () {
            $fileInput.trigger('click');
        });

        // Prevent default drag behaviors
        $(document).on('drag dragstart dragend dragover dragenter dragleave drop', function (e) {
            e.preventDefault();
            e.stopPropagation();
        });

        // Highlight dropzone on drag
        $dropzone.on('dragover dragenter', function () {
            $dropzone.addClass('dragover');
        });

        $dropzone.on('dragleave dragend drop', function () {
            $dropzone.removeClass('dragover');
        });

        // Handle file drop
        $dropzone.on('drop', function (e) {
            var files = e.originalEvent.dataTransfer.files;
            if (files.length > 0) {
                handleFileUpload(files[0]);
            }
        });

        // Handle file selection
        $fileInput.on('change', function () {
            if (this.files.length > 0) {
                handleFileUpload(this.files[0]);
            }
        });
    }

    /**
     * Handle file upload
     */
    function handleFileUpload(file) {
        // Validate file type
        var validTypes = ['text/csv', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];
        var validExtensions = ['.csv', '.xlsx'];
        var fileExtension = '.' + file.name.split('.').pop().toLowerCase();

        if (!validTypes.includes(file.type) && !validExtensions.includes(fileExtension)) {
            showError('Ungültiger Dateityp. Bitte laden Sie eine CSV- oder Excel-Datei (.xlsx) hoch.');
            return;
        }

        // Validate file size (max 10 MB)
        var maxSize = 10 * 1024 * 1024; // 10 MB
        if (file.size > maxSize) {
            showError('Datei zu groß. Maximum: 10 MB.');
            return;
        }

        // Show progress
        $('#import-dropzone').hide();
        $('#upload-progress').show();
        $('#upload-error').hide();

        // Create form data
        var formData = new FormData();
        formData.append('action', 'ahgmh_upload_file');
        formData.append('nonce', ahgmh_admin.nonce);
        formData.append('file', file);

        // Upload file via AJAX
        $.ajax({
            url: ahgmh_admin.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            xhr: function () {
                var xhr = new window.XMLHttpRequest();
                // Upload progress
                xhr.upload.addEventListener('progress', function (e) {
                    if (e.lengthComputable) {
                        var percentComplete = (e.loaded / e.total) * 100;
                        updateUploadProgress(percentComplete);
                    }
                }, false);
                return xhr;
            },
            success: function (response) {
                if (response.success) {
                    handleUploadSuccess(response.data);
                } else {
                    var errorMsg = response.data && response.data.message ? response.data.message : 'Fehler beim Hochladen der Datei.';
                    showError(errorMsg);
                    resetUploadUI();
                }
            },
            error: function () {
                showError('Netzwerkfehler beim Hochladen der Datei.');
                resetUploadUI();
            }
        });
    }

    /**
     * Update upload progress bar
     */
    function updateUploadProgress(percent) {
        $('#upload-progress-bar').css('width', percent + '%');
        $('#upload-progress-text').text('Hochladen... ' + Math.round(percent) + '%');
    }

    /**
     * Handle successful file upload
     */
    function handleUploadSuccess(data) {
        importState.uploadedFile = data.filepath;
        importState.headers = data.headers;

        showNotification(data.message || 'Datei erfolgreich hochgeladen!', 'success');

        // Auto-detect column mapping
        autoMapColumns(data.headers);

        // Move to mapping step
        showStep(2);
    }

    /**
     * Auto-detect and map columns based on headers
     */
    function autoMapColumns(headers) {
        // Populate dropdown options
        populateColumnDropdowns(headers);

        // Auto-detect mappings (simple string matching)
        var fieldMappings = {
            'datum': ['datum', 'date', 'abschussdatum', 'erlegungsdatum'],
            'wildart': ['wildart', 'tierart', 'wild', 'art', 'species'],
            'kategorie': ['kategorie', 'category', 'alter', 'geschlecht', 'altersklasse'],
            'meldegruppe': ['meldegruppe', 'gruppe', 'group'],
            'jagdbezirk': ['jagdbezirk', 'bezirk', 'revier', 'district'],
            'wus_nummer': ['wus', 'wus-nr', 'wus-nummer', 'wus_nummer', 'wildursprungsschein'],
            'bemerkung': ['bemerkung', 'notiz', 'anmerkung', 'comment', 'note']
        };

        // Try to auto-map each field
        for (var field in fieldMappings) {
            var $select = $('.column-mapping-select[data-field="' + field + '"]');
            var mappedColumn = findBestMatch(headers, fieldMappings[field]);

            if (mappedColumn) {
                $select.val(mappedColumn);
                importState.mapping[field] = mappedColumn;
                updateConfidenceBadge(field, 0.9); // High confidence for auto-detected
            } else {
                updateConfidenceBadge(field, 0);
            }
        }

        // Show LJV template detection info if applicable
        checkLJVTemplate(headers);
    }

    /**
     * Find best matching column header
     */
    function findBestMatch(headers, patterns) {
        for (var i = 0; i < headers.length; i++) {
            var header = headers[i].toLowerCase().replace(/[^a-z0-9]/g, '');
            for (var j = 0; j < patterns.length; j++) {
                var pattern = patterns[j].toLowerCase().replace(/[^a-z0-9]/g, '');
                if (header.indexOf(pattern) !== -1 || pattern.indexOf(header) !== -1) {
                    return headers[i];
                }
            }
        }
        return null;
    }

    /**
     * Check if file matches LJV template
     */
    function checkLJVTemplate(headers) {
        var ljvSignatures = {
            'ljv_hessen': ['abschussdatum', 'wus-nr'],
            'ljv_bayern': ['erlegungsdatum', 'wildursprungsschein'],
            'ljv_nrw': ['datum', 'wild', 'wus']
        };

        var detectedTemplate = null;
        var maxMatches = 0;

        for (var template in ljvSignatures) {
            var matches = 0;
            var signatures = ljvSignatures[template];

            for (var i = 0; i < signatures.length; i++) {
                for (var j = 0; j < headers.length; j++) {
                    var header = headers[j].toLowerCase().replace(/[^a-z0-9]/g, '');
                    var sig = signatures[i].toLowerCase().replace(/[^a-z0-9]/g, '');
                    if (header.indexOf(sig) !== -1) {
                        matches++;
                        break;
                    }
                }
            }

            if (matches > maxMatches) {
                maxMatches = matches;
                detectedTemplate = template;
            }
        }

        if (detectedTemplate && maxMatches >= 2) {
            var templateNames = {
                'ljv_hessen': 'LJV Hessen',
                'ljv_bayern': 'LJV Bayern',
                'ljv_nrw': 'LJV NRW'
            };
            $('#template-detection-message').text('Format erkannt: ' + templateNames[detectedTemplate]);
            $('#template-detection-info').show();
        }
    }

    /**
     * Populate column mapping dropdowns
     */
    function populateColumnDropdowns(headers) {
        $('.column-mapping-select').each(function () {
            var $select = $(this);
            var currentValue = $select.val();

            // Clear and add default option
            $select.html('<option value="">-- Nicht zuordnen --</option>');

            // Add header options
            for (var i = 0; i < headers.length; i++) {
                $select.append('<option value="' + escapeHtml(headers[i]) + '">' + escapeHtml(headers[i]) + '</option>');
            }

            // Restore previous selection if exists
            if (currentValue) {
                $select.val(currentValue);
            }
        });
    }

    /**
     * Update confidence badge
     */
    function updateConfidenceBadge(field, confidence) {
        var $badge = $('tr[data-field="' + field + '"] .confidence-badge');
        var $value = $badge.find('.confidence-value');

        if (confidence === 0) {
            $value.text('-');
            $badge.removeClass('high medium low').addClass('none');
        } else {
            $value.text(Math.round(confidence * 100) + '%');
            $badge.removeClass('high medium low none');

            if (confidence >= 0.8) {
                $badge.addClass('high');
            } else if (confidence >= 0.5) {
                $badge.addClass('medium');
            } else {
                $badge.addClass('low');
            }
        }
    }

    /**
     * Initialize column mapping handlers
     */
    function initColumnMapping() {
        // Handle column selection changes
        $(document).on('change', '.column-mapping-select', function () {
            var field = $(this).data('field');
            var column = $(this).val();

            if (column) {
                importState.mapping[field] = column;
                updateConfidenceBadge(field, 1.0); // Manual selection = 100% confidence
            } else {
                delete importState.mapping[field];
                updateConfidenceBadge(field, 0);
            }
        });
    }

    /**
     * Initialize preview functionality
     */
    function initPreview() {
        $('#btn-continue-to-preview').on('click', function () {
            loadPreview();
        });

        $('#btn-execute-import').on('click', function () {
            executeImport();
        });
    }

    /**
     * Load preview data
     */
    function loadPreview() {
        // Validate that required fields are mapped
        var requiredFields = ['wildart', 'kategorie'];
        var missingFields = [];

        for (var i = 0; i < requiredFields.length; i++) {
            if (!importState.mapping[requiredFields[i]]) {
                missingFields.push(requiredFields[i]);
            }
        }

        if (missingFields.length > 0) {
            showNotification('Bitte ordnen Sie die erforderlichen Felder zu: ' + missingFields.join(', '), 'error');
            return;
        }

        // Show preview step
        showStep(3);

        // Fetch preview data
        $.ajax({
            url: ahgmh_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'ahgmh_get_preview',
                nonce: ahgmh_admin.nonce,
                filepath: importState.uploadedFile,
                limit: 5
            },
            success: function (response) {
                if (response.success) {
                    displayPreview(response.data);
                } else {
                    showNotification(response.data && response.data.message ? response.data.message : 'Fehler beim Laden der Vorschau.', 'error');
                }
            },
            error: function () {
                showNotification('Netzwerkfehler beim Laden der Vorschau.', 'error');
            }
        });
    }

    /**
     * Display preview table
     */
    function displayPreview(data) {
        importState.previewData = data;

        // Build table header
        var headerHtml = '<tr>';
        var mappedFields = Object.keys(importState.mapping);

        for (var i = 0; i < mappedFields.length; i++) {
            var field = mappedFields[i];
            var fieldLabels = {
                'datum': 'Datum',
                'wildart': 'Wildart',
                'kategorie': 'Kategorie',
                'meldegruppe': 'Meldegruppe',
                'jagdbezirk': 'Jagdbezirk',
                'wus_nummer': 'WUS-Nummer',
                'bemerkung': 'Bemerkung'
            };
            headerHtml += '<th>' + (fieldLabels[field] || field) + '</th>';
        }
        headerHtml += '</tr>';
        $('#preview-table-head').html(headerHtml);

        // Build table body
        var bodyHtml = '';
        for (var rowIndex = 0; rowIndex < data.data.length; rowIndex++) {
            var row = data.data[rowIndex];
            bodyHtml += '<tr>';

            for (var fieldIndex = 0; fieldIndex < mappedFields.length; fieldIndex++) {
                var field = mappedFields[fieldIndex];
                var columnName = importState.mapping[field];
                var columnIndex = data.headers.indexOf(columnName);
                var value = columnIndex !== -1 && row[columnIndex] ? row[columnIndex] : '';

                bodyHtml += '<td>' + escapeHtml(value) + '</td>';
            }

            bodyHtml += '</tr>';
        }
        $('#preview-table-body').html(bodyHtml);

        // Show validation summary
        validatePreview(data);
    }

    /**
     * Validate preview data
     */
    function validatePreview(data) {
        var errors = [];
        var warnings = [];
        var validRows = 0;

        // Basic validation
        for (var i = 0; i < data.data.length; i++) {
            var row = data.data[i];
            var hasError = false;

            // Check required fields
            var wildartIndex = data.headers.indexOf(importState.mapping['wildart']);
            var kategorieIndex = data.headers.indexOf(importState.mapping['kategorie']);

            if (wildartIndex === -1 || !row[wildartIndex]) {
                errors.push('Zeile ' + (i + 2) + ': Wildart fehlt');
                hasError = true;
            }

            if (kategorieIndex === -1 || !row[kategorieIndex]) {
                errors.push('Zeile ' + (i + 2) + ': Kategorie fehlt');
                hasError = true;
            }

            if (!hasError) {
                validRows++;
            }
        }

        // Display validation results
        $('#validation-errors-list').empty();
        $('#validation-warnings-list').empty();

        if (errors.length > 0) {
            errors.forEach(function (error) {
                $('#validation-errors-list').append('<li>' + escapeHtml(error) + '</li>');
            });
            $('#validation-errors').show();
            $('#btn-execute-import').prop('disabled', true);
        } else {
            $('#validation-errors').hide();
            $('#btn-execute-import').prop('disabled', false);
        }

        if (warnings.length > 0) {
            warnings.forEach(function (warning) {
                $('#validation-warnings-list').append('<li>' + escapeHtml(warning) + '</li>');
            });
            $('#validation-warnings').show();
        } else {
            $('#validation-warnings').hide();
        }

        if (errors.length === 0) {
            $('#validation-success-message').text(validRows + ' von ' + data.total_rows + ' Zeilen bereit zum Import');
            $('#validation-success').show();
        } else {
            $('#validation-success').hide();
        }

        $('#validation-summary').show();
    }

    /**
     * Execute import
     */
    function executeImport() {
        // Confirm import
        if (!confirm('Import jetzt starten? ' + importState.previewData.total_rows + ' Zeilen werden importiert.')) {
            return;
        }

        // Disable button and show progress
        $('#btn-execute-import').prop('disabled', true);
        $('#import-progress').show();
        $('#import-progress-bar').css('width', '0%');
        $('#import-progress-text').text('Import wird vorbereitet...');

        // Simulate progress
        var progress = 0;
        var progressInterval = setInterval(function () {
            progress += 10;
            if (progress >= 90) {
                clearInterval(progressInterval);
            }
            $('#import-progress-bar').css('width', progress + '%');
            $('#import-progress-text').text('Importiere Daten... ' + progress + '%');
        }, 200);

        // Execute import
        $.ajax({
            url: ahgmh_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'ahgmh_execute_import',
                nonce: ahgmh_admin.nonce,
                filepath: importState.uploadedFile,
                mapping: importState.mapping
            },
            success: function (response) {
                clearInterval(progressInterval);
                $('#import-progress-bar').css('width', '100%');
                $('#import-progress-text').text('Import abgeschlossen!');

                if (response.success) {
                    handleImportSuccess(response.data);
                } else {
                    showNotification(response.data && response.data.message ? response.data.message : 'Fehler beim Import.', 'error');
                    $('#import-progress').hide();
                    $('#btn-execute-import').prop('disabled', false);
                }
            },
            error: function () {
                clearInterval(progressInterval);
                showNotification('Netzwerkfehler beim Import.', 'error');
                $('#import-progress').hide();
                $('#btn-execute-import').prop('disabled', false);
            }
        });
    }

    /**
     * Handle successful import
     */
    function handleImportSuccess(data) {
        // Update summary statistics
        $('#import-success-count').text(data.imported || 0);
        $('#import-error-count').text(data.errors ? data.errors.length : 0);
        $('#import-warning-count').text(0);

        // Show error details if any
        if (data.errors && data.errors.length > 0) {
            var detailsHtml = '<ul>';
            data.errors.forEach(function (error) {
                detailsHtml += '<li>' + escapeHtml(error) + '</li>';
            });
            detailsHtml += '</ul>';
            $('#import-details-content').html(detailsHtml);
            $('#import-details').show();
        }

        // Show completion step
        showStep(4);

        // Show notification
        showNotification(data.message || 'Import erfolgreich abgeschlossen!', 'success');
    }

    /**
     * Initialize navigation buttons
     */
    function initNavigation() {
        $('#btn-back-to-upload').on('click', function () {
            showStep(1);
            resetUploadUI();
        });

        $('#btn-back-to-mapping').on('click', function () {
            showStep(2);
        });

        $('#btn-start-new-import').on('click', function () {
            resetImport();
        });
    }

    /**
     * Show specific step
     */
    function showStep(stepNumber) {
        $('.ahgmh-import-step').hide();
        $('#step-upload, #step-mapping, #step-preview, #step-complete').each(function () {
            if ($(this).data('step') === stepNumber) {
                $(this).show();
            }
        });
        importState.currentStep = stepNumber;
    }

    /**
     * Reset upload UI
     */
    function resetUploadUI() {
        $('#import-dropzone').show();
        $('#upload-progress').hide();
        $('#upload-progress-bar').css('width', '0%');
        $('#upload-error').hide();
        $('#import-file-input').val('');
    }

    /**
     * Reset entire import process
     */
    function resetImport() {
        importState = {
            uploadedFile: null,
            headers: [],
            mapping: {},
            previewData: null,
            currentStep: 1
        };

        resetUploadUI();
        $('#validation-summary').hide();
        $('#import-progress').hide();
        $('#import-details').hide();
        showStep(1);
    }

    /**
     * Show error message
     */
    function showError(message) {
        $('#upload-error-message').text(message);
        $('#upload-error').show();
    }

    /**
     * Show notification
     */
    function showNotification(message, type) {
        type = type || 'info';

        var notification = $('<div class="ahgmh-notification ahgmh-notification-' + type + '">' + escapeHtml(message) + '</div>');
        $('body').append(notification);

        setTimeout(function () {
            notification.addClass('show');
        }, 100);

        setTimeout(function () {
            notification.removeClass('show');
            setTimeout(function () {
                notification.remove();
            }, 300);
        }, 3000);
    }

    /**
     * Escape HTML to prevent XSS
     */
    function escapeHtml(text) {
        if (!text) return '';
        var map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.toString().replace(/[&<>"']/g, function (m) { return map[m]; });
    }

    // Initialize on document ready
    $(document).ready(function () {
        // Check if we're on the import page
        if ($('.ahgmh-import-container').length > 0) {
            initImport();
        }
    });

})(jQuery);
