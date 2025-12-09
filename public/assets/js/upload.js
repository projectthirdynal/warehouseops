/**
 * Upload JavaScript - Handle file uploads
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
                        showResult('success',
                            `✅ Upload successful! Processed ${result.processed_rows} of ${result.total_rows} rows.`
                        );
                        uploadForm.reset();
                        fileName.textContent = '';
                        uploadBtn.disabled = true;
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

            const url = (typeof uploadRoute !== 'undefined') ? uploadRoute : '/upload';
            xhr.open('POST', url, true);
            xhr.send(formData);

        } catch (error) {
            showResult('error', `❌ Error: ${error.message}`);
            progressBar.style.display = 'none';
            uploadBtn.disabled = false;
        }
    });

    function showResult(type, message) {
        uploadResult.className = `upload-result ${type}`;
        uploadResult.innerHTML = message;
        uploadResult.style.display = 'block';

        setTimeout(() => {
            uploadResult.style.display = 'none';
        }, 10000);
    }
});
