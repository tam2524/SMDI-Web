document.addEventListener("DOMContentLoaded", function() {
    const form = document.getElementById('inquiryForm');
    form.addEventListener('submit', function(event) {
        event.preventDefault();
        
        // Get and validate inputs
        let lastname = form.lastname.value.trim();
        let firstname = form.firstname.value.trim();
        
        if (!lastname || !firstname) {
            document.querySelector('.result').innerHTML = `
                <div class="alert alert-danger">
                    Please enter both last name and first name
                </div>`;
            return;
        }

        // Convert inputs to Windows-style pattern if not already
        const convertToPattern = (name) => {
            if (name.length >= 2 && !name.includes('*')) {
                return name.substring(0, 2) + '***';
            }
            return name;
        };

        lastname = convertToPattern(lastname);
        firstname = convertToPattern(firstname);

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
                        No records found for: ${lastname.replace(/\*+/g, '*')}, ${firstname.replace(/\*+/g, '*')}
                    </div>`;
            } else {
                // Format name display
                let nameDisplay;
                if (data.full_name) {
                    nameDisplay = data.full_name;
                } else {
                    nameDisplay = `${data.last_name || ''}${data.first_name ? ', ' + data.first_name : ''}`;
                }

                // Format plate number
                const plateNumber = (data.plate_number === 'ND' || !data.plate_number) 
                    ? 'ON PROCESS' 
                    : data.plate_number;

                resultContainer.innerHTML = `
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0 text-white">Inquiry Results</h4>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Customer Name:</strong> ${nameDisplay}</p>
                                <p><strong>Date Registered:</strong> ${data.date_reg || 'N/A'}</p>
                                <p><strong>MV File Number:</strong> ${data.mv_file_number || 'N/A'}</p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Plate Number:</strong> ${plateNumber}</p>
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
                    There was an error processing your request
                </div>`;
        });
    });
});