
let motorcycleData = [];
let selectedMotorcycle = null;

document.addEventListener('DOMContentLoaded', function() {
    
    const uploadModal = document.getElementById('uploadModal');
    const resultsModal = document.getElementById('resultsModal');
    const addPricingBtn = document.getElementById('addPricingBtn');
    const closeBtns = document.querySelectorAll('.close-btn');
    const calculatorFieldset = document.getElementById('calculator-fieldset');
    const modelSearch = document.getElementById('modelSearch');
    const searchResults = document.getElementById('searchResults');
    const selectedModelInfo = document.getElementById('selectedModelInfo');
    const selectedModelName = document.getElementById('selectedModelName');
    const selectedBrand = document.getElementById('selectedBrand');
    const selectedLCP = document.getElementById('selectedLCP');
    const closeResultsBtn = document.getElementById('closeResults');
    const printResultsBtn = document.getElementById('printResults');
    const ADMIN_PASSWORD = "solidforce2025";

    
    if (!uploadModal || !resultsModal || !addPricingBtn || !calculatorFieldset) {
        console.error('Required elements not found in DOM');
        return;
    }

      
    function openModal(modal) {
        if (modal) {
            modal.style.display = 'block';
            document.body.style.overflow = 'hidden';
        }
    }

    function closeModal(modal) {
        if (modal) {
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
        }
    }

    
    addPricingBtn.addEventListener('click', function() {
        openModal(uploadModal);
    });

    
    closeBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const modal = this.closest('.modal');
            closeModal(modal);
        });
    });

    
    if (closeResultsBtn) {
        closeResultsBtn.addEventListener('click', function() {
            closeModal(resultsModal);
        });
    }

    if (printResultsBtn) {
        printResultsBtn.addEventListener('click', function() {
            const resultsContent = document.getElementById('results-content');
            if (resultsContent) {
                const printWindow = window.open('', '_blank');
                printWindow.document.write(`
                    <!DOCTYPE html>
                    <html>
                    <head>
                        <title>Amortization Results</title>
                        <style>
                            body { font-family: Arial, sans-serif; padding: 20px; }
                            .print-header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #333; padding-bottom: 10px; }
                            .amortization-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                            .amortization-table th, .amortization-table td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
                            .amortization-table th { background-color: #f5f5f5; font-weight: bold; }
                            .summary { background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0; }
                            @media print { body { margin: 0; } }
                        </style>
                    </head>
                    <body>
                        ${resultsContent.innerHTML}
                        <script>
                            window.onload = function() { window.print(); setTimeout(() => window.close(), 500); }
                        <\/script>
                    </body>
                    </html>
                `);
                printWindow.document.close();
            }
        });
    }

    
    window.addEventListener('click', function(e) {
        if (e.target === uploadModal) {
            closeModal(uploadModal);
        }
        if (e.target === resultsModal) {
            closeModal(resultsModal);
        }
    });

    
    function checkExistingPricing() {
        console.log('Checking for existing pricing data...');
        
        fetch('calculator.php', { 
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'check_existing=true'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.motorcycles && data.motorcycles.length > 0) {
                console.log(`Found ${data.motorcycles.length} existing motorcycle records`);
                motorcycleData = data.motorcycles;
                
                
                calculatorFieldset.disabled = false;
                
                
                addPricingBtn.textContent = '↻ Update Pricing';
                
                
                showNotification(` Loaded ${data.motorcycles.length} motorcycle models from existing pricing file`, 'success');
            } else {
                console.log('No existing pricing data found');
                calculatorFieldset.disabled = true;
                addPricingBtn.textContent = '+ Add Pricing';
                
                
                setTimeout(() => {
                    openModal(uploadModal);
                }, 500);
            }
        })
        .catch(error => {
            console.error('Error checking existing pricing:', error);
            calculatorFieldset.disabled = true;
            
            setTimeout(() => {
                openModal(uploadModal);
            }, 500);
        });
    }

    
    function searchModels(query) {
        if (!query || query.length < 2) {
            searchResults.innerHTML = '';
            searchResults.style.display = 'none';
            return;
        }

        const searchTerm = query.toLowerCase();
        const matches = motorcycleData.filter(motorcycle => 
            motorcycle.model.toLowerCase().includes(searchTerm) ||
            motorcycle.brand.toLowerCase().includes(searchTerm)
        ).slice(0, 8); 

        if (matches.length === 0) {
            searchResults.innerHTML = '<div class="no-results">No matching models found</div>';
            searchResults.style.display = 'block';
            return;
        }

        searchResults.innerHTML = matches.map(motorcycle => `
            <div class="search-result-item" data-model="${motorcycle.model}">
                <div class="model-name">${highlightMatch(motorcycle.model, searchTerm)}</div>
                <div class="model-brand">${motorcycle.brand}</div>
            </div>
        `).join('');

        searchResults.style.display = 'block';

        
        document.querySelectorAll('.search-result-item').forEach(item => {
            item.addEventListener('click', function() {
                const modelName = this.getAttribute('data-model');
                selectMotorcycle(modelName);
            });
        });
    }

    function highlightMatch(text, searchTerm) {
        const regex = new RegExp(`(${searchTerm})`, 'gi');
        return text.replace(regex, '<mark>$1</mark>');
    }

    function selectMotorcycle(modelName) {
        selectedMotorcycle = motorcycleData.find(m => m.model === modelName);
        
        if (selectedMotorcycle) {
            
            modelSearch.value = selectedMotorcycle.model;
            
            
            searchResults.style.display = 'none';
            
            
            selectedModelName.innerHTML = selectedMotorcycle.model;
            selectedBrand.textContent = selectedMotorcycle.brand;
            
            selectedModelInfo.style.display = 'block';
            
            
            const dpInput = document.getElementById('downpayment');
            dpInput.value = selectedMotorcycle.dp || '';
            
            
            const dpWarning = document.getElementById('dpWarning');
            const minDPSpan = document.getElementById('minDP');
            if (selectedMotorcycle.dp && selectedMotorcycle.dp > 0) {
                minDPSpan.textContent = parseFloat(selectedMotorcycle.dp).toLocaleString('en-US');
                dpWarning.style.display = 'block';
            } else {
                dpWarning.style.display = 'none';
            }
            
            
            dpInput.focus();
        }
    }

    
    let searchTimeout;
    modelSearch.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        const query = this.value.trim();
        
        if (query.length < 2) {
            searchResults.style.display = 'none';
            selectedModelInfo.style.display = 'none';
            selectedMotorcycle = null;
            return;
        }
        
        searchTimeout = setTimeout(() => searchModels(query), 300);
    });

    
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.search-container')) {
            searchResults.style.display = 'none';
        }
    });

    
    function showNotification(message, type = 'info') {
        
        const existingNotification = document.querySelector('.global-notification');
        if (existingNotification) {
            existingNotification.remove();
        }

        const notification = document.createElement('div');
        notification.className = `global-notification ${type}`;
        notification.textContent = message;
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 12px 20px;
            border-radius: 4px;
            color: white;
            font-weight: bold;
            z-index: 1001;
            max-width: 400px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            animation: slideIn 0.3s ease-out;
        `;

        if (type === 'success') {
            notification.style.backgroundColor = '#27ae60';
        } else if (type === 'error') {
            notification.style.backgroundColor = '#e74c3c';
        } else {
            notification.style.backgroundColor = '#3498db';
        }

        document.body.appendChild(notification);

        
        setTimeout(() => {
            if (notification.parentNode) {
                notification.style.animation = 'slideOut 0.3s ease-in';
                setTimeout(() => notification.remove(), 300);
            }
        }, 5000);
    }

    
    const style = document.createElement('style');
    style.textContent = `
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        @keyframes slideOut {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(100%); opacity: 0; }
        }
    `;
    document.head.appendChild(style);

    
    const uploadForm = document.getElementById('uploadForm');
    const uploadStatus = document.getElementById('uploadStatus');

    uploadForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        uploadStatus.textContent = 'Processing...';
        uploadStatus.style.color = '#555';

        fetch('upload.php', { method: 'POST', body: formData })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                uploadStatus.textContent = `Success! ${data.rowCount} records loaded.`;
                uploadStatus.style.color = 'green';
                motorcycleData = data.motorcycles;
                
                
                calculatorFieldset.disabled = false;
                
                
                addPricingBtn.textContent = '↻ Update Pricing';
                
                
                showNotification(`Successfully loaded ${data.rowCount} motorcycle models`, 'success');
                
                
                setTimeout(() => {
                    closeModal(uploadModal);
                }, 1500);
            } else {
                uploadStatus.textContent = `❌ Error: ${data.message}`;
                uploadStatus.style.color = 'red';
                calculatorFieldset.disabled = true;
                
                showNotification(`Upload failed: ${data.message}`, 'error');
            }
        })
        .catch(error => {
            uploadStatus.textContent = 'An unexpected error occurred.';
            uploadStatus.style.color = 'red';
            console.error('Upload Error:', error);
            
            showNotification(' An unexpected error occurred during upload', 'error');
        });
    });

    
    const calculateBtn = document.getElementById('calculateBtn');
    const resultsContent = document.getElementById('results-content');
    const dpInput = document.getElementById('downpayment');
    const dpWarning = document.getElementById('dpWarning');
    const minDPSpan = document.getElementById('minDP');

    
    dpInput.addEventListener('input', function() {
        if (selectedMotorcycle && selectedMotorcycle.dp && selectedMotorcycle.dp > 0) {
            const currentDP = parseFloat(this.value) || 0;
            if (currentDP < selectedMotorcycle.dp) {
                dpWarning.style.display = 'block';
            } else {
                dpWarning.style.display = 'none';
            }
        }
    });

    calculateBtn.addEventListener('click', function() {
        if (!selectedMotorcycle || !dpInput.value) {
            alert('Please select a motorcycle model and enter a down payment.');
            return;
        }

        const downpayment = parseFloat(dpInput.value);

        
        if (selectedMotorcycle.dp && downpayment < selectedMotorcycle.dp) {
            alert(`Down payment must be at least ₱${parseFloat(selectedMotorcycle.dp).toLocaleString('en-US')}`);
            return;
        }

        const formData = new FormData();
        formData.append('model', selectedMotorcycle.model);
        formData.append('downpayment', downpayment);

        calculateBtn.disabled = true;
        calculateBtn.textContent = 'Calculating...';

        fetch('calculator.php', { method: 'POST', body: formData })
        .then(response => response.json())
        .then(data => {
            calculateBtn.disabled = false;
            calculateBtn.textContent = 'Calculate Amortization';

            if (data.success) {
                
                let html = `
                    <div class="results-summary">
                        <div class="model-header">
                            <h3>${data.brand} - ${data.model}</h3>
                        </div>
                        <div class="summary-grid">
                           
                            <div class="summary-item">
                                <label>Down Payment</label>
                                <div class="value">₱${parseFloat(data.downpayment).toLocaleString('en-US')}</div>
                            </div>
                           
                        </div>
                    </div>
                    <div class="amortization-section">
                        <h4>Monthly Amortization Schedule</h4>
                        <div class="terms-grid">`;
                
                for (const term in data.amortization) {
                    const payment = parseFloat(data.amortization[term]).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                    html += `
                            <div class="term-card">
                                <div class="term-months">${term} months</div>
                                <div class="monthly-payment">₱${payment}</div>
                                <div class="per-month">per month</div>
                            </div>`;
                }
                
                html += `
                        </div>
                    </div>`;
                
                resultsContent.innerHTML = html;
                openModal(resultsModal);
            } else {
                alert(`Error: ${data.message}`);
            }
        })
        .catch(error => {
            console.error('Calculation Error:', error);
            calculateBtn.disabled = false;
            calculateBtn.textContent = 'Calculate Amortization';
            alert('An error occurred during calculation. Please try again.');
        });
    });

    
    checkExistingPricing();

    
function openPasswordModal() {
    const passwordModal = document.getElementById('passwordModal');
    if (passwordModal) {
        passwordModal.style.display = 'block';
        document.body.style.overflow = 'hidden';
        document.getElementById('adminPassword').focus();
    }
}

function closePasswordModal() {
    const passwordModal = document.getElementById('passwordModal');
    if (passwordModal) {
        passwordModal.style.display = 'none';
        document.body.style.overflow = 'auto';
        document.getElementById('passwordStatus').textContent = '';
        document.getElementById('passwordForm').reset();
    }
}

function handlePasswordSubmit(e) {
    e.preventDefault();
    
    const passwordInput = document.getElementById('adminPassword');
    const passwordStatus = document.getElementById('passwordStatus');
    const enteredPassword = passwordInput.value.trim();
    
    if (enteredPassword === ADMIN_PASSWORD) {
        passwordStatus.textContent = ' Access granted!';
        passwordStatus.style.color = 'green';
        
        
        setTimeout(() => {
            closePasswordModal();
            openModal(uploadModal);
        }, 1000);
    } else {
        passwordStatus.textContent = 'Incorrect password.';
        passwordStatus.style.color = 'red';
        passwordInput.value = '';
        passwordInput.focus();
    }
}


addPricingBtn.addEventListener('click', function() {
    openPasswordModal(); 
});


const passwordForm = document.getElementById('passwordForm');
if (passwordForm) {
    passwordForm.addEventListener('submit', handlePasswordSubmit);
}


window.addEventListener('click', function(e) {
    const passwordModal = document.getElementById('passwordModal');
    if (e.target === passwordModal) {
        closePasswordModal();
    }
});


document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const passwordModal = document.getElementById('passwordModal');
        if (passwordModal && passwordModal.style.display === 'block') {
            closePasswordModal();
        }
    }
});
});
