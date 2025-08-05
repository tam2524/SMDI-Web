document.addEventListener("DOMContentLoaded", function() {
    const form = document.getElementById('inquiryForm');
    form.addEventListener('submit', function(event) {
        event.preventDefault();

        const formData = new FormData(form);

        fetch('api/lto_plate_inquiry.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            const resultContainer = document.querySelector('.result');
            resultContainer.innerHTML = '';

            if (data.error) {
                resultContainer.innerHTML = `
                    <div class="alert alert-danger">
                        <strong>No Records Found:</strong> ${data.error}
                    </div>
                `;
            } else {
                // Format the name display
                const nameDisplay = data.full_name || 
                                  (data.last_name ? `${data.last_name}${data.first_name ? ', ' + data.first_name : ''}` : 'N/A');
                
                // Format plate number display
                const plateDisplay = (data.plate_number === 'ND' || !data.plate_number) ? 
                                    'ON PROCESS' : 
                                    data.plate_number;

                resultContainer.innerHTML = `
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0 text-white text-start">Inquiry Results</h4>
                    </div>
                    <div class="card-body text-start"> 
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Date Registered:</strong> ${data.date_reg || 'N/A'}</p>
                                <p><strong>MV File Number:</strong> ${data.mv_file_number || 'N/A'}</p>
                                <p><strong>Name:</strong> ${nameDisplay}</p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Branch:</strong> ${data.branch || 'N/A'}</p>
                                <p><strong>Plate Number:</strong> ${plateDisplay}</p>
                                <p><strong>Remarks:</strong> ${data.remarks || 'N/A'}</p>
                            </div>
                        </div>
                    </div>
                </div>`;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            const resultContainer = document.querySelector('.result');
            resultContainer.innerHTML = `
                <div class="alert alert-danger">
                    There was an error processing your request.
                </div>`;
        });
    });
});