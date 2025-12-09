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
                    const result = JSON.parse(xhr.responseText);

                    if (result.success) {
                        let message = `✅ Upload successful!<br>`;
                        message += `Processed ${result.processed_rows} of ${result.total_rows} rows.<br>`;
                        message += `<strong>${result.batch_ready} waybills ready for batch scanning!</strong><br>`;
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
                            // Just refresh the pending list and notify
                            showResult('success', `✅ Data added! ${result.batch_ready} new waybills ready.`, 5000);
                            if (typeof loadPendingWaybills === 'function') {
                                loadPendingWaybills();
                            }
                            // Hide upload section
                            document.getElementById('uploadSection').style.display = 'none';
                        } else {
                            // Reload page after 1 second to update counter and switch view
                            setTimeout(() => {
                                window.location.href = window.location.href;
                            }, 1000);
                        }
                    } else {
                        showResult('error', `❌ ${result.message}`);
                    }
                } else {
                    showResult('error', `❌ Upload failed: ${xhr.statusText}`);
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
});
