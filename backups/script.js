$(document).ready(function() {
    let selectedRecordIds = [];
    let RecordIdToDelete = null;

    $('#addRecordForm input[type="text"], #editRecordForm input[type="text"]').on('keyup input', function() {
        $(this).val($(this).val().toUpperCase());
    });

    function loadRecords(query = '') {
        $.ajax({
            url: 'api/fetch_Records.php',
            method: 'GET',
            data: { query: query },
            success: function(data) {
                $('#RecordTableBody').html(data);
            },
            error: function(xhr, status, error) {
                console.error("Error loading records:", error);
            }
        });
    }

    loadRecords();

    $('#searchInput').on('input', function() {
        const query = $(this).val();
        loadRecords(query);
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

    $('#printButton').on('click', function() {
        if (selectedRecordIds.length > 0) {
            printSelectedRecords();
        } else {
            showWarningModal('No records selected for printing.');
        }
    });

    function updateSelectedRecords() {
        selectedRecordIds = [];
        $('#RecordTableBody input[name="recordCheckbox"]:checked').each(function() {
            selectedRecordIds.push($(this).closest('tr').data('id'));
        });
    }

    function printSelectedRecords() {
        const printContent = $('<div></div>');
        const header = $('<div style="text-align: center; margin-bottom: 20px;">' +
            '<img src="assets/img/smdi_logo.jpg" alt="SMDI_Logo" class="logo-tmdc mb-2" style="max-width: 150px;"/>' +
            '<h4 style="font-size: 16px; margin-bottom: 0;">Solid Motorcycle Distributors, Inc. </h4>' +
            '<p style="font-size: 12px; margin-bottom: 0;">1031 Victoria Bldg., Roxas Avenue, Roxas City, Capiz Philippines 5800</p>' +
            '<h2>Masterlists</h2>' +
        '</div>');
        const table = $('<table class="table table-striped"></table>');
        const thead = $('<thead><tr><th>Family Name</th><th>First Name</th><th>Middle Initial</th><th>Plate Number</th><th>MV File</th><th>Branch</th><th>Batch</th><th>Remarks</th></tr></thead>');
        const tbody = $('<tbody></tbody>');

        const selectedRows = $('#RecordTableBody input[name="recordCheckbox"]:checked').closest('tr');

        if (selectedRows.length > 0) {
            
            printContent.append(header);

            table.append(thead);

            selectedRows.each(function() {
                const recordCells = $(this).find('td:not(.no-print)').map(function() {
                    return `<td>${$(this).text()}</td>`;
                }).get().join('');

                tbody.append(`<tr>${recordCells}</tr>`);
            });

            table.append(tbody);
            printContent.append(table);

            console.log(printContent.html());

            printJS({
                printable: printContent.html(),
                type: 'raw-html',
                style: `
                    h2 {
                        text-align: center;
                        margin-bottom: 20px;
                        font-size: 24px;
                        font-weight: bold;
                    }
                    table {
                        width: 100%;
                        border-collapse: collapse;
                        margin: 20px 0;
                    }
                    th, td {
                        border: 1px solid #ccc;
                        padding: 10px;
                        text-align: left;
                    }
                    th {
                        font-size: 10px;
                        background-color: #f5f5f5;
                        font-weight: bold;
                        color: #333;
                        text-align: center;
                    }
                    td {
                        font-size: 14px; /* Adjust font size for readability */
                        border-bottom: 1px solid #e0e0e0;
                    }
                    .modal-footer, .modal-header {
                        display: none;
                    }
                    @media print {
                        body {
                            margin: 0;
                            padding: 0;
                            font-family: Arial, sans-serif; /* Use a professional font */
                        }
                        .no-print {
                            display: none !important;
                        }
                        .modal-body {
                            padding: 0;
                            margin: 0;
                        }
                        .modal-content {
                            border: none;
                            box-shadow: none;
                        }
                        table {
                            margin: 0;
                            border: 1px solid #ddd; /* Add border around the table for clarity */
                        }
                    }
                    @page {
                        margin: 1cm;
                    }
                `
            });
        } else {
            showWarningModal('No records selected for printing.');
        }
    }

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
    
    $('#RecordTableBody').on('click', '.printLabel-button', function() {
        const row = $(this).closest('tr');
        const familyName = row.find('td:eq(1)').text(); 
        const firstName = row.find('td:eq(2)').text(); 
        const middleName = row.find('td:eq(3)').text(); 
        const branch = row.find('td:eq(6)').text(); 

        printLabel(familyName, firstName, middleName, branch);
    });

    function printLabel(familyName, firstName, middleName, branch) {
        const printContent = `
            <div style="
                font-size: 20px;
                text-align: left;
                padding: 20px;
                border: 1px solid #000;
                border-radius: 5px;
                display: inline-block;
                width: 80%;
                margin: auto;
            ">
                <p style="margin: 5px 0;">
                    <span style="font-weight: bold;">${familyName}, ${firstName} ${middleName}</span>
                      <span style="color: red; font-weight: normal;">Branch:</span> 
                    <span style="font-weight: bold;">${branch}</span>
                </p>
            </div>
        `;

        printJS({
            printable: printContent,
            type: 'raw-html',
            style: `
                div {
                    font-size: 12px;
                    text-align: center;
                    padding: 20px;
                    display: inline-block;
                    width: 80%;
                    margin: auto;
                }
                p {
                    margin: 5px 0;
                }
                span {
                    font-weight: normal;
                }
                @media print {
                    body {
                        margin: 0;
                        padding: 0;
                    }
                }
            `
        });
    }
    
    $('#printLabelsButton').on('click', function() {
    if (selectedRecordIds.length > 0) {
        printSelectedLabels();
    } else {
        showWarningModal('No records selected for printing labels.');
    }
});

function printSelectedLabels() {
    const printContent = $('<div></div>');

    const selectedRows = $('#RecordTableBody input[name="recordCheckbox"]:checked').closest('tr');

    selectedRows.each(function() {
        const familyName = $(this).find('td:eq(1)').text();
        const firstName = $(this).find('td:eq(2)').text();
        const middleName = $(this).find('td:eq(3)').text();
        const branch = $(this).find('td:eq(6)').text();

        const label = `
            <div style="
                font-size: 20px;
                text-align: left;
                padding: 20px;
                border: 1px solid #000;
                border-radius: 5px;
                display: inline-block;
                width: 80%;
                margin: auto;
                margin-bottom: 20px;
            ">
                <p style="margin: 5px 0;">
                    <span style="font-weight: bold;">${familyName}, ${firstName} ${middleName}</span>
                    <span style="color: red; font-weight: normal;">Branch:</span> 
                    <span style="font-weight: bold;">${branch}</span>
                </p>
            </div>
        `;

        printContent.append(label);
    });

    printJS({
        printable: printContent.html(),
        type: 'raw-html',
        style: `
            div {
                font-size: 12px;
                text-align: center;
                padding: 20px;
                display: inline-block;
                width: 80%;
                margin: auto;
            }
            p {
                margin: 5px 0;
            }
            span {
                font-weight: normal;
            }
            @media print {
                body {
                    margin: 0;
                    padding: 0;
                }
            }
        `
    });
}

  $('.dropdown-item').on('click', function(e) {
        e.preventDefault();
        const sortOption = $(this).data('sort');
        let columnIndex;

        switch (sortOption) {
            case 'familyName':
                columnIndex = 1; 
                break;
            case 'batch':
                columnIndex = 7;
                break;
            case 'branch':
                columnIndex = 6; 
                break;
            default:
                return;
        }

        sortTable(columnIndex);
    });

    function sortTable(columnIndex) {
        const table = $('#RecordTable');
        const rows = table.find('tbody tr').get();

        rows.sort(function(a, b) {
            const keyA = $(a).children('td').eq(columnIndex).text().toUpperCase();
            const keyB = $(b).children('td').eq(columnIndex).text().toUpperCase();

            if (keyA < keyB) return -1;
            if (keyA > keyB) return 1;
            return 0;
        });

        $.each(rows, function(index, row) {
            table.children('tbody').append(row);
        });
    }

});
