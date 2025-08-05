document.addEventListener("DOMContentLoaded", function() {
    const form = document.getElementById('inquiryForm');
    form.addEventListener('submit', function(event) {
        event.preventDefault();
        
        const lastname = form.lastname.value.trim();
        const firstname = form.firstname.value.trim();
        
        if (!lastname || !firstname) {
            document.querySelector('.result').innerHTML = `
                <div class="alert alert-danger">
                    Please enter both last name and first name
                </div>`;
            return;
        }

        fetch('api/lto_plate_inquiry.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `lastname=${encodeURIComponent(lastname)}&firstname=${encodeURIComponent(firstname)}`
        })
        .then(response => response.json())
        .then(data => {
            const resultContainer = document.querySelector('.result');
            resultContainer.innerHTML = '';
            
            if (data.error) {
                resultContainer.innerHTML = `
                    <div class="alert alert-danger">
                        No records found for: ${lastname}, ${firstname}
                    </div>`;
            } else if (Array.isArray(data)) {
                // Handle multiple results
                let resultsHTML = `
                <div class="card">
                    <div class="card-header bg-primary text-white ">
                        <h4 class="mb-0 text-white">Multiple Inquiry Results (${data.length})</h4>
                    </div>
                    <div class="card-body">`;
                
                data.forEach((item, index) => {
                    // Format name display
                    let nameDisplay;
                    if (item.full_name) {
                        nameDisplay = item.full_name;
                    } else {
                        nameDisplay = `${item.last_name || ''}${item.first_name ? ', ' + item.first_name : ''}`;
                    }

                    // Format plate number display
                    const plateDisplay = (item.plate_number === 'ND' || !item.plate_number) ? 
                                        'ON PROCESS' : 
                                        item.plate_number;

                    resultsHTML += `
                    <div class="mb-4 ${index > 0 ? 'mt-4 pt-3 border-top' : ''} text-start">
    <h5>Document #${index + 1}</h5>
    <div class="row">
        <div class="col-md-6">
            <p><strong>Date Registered:</strong> ${item.date_reg || 'N/A'}</p>
            <p><strong>Customer Name:</strong> ${nameDisplay}</p>
            <p><strong>Plate Number:</strong> ${plateDisplay}</p>
        </div>
        <div class="col-md-6">
            <p><strong>MV File Number:</strong> ${item.mv_file_number || 'N/A'}</p>
            <p><strong>Branch:</strong> ${item.branch || 'N/A'}</p>
            <p><strong>Remarks:</strong> ${item.remarks || 'N/A'}</p>
        </div>
    </div>
</div>
`;
                });
                
                resultsHTML += `</div></div>`;
                resultContainer.innerHTML = resultsHTML;
            } else {
                // Single result case
                // Format name display
                let nameDisplay;
                if (data.full_name) {
                    nameDisplay = data.full_name;
                } else {
                    nameDisplay = `${data.last_name || ''}${data.first_name ? ', ' + data.first_name : ''}`;
                }

                // Format plate number display
                const plateDisplay = (data.plate_number === 'ND' || !data.plate_number) ? 
                                    'ON PROCESS' : 
                                    data.plate_number;

                resultContainer.innerHTML = `
                <div class="card">
                    <div class="card-header bg-primary text-white ">
                        <h4 class="mb-0 text-white">Inquiry Results</h4>
                    </div>
                    <div class="card-body text-start">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Date Registered:</strong> ${data.date_reg || 'N/A'}</p>
                                <p><strong>Customer Name:</strong> ${nameDisplay}</p>
                                <p><strong>Plate Number:</strong> ${plateDisplay}</p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>MV File Number:</strong> ${data.mv_file_number || 'N/A'}</p>
                                <p><strong>Branch:</strong> ${data.branch || 'N/A'}</p>
                                <p><strong>Remarks:</strong> ${data.remarks || 'N/A'}</p>
                            </div>
                        </div>
                    </div>
                </div>`;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            document.querySelector('.result').innerHTML = `
                <div class="alert alert-danger">
                    NO RECORD FOUND FOR: ${lastname}, ${firstname}
                </div>`;
        });
    });
});