/**
 * Batch Upload JavaScript
 */

document.addEventListener('DOMContentLoaded', function () {
    const uploadForm = document.getElementById('uploadForm');
    const fileInput = document.getElementById('fileInput');
    const dropZone = document.getElementById('dropZone');
    const fileName = document.getElementById('fileName');
    const uploadBtn = document.getElementById('uploadBtn');
    const uploadResult = document.getElementById('uploadResult');
    const progressBar = document.getElementById('progressBar');
    const progressFill = document.getElementById('progressFill');
    let pollInterval = null;

    // File input change
    fileInput.addEventListener('change', function () {
        if (fileInput.files.length > 0) {
            fileName.textContent = fileInput.files[0].name;
            uploadBtn.disabled = false;
        }
    });

    // Drag and drop
    dropZone.addEventListener('dragover', function (e) {
        e.preventDefault();
        dropZone.classList.add('drag-over');
    });

    dropZone.addEventListener('dragleave', function () {
        dropZone.classList.remove('drag-over');
    });

    dropZone.addEventListener('drop', function (e) {
        e.preventDefault();
        dropZone.classList.remove('drag-over');

        const files = e.dataTransfer.files;
        if (files.length > 0) {
            fileInput.files = files;
            fileName.textContent = files[0].name;
            uploadBtn.disabled = false;
        }
    });

    // Form submission
    uploadForm.addEventListener('submit', async function (e) {
        e.preventDefault();

        if (fileInput.files.length === 0) {
            showResult('error', 'Please select a file');
            return;
        }

        const formData = new FormData();
        formData.append('waybill_file', fileInput.files[0]);

        uploadBtn.disabled = true;
        progressBar.style.display = 'block';
        progressFill.style.width = '0%';

        try {
            const xhr = new XMLHttpRequest();

            xhr.upload.addEventListener('progress', function (e) {
                if (e.lengthComputable) {
                    const percentComplete = (e.loaded / e.total) * 100;
                    progressFill.style.width = percentComplete + '%';
                }
            });

            xhr.addEventListener('load', function () {
                if (xhr.status === 200) {
                    try {
                        const result = JSON.parse(xhr.responseText);

                        if (result.success) {
                            // Check if we have an upload ID - if so, it's async or we need to poll for completion
                            if (result.upload_id) {
                                // Async processing - start polling
                                showResult('info', '✅ File uploaded. Processing in background...', 0);
                                if (progressFill) progressFill.style.width = '30%';
                                pollUploadStatus(result.upload_id);
                            } else {
                                // Synchronous completion (Legacy/Fallback)
                                let message = `✅ Upload successful!<br>`;

                                if (result.processed_rows !== undefined && result.total_rows !== undefined) {
                                    message += `Processed ${result.processed_rows} of ${result.total_rows} rows.<br>`;
                                    if (result.batch_ready !== undefined) {
                                        message += `<strong>${result.batch_ready} waybills ready for batch scanning!</strong><br>`;
                                    }
                                }

                                const scannerUrl = (typeof scannerRoute !== 'undefined') ? scannerRoute : 'scanner.php';
                                message += `<br><a href="${scannerUrl}" class="btn btn-primary" style="margin-top:1rem">→ Go to Scanner</a>`;

                                showResult('success', message, 0);
                                uploadForm.reset();
                                fileName.textContent = '';
                                uploadBtn.disabled = true;

                                // Check if we are in scanner view and have an active session
                                const sessionActive = document.getElementById('sessionStatus') &&
                                    document.getElementById('sessionStatus').classList.contains('active');

                                if (sessionActive) {
                                    showResult('success', `✅ Data added! ${result.batch_ready || 'New'} waybills ready.`, 5000);
                                    if (typeof loadPendingWaybills === 'function') {
                                        loadPendingWaybills();
                                    }
                                    if (document.getElementById('uploadSection')) {
                                        document.getElementById('uploadSection').style.display = 'none';
                                    }
                                } else {
                                    setTimeout(() => {
                                        window.location.href = scannerUrl;
                                    }, 1500);
                                }
                            }
                        } else {
                            showResult('error', `❌ ${result.message}`);
                        }
                    } catch (e) {
                        showResult('error', '❌ Error parsing server response');
                        console.error('Server response:', xhr.responseText);
                    }
                } else {
                    // Try to parse error message from JSON response
                    try {
                        const errorResponse = JSON.parse(xhr.responseText);
                        let errorMsg = errorResponse.message || errorResponse.error || 'Upload failed.';

                        // Check for validation errors
                        if (errorResponse.errors) {
                            const details = Object.values(errorResponse.errors).flat().join('<br>');
                            errorMsg += `<br><small>${details}</small>`;
                        }

                        showResult('error', `❌ ${errorMsg}`);
                    } catch (e) {
                        showResult('error', `❌ Upload failed: ${xhr.statusText}`);
                    }
                }

                progressBar.style.display = 'none';
                uploadBtn.disabled = false;
            });

            xhr.addEventListener('error', function () {
                showResult('error', '❌ Upload failed: Network error');
                progressBar.style.display = 'none';
                uploadBtn.disabled = false;
            });

            const url = (typeof uploadBatchRoute !== 'undefined') ? uploadBatchRoute : '/upload-batch';
            xhr.open('POST', url, true);
            xhr.setRequestHeader('X-CSRF-TOKEN', document.querySelector('meta[name="csrf-token"]').content);
            xhr.setRequestHeader('Accept', 'application/json');
            xhr.send(formData);

        } catch (error) {
            showResult('error', `❌ Error: ${error.message}`);
            progressBar.style.display = 'none';
            uploadBtn.disabled = false;
        }
    });

    function showResult(type, message, duration = 10000) {
        uploadResult.className = `upload-result ${type}`;
        uploadResult.innerHTML = message;
        uploadResult.style.display = 'block';

        if (duration > 0) {
            setTimeout(() => {
                uploadResult.style.display = 'none';
            }, duration);
        }
    }

    function pollUploadStatus(uploadId) {
        // Clear any existing timeout
        if (pollInterval) clearTimeout(pollInterval);

        const baseUrl = (typeof uploadStatusBase !== 'undefined') ? uploadStatusBase : '/upload';
        let pollDelay = 3000; // Start at 3 seconds
        const maxPollDelay = 10000; // Max 10 seconds

        const doPoll = async () => {
            try {
                const response = await fetch(`${baseUrl}/${uploadId}/status`);
                const data = await response.json();

                if (data.success) {
                    // Update progress bar (processing is 30-100%)
                    if (data.progress && progressFill) {
                        const processingProgress = 30 + (data.progress * 0.7);
                        progressFill.style.width = processingProgress + '%';
                    }

                    showResult('info', data.message, 0);

                    if (data.status === 'completed') {
                        // Stop polling
                        if (progressFill) progressFill.style.width = '100%';

                        showResult('success', data.message + '<br>Redirecting to scanner...');

                        const scannerUrl = (typeof scannerRoute !== 'undefined') ? scannerRoute : 'scanner.php';
                        setTimeout(() => {
                            window.location.href = scannerUrl;
                        }, 1500);
                        return; // Exit recursion
                    } else if (data.status === 'failed') {
                        if (progressBar) progressBar.style.display = 'none';
                        uploadBtn.disabled = false;
                        showResult('error', data.message);
                        return; // Exit recursion
                    } else {
                        // Exponential backoff: increase delay for next poll
                        pollDelay = Math.min(pollDelay * 1.3, maxPollDelay);
                    }
                }
            } catch (error) {
                console.error('Poll error:', error);
                // On error, slow down polling even more
                pollDelay = Math.min(pollDelay * 1.5, maxPollDelay);
            }

            // Schedule next poll with updated delay
            pollInterval = setTimeout(doPoll, pollDelay);
        };

        // Start polling
        doPoll();
    }
});
