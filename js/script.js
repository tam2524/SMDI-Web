$(document).ready(function() {
    let selectedRecordIds = [];
    let RecordIdToDelete = null;
     let currentPage = 1;

    // Convert input text to uppercase
    $('#addRecordForm input[type="text"], #editRecordForm input[type="text"]').on('keyup input', function() {
        $(this).val($(this).val().toUpperCase());
    });

function loadRecords(query = '', page = currentPage) {
    $.ajax({
        url: 'api/fetch_Records.php',
        method: 'GET',
        data: { query: query, page: page },
        success: function(data) {
            $('#RecordTableBody').html(data);
        },
        error: function(xhr, status, error) {
            console.error("Error loading records:", error);
        }
    });
}

    // Initial load of records
    loadRecords();
    
    // Event delegation for pagination links
    $(document).on('click', '#prevPage', function(e) {
        e.preventDefault(); // Prevent default link behavior
        if (currentPage > 1) {
            currentPage--; // Decrement the page number
            loadRecords($('#searchInput').val(), currentPage); // Load records for the previous page
        }
    });

    $(document).on('click', '#nextPage', function(e) {
        e.preventDefault(); // Prevent default link behavior
        currentPage++; // Increment the page number
        loadRecords($('#searchInput').val(), currentPage); // Load records for the next page
    });


    // Search input event
    $('#searchInput').on('input', function() {
        const query = $(this).val();
        loadRecords(query);
    });

    // Event delegation for pagination links
    $(document).on('click', '.pagination a', function(e) {
        e.preventDefault(); // Prevent default link behavior
        const query = $('#searchInput').val(); // Get the current search query
        const page = $(this).attr('href').split('page=')[1]; // Extract page number from link
        loadRecords(query, page); // Load records for the selected page
    });

    $('#addRecordForm').on('submit', function(e) {
        e.preventDefault();
        $.ajax({
            url: 'api/add_Record.php',
            method: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    loadRecords();
                    $('#addRecordModal').modal('hide');
                    showSuccessModal(response.message);
                     $('#addRecordForm')[0].reset();
                $('#addRecordForm input[type="text"]').val('');
                } else {
                    showErrorModal(response.message);
                }
            },
            error: function(xhr, status, error) {
                console.error("Error adding record:", error);
            }
        });
    });
    
    
 $('#RecordTableBody').on('change', 'input[name="recordCheckbox"]', function() {
        updateSelectedRecords();
    });

    $('#selectAll').on('change', function() {
        const isChecked = $(this).is(':checked');
        $('#RecordTableBody input[name="recordCheckbox"]').prop('checked', isChecked);
        updateSelectedRecords();
    });

    $('#deleteSelectedButton').on('click', function() {
        if (selectedRecordIds.length > 0) {
            $('#confirmationModal').modal('show');
        } else {
            showWarningModal('No records selected for deletion.');
        }
    });

    $('#confirmDeleteBtn').on('click', function() {
        if (selectedRecordIds.length > 0) {
            $.ajax({
                url: 'api/delete_Record.php',
                method: 'POST',
                data: { ids: selectedRecordIds },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        loadRecords();
                        showSuccessModal(response.message);
                    } else {
                        showErrorModal(response.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.error("Error deleting records:", error);
                },
                complete: function() {
                    $('#confirmationModal').modal('hide');
                    selectedRecordIds = [];
                }
            });
        }
    });
    
    
    $(document).ready(function() {
    // Show modal when print button is clicked
    $('#printButton').on('click', function() {
        $('#printOptionsModal').modal('show');
    });

    $('#sortBy').on('change', function() {
        const selectedValue = $(this).val();
        
        // Hide both ranges by default
        $('#batchRange').hide();
        $('#familyNameRangeContainer').hide();

        // Show the appropriate range based on the selected value
        if (selectedValue === 'customerBatchRange') {
            $('#batchRange').show();
        } else if (selectedValue === 'familyName') {
            $('#familyNameRange').show(); // Show family name range
        }
    });

// Handle print confirmation
$('#confirmPrint').on('click', function() {
    const documentType = $('#documentType').val();
    const sortBy = $('#sortBy').val();
    const fromBatch = $('#fromBatch').val();
    const toBatch = $('#toBatch').val();
    const fromLetter = $('#fromLetter').val(); // Get the starting letter input
    const toLetter = $('#toLetter').val(); // Get the ending letter input
    const outputFormat = $('#outputFormat').val(); // Get the selected output format

    // Log the values for debugging
    console.log('documentType:', documentType);
    console.log('sortBy:', sortBy);
    console.log('fromBatch:', fromBatch);
    console.log('toBatch:', toBatch);
    console.log('fromLetter:', fromLetter);
    console.log('toLetter:', toLetter);
    console.log('outputFormat:', outputFormat); // Log the output format

    // Check if documentType and sortBy are selected
    if (documentType && sortBy) {
        // Validate batch range if sorting by customer batch range
        if (sortBy === 'customerBatchRange' && (fromBatch === '' || toBatch === '')) {
            alert('Please enter both From and To batch numbers.');
            return;
        }

        // Validate letter range if sorting by family name
        if (sortBy === 'familyName' && (fromLetter === '' || toLetter === '')) {
            alert('Please enter both From and To letters.');
            return;
        }

        // URL encode the parameters to handle special characters
        const encodedDocumentType = encodeURIComponent(documentType);
        const encodedSortBy = encodeURIComponent(sortBy);
        const encodedFromBatch = encodeURIComponent(fromBatch);
        const encodedToBatch = encodeURIComponent(toBatch);
        const encodedFromLetter = encodeURIComponent(fromLetter);
        const encodedToLetter = encodeURIComponent(toLetter);
        const encodedOutputFormat = encodeURIComponent(outputFormat);

        // Redirect to generate_pdf.php with parameters, including output format
        window.location.href = `api/generate_pdf.php?documentType=${encodedDocumentType}&sortBy=${encodedSortBy}&fromBatch=${encodedFromBatch}&toBatch=${encodedToBatch}&fromLetter=${encodedFromLetter}&toLetter=${encodedToLetter}&outputFormat=${encodedOutputFormat}`;
        $('#printOptionsModal').modal('hide');
    } else {
        alert('Please select all options.');
    }
});

    function showSuccessModal(message) {
        $('#successMessage').text(message);
        $('#successModal').modal('show');
    }

    function showErrorModal(message) {
        $('#errorMessage').text(message);
        $('#errorMessage').show();
        setTimeout(() => {
            $('#errorMessage').hide();
        }, 3000);
    }

    function showWarningModal(message) {
        $('#warningMessage').text(message);
        $('#warningModal').modal('show');
    }

    $('#RecordTableBody').on('click', '.edit-button', function() {
        let recordId = $(this).closest('tr').data('id');

        $.ajax({
            url: 'api/get_Record.php',
            method: 'GET',
            data: { id: recordId },
            success: function(response) {
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

                $('#editRecordModal').modal('show');
            },
            error: function() {
                alert('Error fetching the record');
            }
        });
    });

    $('#editRecordForm').on('submit', function(e) {
        e.preventDefault();
        $.ajax({
            url: 'api/edit_Record.php',
            method: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    loadRecords();
                    $('#editRecordModal').modal('hide');
                    showSuccessModal(response.message);
                } else {
                    showErrorModal(response.message);
                }
            },
            error: function(xhr, status, error) {
                console.error("Error editing Record:", error);
            }
        });
    });

    $('#RecordTableBody').on('click', '.delete-button', function() {
        let row = $(this).closest('tr');
        RecordIdToDelete = row.data('id');
        $('#confirmationModal').modal('show');
    });

    $('#confirmDeleteBtn').click(function() {
        if (RecordIdToDelete !== null) {
            $.ajax({
                url: 'api/delete_Record.php',
                method: 'POST',
                data: { id: RecordIdToDelete },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        loadRecords();
                        showSuccessModal(response.message);
                    } else {
                        showErrorModal(response.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.error("Error deleting Record:", error);
                },
                complete: function() {
                    $('#confirmationModal').modal('hide');
                    RecordIdToDelete = null;
                }
            });
        }
    });
    
   
// Sorting functionality
$('.dropdown-item').on('click', function(e) {
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

    sortRecords(sortColumn); // Call the new sortRecords function
});

function sortRecords(column) {
    $.ajax({
        url: 'api/fetch_Records.php',
        method: 'GET',
        data: { sort: column }, // Pass the sort parameter to the server
        success: function(data) {
            $('#RecordTableBody').html(data);
            currentPage = 1; // Reset to first page after sorting
        },
        error: function(xhr, status, error) {
            console.error("Error loading sorted records:", error);
        }
    });
}
});
});