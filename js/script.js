$(document).ready(function () {
    let selectedRecordIds = [];
    let RecordIdToDelete = null;
    let currentPage = 1;
    let totalPages = 1;


    $('#addRecordForm input[type="text"], #editRecordForm input[type="text"]').on('keyup input', function () {
        $(this).val($(this).val().toUpperCase());
    });


    function loadRecords(query = '', page = 1) {
        $.ajax({
            url: '../api/fetch_Records.php',
            method: 'GET',
            data: { query: query, page: page },
            dataType: 'json',
            success: function (response) {
                $('#RecordTableBody').html(response.html);
                currentPage = response.pagination.currentPage;
                totalPages = response.pagination.totalPages;
                updatePaginationControls();
                updateSelectedRecords();
            },
            error: function (xhr, status, error) {
                console.error("Error loading records:", error);

                if (xhr.responseText) {
                    $('#RecordTableBody').html(xhr.responseText);
                }
            }
        });
    }


    function updatePaginationControls() {
        let paginationHtml = '';


        paginationHtml += `<li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
            <a class="page-link" href="#" id="prevPage">Previous</a>
        </li>`;


        const startPage = Math.max(1, currentPage - 2);
        const endPage = Math.min(totalPages, currentPage + 2);

        if (startPage > 1) {
            paginationHtml += `<li class="page-item"><a class="page-link page-number" href="#" data-page="1">1</a></li>`;
            if (startPage > 2) {
                paginationHtml += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
            }
        }

        for (let i = startPage; i <= endPage; i++) {
            paginationHtml += `<li class="page-item ${i === currentPage ? 'active' : ''}">
                <a class="page-link page-number" href="#" data-page="${i}">${i}</a>
            </li>`;
        }

        if (endPage < totalPages) {
            if (endPage < totalPages - 1) {
                paginationHtml += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
            }
            paginationHtml += `<li class="page-item"><a class="page-link page-number" href="#" data-page="${totalPages}">${totalPages}</a></li>`;
        }


        paginationHtml += `<li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
            <a class="page-link" href="#" id="nextPage">Next</a>
        </li>`;

        $('#paginationControls').html(paginationHtml);
    }


    loadRecords();


    $(document).on('click', '#prevPage', function (e) {
        e.preventDefault();
        if (currentPage > 1) {
            loadRecords($('#searchInput').val(), currentPage - 1);
        }
    });

    $(document).on('click', '#nextPage', function (e) {
        e.preventDefault();
        if (currentPage < totalPages) {
            loadRecords($('#searchInput').val(), currentPage + 1);
        }
    });

    $(document).on('click', '.page-number', function (e) {
        e.preventDefault();
        const page = $(this).data('page');
        loadRecords($('#searchInput').val(), page);
    });


    $('#searchInput').on('input', function () {
        currentPage = 1;
        loadRecords($(this).val());
    });


    $(document).on('click', '#jumpToPageBtn', function () {
        const page = parseInt($('#jumpToPageInput').val());
        if (page >= 1 && page <= totalPages) {
            loadRecords($('#searchInput').val(), page);
        } else {
            showWarningModal('Please enter a valid page number between 1 and ' + totalPages);
        }
    });

    $(document).on('keypress', '#jumpToPageInput', function (e) {
        if (e.which == 13) {
            $('#jumpToPageBtn').click();
        }
    });


    function updateSelectedRecords() {
        selectedRecordIds = [];
        $('#RecordTableBody input[name="recordCheckbox"]:checked').each(function () {
            selectedRecordIds.push($(this).closest('tr').data('id'));
        });
    }


    $('#selectAll').on('change', function () {
        const isChecked = $(this).is(':checked');
        $('#RecordTableBody input[name="recordCheckbox"]').prop('checked', isChecked);
        updateSelectedRecords();
    });

    $('#RecordTableBody').on('click', '.delete-button', function () {
        RecordIdToDelete = $(this).closest('tr').data('id');
        selectedRecordIds = [RecordIdToDelete];

        $('#RecordTableBody input[name="recordCheckbox"]').prop('checked', false);
        $(this).closest('tr').find('input[name="recordCheckbox"]').prop('checked', true);
        $('#confirmationModal').modal('show');
    });


    $('#deleteSelectedButton').on('click', function () {
        updateSelectedRecords();
        if (selectedRecordIds.length > 0) {
            RecordIdToDelete = null;
            $('#confirmationModal').modal('show');
        } else {
            showWarningModal('No records selected for deletion.');
        }
    });


    $('#confirmDeleteBtn').on('click', function () {
        const idsToDelete = selectedRecordIds.length > 0 ? selectedRecordIds :
            (RecordIdToDelete ? [RecordIdToDelete] : []);

        if (idsToDelete.length === 0) {
            showWarningModal('No records selected for deletion.');
            return;
        }

        $.ajax({
            url: '../api/delete_Record.php',
            method: 'POST',
            data: { ids: idsToDelete },
            dataType: 'json',
            success: function (response) {
                if (response.status === 'success') {
                    loadRecords($('#searchInput').val(), currentPage);
                    showSuccessModal(response.message);
                } else {
                    showErrorModal(response.message);
                }
            },
            error: function (xhr, status, error) {
                console.error("Error deleting records:", error);
                showErrorModal('Failed to delete records. Please try again.');
            },
            complete: function () {
                $('#confirmationModal').modal('hide');
                selectedRecordIds = [];
                RecordIdToDelete = null;
                $('#selectAll').prop('checked', false);
            }
        });
    });


    $('#addRecordForm').on('submit', function (e) {
        e.preventDefault();
        $.ajax({
            url: '../api/add_Record.php',
            method: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function (response) {
                if (response.status === 'success') {
                    loadRecords($('#searchInput').val(), currentPage);
                    $('#addRecordModal').modal('hide');
                    showSuccessModal(response.message);
                    $('#addRecordForm')[0].reset();
                    $('#addRecordForm input[type="text"]').val('');
                } else if (response.status === 'duplicate') {
                    $('#duplicateErrorMessage').text(response.message);
                    $('#duplicateErrorModal').modal('show');
                } else {
                    showErrorModal(response.message);
                }
            },
            error: function (xhr, status, error) {
                console.error("Error adding record:", error);
            }
        });
    });


    $('#RecordTableBody').on('click', '.edit-button', function () {
        let recordId = $(this).closest('tr').data('id');

        $.ajax({
            url: '../api/get_Record.php',
            method: 'GET',
            data: { id: recordId },
            success: function (response) {
                let record = JSON.parse(response);

                $('#editRecordId').val(record.record_id);
                $('#editFamilyName').val(record.family_name);
                $('#editFirstName').val(record.first_name);
                $('#editMiddleName').val(record.middle_name);
                $('#editPlateNumber').val(record.plate_number);
                $('#editMvFile').val(record.mv_file);
                $('#editBranch').val(record.branch);
                $('#editBatch').val(record.batch);
                $('#editRemarks').val(record.remarks);
                $('#editDateReg').val(record.date_reg);

                $('#editRecordModal').modal('show');
            },
            error: function () {
                alert('Error fetching the record');
            }
        });
    });


    $('#editRecordForm').on('submit', function (e) {
        e.preventDefault();
        $.ajax({
            url: '../api/edit_Record.php',
            method: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function (response) {
                if (response.status === 'success') {
                    loadRecords($('#searchInput').val(), currentPage);
                    $('#editRecordModal').modal('hide');
                    showSuccessModal(response.message);
                } else if (response.status === 'duplicate') {
                    $('#duplicateErrorMessage').text(response.message);
                    $('#duplicateErrorModal').modal('show');
                } else {
                    $('#errorMessageEdit').text(response.message);
                    $('#errorMessageEdit').show();
                    setTimeout(() => {
                        $('#errorMessageEdit').hide();
                    }, 3000);
                }
            },
            error: function (xhr, status, error) {
                console.error("Error editing Record:", error);
            }
        });
    });



    $('.dropdown-item').on('click', function (e) {
        e.preventDefault();
        const sortOption = $(this).data('sort');
        let sortColumn;

        switch (sortOption) {
            case 'familyName':
                sortColumn = 'family_name';
                break;
            case 'batch':
                sortColumn = 'batch';
                break;
            case 'branch':
                sortColumn = 'branch';
                break;
            default:
                return;
        }

        currentPage = 1;
        loadRecords($('#searchInput').val(), currentPage, sortColumn);
    });


    $('#printButton').on('click', function () {
        $('#printOptionsModal').modal('show');
    });


    $('#sortBy').on('change', function () {
        const selectedValue = $(this).val();
        $('#batchRange').hide();
        $('#familyNameRange').hide();

        if (selectedValue === 'customerBatchRange') {
            $('#batchRange').show();
        } else if (selectedValue === 'familyName') {
            $('#familyNameRange').show();
        }
    });


    $('#confirmPrint').on('click', function () {
        const documentType = $('#documentType').val();
        const sortBy = $('#sortBy').val();
        const fromBatch = $('#fromBatch').val();
        const toBatch = $('#toBatch').val();
        const fromLetter = $('#fromLetter').val();
        const toLetter = $('#toLetter').val();
        const outputFormat = $('#outputFormat').val();

        if (documentType && sortBy) {
            if (sortBy === 'customerBatchRange' && (fromBatch === '' || toBatch === '')) {
                alert('Please enter both From and To batch numbers.');
                return;
            }

            if (sortBy === 'familyName' && (fromLetter === '' || toLetter === '')) {
                alert('Please enter both From and To letters.');
                return;
            }

            const params = new URLSearchParams({
                documentType: documentType,
                sortBy: sortBy,
                fromBatch: fromBatch,
                toBatch: toBatch,
                fromLetter: fromLetter,
                toLetter: toLetter,
                outputFormat: outputFormat
            });

            window.location.href = `../api/generate_pdf.php?${params.toString()}`;
            $('#printOptionsModal').modal('hide');
        } else {
            alert('Please select all options.');
        }
    });


    window.showSuccessModal = function(message) {
        $('#successMessageText').text(message);
        $('#successModal').modal('show');
        setTimeout(() => {
            $('#successModal').modal('hide');
        }, 2000);
    }

    window.showErrorModal = function(message) {
        $('#errorMessageText').text(message);
        $('#errorModal').modal('show');
    }

    window.showWarningModal = function(message) {
        // Handle both ID styles for backwards compatibility if any
        const msgText = $('#warningMessageText').length ? $('#warningMessageText') : $('#warningMessage');
        msgText.text(message);
        $('#warningModal').modal('show');
        setTimeout(() => {
            $('#warningModal').modal('hide');
        }, 2000);
    }
});

